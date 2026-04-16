<?php
// backend/api/locations.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../Database.php';
require_once '../JwtHandler.php';

$token = JwtHandler::getBearerToken();
$decoded = $token ? JwtHandler::decode($token) : null;
$userId = $decoded ? $decoded['user_id'] : 0;

$db = (new Database())->getConnection();

if ($db) {
    $query = "SELECT l.*, c.name_cz as country, c.code as country_code,
                     ROUND(AVG(NULLIF(cons.rating_care, 0)), 1) as avg_rating, 
                     COUNT(cons.id) as total_visits,
                     IF(fav.id IS NOT NULL, 1, 0) as is_favorite
              FROM locations l
              LEFT JOIN countries c ON l.country_id = c.id
              LEFT JOIN consumptions cons ON l.id = cons.location_id
              LEFT JOIN user_favorites fav ON l.id = fav.entity_id 
                   AND fav.entity_type = 'location' 
                   AND fav.user_id = :uid
              WHERE l.is_approved = 1
              GROUP BY l.id
              ORDER BY is_favorite DESC, l.name ASC";
              
    $stmt = $db->prepare($query);
    $stmt->bindParam(':uid', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(["status" => "success", "data" => $locations]);
}
?>