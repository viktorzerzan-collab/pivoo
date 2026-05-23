<?php
// backend/api/update_user.php
require_once '../core/ApiHandler.php';

$api = new ApiHandler();
$api->requireAdmin();

$id = $api->request->getIntParam('id');
$username = $api->request->getParam('username');
$email = $api->request->getParam('email');

if (!$id || !$username || !$email) {
    $api->response->sendError("Chybí povinná data (ID, přezdívka nebo e-mail).", 400);
}

try {
    $query = "UPDATE users 
              SET username = ?, first_name = ?, last_name = ?, email = ?, role = ? 
              WHERE id = ?";
              
    $stmt = $api->db->prepare($query);
    
    $first_name = $api->request->getParam('first_name');
    $last_name = $api->request->getParam('last_name');
    $role = $api->request->getParam('role', 'user');

    if ($stmt->execute([
        $username,
        $first_name,
        $last_name,
        $email,
        $role,
        $id
    ])) {
        $api->response->sendSuccess("Údaje uživatele byly aktualizovány.");
    } else {
        $api->response->sendError("Chyba při ukládání do databáze.", 500);
    }
} catch (PDOException $e) {
    error_log("DB Error (update_user): " . $e->getMessage());
    $api->response->sendError("Uživatelské jméno nebo e-mail již používá někdo jiný, nebo nastala interní chyba.", 500);
}
?>