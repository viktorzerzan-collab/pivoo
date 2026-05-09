<?php
// backend/api/checkin.php
// ZMĚNA: Omezení CORS
header("Access-Control-Allow-Origin: https://www.pivoo.cz");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once '../Database.php';
require_once '../JwtHandler.php';

// Pomocná funkce pro zjištění kurzu z fawazahmed0/currency-api
function getExchangeRate($currency, $dateStr) {
    if ($currency === 'CZK') return 1.0;

    $date = date("Y-m-d", strtotime($dateStr));
    $currencyLower = strtolower($currency);
    
    // API v1 struktura pro konkrétní datum (vrací přepočty 1 vybrané měny na všechny ostatní)
    $url = "https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@{$date}/v1/currencies/{$currencyLower}.json";

    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
    $content = @file_get_contents($url, false, $ctx);

    if (!$content) {
        // Fallback na 'latest', např. pokud je datum dnešek a API se ještě pro dnešek neaktualizovalo
        $url = "https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/{$currencyLower}.json";
        $content = @file_get_contents($url, false, $ctx);
    }

    if (!$content) return null;

    $data = json_decode($content, true);
    if (isset($data[$currencyLower]['czk'])) {
        return (float)$data[$currencyLower]['czk'];
    }
    
    return null;
}

$user = JwtHandler::checkUser();

$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->beer_id) && !empty($data->location_id)) {
    try {
        $query = "INSERT INTO consumptions (user_id, beer_id, location_id, volume, quantity, price, currency, original_price, is_free, rating_beer, rating_care, note, packaging, consumed_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($query);
        
        $volume = !empty($data->volume) ? $data->volume : null;
        $quantity = !empty($data->quantity) ? (int)$data->quantity : 1;
        
        $is_free = (!empty($data->is_free) && $data->is_free) ? 1 : 0;
        
        $currency = !empty($data->currency) ? $data->currency : 'CZK';
        $original_price = (!empty($data->price) && $data->price !== '') ? (float)$data->price : null;
        
        $consumed_at = !empty($data->consumed_at) ? $data->consumed_at : date("Y-m-d H:i:s");

        $czk_price = null;

        // Logika pro výpočet korun z aktuálního kurzu
        if ($is_free) {
            $original_price = null;
            $czk_price = null;
        } elseif ($original_price !== null) {
            if ($currency === 'CZK') {
                $czk_price = $original_price;
            } else {
                $rate = getExchangeRate($currency, $consumed_at);
                if ($rate !== null) {
                    $czk_price = round($original_price * $rate, 2);
                } else {
                    http_response_code(500);
                    echo json_encode(["status" => "error", "message" => "Nepodařilo se získat kurz z API pro přepočet měn. Zkuste to prosím znovu nebo použijte CZK."]);
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
            $czk_price,      
            $currency,       
            $original_price, 
            $is_free, 
            $rating_beer, 
            $rating_care, 
            $note, 
            $packaging,
            $consumed_at
        ])) {
            $new_id = $db->lastInsertId();

            try {
                // --- ZMĚNA: Přesun piva z Konceptu do fronty ke schválení ---
                $db->prepare("UPDATE beers SET is_approved = 0 WHERE id = ? AND is_approved = 2")->execute([$data->beer_id]);

                // Přepočet piva
                $db->prepare("UPDATE beers SET total_checkins = (SELECT COUNT(id) FROM consumptions WHERE beer_id = ?), avg_rating = (SELECT ROUND(AVG(NULLIF(rating_beer, 0)), 1) FROM consumptions WHERE beer_id = ?) WHERE id = ?")->execute([$data->beer_id, $data->beer_id, $data->beer_id]);
                
                // Zjištění a přepočet pivovaru
                $stmtBrew = $db->prepare("SELECT brewery_id FROM beers WHERE id = ?");
                $stmtBrew->execute([$data->beer_id]);
                $brew = $stmtBrew->fetch();
                if ($brew && $brew['brewery_id']) {
                    // --- ZMĚNA: Přesun pivovaru z Konceptu do fronty ke schválení ---
                    $db->prepare("UPDATE breweries SET is_approved = 0 WHERE id = ? AND is_approved = 2")->execute([$brew['brewery_id']]);

                    $db->prepare("UPDATE breweries SET avg_rating = (SELECT ROUND(AVG(NULLIF(c.rating_beer, 0)), 1) FROM consumptions c JOIN beers b ON c.beer_id = b.id WHERE b.brewery_id = ?) WHERE id = ?")->execute([$brew['brewery_id'], $brew['brewery_id']]);
                }
                
                // Přepočet lokace
                $db->prepare("UPDATE locations SET total_visits = (SELECT COUNT(id) FROM consumptions WHERE location_id = ?), avg_rating = (SELECT ROUND(AVG(NULLIF(rating_beer, 0)), 1) FROM consumptions WHERE location_id = ?) WHERE id = ?")->execute([$data->location_id, $data->location_id, $data->location_id]);

                // Odstranění z wishlistu
                $db->prepare("DELETE FROM user_wishlists WHERE user_id = ? AND entity_id = ? AND entity_type = 'beer'")->execute([$user['user_id'], $data->beer_id]);
                $db->prepare("DELETE FROM user_wishlists WHERE user_id = ? AND entity_id = ? AND entity_type = 'location'")->execute([$user['user_id'], $data->location_id]);
                
                if ($brew && $brew['brewery_id']) {
                    $db->prepare("DELETE FROM user_wishlists WHERE user_id = ? AND entity_id = ? AND entity_type = 'brewery'")->execute([$user['user_id'], $brew['brewery_id']]);
                }
                
            } catch (PDOException $e) {
                error_log("Chyba při přepočtu statistik nebo úpravě wishlistu (checkin): " . $e->getMessage());
            }

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