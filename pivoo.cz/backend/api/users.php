<?php
// backend/api/users.php
require_once '../core/ApiHandler.php';

$api = new ApiHandler();
$api->requireAdmin();

try {
    $query = "SELECT id, username, first_name, last_name, email, role, avatar, is_banned FROM users ORDER BY id DESC";
    $stmt = $api->db->query($query);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $api->response->sendSuccess("", ["data" => $users]);
} catch (Exception $e) {
    error_log("DB Error (users): " . $e->getMessage());
    $api->response->sendError("Vnitřní chyba při načítání uživatelů.", 500);
}
?>