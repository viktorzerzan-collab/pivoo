<?php
// backend/api/add_style.php
require_once '../core/ApiHandler.php';

// Inicializace jádra a ověření administrátora (hlavičky a spojení s DB se vyřeší automaticky)
$api = new ApiHandler();
$api->requireAdmin();

// Načtení povinného parametru přes bezpečný Request Wrapper
$name = $api->request->getParam('name');

if (!$name) {
    $api->response->sendError("Název stylu je povinný.", 400);
}

try {
    $stmt = $api->db->prepare("INSERT INTO beer_styles (name) VALUES (?)");
    $stmt->execute([$name]);
    $api->response->sendSuccess("Pivní styl byl úspěšně přidán.");
} catch (PDOException $e) {
    // Logujeme skutečnou chybu, uživateli necháváme původní zprávu
    error_log("DB Error (add_style): " . $e->getMessage());
    $api->response->sendError("Tento styl již v databázi pravděpodobně existuje.", 500);
}
?>