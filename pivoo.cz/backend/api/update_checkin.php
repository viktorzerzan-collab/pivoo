<?php
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
    
    $url = "https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@{$date}/v1/currencies/{$currencyLower}.json";

    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
    $content = @file_get_contents($url, false, $ctx);

    if (!$content) {
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

if (!empty($data->id)) {
    try {
        $stmtOld = $db->prepare("SELECT beer_id, location_id FROM consumptions WHERE id = ? AND user_id = ?");
        $stmtOld->execute([$data->id, $user['user_id']]);
        $oldCons = $stmtOld->fetch();

        $query = "UPDATE consumptions 
                  SET beer_id = ?, location_id = ?, volume = ?, quantity = ?, price = ?, currency = ?, original_price = ?, is_free = ?, rating_beer = ?, rating_care = ?, note = ?, packaging = ?, consumed_at = ? 
                  WHERE id = ? AND user_id = ?";
                  
        $stmt = $db->prepare($query);
        
        $beer_id = !empty($data->beer_id) ? $data->beer_id : null;
        $location_id = !empty($data->location_id) ? $data->location_id : null;
        $volume = !empty($data->volume) ? $data->volume : null;
        $quantity = !empty($data->quantity) ? (int)$data->quantity : 1;
        
        $is_free = (!empty($data->is_free) && $data->is_free) ? 1 : 0;
        
        $currency = !empty($data->currency) ? $data->currency : 'CZK';
        $original_price = (!empty($data->original_price) && $data->original_price !== '') ? (float)$data->original_price : null;
        
        $consumed_at = !empty($data->consumed_at) ? $data->consumed_at : null;

        $czk_price = null;

        // Logika pro výpočet korun z aktuálního kurzu
        if ($is_free) {
            $original_price = null;
            $czk_price = null;
        } elseif ($original_price !== null) {
            if ($currency === 'CZK') {
                $czk_price = $original_price;
            } else {
                $rate = getExchangeRate($currency, $consumed_at ? $consumed_at : date("Y-m-d H:i:s"));
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
            $beer_id, $location_id, $volume, $quantity, 
            $czk_price, $currency, $original_price, 
            $is_free, $rating_beer, $rating_care,
            $note, $packaging, $consumed_at, $data->id, $user['user_id']
        ])) {
            
            $beerIdsToRecalc = array_unique(array_filter([$oldCons ? $oldCons['beer_id'] : null, $beer_id]));
            $locIdsToRecalc = array_unique(array_filter([$oldCons ? $oldCons['location_id'] : null, $location_id]));
            
            try {
                foreach ($beerIdsToRecalc as $bid) {
                    $db->prepare("UPDATE beers SET total_checkins = (SELECT COUNT(id) FROM consumptions WHERE beer_id = ?), avg_rating = (SELECT ROUND(AVG(NULLIF(rating_beer, 0)), 1) FROM consumptions WHERE beer_id = ?) WHERE id = ?")->execute([$bid, $bid, $bid]);
                    
                    $stmtBrew = $db->prepare("SELECT brewery_id FROM beers WHERE id = ?");
                    $stmtBrew->execute([$bid]);
                    $brew = $stmtBrew->fetch();
                    if ($brew && $brew['brewery_id']) {
                        $db->prepare("UPDATE breweries SET avg_rating = (SELECT ROUND(AVG(NULLIF(c.rating_beer, 0)), 1) FROM consumptions c JOIN beers b ON c.beer_id = b.id WHERE b.brewery_id = ?) WHERE id = ?")->execute([$brew['brewery_id'], $brew['brewery_id']]);
                    }
                }
                foreach ($locIdsToRecalc as $lid) {
                    $db->prepare("UPDATE locations SET total_visits = (SELECT COUNT(id) FROM consumptions WHERE location_id = ?), avg_rating = (SELECT ROUND(AVG(NULLIF(rating_beer, 0)), 1) FROM consumptions WHERE location_id = ?) WHERE id = ?")->execute([$lid, $lid, $lid]);
                }
            } catch (PDOException $e) {
                error_log("Chyba při přepočtu statistik po updatu (update_checkin): " . $e->getMessage());
            }

            echo json_encode([
                "status" => "success", 
                "message" => "Záznam byl úspěšně upraven.",
                "price" => $czk_price,
                "original_price" => $original_price,
                "currency" => $currency
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Nepodařilo se upravit záznam."]);
        }
    } catch (PDOException $e) {
        error_log("DB Error (update_checkin): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Vnitřní chyba databáze."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Chybí ID záznamu."]);
}
?>