<?php
// backend/api/update_checkin.php
require_once '../core/ApiHandler.php';

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

$api = new ApiHandler();
$user = JwtHandler::checkUser();

$id = $api->request->getIntParam('id');

if (!$id) {
    $api->response->sendError("Chybí ID záznamu.", 400);
}

try {
    $stmtOld = $api->db->prepare("SELECT beer_id, location_id FROM consumptions WHERE id = ? AND user_id = ?");
    $stmtOld->execute([$id, $user['user_id']]);
    $oldCons = $stmtOld->fetch();

    $query = "UPDATE consumptions 
              SET beer_id = ?, location_id = ?, volume = ?, quantity = ?, price = ?, currency = ?, original_price = ?, is_free = ?, rating_beer = ?, rating_care = ?, note = ?, packaging = ?, consumed_at = ? 
              WHERE id = ? AND user_id = ?";
              
    $stmt = $api->db->prepare($query);
    
    $beer_id = $api->request->getIntParam('beer_id');
    $location_id = $api->request->getIntParam('location_id');
    $volume = $api->request->getParam('volume');
    $quantity = $api->request->getIntParam('quantity', 1);
    
    $is_free = $api->request->getBoolParam('is_free');
    
    $currency = $api->request->getParam('currency', 'CZK');
    $original_price_raw = $api->request->getParam('original_price');
    $original_price = ($original_price_raw !== null && $original_price_raw !== '') ? (float)$original_price_raw : null;
    
    $consumed_at = $api->request->getParam('consumed_at');

    $czk_price = null;

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
                $api->response->sendError("Nepodařilo se získat kurz z API pro přepočet měn. Zkuste to prosím znovu nebo použijte CZK.", 500);
            }
        }
    }

    $rating_beer = $api->request->getIntParam('rating_beer');
    $rating_beer = ($rating_beer > 0) ? $rating_beer : null;
    
    $rating_care = $api->request->getIntParam('rating_care');
    $rating_care = ($rating_care > 0) ? $rating_care : null;
    
    $note = $api->request->getParam('note');
    $packaging = $api->request->getParam('packaging', 'točené');

    if ($stmt->execute([
        $beer_id, $location_id, $volume, $quantity, 
        $czk_price, $currency, $original_price, 
        $is_free, $rating_beer, $rating_care,
        $note, $packaging, $consumed_at, $id, $user['user_id']
    ])) {
        
        $beerIdsToRecalc = array_unique(array_filter([$oldCons ? $oldCons['beer_id'] : null, $beer_id]));
        $locIdsToRecalc = array_unique(array_filter([$oldCons ? $oldCons['location_id'] : null, $location_id]));
        
        try {
            foreach ($beerIdsToRecalc as $bid) {
                $api->db->prepare("UPDATE beers SET total_checkins = (SELECT COUNT(id) FROM consumptions WHERE beer_id = ?), avg_rating = (SELECT ROUND(AVG(NULLIF(rating_beer, 0)), 1) FROM consumptions WHERE beer_id = ?) WHERE id = ?")->execute([$bid, $bid, $bid]);
                
                $stmtBrew = $api->db->prepare("SELECT brewery_id FROM beers WHERE id = ?");
                $stmtBrew->execute([$bid]);
                $brew = $stmtBrew->fetch();
                if ($brew && $brew['brewery_id']) {
                    $api->db->prepare("UPDATE breweries SET avg_rating = (SELECT ROUND(AVG(NULLIF(c.rating_beer, 0)), 1) FROM consumptions c JOIN beers b ON c.beer_id = b.id WHERE b.brewery_id = ?) WHERE id = ?")->execute([$brew['brewery_id'], $brew['brewery_id']]);
                }
            }
            foreach ($locIdsToRecalc as $lid) {
                $api->db->prepare("UPDATE locations SET total_visits = (SELECT COUNT(id) FROM consumptions WHERE location_id = ?), avg_rating = (SELECT ROUND(AVG(NULLIF(rating_beer, 0)), 1) FROM consumptions WHERE location_id = ?) WHERE id = ?")->execute([$lid, $lid, $lid]);
            }
        } catch (PDOException $e) {
            error_log("Chyba při přepočtu statistik po updatu (update_checkin): " . $e->getMessage());
        }

        $api->response->sendSuccess("Záznam byl úspěšně upraven.", [
            "price" => $czk_price,
            "original_price" => $original_price,
            "currency" => $currency
        ]);
    } else {
        $api->response->sendError("Nepodařilo se upravit záznam.", 500);
    }
} catch (PDOException $e) {
    error_log("DB Error (update_checkin): " . $e->getMessage());
    $api->response->sendError("Vnitřní chyba databáze.", 500);
}
?>