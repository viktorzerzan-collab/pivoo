<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../Database.php';
require_once '../JwtHandler.php';

// ZABEZPEČENÍ!
JwtHandler::checkAdmin();

$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->name)) {
    try {
        $stmt = $db->prepare("INSERT INTO beer_styles (name) VALUES (?)");
        $stmt->execute([$data->name]);
        echo json_encode(["status" => "success", "message" => "Pivní styl byl úspěšně přidán."]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Tento styl již v databázi pravděpodobně existuje."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Název stylu je povinný."]);
}
?>