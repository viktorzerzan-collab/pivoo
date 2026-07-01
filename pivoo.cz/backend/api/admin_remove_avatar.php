<?php
// backend/api/admin_remove_avatar.php
require_once '../core/ApiHandler.php';

$api = new ApiHandler();
$api->requireAdmin();

$user_id = $api->request->getIntParam('user_id');

if (!$user_id) {
    $api->response->sendError("Chybí ID uživatele.", 400);
}

try {
    $stmt_find = $api->db->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt_find->execute([$user_id]);
    $targetUser = $stmt_find->fetch();

    if ($targetUser && $targetUser['avatar']) {
        $file_path = '../uploads/avatars/' . $targetUser['avatar'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        $stmt_update = $api->db->prepare("UPDATE users SET avatar = NULL WHERE id = ?");
        if ($stmt_update->execute([$user_id])) {
            $api->response->sendSuccess("Profilová fotka byla úspěšně smazána.");
        } else {
            $api->response->sendError("Chyba při aktualizaci databáze.", 500);
        }
    } else {
        $api->response->sendError("Tento uživatel nemá žádnou profilovou fotku.", 400);
    }
} catch (Exception $e) {
    error_log("Error (admin_remove_avatar): " . $e->getMessage());
    $api->response->sendError("Chyba na straně serveru při odstraňování avataru.", 500);
}
?>