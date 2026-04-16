<?php
// backend/api/beers.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../Database.php';
require_once '../JwtHandler.php';

// Pokusíme se identifikovat uživatele z tokenu (pokud je přihlášen)
$token = JwtHandler::getBearerToken();
$decoded = $token ? JwtHandler::decode($token) : null;
$userId = $decoded ? $decoded['user_id'] : 0;

$db = (new Database())->getConnection();

if ($db) {
    // Upravený dotaz: přidáno is_favorite a JOIN na user_favorites
    $query = "SELECT b.*, br.name as brewery_name, c.name_cz as brewery_country, c.code as brewery_country_code, bs.name as style,
                     ROUND(AVG(NULLIF(cons.rating_beer, 0)), 1) as avg_rating, 
                     COUNT(cons.id) as total_checkins,
                     IF(fav.id IS NOT NULL, 1, 0) as is_favorite
              FROM beers b
              LEFT JOIN breweries br ON b.brewery_id = br.id
              LEFT JOIN countries c ON br.country_id = c.id
              LEFT JOIN beer_styles bs ON b.style_id = bs.id
              LEFT JOIN consumptions cons ON b.id = cons.beer_id
              LEFT JOIN user_favorites fav ON b.id = fav.entity_id 
                   AND fav.entity_type = 'beer' 
                   AND fav.user_id = :uid
              WHERE b.is_approved = 1
              GROUP BY b.id
              ORDER BY is_favorite DESC, b.name ASC";
              
    $stmt = $db->prepare($query);
    $stmt->bindParam(':uid', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $beers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(["status" => "success", "data" => $beers]);
}
?>