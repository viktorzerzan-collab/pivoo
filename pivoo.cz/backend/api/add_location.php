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

if (!empty($data->name) && !empty($data->type)) {
    try {
        // Vkládáme včetně is_approved = 1
        $query = "INSERT INTO locations (name, type, city, country, address, street_number, zip_code, email, phone, website, opening_hours, is_approved) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        
        $stmt = $db->prepare($query);
        
        $city = !empty($data->city) ? $data->city : null;
        $country = !empty($data->country) ? $data->country : 'Česká republika';
        $address = !empty($data->address) ? $data->address : null;
        $street_number = !empty($data->street_number) ? $data->street_number : null;
        $zip_code = !empty($data->zip_code) ? $data->zip_code : null;
        $email = !empty($data->email) ? $data->email : null;
        $phone = !empty($data->phone) ? $data->phone : null;
        $website = !empty($data->website) ? $data->website : null;
        $opening_hours = !empty($data->opening_hours) ? $data->opening_hours : null;

        if ($stmt->execute([
            $data->name, 
            $data->type,
            $city, 
            $country, 
            $address, 
            $street_number, 
            $zip_code, 
            $email, 
            $phone, 
            $website, 
            $opening_hours
        ])) {
            echo json_encode(["status" => "success", "message" => "Nové místo bylo úspěšně přidáno."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Nepodařilo se uložit místo."]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Chyba databáze: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Název a typ místa jsou povinné údaje."]);
}
?>