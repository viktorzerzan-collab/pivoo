<?php
// backend/api/update_beer.php
require_once '../core/ApiHandler.php';

$api = new ApiHandler();
$api->requireAdmin();

$id = $api->request->getIntParam('id');
$name = $api->request->getParam('name');

if (!$id || !$name) {
    $api->response->sendError("Chybí povinná data (ID nebo název).", 400);
}

try {
    $query = "UPDATE beers 
              SET name = ?, brewery_id = ?, style_id = ?, epm = ?, abv = ?,
                  ibu = ?, ebc = ?, hops = ?, malts = ?, fermentation = ?, tags = ?,
                  is_unfiltered = ?, is_unpasteurized = ?
              WHERE id = ?";
              
    $stmt = $api->db->prepare($query);
    
    $brewery_id = $api->request->getParam('brewery_id');
    $style_id = $api->request->getParam('style_id');
    $epm = $api->request->getParam('epm');
    $abv = $api->request->getParam('abv');
    $ibu = $api->request->getParam('ibu');
    $ebc = $api->request->getParam('ebc');
    $hops = $api->request->getParam('hops');
    $malts = $api->request->getParam('malts');
    $fermentation = $api->request->getParam('fermentation');
    $tags = $api->request->getParam('tags');
    $is_unfiltered = $api->request->getBoolParam('is_unfiltered');
    $is_unpasteurized = $api->request->getBoolParam('is_unpasteurized');

    if ($stmt->execute([
        $name, $brewery_id, $style_id, $epm, $abv,
        $ibu, $ebc, $hops, $malts, $fermentation, $tags, $is_unfiltered, $is_unpasteurized,
        $id
    ])) {
        $api->response->sendSuccess("Pivo v katalogu bylo úspěšně upraveno.");
    } else {
        $api->response->sendError("Nepodařilo se upravit pivo.", 500);
    }
} catch (Exception $e) {
    error_log("DB Error (update_beer): " . $e->getMessage());
    $api->response->sendError("Vnitřní chyba databáze při úpravě piva.", 500);
}
?>