<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../Database.php';

$db = (new Database())->getConnection();

if ($db) {
    $query = "SELECT l.*, c.name_cz as country, c.code as country_code,
                     ROUND(AVG(NULLIF(cons.rating_care, 0)), 1) as avg_rating, 
                     COUNT(cons.id) as total_visits
              FROM locations l
              LEFT JOIN countries c ON l.country_id = c.id
              LEFT JOIN consumptions cons ON l.id = cons.location_id
              WHERE l.is_approved = 1
              GROUP BY l.id
              ORDER BY l.name ASC";
              
    $stmt = $db->query($query);
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(["status" => "success", "data" => $locations]);
}
?>