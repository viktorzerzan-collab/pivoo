<?php
// backend/api/stats.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
// ZMĚNA: Povolujeme Authorization hlavičku
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once '../Database.php';
require_once '../JwtHandler.php';

// ZABEZPEČENÍ: Vytáhneme uživatele z tokenu. Pokud token chybí nebo je neplatný, skript se zde ukončí (401).
$user = JwtHandler::checkUser();
$user_id = $user['user_id']; // ID bereme výhradně z tokenu!

$database = new Database();
$db = $database->getConnection();

if ($db) {
    // 1. Spočítáme celkové sumy
    $query = "SELECT 
                SUM(volume * quantity) as total_liters, 
                SUM(price * quantity) as total_spent,
                SUM(quantity) as total_beers
              FROM consumptions 
              WHERE user_id = :uid";
              
    $stmt = $db->prepare($query);
    $stmt->bindParam(':uid', $user_id);
    $stmt->execute();
    $stats = $stmt->fetch();

    // 2. Najdeme nejoblíbenější pivo
    $query_fav = "SELECT beers.name, SUM(consumptions.quantity) as qty
                  FROM consumptions 
                  JOIN beers ON consumptions.beer_id = beers.id
                  WHERE consumptions.user_id = :uid
                  GROUP BY consumptions.beer_id
                  ORDER BY qty DESC LIMIT 1";
                  
    $stmt_fav = $db->prepare($query_fav);
    $stmt_fav->bindParam(':uid', $user_id);
    $stmt_fav->execute();
    $fav = $stmt_fav->fetch();

    // 3. Pošleme data zpět do Vue
    echo json_encode([
        "status" => "success",
        "stats" => [
            "liters" => $stats['total_liters'] ? round($stats['total_liters'], 2) : 0,
            "spent" => $stats['total_spent'] ? (int)$stats['total_spent'] : 0,
            "count" => $stats['total_beers'] ? (int)$stats['total_beers'] : 0,
            "favorite" => $fav ? $fav['name'] : 'Zatím žádné'
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Chyba spojení s DB."]);
}
?>