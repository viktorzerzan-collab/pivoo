<?php
// backend/api/history.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
// ZMĚNA: Povolujeme Authorization hlavičku
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once '../Database.php';
require_once '../JwtHandler.php';

// ZABEZPEČENÍ: Vytáhneme uživatele z tokenu.
$user = JwtHandler::checkUser();
$user_id = $user['user_id']; // ID bereme výhradně z tokenu!

$db = (new Database())->getConnection();

if ($db) {
    $query = "SELECT c.*,
                     b.name as beer_name, l.name as location_name
              FROM consumptions c
              JOIN beers b ON c.beer_id = b.id
              JOIN locations l ON c.location_id = l.id
              WHERE c.user_id = :uid
              ORDER BY c.consumed_at DESC
              LIMIT 10";
              
    $stmt = $db->prepare($query);
    $stmt->bindParam(':uid', $user_id);
    $stmt->execute();
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "success", "data" => $history]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Chyba spojení s DB."]);
}
?>