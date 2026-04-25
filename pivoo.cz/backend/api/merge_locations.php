<?php
// backend/api/merge_locations.php
header("Access-Control-Allow-Origin: https://www.pivoo.cz");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Ošetření preflight dotazu (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(200); 
    exit(); 
}

require_once '../Database.php';
require_once '../JwtHandler.php';

// ZABEZPEČENÍ: Pouze pro administrátory
JwtHandler::checkAdmin();

$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->source_id) && !empty($data->target_id)) {
    
    // Ochrana před sloučením podniku se sebou samým
    if ($data->source_id == $data->target_id) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Nelze sloučit podnik sám se sebou."]);
        exit();
    }

    try {
        // Zahájení transakce (buď se provede vše, nebo nic)
        $db->beginTransaction();

        // 1. Krok: Přesun všech záznamů (konzumací) ze zdrojového podniku na cílový
        $queryUpdate = "UPDATE consumptions SET location_id = ? WHERE location_id = ?";
        $stmtUpdate = $db->prepare($queryUpdate);
        $stmtUpdate->execute([$data->target_id, $data->source_id]);

        // 2. Krok: Smazání původního (duplicitního) podniku
        $queryDelete = "DELETE FROM locations WHERE id = ?";
        $stmtDelete = $db->prepare($queryDelete);
        $stmtDelete->execute([$data->source_id]);

        // Potvrzení transakce
        $db->commit();

        echo json_encode(["status" => "success", "message" => "Podniky byly úspěšně sloučeny. Záznamy byly přesunuty a duplicita byla smazána."]);

    } catch (Exception $e) {
        // V případě chyby vrátíme databázi do původního stavu
        $db->rollBack();
        
        error_log("DB Error (merge_locations): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Vnitřní chyba serveru při slučování podniků."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Chybí ID zdrojového nebo cílového podniku."]);
}
?>