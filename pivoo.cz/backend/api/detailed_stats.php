<?php
// backend/api/detailed_stats.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once '../Database.php';
require_once '../JwtHandler.php';

$user = JwtHandler::checkUser();
$user_id = $user['user_id'];
$db = (new Database())->getConnection();

$period = $_GET['period'] ?? 'all';
$scope = $_GET['scope'] ?? 'me'; 
$dateCondition = "";

if ($period === 'month') {
    $dateCondition = " AND consumed_at >= DATE_FORMAT(NOW() ,'%Y-%m-01 00:00:00')";
} elseif ($period === 'year') {
    $dateCondition = " AND consumed_at >= DATE_FORMAT(NOW() ,'%Y-01-01 00:00:00')";
}

$userCondition = ($scope === 'global') ? "1=1" : "c.user_id = :uid";
$params = ($scope === 'global') ? [] : ['uid' => $user_id];

$response = [
    "beers" => [],
    "breweries" => [],
    "locations" => [],
    "days" => [],
    "collector" => ["unique_count" => 0, "total_count" => 0],
    "styles" => [],
    "prices" => ["avg_price" => 0, "min_price" => 0, "max_price" => 0]
];

if ($db) {
    // 1. TOP PIVA
    try {
        $beers_query = "SELECT b.name, br.name as brewery, SUM(c.quantity) as count
                        FROM consumptions c
                        JOIN beers b ON c.beer_id = b.id
                        JOIN breweries br ON b.brewery_id = br.id
                        WHERE $userCondition $dateCondition
                        GROUP BY c.beer_id ORDER BY count DESC LIMIT 5";
        $stmt = $db->prepare($beers_query);
        $stmt->execute($params);
        $response["beers"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    // 2. TOP PIVOVARY
    try {
        $brew_query = "SELECT br.name, COUNT(DISTINCT c.beer_id) as beer_types, SUM(c.quantity) as count
                       FROM consumptions c
                       JOIN beers b ON c.beer_id = b.id
                       JOIN breweries br ON b.brewery_id = br.id
                       WHERE $userCondition $dateCondition
                       GROUP BY br.id ORDER BY count DESC LIMIT 5";
        $stmt = $db->prepare($brew_query);
        $stmt->execute($params);
        $response["breweries"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    // 3. TOP MÍSTA
    try {
        $loc_query = "SELECT l.name, l.city, COUNT(c.id) as visits, SUM(c.quantity) as count
                      FROM consumptions c
                      JOIN locations l ON c.location_id = l.id
                      WHERE $userCondition $dateCondition
                      GROUP BY l.id ORDER BY count DESC LIMIT 5";
        $stmt = $db->prepare($loc_query);
        $stmt->execute($params);
        $response["locations"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    // 4. AKTIVITA PODLE DNŮ
    try {
        $days_query = "SELECT WEEKDAY(c.consumed_at) as day_index, SUM(c.quantity) as count
                       FROM consumptions c
                       WHERE $userCondition $dateCondition
                       GROUP BY day_index ORDER BY day_index ASC";
        $stmt = $db->prepare($days_query);
        $stmt->execute($params);
        $response["days"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    // 5. SBĚRATEL
    try {
        $unique_query = "SELECT COUNT(DISTINCT beer_id) as unique_count, SUM(quantity) as total_count
                         FROM consumptions c
                         WHERE $userCondition $dateCondition";
        $stmt = $db->prepare($unique_query);
        $stmt->execute($params);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($res) $response["collector"] = $res;
    } catch (Exception $e) {}

    // 6. PIVNÍ STYLY - OPRAVENO na beer_styles
    try {
        $styles_query = "SELECT s.name, SUM(c.quantity) as count
                         FROM consumptions c
                         JOIN beers b ON c.beer_id = b.id
                         JOIN beer_styles s ON b.style_id = s.id
                         WHERE $userCondition $dateCondition
                         GROUP BY s.id ORDER BY count DESC LIMIT 5";
        $stmt = $db->prepare($styles_query);
        $stmt->execute($params);
        $response["styles"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    // 7. CENY
    try {
        $price_query = "SELECT 
                            AVG(price) as avg_price, 
                            MAX(price) as max_price, 
                            MIN(price) as min_price 
                        FROM consumptions c
                        WHERE $userCondition $dateCondition AND price > 0";
        $stmt = $db->prepare($price_query);
        $stmt->execute($params);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($res) $response["prices"] = $res;
    } catch (Exception $e) {}

    echo json_encode([
        "status" => "success",
        "data" => $response
    ]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Chyba spojení s DB."]);
}
?>