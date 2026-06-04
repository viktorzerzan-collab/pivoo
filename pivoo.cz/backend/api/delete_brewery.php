<?php
// backend/api/delete_brewery.php
require_once '../core/ApiHandler.php';

$api = new ApiHandler();
$api->requireAdmin();

$id = $api->request->getIntParam('id');

if (!$id) {
    $api->response->sendError("Chybí ID pivovaru.", 400);
}

try {
    // Kontrola, zda pivovar nemá přiřazená piva
    $check = $api->db->prepare("SELECT COUNT(*) FROM beers WHERE brewery_id = ?");
    $check->execute([$id]);
    if ($check->fetchColumn() > 0) {
        $api->response->sendError("Nelze smazat pivovar, který má v katalogu piva.", 400);
    }

    // Získání názvu loga pro smazání souboru
    $stmt_old = $api->db->prepare("SELECT logo FROM breweries WHERE id = ?");
    $stmt_old->execute([$id]);
    $brewery = $stmt_old->fetch();
    
    if ($brewery && $brewery['logo']) {
        $file_path = '../uploads/logos/' . $brewery['logo'];
        if (file_exists($file_path)) unlink($file_path);
    }

    $stmt = $api->db->prepare("DELETE FROM breweries WHERE id = ?");
    if ($stmt->execute([$id])) {
        $api->response->sendSuccess("Pivovar byl úspěšně smazán.");
    } else {
        $api->response->sendError("Pivovar se nepodařilo smazat.", 500);
    }
} catch (Exception $e) {
    error_log("DB Error (delete_brewery): " . $e->getMessage());
    $api->response->sendError("Vnitřní chyba serveru při mazání pivovaru.", 500);
}
?>