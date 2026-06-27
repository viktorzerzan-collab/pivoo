<?php
// backend/api/add_location.php
require_once '../core/ApiHandler.php';

// Inicializace
$api = new ApiHandler();

// ZMĚNA: Umožníme přidávat lokace všem přihlášeným uživatelům (nejen adminům)
$user = $api->requireUser(); 

$name = $api->request->getParam('name');
$type = $api->request->getParam('type');

if (!$name || !$type) {
    $api->response->sendError("Název a typ místa jsou povinné údaje.", 400);
}

// ZMĚNA: Admin schvaluje rovnou, pro běžného uživatele jde lokace do fronty ke schválení
$is_approved = (isset($user['role']) && $user['role'] === 'admin') ? 1 : 0;

try {
    // ZMĚNA: Místo natvrdo napsané 1 v SQL dotazu vložíme parametr pro is_approved
    $query = "INSERT INTO locations (name, type, city, country_id, address, zip_code, email, phone, website, lat, lng, opening_hours, is_approved, created_by) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $api->db->prepare($query);
    
    $city = $api->request->getParam('city');
    $country_id = $api->request->getIntParam('country_id', 1); 
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
        $is_approved, // Doplněná proměnná z logiky výše
        $user['user_id']
    ])) {
        $new_id = $api->db->lastInsertId();
        
        // ZMĚNA: Zpráva a návratová data odráží stav schválení
        $message = $is_approved ? "Nové místo bylo úspěšně přidáno." : "Místo bylo přidáno a čeká na schválení administrátorem.";
        
        $api->response->sendSuccess($message, [
            "id" => $new_id,
            "is_approved" => $is_approved,
            "lat" => $lat,
            "lng" => $lng
        ]);
    } else {
        $api->response->sendError("Nepodařilo se uložit místo.", 500);
    }
} catch (PDOException $e) {
    error_log("DB Error (add_location): " . $e->getMessage());
    $api->response->sendError("Vnitřní chyba databáze při ukládání lokace.", 500);
}
?>