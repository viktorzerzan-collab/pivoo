<?php
// backend/api/approve_entity.php
require_once '../core/ApiHandler.php';

$api = new ApiHandler();
$api->requireAdmin();

$entity_type = $api->request->getParam('entity_type');
$entity_id = $api->request->getIntParam('entity_id');
$action = $api->request->getParam('action');

if (!$entity_type || !$entity_id || !$action) {
    $api->response->sendError("Chybí povinná data (typ, id, akce).", 400);
}

$allowed_types = [
    'beer' => 'beers', 
    'brewery' => 'breweries', 
    'location' => 'locations'
];

if (!array_key_exists($entity_type, $allowed_types)) {
    $api->response->sendError("Neplatný typ entity.", 400);
}

$table = $allowed_types[$entity_type];

try {
    if ($action === 'approve') {
        $stmt = $api->db->prepare("UPDATE {$table} SET is_approved = 1 WHERE id = ?");
        if ($stmt->execute([$entity_id])) {
            $api->response->sendSuccess("Záznam byl úspěšně schválen a je viditelný pro všechny.");
        } else {
            $api->response->sendError("Nepodařilo se uložit schválení do databáze.", 500);
        }
    } elseif ($action === 'reject') {
        $stmt = $api->db->prepare("DELETE FROM {$table} WHERE id = ?");
        if ($stmt->execute([$entity_id])) {
            $api->response->sendSuccess("Záznam byl zamítnut a trvale smazán.");
        } else {
            $api->response->sendError("Záznam se nepodařilo smazat. Může být navázán na existující konzumace.", 500);
        }
    } else {
        $api->response->sendError("Neznámá akce.", 400);
    }
} catch (Exception $e) {
    error_log("DB Error (approve_entity): " . $e->getMessage());
    $api->response->sendError("Vnitřní chyba serveru při zpracování požadavku.", 500);
}
?>