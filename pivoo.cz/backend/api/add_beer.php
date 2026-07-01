<?php
// backend/api/add_beer.php
require_once '../core/ApiHandler.php';

// Inicializace jádra a ověření administrátora (hlavičky a spojení s DB se vyřeší automaticky)
$api = new ApiHandler();
$user = $api->requireAdmin();

// Načtení povinných parametrů
$name = $api->request->getParam('name');
$brewery_id = $api->request->getParam('brewery_id');
$style_id = $api->request->getParam('style_id');

if (!$name || !$brewery_id || !$style_id) {
    $api->response->sendError("Název piva, pivovar a styl jsou povinné údaje.", 400);
}

try {
    $query = "INSERT INTO beers (
                name, brewery_id, style_id, epm, abv, 
                ibu, ebc, hops, malts, fermentation, tags, is_unfiltered, is_unpasteurized,
                is_approved, created_by
              ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)";
    
    $stmt = $api->db->prepare($query);
    
    // Načtení nepovinných parametrů přes bezpečný Request Wrapper
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
        $ibu, $ebc, $hops, $malts, $fermentation, $tags, $is_unfiltered, $is_unpasteurized, $user['user_id']
    ])) {
        $new_id = $api->db->lastInsertId();
        $api->response->sendSuccess("Pivo bylo úspěšně přidáno do katalogu.", ["id" => $new_id]);
    } else {
        $api->response->sendError("Pivo se nepodařilo uložit.", 500);
    }
} catch (PDOException $e) {
    error_log("DB Error (add_beer): " . $e->getMessage());
    $api->response->sendError("Vnitřní chyba serveru při komunikaci s databází.", 500);
}
?>