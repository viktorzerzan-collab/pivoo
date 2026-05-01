<?php
// backend/api/toggle_wishlist.php
header("Access-Control-Allow-Origin: https://www.pivoo.cz");
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
        // Ověříme, zda už položka ve wishlistu je
        $query = "SELECT id FROM user_wishlists WHERE user_id = ? AND entity_id = ? AND entity_type = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$user['user_id'], $data->entity_id, $data->entity_type]);
        $wishlist = $stmt->fetch();

        if ($wishlist) {
            // Pokud existuje, smažeme ji (odebrání z wishlistu)
            $delete = $db->prepare("DELETE FROM user_wishlists WHERE id = ?");
            $delete->execute([$wishlist['id']]);
            echo json_encode(["status" => "success", "message" => "Odebráno z wishlistu.", "is_wishlist" => false]);
        } else {
            // Pokud neexistuje, přidáme ji
            $insert = $db->prepare("INSERT INTO user_wishlists (user_id, entity_id, entity_type) VALUES (?, ?, ?)");
            $insert->execute([$user['user_id'], $data->entity_id, $data->entity_type]);
            echo json_encode(["status" => "success", "message" => "Přidáno do wishlistu.", "is_wishlist" => true]);
        }
    } catch (PDOException $e) {
        // Skrytí SQL chyby z výstupu
        error_log("DB Error (toggle_wishlist): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Vnitřní chyba při ukládání do wishlistu."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Chybí parametry požadavku."]);
}
?>