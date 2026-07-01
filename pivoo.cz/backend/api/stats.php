<?php
// backend/api/stats.php
require_once '../core/ApiHandler.php';

$api = new ApiHandler();
$user = JwtHandler::checkUser();
$user_id = $user['user_id'];

$period = isset($_GET['period']) ? $_GET['period'] : 'all';
$dateCondition = "";

if ($period === 'month') {
    date_default_timezone_set('Europe/Prague');
    $currentYear = date('Y');
    $currentMonth = date('m');
    $dateCondition = " AND YEAR(consumed_at) = $currentYear AND MONTH(consumed_at) = $currentMonth";
}

try {
    $query = "SELECT 
                SUM(quantity) as total_beers,
                COUNT(DISTINCT beer_id) as unique_beers,
                SUM(CASE WHEN is_free = 0 THEN price * quantity ELSE 0 END) as total_price,
                COUNT(DISTINCT location_id) as unique_locations,
                SUM(volume * quantity) as total_liters
              FROM consumptions 
              WHERE user_id = :uid" . $dateCondition;
              
    $stmt = $api->db->prepare($query);
    $stmt->bindParam(':uid', $user_id);
    $stmt->execute();
    $stats = $stmt->fetch();

    $query_fav = "SELECT beers.name, SUM(consumptions.quantity) as qty
                  FROM consumptions 
                  JOIN beers ON consumptions.beer_id = beers.id
                  WHERE consumptions.user_id = :uid" . $dateCondition . "
                  GROUP BY consumptions.beer_id
                  ORDER BY qty DESC LIMIT 1";
                  
    $stmt_fav = $api->db->prepare($query_fav);
    $stmt_fav->bindParam(':uid', $user_id);
    $stmt_fav->execute();
    $fav = $stmt_fav->fetch();

    $api->response->sendSuccess("", [
        "data" => [
            "total_beers" => $stats['total_beers'] ? (int)$stats['total_beers'] : 0,
            "unique_beers" => $stats['unique_beers'] ? (int)$stats['unique_beers'] : 0,
            "total_price" => $stats['total_price'] ? (float)$stats['total_price'] : 0,
            "unique_locations" => $stats['unique_locations'] ? (int)$stats['unique_locations'] : 0,
            "total_liters" => $stats['total_liters'] ? round($stats['total_liters'], 2) : 0,
            "favorite" => $fav ? $fav['name'] : 'Žádné záznamy'
        ]
    ]);
} catch (Exception $e) {
    error_log("DB Error (stats): " . $e->getMessage());
    $api->response->sendError("Vnitřní chyba při výpočtu statistik.", 500);
}
?>