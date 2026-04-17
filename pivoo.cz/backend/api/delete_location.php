<?php
// ZMĚNA: Omezení CORS
header("Access-Control-Allow-Origin: https://www.pivoo.cz");
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

$db = (new Database())->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id)) {
    try {
        $stmt = $db->prepare("DELETE FROM locations WHERE id = ?");
        $stmt->execute([$data->id]);
        echo json_encode(["status" => "success", "message" => "Lokace byla smazána."]);
    } catch (PDOException $e) {
        // ZMĚNA: Tiché logování chyby
        error_log("DB Error (delete_location): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Lokaci nelze smazat, pravděpodobně k ní existují zápisy konzumací."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Chybí ID lokace."]);
}
?>