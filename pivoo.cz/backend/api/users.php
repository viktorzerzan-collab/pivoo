<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../Database.php';

$database = new Database();
$db = $database->getConnection();

if ($db) {
    try {
        // Vybereme základní údaje o všech uživatelích
        $query = "SELECT id, username, first_name, last_name, email, role FROM users ORDER BY id DESC";
        $stmt = $db->query($query);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(["status" => "success", "data" => $users]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Chyba při načítání uživatelů."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Chyba DB."]);
}