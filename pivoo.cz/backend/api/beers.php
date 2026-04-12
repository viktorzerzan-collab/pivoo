<?php
// backend/api/beers.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../Database.php';

$db = (new Database())->getConnection();

if ($db) {
    // Přidali jsme výpočet průměru (AVG) a počtu zápisů (COUNT). 
    // NULLIF zajistí, že zápisy s 0 hvězdičkami (kde uživatel nehodnotil) nezkazí průměr.
    $query = "SELECT b.*, br.name as brewery_name, 
                     ROUND(AVG(NULLIF(c.rating_beer, 0)), 1) as avg_rating, 
                     COUNT(c.id) as total_checkins
              FROM beers b
              LEFT JOIN breweries br ON b.brewery_id = br.id
              LEFT JOIN consumptions c ON b.id = c.beer_id
              WHERE b.is_approved = 1
              GROUP BY b.id
              ORDER BY b.name ASC";
              
    $stmt = $db->query($query);
    $beers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(["status" => "success", "data" => $beers]);
}