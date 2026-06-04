<?php
// backend/api/update_brewery.php
require_once '../core/ApiHandler.php';

$api = new ApiHandler();
$api->requireAdmin();

$id = $api->request->getIntParam('id');
$name = $api->request->getParam('name');

if (!$id || !$name) {
    $api->response->sendError("ID a Název jsou povinné.", 400);
}

try {
    $new_logo_filename = null;
    $upload_dir = '../uploads/logos/';
    
    $logoFile = $api->request->getFile('logoFile');

    if ($logoFile && $logoFile['error'] === UPLOAD_ERR_OK) {
        if ($logoFile['size'] > 5 * 1024 * 1024) {
            $api->response->sendError("Soubor loga je příliš velký.", 400);
        }

        $stmt_old = $api->db->prepare("SELECT logo FROM breweries WHERE id = ?");
        $stmt_old->execute([$id]);
        $oldBrewery = $stmt_old->fetch();
        
        $file_tmp = $logoFile['tmp_name'];
        $image_info = getimagesize($file_tmp);
        $allowed_types = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];
        
        if ($image_info && in_array($image_info[2], $allowed_types)) {
            $w = $image_info[0]; $h = $image_info[1];
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
                
                $new_logo_filename = uniqid('logo_') . '.webp';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                if (imagewebp($dst, $upload_dir . $new_logo_filename, 80)) {
                    if ($oldBrewery && $oldBrewery['logo'] && file_exists($upload_dir . $oldBrewery['logo'])) {
                        unlink($upload_dir . $oldBrewery['logo']);
                    }
                }
                
                imagedestroy($src); imagedestroy($dst);
            }
        }
    }

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

    if ($new_logo_filename) {
        $query = "UPDATE breweries SET name=?, city=?, country_id=?, address=?, zip_code=?, email=?, phone=?, website=?, lat=?, lng=?, opening_hours=?, logo=? WHERE id=?";
        $params = [$name, $city, $country_id, $address, $zip_code, $email, $phone, $website, $lat, $lng, $opening_hours, $new_logo_filename, $id];
    } else {
        $query = "UPDATE breweries SET name=?, city=?, country_id=?, address=?, zip_code=?, email=?, phone=?, website=?, lat=?, lng=?, opening_hours=? WHERE id=?";
        $params = [$name, $city, $country_id, $address, $zip_code, $email, $phone, $website, $lat, $lng, $opening_hours, $id];
    }

    $stmt = $api->db->prepare($query);
    if ($stmt->execute($params)) {
        $api->response->sendSuccess("Pivovar byl aktualizován.");
    } else {
        $api->response->sendError("Chyba při aktualizaci pivovaru.", 500);
    }
} catch (PDOException $e) {
    error_log("DB Error (update_brewery): " . $e->getMessage());
    $api->response->sendError("Vnitřní chyba databáze.", 500);
}
?>