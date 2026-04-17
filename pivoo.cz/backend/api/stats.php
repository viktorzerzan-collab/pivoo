<?php
// ZMĚNA: Omezení CORS
header("Access-Control-Allow-Origin: https://www.pivoo.cz");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once '../Database.php';
require_once '../JwtHandler.php';

// ZABEZPEČENÍ
$user = JwtHandler::checkUser();
$user_id = $user['user_id'];

$database = new Database();
$db = $database->getConnection();

// Zjištění období
$period = isset($_GET['period']) ? $_GET['period'] : 'all';
$dateCondition = "";

if ($period === 'month') {
    // Filtr od prvního dne aktuálního měsíce 00:00:00
    $dateCondition = " AND consumed_at >= DATE_FORMAT(NOW() ,'%Y-%m-01 00:00:00')";
}

if ($db) {
    try {
        // 1. Součty
        $query = "SELECT 
                    SUM(quantity) as total_beers,
                    COUNT(DISTINCT beer_id) as unique_beers,
                    SUM(price * quantity) as total_price,
                    COUNT(DISTINCT location_id) as unique_locations,
                    SUM(volume * quantity) as total_liters
                  FROM consumptions 
                  WHERE user_id = :uid" . $dateCondition;
                  
        $stmt = $db->prepare($query);
        $stmt->bindParam(':uid', $user_id);
        $stmt->execute();
        $stats = $stmt->fetch();

        // 2. Nejoblíbenější pivo
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
        // ZMĚNA: Skrytí SQL chyby
        error_log("DB Error (stats): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Vnitřní chyba při výpočtu statistik."]);
    }
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Chyba DB."]);
}
?>