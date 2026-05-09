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
$db = (new Database())->getConnection();

$allowed_scopes = ['me', 'global'];
$scope = isset($_GET['scope']) && in_array($_GET['scope'], $allowed_scopes) ? $_GET['scope'] : 'me';

$date_from = isset($_GET['date_from']) && !empty($_GET['date_from']) ? $_GET['date_from'] : null;
$date_to = isset($_GET['date_to']) && !empty($_GET['date_to']) ? $_GET['date_to'] : null;

$userCondition = ($scope === 'global') ? "1=1" : "c.user_id = :uid";
$params = [];

if ($scope !== 'global') {
    $params['uid'] = $user_id;
}

$dateCondition = "";
if ($date_from && $date_to) {
    // Použijeme DATE() pro bezpečné porovnání čistých dat
    $dateCondition = " AND DATE(c.consumed_at) >= :date_from AND DATE(c.consumed_at) <= :date_to";
    $params['date_from'] = $date_from;
    $params['date_to'] = $date_to;
}

$response = [
    "beers" => [],
    "breweries" => [],
    "locations" => [],
    "days" => [],
    "months" => [],
    "month_days" => [],
    "collector" => ["unique_count" => 0, "total_count" => 0],
    "overview" => ["total_beers" => 0, "total_liters" => 0, "total_price" => 0, "favorite" => null],
    "styles" => [],
    "prices" => ["avg_price" => 0, "min_price" => 0, "max_price" => 0],
    "price_details" => ["min_beer" => null, "max_beer" => null]
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
    } catch (Exception $e) { error_log("DB Error detailed_stats (beers): " . $e->getMessage()); }

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
    } catch (Exception $e) { error_log("DB Error detailed_stats (breweries): " . $e->getMessage()); }

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
    } catch (Exception $e) { error_log("DB Error detailed_stats (locations): " . $e->getMessage()); }

    // 4. AKTIVITA PODLE DNŮ V TÝDNU (Týdenní rytmus)
    try {
        $days_query = "SELECT WEEKDAY(c.consumed_at) as day_index, SUM(c.quantity) as count
                       FROM consumptions c
                       WHERE $userCondition $dateCondition
                       GROUP BY day_index ORDER BY day_index ASC";
        $stmt = $db->prepare($days_query);
        $stmt->execute($params);
        $response["days"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { error_log("DB Error detailed_stats (days): " . $e->getMessage()); }

    // 4B. AKTIVITA PODLE MĚSÍCŮ V ROCE (Roční rytmus)
    try {
        $months_query = "SELECT MONTH(c.consumed_at) as month_index, SUM(c.quantity) as count
                         FROM consumptions c
                         WHERE $userCondition $dateCondition
                         GROUP BY month_index ORDER BY month_index ASC";
        $stmt = $db->prepare($months_query);
        $stmt->execute($params);
        $response["months"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { error_log("DB Error detailed_stats (months): " . $e->getMessage()); }

    // 4C. AKTIVITA PODLE DNŮ V MĚSÍCI (Měsíční rytmus)
    try {
        $month_days_query = "SELECT DAY(c.consumed_at) as day_index, SUM(c.quantity) as count
                             FROM consumptions c
                             WHERE $userCondition $dateCondition
                             GROUP BY day_index ORDER BY day_index ASC";
        $stmt = $db->prepare($month_days_query);
        $stmt->execute($params);
        $response["month_days"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { error_log("DB Error detailed_stats (month_days): " . $e->getMessage()); }

    // 5. SBĚRATEL A CELKOVÝ SOUHRN (pro StatsBoard)
    try {
        $overview_query = "SELECT 
                            COUNT(DISTINCT beer_id) as unique_count, 
                            SUM(quantity) as total_count,
                            SUM(volume * quantity) as total_liters,
                            SUM(CASE WHEN is_free = 0 THEN price * quantity ELSE 0 END) as total_price
                           FROM consumptions c
                           WHERE $userCondition $dateCondition";
        $stmt = $db->prepare($overview_query);
        $stmt->execute($params);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($res) {
            $response["collector"] = [
                "unique_count" => $res['unique_count'] ? (int)$res['unique_count'] : 0,
                "total_count" => $res['total_count'] ? (int)$res['total_count'] : 0
            ];
            $response["overview"] = [
                "total_beers" => $res['total_count'] ? (int)$res['total_count'] : 0,
                "total_liters" => $res['total_liters'] ? round($res['total_liters'], 2) : 0,
                "total_price" => $res['total_price'] ? (float)$res['total_price'] : 0,
                "favorite" => null
            ];
        }
    } catch (Exception $e) { error_log("DB Error detailed_stats (overview): " . $e->getMessage()); }

    // Přiřazení nejoblíbenějšího piva do souhrnu z již zjištěného pole "beers"
    if (!empty($response["beers"])) {
        $response["overview"]["favorite"] = $response["beers"][0]['name'];
    }

    // 6. PIVNÍ STYLY
    try {
        $styles_query = "SELECT s.name, SUM(c.quantity) as count
                         FROM consumptions c
                         JOIN beers b ON c.beer_id = b.id
                         JOIN beer_styles s ON b.style_id = s.id
                         WHERE $userCondition $dateCondition
                         GROUP BY s.id ORDER BY count DESC";
        $stmt = $db->prepare($styles_query);
        $stmt->execute($params);
        $response["styles"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { error_log("DB Error detailed_stats (styles): " . $e->getMessage()); }

    // 7. CENY A EXTRÉMY
    try {
        $price_query = "SELECT 
                            AVG(price) as avg_price, 
                            MAX(price) as max_price, 
                            MIN(price) as min_price 
                        FROM consumptions c
                        WHERE $userCondition $dateCondition AND price > 0 AND is_free = 0";
        $stmt = $db->prepare($price_query);
        $stmt->execute($params);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($res) $response["prices"] = $res;

        // Název nejdražšího piva
        $max_query = "SELECT b.name FROM consumptions c JOIN beers b ON c.beer_id = b.id WHERE $userCondition $dateCondition AND c.price > 0 AND c.is_free = 0 ORDER BY c.price DESC LIMIT 1";
        $stmt = $db->prepare($max_query);
        $stmt->execute($params);
        $max_res = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($max_res) $response["price_details"]["max_beer"] = $max_res['name'];

        // Název nejlevnějšího piva
        $min_query = "SELECT b.name FROM consumptions c JOIN beers b ON c.beer_id = b.id WHERE $userCondition $dateCondition AND c.price > 0 AND c.is_free = 0 ORDER BY c.price ASC LIMIT 1";
        $stmt = $db->prepare($min_query);
        $stmt->execute($params);
        $min_res = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($min_res) $response["price_details"]["min_beer"] = $min_res['name'];

    } catch (Exception $e) { error_log("DB Error detailed_stats (prices): " . $e->getMessage()); }

    echo json_encode([
        "status" => "success",
        "data" => $response
    ]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Chyba spojení s databází."]);
}
?>