<?php
// backend/api/breweries.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../Database.php';

$database = new Database();
$db = $database->getConnection();

if ($db) {
    $stmt = $db->query("SELECT * FROM breweries WHERE is_approved = 1 ORDER BY name ASC");
    $breweries = $stmt->fetchAll();
    
    echo json_encode(["status" => "success", "data" => $breweries]);
}