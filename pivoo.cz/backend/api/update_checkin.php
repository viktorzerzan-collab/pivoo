<?php
// backend/api/update_checkin.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once '../Database.php';
$db = (new Database())->getConnection();
$data = json_decode(file_get_contents("php://input"));

// Vyžadujeme ID záznamu, ID uživatele (aby si lidi nemohli editovat piva navzájem) a všechna nová data
if(!empty($data->id) && !empty($data->user_id) && !empty($data->beer_id) && !empty($data->location_id) && !empty($data->volume) && !empty($data->quantity)) {
    
    // UPDATE SQL dotaz - změníme všechna pole pro dané ID záznamu a uživatele
    $query = "UPDATE consumptions SET 
                beer_id = :bid, 
                location_id = :lid, 
                volume = :vol, 
                quantity = :qty, 
                price = :price, 
                rating_beer = :rating, 
                note = :note 
              WHERE id = :id AND user_id = :uid";
              
    $stmt = $db->prepare($query);
    
    // Připravíme parametry
    $stmt->bindParam(':bid', $data->beer_id);
    $stmt->bindParam(':lid', $data->location_id);
    $stmt->bindParam(':vol', $data->volume);
    $stmt->bindParam(':qty', $data->quantity);
    
    // Nepovinné údaje (Cena za kus, Hodnocení, Poznámka) - ošetříme prázdné hodnoty jako NULL
    $price = !empty($data->price) ? $data->price : null;
    $stmt->bindParam(':price', $price);
    
    $rating = !empty($data->rating_beer) ? $data->rating_beer : null;
    $stmt->bindParam(':rating', $rating);
    
    $note = !empty($data->note) ? $data->note : null;
    $stmt->bindParam(':note', $note);
    
    // IDs pro identifikaci záznamu
    $stmt->bindParam(':id', $data->id);
    $stmt->bindParam(':uid', $data->user_id);
    
    if($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Záznam byl úspěšně upraven."]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Chyba při ukládání změn do databáze."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Chybí parametry pro editaci."]);
}