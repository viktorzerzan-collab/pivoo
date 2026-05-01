<?php
// ZMĚNA: Omezení CORS
header("Access-Control-Allow-Origin: https://www.pivoo.cz");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once '../Database.php';
require_once '../JwtHandler.php';

// ZABEZPEČENÍ
$user = JwtHandler::checkUser();

$db = (new Database())->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id)) {
    // ZMĚNA: Přidán try-catch blok pro bezpečné odchycení PDO výjimek
    try {
        // PŘIDÁNO: Získat údaje před smazáním pro přepočet
        $stmtOld = $db->prepare("SELECT beer_id, location_id FROM consumptions WHERE id = :id AND user_id = :uid");
        $stmtOld->execute([':id' => $data->id, ':uid' => $user['user_id']]);
        $oldCons = $stmtOld->fetch();

        // Aplikujeme ochranu z tokenu -> Můžu smazat jen co je moje
        $query = "DELETE FROM consumptions WHERE id = :id AND user_id = :uid";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $data->id);
        $stmt->bindParam(':uid', $user['user_id']); 
        
        if($stmt->execute()) {
            
            // PŘIDÁNO: Přepočet po smazání záznamu
            if ($oldCons) {
                $bid = $oldCons['beer_id'];
                $lid = $oldCons['location_id'];
                try {
                    $db->prepare("UPDATE beers SET total_checkins = (SELECT COUNT(id) FROM consumptions WHERE beer_id = ?), avg_rating = (SELECT ROUND(AVG(NULLIF(rating_beer, 0)), 1) FROM consumptions WHERE beer_id = ?) WHERE id = ?")->execute([$bid, $bid, $bid]);
                    
                    $stmtBrew = $db->prepare("SELECT brewery_id FROM beers WHERE id = ?");
                    $stmtBrew->execute([$bid]);
                    $brew = $stmtBrew->fetch();
                    if ($brew && $brew['brewery_id']) {
                        $db->prepare("UPDATE breweries SET avg_rating = (SELECT ROUND(AVG(NULLIF(c.rating_beer, 0)), 1) FROM consumptions c JOIN beers b ON c.beer_id = b.id WHERE b.brewery_id = ?) WHERE id = ?")->execute([$brew['brewery_id'], $brew['brewery_id']]);
                    }
                    
                    $db->prepare("UPDATE locations SET total_visits = (SELECT COUNT(id) FROM consumptions WHERE location_id = ?), avg_rating = (SELECT ROUND(AVG(NULLIF(rating_beer, 0)), 1) FROM consumptions WHERE location_id = ?) WHERE id = ?")->execute([$lid, $lid, $lid]);
                } catch (PDOException $e) {
                    error_log("Chyba při přepočtu statistik po smazání (delete_checkin): " . $e->getMessage());
                }
            }

            echo json_encode(["status" => "success", "message" => "Záznam byl úspěšně smazán."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Chyba při mazání z databáze."]);
        }
    } catch (PDOException $e) {
        // ZMĚNA: Tiché logování chyby
        error_log("DB Error (delete_checkin): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Vnitřní chyba při mazání záznamu."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Chybí parametry pro smazání."]);
}
?>