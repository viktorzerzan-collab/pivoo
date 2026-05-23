<?php
// backend/api/delete_checkin.php
require_once '../core/ApiHandler.php';

$api = new ApiHandler();
$user = JwtHandler::checkUser();

$id = $api->request->getIntParam('id');

if (!$id) {
    $api->response->sendError("Chybí parametry pro smazání.", 400);
}

try {
    // Získat údaje před smazáním pro přepočet
    $stmtOld = $api->db->prepare("SELECT beer_id, location_id FROM consumptions WHERE id = :id AND user_id = :uid");
    $stmtOld->execute([':id' => $id, ':uid' => $user['user_id']]);
    $oldCons = $stmtOld->fetch();

    // Aplikujeme ochranu z tokenu -> Můžu smazat jen co je moje
    $query = "DELETE FROM consumptions WHERE id = :id AND user_id = :uid";
    $stmt = $api->db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':uid', $user['user_id']); 
    
    if ($stmt->execute()) {
        
        // Přepočet po smazání záznamu
        if ($oldCons) {
            $bid = $oldCons['beer_id'];
            $lid = $oldCons['location_id'];
            try {
                $api->db->prepare("UPDATE beers SET total_checkins = (SELECT COUNT(id) FROM consumptions WHERE beer_id = ?), avg_rating = (SELECT ROUND(AVG(NULLIF(rating_beer, 0)), 1) FROM consumptions WHERE beer_id = ?) WHERE id = ?")->execute([$bid, $bid, $bid]);
                
                $stmtBrew = $api->db->prepare("SELECT brewery_id FROM beers WHERE id = ?");
                $stmtBrew->execute([$bid]);
                $brew = $stmtBrew->fetch();
                if ($brew && $brew['brewery_id']) {
                    $api->db->prepare("UPDATE breweries SET avg_rating = (SELECT ROUND(AVG(NULLIF(c.rating_beer, 0)), 1) FROM consumptions c JOIN beers b ON c.beer_id = b.id WHERE b.brewery_id = ?) WHERE id = ?")->execute([$brew['brewery_id'], $brew['brewery_id']]);
                }
                
                $api->db->prepare("UPDATE locations SET total_visits = (SELECT COUNT(id) FROM consumptions WHERE location_id = ?), avg_rating = (SELECT ROUND(AVG(NULLIF(rating_beer, 0)), 1) FROM consumptions WHERE location_id = ?) WHERE id = ?")->execute([$lid, $lid, $lid]);
            } catch (PDOException $e) {
                error_log("Chyba při přepočtu statistik po smazání (delete_checkin): " . $e->getMessage());
            }
        }

        $api->response->sendSuccess("Záznam byl úspěšně smazán.");
    } else {
        $api->response->sendError("Chyba při mazání z databáze.", 500);
    }
} catch (PDOException $e) {
    error_log("DB Error (delete_checkin): " . $e->getMessage());
    $api->response->sendError("Vnitřní chyba při mazání záznamu.", 500);
}
?>