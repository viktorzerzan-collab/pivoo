<?php
// backend/api/add_location.php
header("Access-Control-Allow-Origin: https://www.pivoo.cz");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../Database.php';
require_once '../JwtHandler.php';

// ZABEZPEČENÍ a získání informací o adminovi
$user = JwtHandler::checkAdmin();

$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->name) && !empty($data->type)) {
    try {
        // PŘIDÁNO: Uložení autora
        $query = "INSERT INTO locations (name, type, city, country_id, address, zip_code, email, phone, website, lat, lng, opening_hours, is_approved, created_by) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)";
        
        $stmt = $db->prepare($query);
        
        $city = !empty($data->city) ? $data->city : null;
        $country_id = !empty($data->country_id) ? (int)$data->country_id : 1; 
        $address = !empty($data->address) ? $data->address : null;
        $zip_code = !empty($data->zip_code) ? $data->zip_code : null;
        $email = !empty($data->email) ? $data->email : null;
        $phone = !empty($data->phone) ? $data->phone : null;
        $website = !empty($data->website) ? $data->website : null;
        $lat = !empty($data->lat) ? (float)$data->lat : null;
        $lng = !empty($data->lng) ? (float)$data->lng : null;
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
            $lat,
            $lng,
            $opening_hours,
            $user['user_id']
        ])) {
            $new_id = $db->lastInsertId();
            echo json_encode(["status" => "success", "message" => "Nové místo bylo úspěšně přidáno.", "id" => $new_id]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Nepodařilo se uložit místo."]);
        }
    } catch (PDOException $e) {
        error_log("DB Error (add_location): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Vnitřní chyba databáze při ukládání lokace."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Název a typ místa jsou povinné údaje."]);
}
?>