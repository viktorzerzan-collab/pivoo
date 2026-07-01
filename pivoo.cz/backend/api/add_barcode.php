<?php
require_once '../core/ApiHandler.php';

$api = new ApiHandler();
$api->requireAdmin();

$ean = $api->request->getParam('ean');
$beer_id = $api->request->getIntParam('beer_id');
$packaging = $api->request->getParam('packaging');
$volume = $api->request->getFloatParam('volume');

if (empty($ean) || empty($beer_id) || empty($packaging) || empty($volume)) {
    $api->response->sendError('Chybí povinná data (EAN, pivo, balení nebo objem).', 400);
}

try {
    // Kontrola duplicity
    $stmt = $api->db->prepare("SELECT barcode FROM beer_barcodes WHERE barcode = ?");
    $stmt->execute([$ean]);
    if ($stmt->fetch()) {
        $api->response->sendError('Tento EAN kód již v databázi existuje.', 400);
    }

    $stmt = $api->db->prepare("
        INSERT INTO beer_barcodes (barcode, beer_id, packaging, volume) 
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->execute([$ean, $beer_id, $packaging, $volume]);
    $api->response->sendSuccess("Čárový kód byl úspěšně přidán.");
} catch (PDOException $e) {
    error_log("DB Error (add_barcode): " . $e->getMessage());
    $api->response->sendError('Chyba při ukládání do databáze.', 500);
}
?>