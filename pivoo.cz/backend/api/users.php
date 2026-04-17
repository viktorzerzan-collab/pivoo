<?php
// ZMĚNA: Omezení CORS
header("Access-Control-Allow-Origin: https://www.pivoo.cz");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../Database.php';
require_once '../JwtHandler.php';

// UZAMČENÍ ENDPOINTU! Bez platného tokenu to dál nepojede.
JwtHandler::checkAdmin();

$database = new Database();
$db = $database->getConnection();

if ($db) {
    try {
        $query = "SELECT id, username, first_name, last_name, email, role, avatar, is_banned FROM users ORDER BY id DESC";
        $stmt = $db->query($query);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(["status" => "success", "data" => $users]);
    } catch (Exception $e) {
        // ZMĚNA: Zalogování chyby na server
        error_log("DB Error (users): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Vnitřní chyba při načítání uživatelů."]);
    }
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Chyba spojení s databází."]);
}
?>