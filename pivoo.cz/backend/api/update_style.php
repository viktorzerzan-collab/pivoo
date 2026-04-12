<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../Database.php';

$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id) && !empty($data->name)) {
    try {
        $stmt = $db->prepare("UPDATE beer_styles SET name = ? WHERE id = ?");
        if ($stmt->execute([$data->name, $data->id])) {
            echo json_encode(["status" => "success", "message" => "Pivní styl byl upraven."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Chyba při ukládání úpravy."]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Název už existuje nebo nastala chyba."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "ID a Název jsou povinné."]);
}
?>