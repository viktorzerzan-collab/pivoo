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

if (!empty($data->id) && !empty($data->name)) {
    try {
        $query = "UPDATE breweries 
                  SET name = ?, city = ?, country = ?, address = ?, street_number = ?, zip_code = ?, email = ?, phone = ?, website = ? 
                  WHERE id = ?";
        $stmt = $db->prepare($query);
        
        $city = !empty($data->city) ? $data->city : null;
        $country = !empty($data->country) ? $data->country : null;
        $address = !empty($data->address) ? $data->address : null;
        $street_number = !empty($data->street_number) ? $data->street_number : null;
        $zip_code = !empty($data->zip_code) ? $data->zip_code : null;
        $email = !empty($data->email) ? $data->email : null;
        $phone = !empty($data->phone) ? $data->phone : null;
        $website = !empty($data->website) ? $data->website : null;

        if ($stmt->execute([$data->name, $city, $country, $address, $street_number, $zip_code, $email, $phone, $website, $data->id])) {
            echo json_encode(["status" => "success", "message" => "Pivovar byl aktualizován."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Chyba při aktualizaci pivovaru."]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Chyba DB: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "ID a Název jsou povinné."]);
}
?>