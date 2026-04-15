<?php
// backend/api/stats.php
header("Access-Control-Allow-Origin: *");
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

// Zjištění období (na Dashboardu budeme posílat ?period=month)
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
                    SUM(volume * quantity) as total_liters, 
                    SUM(price * quantity) as total_spent,
                    SUM(quantity) as total_beers
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
            "stats" => [
                "liters" => $stats['total_liters'] ? round($stats['total_liters'], 2) : 0,
                "spent" => $stats['total_spent'] ? (int)$stats['total_spent'] : 0,
                "count" => $stats['total_beers'] ? (int)$stats['total_beers'] : 0,
                "favorite" => $fav ? $fav['name'] : 'Žádné záznamy'
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Chyba DB."]);
}
?>