<?php
// backend/api/update_avatar.php
require_once '../core/ApiHandler.php';

$api = new ApiHandler();
$user = JwtHandler::checkUser();

try {
    $action = $api->request->getParam('action', 'upload');
    $upload_dir = '../uploads/avatars/';

    $stmt = $api->db->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$user['user_id']]);
    $currentUser = $stmt->fetch();
    $old_avatar = $currentUser ? $currentUser['avatar'] : null;

    if ($action === 'remove') {
        if ($old_avatar && file_exists($upload_dir . $old_avatar)) {
            unlink($upload_dir . $old_avatar);
        }
        $api->db->prepare("UPDATE users SET avatar = NULL WHERE id = ?")->execute([$user['user_id']]);
        $api->response->sendSuccess("Fotka byla odebrána.", ["avatar" => null]);
    }

    if ($action === 'upload') {
        $avatarFile = $api->request->getFile('avatar');
        
        if ($avatarFile && $avatarFile['error'] === UPLOAD_ERR_OK) {
            if ($avatarFile['size'] > 5 * 1024 * 1024) {
                $api->response->sendError("Soubor je příliš velký. Maximum je 5 MB.", 400);
            }

            $file_tmp = $avatarFile['tmp_name'];
            $image_info = getimagesize($file_tmp);
            
            $allowed_types = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];

            if ($image_info && in_array($image_info[2], $allowed_types)) {
                $w = $image_info[0]; $h = $image_info[1];
                $type = $image_info[2];
                $src = null;

                if($type === IMAGETYPE_JPEG) $src = imagecreatefromjpeg($file_tmp);
                elseif($type === IMAGETYPE_PNG) $src = imagecreatefrompng($file_tmp);
                elseif($type === IMAGETYPE_WEBP) $src = imagecreatefromwebp($file_tmp);

                if ($src) {
                    $target_size = 400;
                    $min_side = min($w, $h);
                    $dst = imagecreatetruecolor($target_size, $target_size);
                    
                    imagealphablending($dst, false);
                    imagesavealpha($dst, true);
                    
                    imagecopyresampled($dst, $src, 0, 0, ($w-$min_side)/2, ($h-$min_side)/2, $target_size, $target_size, $min_side, $min_side);

                    $new_filename = uniqid('avatar_') . '.webp';
                    
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

                    if (imagewebp($dst, $upload_dir . $new_filename, 80)) {
                        if ($old_avatar && file_exists($upload_dir . $old_avatar)) {
                            unlink($upload_dir . $old_avatar);
                        }
                        
                        $api->db->prepare("UPDATE users SET avatar = ? WHERE id = ?")->execute([$new_filename, $user['user_id']]);

                        $api->response->sendSuccess("Fotka aktualizována.", ["avatar" => $new_filename]);
                    } else {
                        throw new Exception("Selhalo uložení obrázku.");
                    }
                    imagedestroy($src); imagedestroy($dst);
                }
            } else {
                $api->response->sendError("Nepodporovaný formát obrázku (JPG, PNG, WEBP).", 400);
            }
        } else {
            $api->response->sendError("Žádný soubor nebyl nahrán nebo nastala chyba přenosu.", 400);
        }
    }
} catch (Exception $e) {
    error_log("Avatar Upload Error: " . $e->getMessage());
    $api->response->sendError("Při zpracování fotky nastala chyba.", 500);
}
?>