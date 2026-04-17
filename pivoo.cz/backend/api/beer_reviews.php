<?php
// ZMĚNA: Omezení CORS
header("Access-Control-Allow-Origin: https://www.pivoo.cz");
header("Content-Type: application/json; charset=UTF-8");
require_once '../Database.php';

$db = (new Database())->getConnection();
$beer_id = isset($_GET['beer_id']) ? $_GET['beer_id'] : null;

if ($db && $beer_id) {
    try {
        $query = "SELECT c.rating_beer, c.consumed_at, c.note, l.name as location_name, u.username as user_name
                  FROM consumptions c
                  LEFT JOIN locations l ON c.location_id = l.id
                  LEFT JOIN users u ON c.user_id = u.id
                  WHERE c.beer_id = :bid AND (c.rating_beer > 0 OR c.note IS NOT NULL)
                  ORDER BY c.consumed_at DESC
                  LIMIT 15";
                  
        $stmt = $db->prepare($query);
        $stmt->bindParam(':bid', $beer_id);
        $stmt->execute();
        
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        
    } catch (PDOException $e) {
        // ZMĚNA: Skrytí SQL chyby
        error_log("DB Error (beer_reviews): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Vnitřní chyba při načítání recenzí."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Chybí ID piva."]);
}
?>