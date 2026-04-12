<?php
// backend/api/stats.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../Database.php';

$database = new Database();
$db = $database->getConnection();

// Očekáváme, že nám Vue pošle ID přihlášeného uživatele v adrese (GET parametr)
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

if ($db && $user_id) {
    // 1. Spočítáme celkové sumy (Litrů, Peněz a Počtu kusů)
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

    // 2. Najdeme nejoblíbenější pivo (to, kterého vypil nejvíc kusů)
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

    // 3. Pošleme to krásně zabalené zpět do Vue
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
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Chybí ID uživatele nebo spojení s DB."]);
}