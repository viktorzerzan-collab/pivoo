<?php
// backend/api/update_profile.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once '../Database.php';
require_once '../JwtHandler.php';

// ZABEZPEČENÍ
$user = JwtHandler::checkUser();

$db = (new Database())->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->old_password) && !empty($data->new_password)) {
    // 1. Ověříme, zda se staré heslo shoduje s tím v databázi
    $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$user['user_id']]);
    $dbUser = $stmt->fetch();

    if ($dbUser && password_verify($data->old_password, $dbUser['password_hash'])) {
        
        // 2. Kontrola síly nového hesla (stejná pravidla jako při registraci)
        if (strlen($data->new_password) < 8 || !preg_match('/[0-9]/', $data->new_password) || !preg_match('/[^a-zA-Z0-9]/', $data->new_password)) {
            echo json_encode(["status" => "error", "message" => "Nové heslo musí mít alespoň 8 znaků, obsahovat číslo a speciální znak."]);
            exit();
        }

        // 3. Zahešujeme a uložíme nové heslo
        $new_hash = password_hash($data->new_password, PASSWORD_DEFAULT);
        $update = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        
        if ($update->execute([$new_hash, $user['user_id']])) {
            echo json_encode(["status" => "success", "message" => "Heslo bylo úspěšně změněno."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Chyba při ukládání do databáze."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Původní heslo není správné."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Vyplňte všechna pole pro změnu hesla."]);
}
?>