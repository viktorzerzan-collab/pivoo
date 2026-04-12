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

$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->user_id) && !empty($data->beer_id) && !empty($data->location_id)) {
    try {
        $query = "INSERT INTO consumptions (user_id, beer_id, location_id, volume, quantity, price, rating_beer, rating_care, note, packaging) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($query);
        
        $volume = !empty($data->volume) ? $data->volume : null;
        $quantity = !empty($data->quantity) ? (int)$data->quantity : 1;
        $price = (!empty($data->price) && $data->price !== '') ? $data->price : null;
        $rating_beer = !empty($data->rating_beer) ? (int)$data->rating_beer : 0;
        $rating_care = !empty($data->rating_care) ? (int)$data->rating_care : 0;
        $note = !empty($data->note) ? $data->note : null;
        $packaging = !empty($data->packaging) ? $data->packaging : 'točené';

        if ($stmt->execute([
            $data->user_id, 
            $data->beer_id, 
            $data->location_id, 
            $volume, 
            $quantity, 
            $price, 
            $rating_beer, 
            $rating_care, 
            $note, 
            $packaging
        ])) {
            echo json_encode(["status" => "success", "message" => "Zapsáno do deníčku!"]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Chyba při zápisu."]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Chyba DB: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Chybí data."]);
}
?>