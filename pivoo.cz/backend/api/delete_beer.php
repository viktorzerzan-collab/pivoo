<?php
header("Access-Control-Allow-Origin: *");
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
        // Nejprve smažeme záznamy o konzumaci tohoto piva
        $db->prepare("DELETE FROM consumptions WHERE beer_id = ?")->execute([$data->id]);
        
        $query = "DELETE FROM beers WHERE id = ?";
        $stmt = $db->prepare($query);
        
        if($stmt->execute([$data->id])) {
            echo json_encode(["status" => "success", "message" => "Pivo bylo odstraněno z katalogu."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Chyba při mazání."]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Chybí ID piva."]);
}
?>