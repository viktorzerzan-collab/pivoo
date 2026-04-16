<?php
// backend/api/toggle_favorite.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../Database.php';
require_once '../JwtHandler.php';

// Brána pro přihlášeného uživatele - získáme jeho ID z tokenu
$user = JwtHandler::checkUser();

$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->entity_id) && !empty($data->entity_type)) {
    try {
        // Ověříme, zda už položka v oblíbených je
        $query = "SELECT id FROM user_favorites WHERE user_id = ? AND entity_id = ? AND entity_type = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$user['user_id'], $data->entity_id, $data->entity_type]);
        $favorite = $stmt->fetch();

        if ($favorite) {
            // Pokud existuje, smažeme ji (odebrání z oblíbených)
            $delete = $db->prepare("DELETE FROM user_favorites WHERE id = ?");
            $delete->execute([$favorite['id']]);
            echo json_encode(["status" => "success", "message" => "Odebráno z oblíbených.", "is_favorite" => false]);
        } else {
            // Pokud neexistuje, přidáme ji
            $insert = $db->prepare("INSERT INTO user_favorites (user_id, entity_id, entity_type) VALUES (?, ?, ?)");
            $insert->execute([$user['user_id'], $data->entity_id, $data->entity_type]);
            echo json_encode(["status" => "success", "message" => "Přidáno do oblíbených.", "is_favorite" => true]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Chyba databáze: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Chybí parametry požadavku."]);
}
?>