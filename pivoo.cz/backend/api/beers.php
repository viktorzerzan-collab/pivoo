<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../Database.php';

$db = (new Database())->getConnection();

if ($db) {
    $query = "SELECT b.*, br.name as brewery_name, c.name_cz as brewery_country, c.code as brewery_country_code, bs.name as style,
                     ROUND(AVG(NULLIF(cons.rating_beer, 0)), 1) as avg_rating, 
                     COUNT(cons.id) as total_checkins
              FROM beers b
              LEFT JOIN breweries br ON b.brewery_id = br.id
              LEFT JOIN countries c ON br.country_id = c.id
              LEFT JOIN beer_styles bs ON b.style_id = bs.id
              LEFT JOIN consumptions cons ON b.id = cons.beer_id
              WHERE b.is_approved = 1
              GROUP BY b.id
              ORDER BY b.name ASC";
              
    $stmt = $db->query($query);
    $beers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(["status" => "success", "data" => $beers]);
}
?>