<?php
// backend/api/locations.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../Database.php';

$database = new Database();
$db = $database->getConnection();

if ($db) {
    $stmt = $db->query("SELECT * FROM locations WHERE is_approved = 1");
    $locations = $stmt->fetchAll();
    
    echo json_encode(["status" => "success", "data" => $locations]);
}