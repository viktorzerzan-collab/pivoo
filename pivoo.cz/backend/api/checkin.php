<?php
// ZMĚNA: Omezení CORS
header("Access-Control-Allow-Origin: https://www.pivoo.cz");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once '../Database.php';
require_once '../JwtHandler.php';

// Pomocná funkce pro zjištění kurzu z ČNB
function getCnbRate($currency, $dateStr) {
    if ($currency === 'CZK') return 1.0;

    // Datum pro ČNB musí být ve formátu DD.MM.YYYY
    $date = date("d.m.Y", strtotime($dateStr));
    $url = "https://www.cnb.cz/cs/financni-trhy/devizovy-trh/kurzy-devizoveho-trhu/kurzy-devizoveho-trhu/denni_kurz.txt?date=" . $date;

    // Krátký timeout, aby se aplikace nezasekla, kdyby ČNB neodpovídala
    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
    $content = @file_get_contents($url, false, $ctx);

    if (!$content) return null;

    $lines = explode("\n", $content);
    foreach ($lines as $line) {
        if (strpos($line, '|' . $currency . '|') !== false) {
            $parts = explode('|', $line);
            // Formát je: země|měna|množství|kód|kurz (např. EMU|euro|1|EUR|25,123)
            if (count($parts) >= 5) {
                $amount = (float)$parts[2];
                // ČNB používá desetinnou čárku, my potřebujeme tečku
                $rateRaw = str_replace(',', '.', $parts[4]);
                $rate = (float)$rateRaw;
                
                return ($amount > 0) ? ($rate / $amount) : null;
            }
        }
    }
    return null;
}

$user = JwtHandler::checkUser();

$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->beer_id) && !empty($data->location_id)) {
    try {
        // ÚPRAVA: Přidány sloupce currency a original_price
        $query = "INSERT INTO consumptions (user_id, beer_id, location_id, volume, quantity, price, currency, original_price, is_free, rating_beer, rating_care, note, packaging, consumed_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($query);
        
        $volume = !empty($data->volume) ? $data->volume : null;
        $quantity = !empty($data->quantity) ? (int)$data->quantity : 1;
        
        $is_free = (!empty($data->is_free) && $data->is_free) ? 1 : 0;
        
        // ZMĚNA: Načtení měny a původní ceny
        $currency = !empty($data->currency) ? $data->currency : 'CZK';
        $original_price = (!empty($data->price) && $data->price !== '') ? (float)$data->price : null;
        
        $consumed_at = !empty($data->consumed_at) ? $data->consumed_at : date("Y-m-d H:i:s");

        $czk_price = null;

        // Logika pro výpočet korun z aktuálního kurzu ČNB
        if ($is_free) {
            $original_price = null;
            $czk_price = null;
        } elseif ($original_price !== null) {
            if ($currency === 'CZK') {
                $czk_price = $original_price;
            } else {
                $rate = getCnbRate($currency, $consumed_at);
                if ($rate !== null) {
                    $czk_price = round($original_price * $rate, 2);
                } else {
                    http_response_code(500);
                    echo json_encode(["status" => "error", "message" => "Nepodařilo se získat kurz z ČNB pro přepočet. Zkuste to prosím znovu nebo použijte CZK."]);
                    exit();
                }
            }
        }
        
        $rating_beer = (!empty($data->rating_beer) && $data->rating_beer > 0) ? (int)$data->rating_beer : null;
        $rating_care = (!empty($data->rating_care) && $data->rating_care > 0) ? (int)$data->rating_care : null;
        
        $note = !empty($data->note) ? $data->note : null;
        $packaging = !empty($data->packaging) ? $data->packaging : 'točené';

        if ($stmt->execute([
            $user['user_id'], 
            $data->beer_id, 
            $data->location_id, 
            $volume, 
            $quantity, 
            $czk_price,      // Přepočtená hodnota v CZK
            $currency,       // Vybraná měna
            $original_price, // Zadaná částka uživatelem
            $is_free, 
            $rating_beer, 
            $rating_care, 
            $note, 
            $packaging,
            $consumed_at
        ])) {
            $new_id = $db->lastInsertId();
            // ZMĚNA: Vracíme vypočítané cenové údaje zpět na frontend
            echo json_encode([
                "status" => "success", 
                "message" => "Zapsáno do deníčku!", 
                "id" => $new_id,
                "price" => $czk_price,
                "original_price" => $original_price,
                "currency" => $currency
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Chyba při zápisu."]);
        }
    } catch (PDOException $e) {
        error_log("DB Error (checkin): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Vnitřní chyba databáze."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Chybí data (Pivo nebo Místo)."]);
}
?>