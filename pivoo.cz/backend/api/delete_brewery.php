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
        // Kontrola, zda existují piva pro tento pivovar
        $check = $db->prepare("SELECT COUNT(*) FROM beers WHERE brewery_id = ?");
        $check->execute([$data->id]);
        if ($check->fetchColumn() > 0) {
            echo json_encode(["status" => "error", "message" => "Nelze smazat pivovar, který má v katalogu piva."]);
            exit();
        }

        $stmt = $db->prepare("DELETE FROM breweries WHERE id = ?");
        $stmt->execute([$data->id]);
        echo json_encode(["status" => "success", "message" => "Pivovar byl smazán."]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Chyba DB."]);
    }
}