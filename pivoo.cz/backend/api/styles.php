<?php
// ZMĚNA: Omezení CORS
header("Access-Control-Allow-Origin: https://www.pivoo.cz");
header("Content-Type: application/json; charset=UTF-8");

require_once '../Database.php';

$database = new Database();
$db = $database->getConnection();

if ($db) {
    // ZMĚNA: Přidán try-catch blok pro bezpečné odchycení chyb
    try {
        $stmt = $db->query("SELECT id, name FROM beer_styles ORDER BY name ASC");
        $styles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["status" => "success", "data" => $styles]);
    } catch (PDOException $e) {
        error_log("DB Error (styles): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Vnitřní chyba při načítání stylů."]);
    }
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Chyba DB."]);
}
?>