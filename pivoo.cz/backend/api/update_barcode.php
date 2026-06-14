<?php
require_once '../core/ApiHandler.php';

$api = new ApiHandler();
$api->requireAdmin();

$old_barcode = $api->request->getParam('id');
$new_barcode = $api->request->getParam('ean');
$beer_id = $api->request->getIntParam('beer_id');
$packaging = $api->request->getParam('packaging');
$volume = $api->request->getFloatParam('volume');

if (empty($old_barcode) || empty($new_barcode) || empty($beer_id) || empty($packaging) || empty($volume)) {
    $api->response->sendError('Chybí povinná data.', 400);
}

try {
    if ($old_barcode !== $new_barcode) {
        $stmt = $api->db->prepare("SELECT barcode FROM beer_barcodes WHERE barcode = ?");
        $stmt->execute([$new_barcode]);
        if ($stmt->fetch()) {
            $api->response->sendError('Tento EAN kód již v databázi existuje u jiného piva.', 400);
        }
    }

    $stmt = $api->db->prepare("
        UPDATE beer_barcodes 
        SET barcode = ?, beer_id = ?, packaging = ?, volume = ? 
        WHERE barcode = ?
    ");
    
    $stmt->execute([$new_barcode, $beer_id, $packaging, $volume, $old_barcode]);
    $api->response->sendSuccess("Záznam byl úspěšně upraven.");
} catch (PDOException $e) {
    error_log("DB Error (update_barcode): " . $e->getMessage());
    $api->response->sendError('Chyba při aktualizaci v databáze.', 500);
}
?>