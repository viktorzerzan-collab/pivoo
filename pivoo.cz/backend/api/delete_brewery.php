<?php
// ZMĚNA: Přísnější CORS politika, povolen pouze přístup z vlastní domény
header("Access-Control-Allow-Origin: https://www.pivoo.cz");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once '../Database.php';
require_once '../JwtHandler.php';

// ZABEZPEČENÍ: Pouze pro administrátory
JwtHandler::checkAdmin();

$db = (new Database())->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id)) {
    try {
        // Kontrola, zda pivovar nemá přiřazená piva
        $check = $db->prepare("SELECT COUNT(*) FROM beers WHERE brewery_id = ?");
        $check->execute([$data->id]);
        if ($check->fetchColumn() > 0) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Nelze smazat pivovar, který má v katalogu piva."]);
            exit();
        }

        // Získání názvu loga pro smazání souboru
        $stmt_old = $db->prepare("SELECT logo FROM breweries WHERE id = ?");
        $stmt_old->execute([$data->id]);
        $brewery = $stmt_old->fetch();
        
        if ($brewery && $brewery['logo']) {
            $file_path = '../uploads/logos/' . $brewery['logo'];
            if (file_exists($file_path)) unlink($file_path);
        }

        $stmt = $db->prepare("DELETE FROM breweries WHERE id = ?");
        if ($stmt->execute([$data->id])) {
            echo json_encode(["status" => "success", "message" => "Pivovar byl úspěšně smazán."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Pivovar se nepodařilo smazat."]);
        }
    } catch (Exception $e) {
        // ZMĚNA: Skrytí konkrétní chyby databáze
        error_log("DB Error (delete_brewery): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Vnitřní chyba serveru při mazání pivovaru."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Chybí ID pivovaru."]);
}
?>