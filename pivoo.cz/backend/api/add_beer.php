<?php
// backend/api/add_beer.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once '../Database.php';
$db = (new Database())->getConnection();
$data = json_decode(file_get_contents("php://input"));

if(!empty($data->name) && !empty($data->brewery_id)) {
    // Vložíme nové pivo. is_approved nastavíme rovnou na 1, ať ho hned vidíme v katalogu.
    $query = "INSERT INTO beers (brewery_id, name, style, epm, abv, is_approved) 
              VALUES (:brewery_id, :name, :style, :epm, :abv, 1)";
              
    $stmt = $db->prepare($query);
    $stmt->bindParam(':brewery_id', $data->brewery_id);
    $stmt->bindParam(':name', $data->name);
    
    // Nepovinné údaje
    $style = !empty($data->style) ? $data->style : null;
    $epm = !empty($data->epm) ? $data->epm : null;
    $abv = !empty($data->abv) ? $data->abv : null;
    
    $stmt->bindParam(':style', $style);
    $stmt->bindParam(':epm', $epm);
    $stmt->bindParam(':abv', $abv);
    
    if($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Nové pivo bylo přidáno do katalogu! 🎉"]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Chyba databáze při ukládání."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Musíš vyplnit aspoň název piva a pivovar!"]);
}