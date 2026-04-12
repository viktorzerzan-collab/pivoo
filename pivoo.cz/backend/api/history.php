<?php
// backend/api/history.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../Database.php';

$db = (new Database())->getConnection();
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

if ($db && $user_id) {
    // Tady je ta hlavní změna: c.* zajistí, že se stáhne úplně vše (ID piva, ID lokace, poznámky, hodnocení...)
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
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Chybí ID uživatele."]);
}