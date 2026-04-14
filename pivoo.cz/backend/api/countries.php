<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../Database.php';

$db = (new Database())->getConnection();

if ($db) {
    $stmt = $db->query("SELECT id, code, name_cz FROM countries ORDER BY name_cz ASC");
    $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(["status" => "success", "data" => $countries]);
}
?>