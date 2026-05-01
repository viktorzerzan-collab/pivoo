<?php
// ZMĚNA: Omezení CORS
header("Access-Control-Allow-Origin: https://www.pivoo.cz");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(200); 
    exit(); 
}

require_once '../Database.php';
require_once '../JwtHandler.php';

// ZABEZPEČENÍ!
JwtHandler::checkAdmin();

$db = (new Database())->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id)) {
    try {
        // PŘIDÁNO: Zjištění ID pivovaru PŘED smazáním piva
        $stmtBrew = $db->prepare("SELECT brewery_id FROM beers WHERE id = ?");
        $stmtBrew->execute([$data->id]);
        $brew = $stmtBrew->fetch();

        // Nejprve smažeme záznamy o konzumaci tohoto piva
        $db->prepare("DELETE FROM consumptions WHERE beer_id = ?")->execute([$data->id]);
        
        $query = "DELETE FROM beers WHERE id = ?";
        $stmt = $db->prepare($query);
        
        if($stmt->execute([$data->id])) {
            
            // PŘIDÁNO: Přepočet statistik pivovaru po smazání piva
            if ($brew && $brew['brewery_id']) {
                $brewId = $brew['brewery_id'];
                try {
                    $db->prepare("UPDATE breweries SET total_beers_in_catalog = (SELECT COUNT(id) FROM beers WHERE brewery_id = ?), avg_rating = (SELECT ROUND(AVG(NULLIF(c.rating_beer, 0)), 1) FROM consumptions c JOIN beers b ON c.beer_id = b.id WHERE b.brewery_id = ?) WHERE id = ?")->execute([$brewId, $brewId, $brewId]);
                } catch (PDOException $e) {
                    error_log("Chyba při přepočtu pivovaru po smazání piva (delete_beer): " . $e->getMessage());
                }
            }

            echo json_encode(["status" => "success", "message" => "Pivo bylo odstraněno z katalogu."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Chyba při mazání."]);
        }
    } catch (Exception $e) {
        // ZMĚNA: Úprava zranitelnosti Information Disclosure
        error_log("DB Error (delete_beer): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Vnitřní chyba serveru při mazání piva."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Chybí ID piva."]);
}
?>