<?php
// backend/api/locations.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../Database.php';

$database = new Database();
$db = $database->getConnection();

if ($db) {
    // Spočítáme průměr rating_care (obsluha) a počet záznamů
    // LEFT JOIN na consumptions nám umožní získat statistiky pro každou lokaci
    $query = "SELECT l.*, 
                     ROUND(AVG(NULLIF(c.rating_care, 0)), 1) as avg_rating, 
                     COUNT(c.id) as total_visits
              FROM locations l
              LEFT JOIN consumptions c ON l.id = c.location_id
              WHERE l.is_approved = 1
              GROUP BY l.id
              ORDER BY l.name ASC";
              
    $stmt = $db->query($query);
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(["status" => "success", "data" => $locations]);
}
?>