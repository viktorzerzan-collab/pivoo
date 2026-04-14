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

JwtHandler::checkAdmin();

$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id) && !empty($data->name)) {
    try {
        $query = "UPDATE locations 
                  SET name = ?, type = ?, city = ?, country_id = ?, address = ?, zip_code = ?, email = ?, phone = ?, website = ?, opening_hours = ? 
                  WHERE id = ?";
                  
        $stmt = $db->prepare($query);
        
        $city = !empty($data->city) ? $data->city : null;
        $country_id = !empty($data->country_id) ? (int)$data->country_id : null;
        $address = !empty($data->address) ? $data->address : null;
        $zip_code = !empty($data->zip_code) ? $data->zip_code : null;
        $email = !empty($data->email) ? $data->email : null;
        $phone = !empty($data->phone) ? $data->phone : null;
        $website = !empty($data->website) ? $data->website : null;
        $opening_hours = !empty($data->opening_hours) ? $data->opening_hours : null;

        if ($stmt->execute([
            $data->name, 
            $data->type, 
            $city, 
            $country_id, 
            $address, 
            $zip_code, 
            $email, 
            $phone, 
            $website, 
            $opening_hours,
            $data->id
        ])) {
            echo json_encode(["status" => "success", "message" => "Místo bylo úspěšně aktualizováno."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Chyba při aktualizaci v DB."]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Chyba databáze: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Chybí ID nebo název místa."]);
}
?>