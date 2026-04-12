<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../Database.php';
$db = (new Database())->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id)) {
    try {
        $stmt = $db->prepare("DELETE FROM beer_styles WHERE id = ?");
        $stmt->execute([$data->id]);
        echo json_encode(["status" => "success", "message" => "Styl byl smazán."]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Styl nelze smazat, protože je přiřazen k pivu v katalogu."]);
    }
}