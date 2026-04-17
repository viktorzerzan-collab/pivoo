<?php
// ZMĚNA: Omezení CORS
header("Access-Control-Allow-Origin: https://www.pivoo.cz");
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
        $stmt = $db->prepare("DELETE FROM beer_styles WHERE id = ?");
        $stmt->execute([$data->id]);
        echo json_encode(["status" => "success", "message" => "Styl byl smazán."]);
    } catch (Exception $e) {
        // ZMĚNA: Logujeme chybu na serveru, uživateli necháváme lidskou zprávu, proč to nejspíš selhalo.
        error_log("DB Error (delete_style): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Styl nelze smazat, pravděpodobně je přiřazen k pivu v katalogu."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Chybí ID."]);
}
?>