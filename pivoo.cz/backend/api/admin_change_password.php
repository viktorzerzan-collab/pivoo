<?php
// backend/api/admin_change_password.php
require_once '../core/ApiHandler.php';

$api = new ApiHandler();
$api->requireAdmin();

$user_id = $api->request->getIntParam('user_id');
$new_password = $api->request->getParam('new_password');

if (!$user_id || !$new_password) {
    $api->response->sendError("Chybí ID uživatele nebo nové heslo.", 400);
}

if (strlen($new_password) < 8 || !preg_match('/[0-9]/', $new_password) || !preg_match('/[^a-zA-Z0-9]/', $new_password)) {
    $api->response->sendError("Nové heslo musí mít alespoň 8 znaků, obsahovat číslo a speciální znak.", 400);
}

try {
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

    $query = "UPDATE users SET password_hash = ? WHERE id = ?";
    $stmt = $api->db->prepare($query);
    
    if ($stmt->execute([$new_hash, $user_id])) {
        $api->response->sendSuccess("Heslo bylo úspěšně změněno.");
    } else {
        $api->response->sendError("Nepodařilo se změnit heslo v databázi.", 500);
    }
} catch (PDOException $e) {
    error_log("DB Error (admin_change_password): " . $e->getMessage());
    $api->response->sendError("Vnitřní chyba databáze při změně hesla.", 500);
}
?>