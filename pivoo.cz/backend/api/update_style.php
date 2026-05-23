<?php
// backend/api/update_style.php
require_once '../core/ApiHandler.php';

$api = new ApiHandler();
$api->requireAdmin();

$id = $api->request->getIntParam('id');
$name = $api->request->getParam('name');

if (!$id || !$name) {
    $api->response->sendError("ID a Název jsou povinné.", 400);
}

try {
    $stmt = $api->db->prepare("UPDATE beer_styles SET name = ? WHERE id = ?");
    if ($stmt->execute([$name, $id])) {
        $api->response->sendSuccess("Pivní styl byl upraven.");
    } else {
        $api->response->sendError("Chyba při ukládání úpravy.", 500);
    }
} catch (PDOException $e) {
    error_log("DB Error (update_style): " . $e->getMessage());
    $api->response->sendError("Název už existuje nebo nastala interní chyba serveru.", 500);
}
?>