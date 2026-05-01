<?php
header("Access-Control-Allow-Origin: https://www.pivoo.cz");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once '../Database.php';
require_once '../JwtHandler.php';

$user = JwtHandler::checkUser();
$user_id = $user['user_id'];

$database = new Database();
$db = $database->getConnection();

$period = isset($_GET['period']) ? $_GET['period'] : 'all';
$dateCondition = "";

if ($period === 'month') {
    date_default_timezone_set('Europe/Prague');
    $currentYear = date('Y');
    $currentMonth = date('m');
    $dateCondition = " AND YEAR(consumed_at) = $currentYear AND MONTH(consumed_at) = $currentMonth";
}

if ($db) {
    try {
        // ÚPRAVA: total_price počítáme pouze tam, kde is_free = 0
        $query = "SELECT 
                    SUM(quantity) as total_beers,
                    COUNT(DISTINCT beer_id) as unique_beers,
                    SUM(CASE WHEN is_free = 0 THEN price * quantity ELSE 0 END) as total_price,
                    COUNT(DISTINCT location_id) as unique_locations,
                    SUM(volume * quantity) as total_liters
                  FROM consumptions 
                  WHERE user_id = :uid" . $dateCondition;
                  
        $stmt = $db->prepare($query);
        $stmt->bindParam(':uid', $user_id);
        $stmt->execute();
        $stats = $stmt->fetch();

        $query_fav = "SELECT beers.name, SUM(consumptions.quantity) as qty
                      FROM consumptions 
                      JOIN beers ON consumptions.beer_id = beers.id
                      WHERE consumptions.user_id = :uid" . $dateCondition . "
                      GROUP BY consumptions.beer_id
                      ORDER BY qty DESC LIMIT 1";
                      
        $stmt_fav = $db->prepare($query_fav);
        $stmt_fav->bindParam(':uid', $user_id);
        $stmt_fav->execute();
        $fav = $stmt_fav->fetch();

        echo json_encode([
            "status" => "success",
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
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Vnitřní chyba při výpočtu statistik."]);
    }
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Chyba DB."]);
}
?>