<?php
// backend/api/delete_profile.php
require_once '../core/ApiHandler.php';

$api = new ApiHandler();
$user = JwtHandler::checkUser();

$password = $api->request->getParam('password');

if (!$password) {
    $api->response->sendError("Zadejte heslo pro potvrzení.", 400);
}

$stmt = $api->db->prepare("SELECT password_hash, avatar FROM users WHERE id = ?");
$stmt->execute([$user['user_id']]);
$dbUser = $stmt->fetch();

if ($dbUser && password_verify($password, $dbUser['password_hash'])) {
    try {
        $api->db->beginTransaction();
        
        // 1. Smazání fotky z FTP před smazáním záznamu z DB
        if ($dbUser['avatar']) {
            $file_path = '../uploads/avatars/' . $dbUser['avatar'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        $api->db->prepare("DELETE FROM consumptions WHERE user_id = ?")->execute([$user['user_id']]);
        $api->db->prepare("DELETE FROM users WHERE id = ?")->execute([$user['user_id']]);
        $api->db->commit();
        
        $api->response->sendSuccess("Účet i data byla smazána.");
    } catch (Exception $e) {
        $api->db->rollBack();
        error_log("DB Error (delete_profile): " . $e->getMessage());
        $api->response->sendError("Vnitřní chyba při mazání profilu.", 500);
    }
} else {
    $api->response->sendError("Nesprávné heslo.", 400);
}
?>