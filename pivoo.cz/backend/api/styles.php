<?php
// backend/api/styles.php
require_once '../core/ApiHandler.php';

$api = new ApiHandler();

try {
    $stmt = $api->db->query("SELECT id, name FROM beer_styles ORDER BY name ASC");
    $styles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $api->response->sendSuccess("", ["data" => $styles]);
} catch (PDOException $e) {
    error_log("DB Error (styles): " . $e->getMessage());
    $api->response->sendError("Vnitřní chyba při načítání stylů.", 500);
}
?>