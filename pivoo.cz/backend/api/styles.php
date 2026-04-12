<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../Database.php';

$database = new Database();
$db = $database->getConnection();

if ($db) {
    $stmt = $db->query("SELECT id, name FROM beer_styles ORDER BY name ASC");
    $styles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(["status" => "success", "data" => $styles]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Chyba DB."]);
}
?>