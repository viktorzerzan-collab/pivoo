<?php
// ZMĚNA: Omezení CORS
header("Access-Control-Allow-Origin: https://www.pivoo.cz");
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

if (!empty($data->user_id)) {
    try {
        // Zjistíme, jaký avatar uživatel aktuálně má
        $stmt_find = $db->prepare("SELECT avatar FROM users WHERE id = ?");
        $stmt_find->execute([$data->user_id]);
        $targetUser = $stmt_find->fetch();

        if ($targetUser && $targetUser['avatar']) {
            // Smažeme fyzický soubor z FTP
            $file_path = '../uploads/avatars/' . $targetUser['avatar'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }

            // Vymažeme záznam z databáze
            $stmt_update = $db->prepare("UPDATE users SET avatar = NULL WHERE id = ?");
            if ($stmt_update->execute([$data->user_id])) {
                echo json_encode(["status" => "success", "message" => "Profilová fotka byla úspěšně smazána."]);
            } else {
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "Chyba při aktualizaci databáze."]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Tento uživatel nemá žádnou profilovou fotku."]);
        }
    } catch (Exception $e) {
        // ZMĚNA: Skrytí chybové hlášky
        error_log("Error (admin_remove_avatar): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Chyba na straně serveru při odstraňování avataru."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Chybí ID uživatele."]);
}
?>