<?php
// ZMĚNA: Omezení CORS
header("Access-Control-Allow-Origin: https://www.pivoo.cz");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once '../Database.php';
require_once '../JwtHandler.php';

$user = JwtHandler::checkUser();
$db = (new Database())->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->password)) {
    $stmt = $db->prepare("SELECT password_hash, avatar FROM users WHERE id = ?");
    $stmt->execute([$user['user_id']]);
    $dbUser = $stmt->fetch();

    if ($dbUser && password_verify($data->password, $dbUser['password_hash'])) {
        try {
            $db->beginTransaction();
            
            // 1. Smazání fotky z FTP před smazáním záznamu z DB
            if ($dbUser['avatar']) {
                $file_path = '../uploads/avatars/' . $dbUser['avatar'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            $db->prepare("DELETE FROM consumptions WHERE user_id = ?")->execute([$user['user_id']]);
            $db->prepare("DELETE FROM users WHERE id = ?")->execute([$user['user_id']]);
            $db->commit();
            
            echo json_encode(["status" => "success", "message" => "Účet i data byla smazána."]);
        } catch (Exception $e) {
            $db->rollBack();
            // ZMĚNA: Tiché logování chyby
            error_log("DB Error (delete_profile): " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Vnitřní chyba při mazání profilu."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Nesprávné heslo."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Zadejte heslo pro potvrzení."]);
}
?>