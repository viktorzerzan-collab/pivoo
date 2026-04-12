<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../Database.php';
require_once '../JwtHandler.php';

// ZABEZPEČENÍ!
JwtHandler::checkAdmin();

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id) && !empty($data->name)) {
    try {
        $query = "UPDATE beers 
                  SET name = ?, brewery_id = ?, style = ?, epm = ?, abv = ? 
                  WHERE id = ?";
                  
        $stmt = $db->prepare($query);
        
        $epm = ($data->epm !== '') ? $data->epm : null;
        $abv = ($data->abv !== '') ? $data->abv : null;

        if ($stmt->execute([$data->name, $data->brewery_id, $data->style, $epm, $abv, $data->id])) {
            echo json_encode(["status" => "success", "message" => "Pivo v katalogu bylo úspěšně upraveno."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Nepodařilo se upravit pivo."]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Chyba databáze: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Chybí povinná data (ID nebo název)."]);
}
?>