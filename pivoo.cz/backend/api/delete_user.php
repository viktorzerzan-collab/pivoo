<?php
// backend/api/delete_user.php
require_once '../core/ApiHandler.php';

$api = new ApiHandler();
$api->requireAdmin();

$id = $api->request->getIntParam('id');

if (!$id) {
    $api->response->sendError("Chybí ID uživatele.", 400);
}

try {
    // Zjistíme avatar mazaného uživatele
    $stmt_find = $api->db->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt_find->execute([$id]);
    $targetUser = $stmt_find->fetch();

    $api->db->beginTransaction();
    
    // Smazání souboru z disku
    if ($targetUser && $targetUser['avatar']) {
        $file_path = '../uploads/avatars/' . $targetUser['avatar'];
        if (file_exists($file_path)) { unlink($file_path); }
    }

    $api->db->prepare("DELETE FROM consumptions WHERE user_id = ?")->execute([$id]);
    $api->db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    
    $api->db->commit();
    $api->response->sendSuccess("Uživatel smazán.");
} catch (Exception $e) {
    $api->db->rollBack();
    error_log("DB Error (delete_user): " . $e->getMessage());
    $api->response->sendError("Vnitřní chyba při mazání uživatele.", 500);
}
?>