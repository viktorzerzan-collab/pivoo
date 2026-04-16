<?php
// backend/api/breweries.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../Database.php';
require_once '../JwtHandler.php';

$token = JwtHandler::getBearerToken();
$decoded = $token ? JwtHandler::decode($token) : null;
$userId = $decoded ? $decoded['user_id'] : 0;

$db = (new Database())->getConnection();

if ($db) {
    $query = "SELECT br.*, c.name_cz as country, c.code as country_code,
                     ROUND(AVG(NULLIF(cons.rating_beer, 0)), 1) as avg_rating, 
                     COUNT(DISTINCT b.id) as total_beers_in_catalog,
                     IF(fav.id IS NOT NULL, 1, 0) as is_favorite
              FROM breweries br
              LEFT JOIN countries c ON br.country_id = c.id
              LEFT JOIN beers b ON br.id = b.brewery_id
              LEFT JOIN consumptions cons ON b.id = cons.beer_id
              LEFT JOIN user_favorites fav ON br.id = fav.entity_id 
                   AND fav.entity_type = 'brewery' 
                   AND fav.user_id = :uid
              WHERE br.is_approved = 1
              GROUP BY br.id
              ORDER BY is_favorite DESC, br.name ASC";
              
    $stmt = $db->prepare($query);
    $stmt->bindParam(':uid', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $breweries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(["status" => "success", "data" => $breweries]);
}
?>