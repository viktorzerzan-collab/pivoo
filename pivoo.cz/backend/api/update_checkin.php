<?php
// ZMĚNA: Omezení CORS
header("Access-Control-Allow-Origin: https://www.pivoo.cz");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once '../Database.php';
require_once '../JwtHandler.php';

$user = JwtHandler::checkUser();

$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id)) {
    try {
        $query = "UPDATE consumptions 
                  SET beer_id = ?, location_id = ?, volume = ?, quantity = ?, price = ?, rating_beer = ?, rating_care = ?, note = ?, packaging = ?, consumed_at = ? 
                  WHERE id = ? AND user_id = ?";
                  
        $stmt = $db->prepare($query);
        
        $beer_id = !empty($data->beer_id) ? $data->beer_id : null;
        $location_id = !empty($data->location_id) ? $data->location_id : null;
        $volume = !empty($data->volume) ? $data->volume : null;
        $quantity = !empty($data->quantity) ? (int)$data->quantity : 1;
        $price = (!empty($data->price) && $data->price !== '') ? $data->price : null;
        
        // Stejné ošetření nulových hodnocení jako u přidávání
        $rating_beer = (!empty($data->rating_beer) && $data->rating_beer > 0) ? (int)$data->rating_beer : null;
        $rating_care = (!empty($data->rating_care) && $data->rating_care > 0) ? (int)$data->rating_care : null;
        
        $note = !empty($data->note) ? $data->note : null;
        $packaging = !empty($data->packaging) ? $data->packaging : 'točené';
        $consumed_at = !empty($data->consumed_at) ? $data->consumed_at : null;

        if ($stmt->execute([
            $beer_id, $location_id, $volume, $quantity, $price, $rating_beer, $rating_care,
            $note, $packaging, $consumed_at, $data->id, $user['user_id']
        ])) {
            echo json_encode(["status" => "success", "message" => "Záznam byl úspěšně upraven."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Nepodařilo se upravit záznam."]);
        }
    } catch (PDOException $e) {
        // ZMĚNA: Skrytí chyby
        error_log("DB Error (update_checkin): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Vnitřní chyba databáze."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Chybí ID záznamu."]);
}
?>