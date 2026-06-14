<?php
require_once '../core/ApiHandler.php';

$api = new ApiHandler();
$api->requireAdmin();

$barcode = $api->request->getParam('id');

if (empty($barcode)) {
    $api->response->sendError('Chybí ID (EAN) ke smazání.', 400);
}

try {
    $stmt = $api->db->prepare("DELETE FROM beer_barcodes WHERE barcode = ?");
    $stmt->execute([$barcode]);
    
    if ($stmt->rowCount() > 0) {
        $api->response->sendSuccess("Čárový kód byl úspěšně smazán.");
    } else {
        $api->response->sendError('Čárový kód nebyl nalezen.', 404);
    }
} catch (PDOException $e) {
    error_log("DB Error (delete_barcode): " . $e->getMessage());
    $api->response->sendError('Chyba při mazání z databáze.', 500);
}
?>