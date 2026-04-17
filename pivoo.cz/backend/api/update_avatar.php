<?php
// backend/api/update_avatar.php
header("Access-Control-Allow-Origin: https://www.pivoo.cz");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once '../Database.php';
require_once '../JwtHandler.php';

try {
    // Ověření uživatele přes JWT
    $user = JwtHandler::checkUser();
    $db = (new Database())->getConnection();

    $action = $_POST['action'] ?? 'upload';
    $upload_dir = '../uploads/avatars/';

    // Zjištění současné fotky pro případné smazání
    $stmt = $db->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$user['user_id']]);
    $currentUser = $stmt->fetch();
    $old_avatar = $currentUser ? $currentUser['avatar'] : null;

    // --- AKCE: ODEBRAT FOTKU ---
    if ($action === 'remove') {
        if ($old_avatar && file_exists($upload_dir . $old_avatar)) {
            unlink($upload_dir . $old_avatar);
        }
        $db->prepare("UPDATE users SET avatar = NULL WHERE id = ?")->execute([$user['user_id']]);
        echo json_encode(["status" => "success", "message" => "Fotka byla odebrána.", "avatar" => null]);
        exit();
    }

    // --- AKCE: NAHRÁT A NAHRADIT FOTKU ---
    if ($action === 'upload' && isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        
        // Validace velikosti (max 5 MB)
        if ($_FILES['avatar']['size'] > 5 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Soubor je příliš velký. Maximum je 5 MB."]);
            exit();
        }

        $file_tmp = $_FILES['avatar']['tmp_name'];
        $image_info = getimagesize($file_tmp);
        
        // Whitelist povolených MIME typů
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
                
                // Zachování průhlednosti pro WebP/PNG
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                
                imagecopyresampled($dst, $src, 0, 0, ($w-$min_side)/2, ($h-$min_side)/2, $target_size, $target_size, $min_side, $min_side);

                // Generování unikátního názvu a vynucení bezpečné přípony .webp
                $new_filename = uniqid('avatar_') . '.webp';
                
                if (imagewebp($dst, $upload_dir . $new_filename, 80)) {
                    // Smazání starého souboru
                    if ($old_avatar && file_exists($upload_dir . $old_avatar)) {
                        unlink($upload_dir . $old_avatar);
                    }
                    
                    $db->prepare("UPDATE users SET avatar = ? WHERE id = ?")->execute([$new_filename, $user['user_id']]);

                    echo json_encode(["status" => "success", "message" => "Fotka aktualizována.", "avatar" => $new_filename]);
                } else {
                    throw new Exception("Selhalo uložení obrázku.");
                }
                imagedestroy($src); imagedestroy($dst);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Nepodporovaný formát obrázku (JPG, PNG, WEBP)."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Žádný soubor nebyl nahrán nebo nastala chyba přenosu."]);
    }
} catch (Exception $e) {
    error_log("Avatar Upload Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Při zpracování fotky nastala chyba."]);
}
?>