<?php
// backend/api/delete_user.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once '../Database.php';
require_once '../JwtHandler.php';

JwtHandler::checkAdmin();

$db = (new Database())->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id)) {
    try {
        // Zjistíme avatar mazaného uživatele
        $stmt_find = $db->prepare("SELECT avatar FROM users WHERE id = ?");
        $stmt_find->execute([$data->id]);
        $targetUser = $stmt_find->fetch();

        $db->beginTransaction();
        
        // Smazání souboru z disku
        if ($targetUser && $targetUser['avatar']) {
            $file_path = '../uploads/avatars/' . $targetUser['avatar'];
            if (file_exists($file_path)) { unlink($file_path); }
        }

        $db->prepare("DELETE FROM consumptions WHERE user_id = ?")->execute([$data->id]);
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$data->id]);
        
        $db->commit();
        echo json_encode(["status" => "success", "message" => "Uživatel smazán."]);
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Chyba: " . $e->getMessage()]);
    }
}
?>