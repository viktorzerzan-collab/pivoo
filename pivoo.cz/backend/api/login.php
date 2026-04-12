<?php
// Povolení komunikace mezi localhostem (Vue) a serverem (Český hosting) - tzv. CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Pokud prohlížeč posílá tzv. preflight request, rovnou ho ukončíme úspěchem
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../Database.php';

$database = new Database();
$db = $database->getConnection();

// Získání dat, která nám pošle Vue.js (budou ve formátu JSON)
$data = json_decode(file_get_contents("php://input"));

if(!empty($data->username) && !empty($data->password)) {
    // Najdeme uživatele podle jména
    $query = "SELECT id, username, first_name, password_hash, role FROM users WHERE username = :username LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $data->username);
    $stmt->execute();
    
    $user = $stmt->fetch();
    
    // Ověříme, zda uživatel existuje a zda heslo sedí s hashem v databázi
    if($user && password_verify($data->password, $user['password_hash'])) {
        // TADY SE POZDĚJI VYGENERUJE JWT TOKEN, zatím pošleme jen success
        echo json_encode([
            "status" => "success",
            "message" => "Vítej v hospodě, " . $user['first_name'] . "!",
            "user" => [
                "id" => $user['id'],
                "username" => $user['username'],
                "name" => $user['first_name'],
                "role" => $user['role']
            ]
        ]);
    } else {
        http_response_code(401); // 401 znamená Neautorizováno
        echo json_encode(["status" => "error", "message" => "Špatné jméno nebo heslo, zkus to znovu."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Musíš vyplnit jméno i heslo."]);
}