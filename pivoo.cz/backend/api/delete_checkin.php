<?php
// backend/api/delete_checkin.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once '../Database.php';
$db = (new Database())->getConnection();
$data = json_decode(file_get_contents("php://input"));

// Vyžadujeme ID záznamu a ID uživatele (aby si lidi nemohli mazat piva navzájem)
if (!empty($data->id) && !empty($data->user_id)) {
    $query = "DELETE FROM consumptions WHERE id = :id AND user_id = :uid";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $data->id);
    $stmt->bindParam(':uid', $data->user_id);
    
    if($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Záznam byl úspěšně smazán."]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Chyba při mazání z databáze."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Chybí parametry pro smazání."]);
}