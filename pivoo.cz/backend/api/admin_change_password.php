<?php
// ZMĚNA: Omezení CORS
header("Access-Control-Allow-Origin: https://www.pivoo.cz");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Ošetření preflight dotazu (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../Database.php';
require_once '../JwtHandler.php';

// ZABEZPEČENÍ: Tento endpoint může zavolat pouze administrátor
JwtHandler::checkAdmin();

$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"));

// Kontrola, zda máme všechna potřebná data
if (!empty($data->user_id) && !empty($data->new_password)) {
    
    // Dodatečná kontrola síly hesla i na straně serveru
    if (strlen($data->new_password) < 8 || !preg_match('/[0-9]/', $data->new_password) || !preg_match('/[^a-zA-Z0-9]/', $data->new_password)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Nové heslo musí mít alespoň 8 znaků, obsahovat číslo a speciální znak."]);
        exit();
    }

    try {
        // Zašifrování nového hesla
        $new_hash = password_hash($data->new_password, PASSWORD_DEFAULT);

        // Uložení do databáze
        $query = "UPDATE users SET password_hash = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$new_hash, $data->user_id])) {
            echo json_encode(["status" => "success", "message" => "Heslo bylo úspěšně změněno."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Nepodařilo se změnit heslo v databázi."]);
        }
    } catch (PDOException $e) {
        // ZMĚNA: Skrytí chybové hlášky
        error_log("DB Error (admin_change_password): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Vnitřní chyba databáze při změně hesla."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Chybí ID uživatele nebo nové heslo."]);
}
?>