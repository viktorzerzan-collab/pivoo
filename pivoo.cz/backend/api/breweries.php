<?php
// backend/api/breweries.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../Database.php';

$database = new Database();
$db = $database->getConnection();

if ($db) {
    // Komplexní dotaz: 
    // 1. Vybere pivovar (br)
    // 2. Připojí k němu všechna jeho piva (b)
    // 3. Připojí ke každému pivu všechny jeho konzumace/zápisy (c)
    // 4. NULLIF(c.rating_beer, 0) zajistí, že piva bez hodnocení (0) neovlivní průměr
    $query = "SELECT br.*, 
                     ROUND(AVG(NULLIF(c.rating_beer, 0)), 1) as avg_rating, 
                     COUNT(DISTINCT b.id) as total_beers_in_catalog
              FROM breweries br
              LEFT JOIN beers b ON br.id = b.brewery_id
              LEFT JOIN consumptions c ON b.id = c.beer_id
              WHERE br.is_approved = 1
              GROUP BY br.id
              ORDER BY br.name ASC";
              
    $stmt = $db->query($query);
    $breweries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(["status" => "success", "data" => $breweries]);
}
?>