<?php
// backend/api/approve_entity.php
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

// Zabezpečení: pouze administrátor
JwtHandler::checkAdmin();

$db = (new Database())->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->entity_type) && !empty($data->entity_id) && !empty($data->action)) {
    // Mapa povolených typů entit na skutečné názvy tabulek
    $allowed_types = [
        'beer' => 'beers', 
        'brewery' => 'breweries', 
        'location' => 'locations'
    ];
    
    if (!array_key_exists($data->entity_type, $allowed_types)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Neplatný typ entity."]);
        exit();
    }
    
    $table = $allowed_types[$data->entity_type];
    
    try {
        if ($data->action === 'approve') {
            // Schválení entity
            $stmt = $db->prepare("UPDATE {$table} SET is_approved = 1 WHERE id = ?");
            if ($stmt->execute([$data->entity_id])) {
                echo json_encode(["status" => "success", "message" => "Záznam byl úspěšně schválen a je viditelný pro všechny."]);
            } else {
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "Nepodařilo se uložit schválení do databáze."]);
            }
        } elseif ($data->action === 'reject') {
            // Zamítnutí (smazání) entity. U AI záznamů předpokládáme, že nemají žádné složité vazby.
            $stmt = $db->prepare("DELETE FROM {$table} WHERE id = ?");
            if ($stmt->execute([$data->entity_id])) {
                echo json_encode(["status" => "success", "message" => "Záznam byl zamítnut a trvale smazán."]);
            } else {
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "Záznam se nepodařilo smazat. Může být navázán na existující konzumace."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Neznámá akce."]);
        }
    } catch (Exception $e) {
        error_log("DB Error (approve_entity): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Vnitřní chyba serveru při zpracování požadavku."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Chybí povinná data (typ, id, akce)."]);
}
?>