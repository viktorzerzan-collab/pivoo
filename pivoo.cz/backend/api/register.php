<?php
// backend/api/register.php
require_once '../core/ApiHandler.php';

$api = new ApiHandler();

try {
    $username = trim($api->request->getParam('username', ''));
    $first_name = trim($api->request->getParam('first_name', ''));
    $last_name = trim($api->request->getParam('last_name', ''));
    $email = trim($api->request->getParam('email', ''));
    $password = $api->request->getParam('password', '');
    $birthdate = $api->request->getParam('birthdate', '');
    
    $default_currency = strtoupper(trim($api->request->getParam('default_currency', 'CZK')));
    $allowed_currencies = ['CZK', 'EUR', 'PLN', 'GBP'];
    if (!in_array($default_currency, $allowed_currencies)) {
        $default_currency = 'CZK';
    }

    if (empty($username) || empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($birthdate)) {
        $api->response->sendError("Vyplňte všechny povinné údaje.", 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $api->response->sendError("Neplatný formát e-mailové adresy.", 400);
    }

    if (strlen($password) < 8 || !preg_match('/[0-9]/', $password) || !preg_match('/[^a-zA-Z0-9]/', $password)) {
        $api->response->sendError("Heslo musí mít alespoň 8 znaků, obsahovat číslo a speciální znak.", 400);
    }

    try {
        $bday = new DateTime($birthdate);
        $today = new DateTime('today');
        if ($bday->diff($today)->y < 18) {
            $api->response->sendError("Musí vám být 18+.", 400);
        }
    } catch (Exception $e) {
        $api->response->sendError("Neplatný formát data narození.", 400);
    }

    $avatar_filename = null;
    $avatarFile = $api->request->getFile('avatar');
    
    if ($avatarFile && $avatarFile['error'] === UPLOAD_ERR_OK) {
        if ($avatarFile['size'] > 5 * 1024 * 1024) {
            $api->response->sendError("Soubor profilové fotky je příliš velký.", 400);
        }

        $file_tmp = $avatarFile['tmp_name'];
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
                
                imagecopyresampled($dst, $src, 0, 0, ($w-$min_side)/2, ($h-$min_side)/2, $target_size, $target_size, $min_side, $min_side);
                
                $avatar_filename = uniqid('avatar_') . '.webp';
                $upload_dir = '../uploads/avatars/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                imagewebp($dst, $upload_dir . $avatar_filename, 80);
                
                imagedestroy($src); 
                imagedestroy($dst);
            } else {
                $api->response->sendError("Nepodařilo se zpracovat obrázek.", 400);
            }
        } else {
            $api->response->sendError("Nepodporovaný formát obrázku (pouze JPG, PNG, WEBP).", 400);
        }
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    $check_stmt = $api->db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $check_stmt->execute([$username, $email]);
    if ($check_stmt->fetch()) {
        $api->response->sendError("Uživatelské jméno nebo e-mail již existuje.", 409);
    }

    $query = "INSERT INTO users (username, first_name, last_name, email, birthdate, password_hash, role, avatar, theme_mode, theme_preference, default_currency) 
              VALUES (?, ?, ?, ?, ?, ?, 'user', ?, 'manual', 'light', ?)";
    $insert = $api->db->prepare($query);
    $insert->execute([$username, $first_name, $last_name, $email, $birthdate, $password_hash, $avatar_filename, $default_currency]);

    $api->response->sendSuccess("Registrace úspěšná.");

} catch (Throwable $e) {
    error_log("Registration Error: " . $e->getMessage());
    $api->response->sendError("Omlouváme se, při registraci nastala chyba.", 500);
}
?>