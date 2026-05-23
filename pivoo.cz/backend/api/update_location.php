<?php
// backend/api/update_location.php
require_once '../core/ApiHandler.php';

$api = new ApiHandler();
$api->requireAdmin();

$id = $api->request->getIntParam('id');
$name = $api->request->getParam('name');

if (!$id || !$name) {
    $api->response->sendError("Chybí ID nebo název místa.", 400);
}

try {
    $query = "UPDATE locations 
              SET name = ?, type = ?, city = ?, country_id = ?, address = ?, zip_code = ?, email = ?, phone = ?, website = ?, lat = ?, lng = ?, opening_hours = ? 
              WHERE id = ?";
              
    $stmt = $api->db->prepare($query);
    
    $type = $api->request->getParam('type');
    $city = $api->request->getParam('city');
    $country_id = $api->request->getIntParam('country_id');
    $address = $api->request->getParam('address');
    $zip_code = $api->request->getParam('zip_code');
    $email = $api->request->getParam('email');
    $phone = $api->request->getParam('phone');
    $website = $api->request->getParam('website');
    $lat = $api->request->getFloatParam('lat');
    $lng = $api->request->getFloatParam('lng');
    $opening_hours = $api->request->getParam('opening_hours');

    if ($stmt->execute([
        $name, 
        $type, 
        $city, 
        $country_id, 
        $address, 
        $zip_code, 
        $email, 
        $phone, 
        $website, 
        $lat,
        $lng,
        $opening_hours,
        $id
    ])) {
        $api->response->sendSuccess("Místo bylo úspěšně aktualizováno.");
    } else {
        $api->response->sendError("Chyba při aktualizaci v DB.", 500);
    }
} catch (PDOException $e) {
    error_log("DB Error (update_location): " . $e->getMessage());
    $api->response->sendError("Vnitřní chyba databáze při aktualizaci lokace.", 500);
}
?>