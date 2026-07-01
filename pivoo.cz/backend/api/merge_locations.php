<?php
// backend/api/merge_locations.php
require_once '../core/ApiHandler.php';

$api = new ApiHandler();
$api->requireAdmin();

$source_id = $api->request->getIntParam('source_id');
$target_id = $api->request->getIntParam('target_id');

if (!$source_id || !$target_id) {
    $api->response->sendError("Chybí ID zdrojového nebo cílového podniku.", 400);
}

if ($source_id == $target_id) {
    $api->response->sendError("Nelze sloučit podnik sám se sebou.", 400);
}

try {
    $api->db->beginTransaction();

    $queryUpdate = "UPDATE consumptions SET location_id = ? WHERE location_id = ?";
    $stmtUpdate = $api->db->prepare($queryUpdate);
    $stmtUpdate->execute([$target_id, $source_id]);

    $queryDelete = "DELETE FROM locations WHERE id = ?";
    $stmtDelete = $api->db->prepare($queryDelete);
    $stmtDelete->execute([$source_id]);

    $api->db->commit();

    try {
        $api->db->prepare("UPDATE locations SET total_visits = (SELECT COUNT(id) FROM consumptions WHERE location_id = ?), avg_rating = (SELECT ROUND(AVG(NULLIF(rating_beer, 0)), 1) FROM consumptions WHERE location_id = ?) WHERE id = ?")->execute([$target_id, $target_id, $target_id]);
    } catch (PDOException $e) {
        error_log("Chyba při přepočtu (merge_locations): " . $e->getMessage());
    }

    $api->response->sendSuccess("Podniky byly úspěšně sloučeny. Záznamy byly přesunuty a duplicita byla smazána.");

} catch (Exception $e) {
    $api->db->rollBack();
    error_log("DB Error (merge_locations): " . $e->getMessage());
    $api->response->sendError("Vnitřní chyba serveru při slučování podniků.", 500);
}
?>