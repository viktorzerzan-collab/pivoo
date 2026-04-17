<?php
// ZMĚNA: Omezení CORS
header("Access-Control-Allow-Origin: https://www.pivoo.cz");
header("Content-Type: application/json; charset=UTF-8");
require_once '../Database.php';

$db = (new Database())->getConnection();

if ($db) {
    // ZMĚNA: Přidán try-catch blok
    try {
        $stmt = $db->query("SELECT id, code, name_cz FROM countries ORDER BY name_cz ASC");
        $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["status" => "success", "data" => $countries]);
    } catch (PDOException $e) {
        error_log("DB Error (countries): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Vnitřní chyba při načítání zemí."]);
    }
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Chyba databáze."]);
}
?>