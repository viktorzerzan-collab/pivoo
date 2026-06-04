<?php
// backend/api/delete_style.php
require_once '../core/ApiHandler.php';

$api = new ApiHandler();
$api->requireAdmin();

$id = $api->request->getIntParam('id');

if (!$id) {
    $api->response->sendError("Chybí ID.", 400);
}

try {
    $stmt = $api->db->prepare("DELETE FROM beer_styles WHERE id = ?");
    $stmt->execute([$id]);
    $api->response->sendSuccess("Styl byl smazán.");
} catch (Exception $e) {
    error_log("DB Error (delete_style): " . $e->getMessage());
    $api->response->sendError("Styl nelze smazat, pravděpodobně je přiřazen k pivu v katalogu.", 500);
}
?>