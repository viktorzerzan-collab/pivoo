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

// UZAMČENÍ ENDPOINTU!
JwtHandler::checkAdmin();

$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->name) && !empty($data->brewery_id) && !empty($data->style)) {
    try {
        $query = "INSERT INTO beers (name, brewery_id, style, epm, abv, is_approved) 
                  VALUES (?, ?, ?, ?, ?, 1)";
        $stmt = $db->prepare($query);
        
        $epm = ($data->epm !== '') ? $data->epm : null;
        $abv = ($data->abv !== '') ? $data->abv : null;

        if ($stmt->execute([$data->name, $data->brewery_id, $data->style, $epm, $abv])) {
            echo json_encode(["status" => "success", "message" => "Pivo bylo úspěšně přidáno do katalogu."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Pivo se nepodařilo uložit."]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Chyba databáze: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Název piva, pivovar a styl jsou povinné údaje."]);
}
?>