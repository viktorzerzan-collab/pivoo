<?php
// backend/api/users.php
require_once '../core/ApiHandler.php';

$api = new ApiHandler();
$api->requireAdmin();

try {
    // Přidán sloupec is_2fa_enabled pro frontend administrace
    $query = "SELECT id, username, first_name, last_name, email, role, avatar, is_banned, is_2fa_enabled FROM users ORDER BY id DESC";
    $stmt = $api->db->query($query);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Projdeme výsledky a přetypujeme is_2fa_enabled na boolean (čistě pro jistotu, Vue očekává true/false nebo 1/0)
    foreach ($users as &$user) {
        $user['is_2fa_enabled'] = (bool)$user['is_2fa_enabled'];
    }
    
    $api->response->sendSuccess("", ["data" => $users]);
} catch (Exception $e) {
    error_log("DB Error (users): " . $e->getMessage());
    $api->response->sendError("Vnitřní chyba při načítání uživatelů.", 500);
}
?>