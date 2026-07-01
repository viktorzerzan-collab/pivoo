<?php
// backend/api/add_brewery.php
require_once '../core/ApiHandler.php';

// Inicializace
$api = new ApiHandler();
$user = $api->requireAdmin();

$name = $api->request->getParam('name');

if (!$name) {
    $api->response->sendError("Název pivovaru je povinný.", 400);
}

try {
    $logo_filename = null;
    $logoFile = $api->request->getFile('logoFile');

    if ($logoFile && $logoFile['error'] === UPLOAD_ERR_OK) {
        if ($logoFile['size'] > 5 * 1024 * 1024) {
            $api->response->sendError("Soubor loga je příliš velký. Maximum je 5 MB.", 400);
        }

        $file_tmp = $logoFile['tmp_name'];
        $image_info = getimagesize($file_tmp);
        
        $allowed_types = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];
        
        if ($image_info && in_array($image_info[2], $allowed_types)) {
            $w = $image_info[0]; 
            $h = $image_info[1];
            $type = $image_info[2];
            $src = null;

            if($type == IMAGETYPE_JPEG) $src = imagecreatefromjpeg($file_tmp);
            elseif($type == IMAGETYPE_PNG) $src = imagecreatefrompng($file_tmp);
            elseif($type == IMAGETYPE_WEBP) $src = imagecreatefromwebp($file_tmp);

            if ($src) {
                $target_size = 400;
                $min_side = min($w, $h);
                $dst = imagecreatetruecolor($target_size, $target_size);
                
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                
                $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
                imagefilledrectangle($dst, 0, 0, $target_size, $target_size, $transparent);

                imagecopyresampled($dst, $src, 0, 0, ($w-$min_side)/2, ($h-$min_side)/2, $target_size, $target_size, $min_side, $min_side);
                
                $logo_filename = uniqid('logo_') . '.webp';
                $upload_dir = '../uploads/logos/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                imagewebp($dst, $upload_dir . $logo_filename, 80);
                
                imagedestroy($src); 
                imagedestroy($dst);
            }
        } else {
            $api->response->sendError("Nepodporovaný formát obrázku.", 400);
        }
    }

    $query = "INSERT INTO breweries (name, city, country_id, address, zip_code, email, phone, website, lat, lng, opening_hours, is_approved, logo, created_by) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)";
    $stmt = $api->db->prepare($query);
    
    // Čistší načtení parametrů díky request handleru
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

    if ($stmt->execute([$name, $city, $country_id, $address, $zip_code, $email, $phone, $website, $lat, $lng, $opening_hours, $logo_filename, $user['user_id']])) {
        $new_id = $api->db->lastInsertId();
        $api->response->sendSuccess("Pivovar byl úspěšně přidán do katalogu.", ["id" => $new_id]);
    } else {
        $api->response->sendError("Pivovar se nepodařilo uložit.", 500);
    }
} catch (PDOException $e) {
    error_log("DB Error (add_brewery): " . $e->getMessage());
    $api->response->sendError("Vnitřní chyba databáze při ukládání pivovaru.", 500);
}
?>