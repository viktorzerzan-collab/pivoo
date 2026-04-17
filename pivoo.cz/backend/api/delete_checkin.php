<?php
// ZMĚNA: Omezení CORS
header("Access-Control-Allow-Origin: https://www.pivoo.cz");
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

if (!empty($data->id)) {
    // ZMĚNA: Přidán try-catch blok pro bezpečné odchycení PDO výjimek
    try {
        // Aplikujeme ochranu z tokenu -> Můžu smazat jen co je moje
        $query = "DELETE FROM consumptions WHERE id = :id AND user_id = :uid";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $data->id);
        $stmt->bindParam(':uid', $user['user_id']); 
        
        if($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Záznam byl úspěšně smazán."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Chyba při mazání z databáze."]);
        }
    } catch (PDOException $e) {
        // ZMĚNA: Tiché logování chyby
        error_log("DB Error (delete_checkin): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Vnitřní chyba při mazání záznamu."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Chybí parametry pro smazání."]);
}
?>