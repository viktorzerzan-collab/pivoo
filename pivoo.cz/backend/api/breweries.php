<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../Database.php';

$db = (new Database())->getConnection();

if ($db) {
    $query = "SELECT br.*, c.name_cz as country, c.code as country_code,
                     ROUND(AVG(NULLIF(cons.rating_beer, 0)), 1) as avg_rating, 
                     COUNT(DISTINCT b.id) as total_beers_in_catalog
              FROM breweries br
              LEFT JOIN countries c ON br.country_id = c.id
              LEFT JOIN beers b ON br.id = b.brewery_id
              LEFT JOIN consumptions cons ON b.id = cons.beer_id
              WHERE br.is_approved = 1
              GROUP BY br.id
              ORDER BY br.name ASC";
              
    $stmt = $db->query($query);
    $breweries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(["status" => "success", "data" => $breweries]);
}
?>