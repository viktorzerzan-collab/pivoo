<?php
// backend/api/checkin.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once '../Database.php';
$db = (new Database())->getConnection();
$data = json_decode(file_get_contents("php://input"));

// Nyní vyžadujeme i quantity (počet kusů)
if(!empty($data->user_id) && !empty($data->beer_id) && !empty($data->location_id) && !empty($data->volume) && !empty($data->quantity)) {
    
    $query = "INSERT INTO consumptions (user_id, beer_id, location_id, volume, quantity, price, rating_beer, note, consumed_at) 
              VALUES (:uid, :bid, :lid, :vol, :qty, :price, :rating, :note, NOW())";
              
    $stmt = $db->prepare($query);
    $stmt->bindParam(':uid', $data->user_id);
    $stmt->bindParam(':bid', $data->beer_id);
    $stmt->bindParam(':lid', $data->location_id);
    $stmt->bindParam(':vol', $data->volume);
    $stmt->bindParam(':qty', $data->quantity); // Zde se ukládá počet kusů
    
    // Nepovinné údaje (Cena za kus, Hodnocení, Poznámka)
    $price = !empty($data->price) ? $data->price : null;
    $stmt->bindParam(':price', $price);
    
    $rating = !empty($data->rating_beer) ? $data->rating_beer : null;
    $stmt->bindParam(':rating', $rating);
    
    $note = !empty($data->note) ? $data->note : null;
    $stmt->bindParam(':note', $note);
    
    if($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Na zdraví! Pivo bylo úspěšně zapsáno. 🍻"]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Chyba databáze."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Musíš vyplnit co, kde, kolik a počet kusů!"]);
}