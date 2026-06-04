<?php
// backend/api/countries.php
require_once '../core/ApiHandler.php';

$api = new ApiHandler();

try {
    $stmt = $api->db->query("SELECT id, code, name_cz FROM countries ORDER BY name_cz ASC");
    $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $api->response->sendSuccess("", ["data" => $countries]);
} catch (PDOException $e) {
    error_log("DB Error (countries): " . $e->getMessage());
    $api->response->sendError("Vnitřní chyba při načítání zemí.", 500);
}
?>