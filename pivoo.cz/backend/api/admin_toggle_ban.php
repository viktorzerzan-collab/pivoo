<?php
// backend/api/admin_toggle_ban.php
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

// ZABEZPEČENÍ: Pouze pro administrátory
JwtHandler::checkAdmin();

$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->user_id) && isset($data->is_banned)) {
    try {
        $stmt = $db->prepare("UPDATE users SET is_banned = ? WHERE id = ?");
        
        if ($stmt->execute([$data->is_banned, $data->user_id])) {
            $msg = $data->is_banned ? "Uživatel byl zablokován a odhlášen." : "Uživatel byl odblokován.";
            echo json_encode(["status" => "success", "message" => $msg]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Chyba při aktualizaci databáze."]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Chyba serveru: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Chybí ID uživatele nebo stav blokace."]);
}
?>