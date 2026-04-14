<?php
// backend/api/update_user.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../Database.php';
require_once '../JwtHandler.php';

// ZABEZPEČENÍ: Pouze pro administrátory
JwtHandler::checkAdmin();

$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id) && !empty($data->username) && !empty($data->email)) {
    try {
        $query = "UPDATE users 
                  SET username = ?, first_name = ?, last_name = ?, email = ?, role = ? 
                  WHERE id = ?";
                  
        $stmt = $db->prepare($query);
        
        $first_name = !empty($data->first_name) ? $data->first_name : null;
        $last_name = !empty($data->last_name) ? $data->last_name : null;
        $role = !empty($data->role) ? $data->role : 'user';

        if ($stmt->execute([
            $data->username,
            $first_name,
            $last_name,
            $data->email,
            $role,
            $data->id
        ])) {
            echo json_encode(["status" => "success", "message" => "Údaje uživatele byly aktualizovány."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Chyba při ukládání do databáze."]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        // Ošetření unikátních klíčů (např. pokud už e-mail nebo username existuje u jiného uživatele)
        echo json_encode(["status" => "error", "message" => "Uživatelské jméno nebo e-mail již používá někdo jiný."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Chybí povinná data (ID, přezdívka nebo e-mail)."]);
}
?>