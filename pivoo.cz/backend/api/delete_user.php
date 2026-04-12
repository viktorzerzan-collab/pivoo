<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once '../Database.php';
require_once '../JwtHandler.php';

// ZABEZPEČENÍ
JwtHandler::checkAdmin();

$db = (new Database())->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id)) {
    try {
        $db->beginTransaction();
        $db->prepare("DELETE FROM consumptions WHERE user_id = ?")->execute([$data->id]);
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$data->id]);
        
        $db->commit();
        echo json_encode(["status" => "success", "message" => "Uživatel byl smazán."]);
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Chyba: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Chybí ID."]);
}
?>