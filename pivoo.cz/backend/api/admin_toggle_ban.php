<?php
// backend/api/admin_toggle_ban.php
require_once '../core/ApiHandler.php';

$api = new ApiHandler();
$api->requireAdmin();

$user_id = $api->request->getIntParam('user_id');
$is_banned_raw = $api->request->getParam('is_banned');

if (!$user_id || $is_banned_raw === null) {
    $api->response->sendError("Chybí ID uživatele nebo stav blokace.", 400);
}

$is_banned = $api->request->getBoolParam('is_banned');

try {
    $stmt = $api->db->prepare("UPDATE users SET is_banned = ? WHERE id = ?");
    
    if ($stmt->execute([$is_banned, $user_id])) {
        $msg = $is_banned ? "Uživatel byl zablokován a odhlášen." : "Uživatel byl odblokován.";
        $api->response->sendSuccess($msg);
    } else {
        $api->response->sendError("Chyba při aktualizaci databáze.", 500);
    }
} catch (Exception $e) {
    error_log("DB Error (admin_toggle_ban): " . $e->getMessage());
    $api->response->sendError("Vnitřní chyba serveru při úpravě stavu blokace.", 500);
}
?>