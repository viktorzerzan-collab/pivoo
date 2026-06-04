<?php
// backend/api/delete_beer.php
require_once '../core/ApiHandler.php';

$api = new ApiHandler();
$api->requireAdmin();

$id = $api->request->getIntParam('id');

if (!$id) {
    $api->response->sendError("Chybí ID piva.", 400);
}

try {
    // Zjištění ID pivovaru PŘED smazáním piva
    $stmtBrew = $api->db->prepare("SELECT brewery_id FROM beers WHERE id = ?");
    $stmtBrew->execute([$id]);
    $brew = $stmtBrew->fetch();

    // Nejprve smažeme záznamy o konzumaci tohoto piva
    $api->db->prepare("DELETE FROM consumptions WHERE beer_id = ?")->execute([$id]);
    
    $query = "DELETE FROM beers WHERE id = ?";
    $stmt = $api->db->prepare($query);
    
    if ($stmt->execute([$id])) {
        // Přepočet statistik pivovaru po smazání piva
        if ($brew && $brew['brewery_id']) {
            $brewId = $brew['brewery_id'];
            try {
                $api->db->prepare("UPDATE breweries SET total_beers_in_catalog = (SELECT COUNT(id) FROM beers WHERE brewery_id = ?), avg_rating = (SELECT ROUND(AVG(NULLIF(c.rating_beer, 0)), 1) FROM consumptions c JOIN beers b ON c.beer_id = b.id WHERE b.brewery_id = ?) WHERE id = ?")->execute([$brewId, $brewId, $brewId]);
            } catch (PDOException $e) {
                error_log("Chyba při přepočtu pivovaru po smazání piva (delete_beer): " . $e->getMessage());
            }
        }
        $api->response->sendSuccess("Pivo bylo odstraněno z katalogu.");
    } else {
        $api->response->sendError("Chyba při mazání.", 500);
    }
} catch (Exception $e) {
    error_log("DB Error (delete_beer): " . $e->getMessage());
    $api->response->sendError("Vnitřní chyba serveru při mazání piva.", 500);
}
?>