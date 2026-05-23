<?php
// backend/api/delete_location.php
require_once '../core/ApiHandler.php';

$api = new ApiHandler();
$api->requireAdmin();

$id = $api->request->getIntParam('id');

if (!$id) {
    $api->response->sendError("Chybí ID lokace.", 400);
}

try {
    $stmt = $api->db->prepare("DELETE FROM locations WHERE id = ?");
    $stmt->execute([$id]);
    $api->response->sendSuccess("Lokace byla smazána.");
} catch (PDOException $e) {
    error_log("DB Error (delete_location): " . $e->getMessage());
    $api->response->sendError("Lokaci nelze smazat, pravděpodobně k ní existují zápisy konzumací.", 500);
}
?>