<?php
// backend/api/update_avatar.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once '../Database.php';
require_once '../JwtHandler.php';

try {
    $user = JwtHandler::checkUser();
    $db = (new Database())->getConnection();

    $action = $_POST['action'] ?? 'upload';
    $upload_dir = '../uploads/avatars/';

    // 1. Zjistíme současný název souboru z databáze
    $stmt = $db->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$user['user_id']]);
    $currentUser = $stmt->fetch();
    $old_avatar = $currentUser ? $currentUser['avatar'] : null;

    // --- AKCE: ODEBRAT FOTKU ---
    if ($action === 'remove') {
        if ($old_avatar && file_exists($upload_dir . $old_avatar)) {
            unlink($upload_dir . $old_avatar); // Smazání z disku
        }
        $db->prepare("UPDATE users SET avatar = NULL WHERE id = ?")->execute([$user['user_id']]);
        echo json_encode(["status" => "success", "message" => "Fotka byla odebrána.", "avatar" => null]);
        exit();
    }

    // --- AKCE: NAHRÁT A NAHRADIT FOTKU ---
    if ($action === 'upload' && isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['avatar']['tmp_name'];
        $image_info = getimagesize($file_tmp);
        if ($image_info === false) throw new Exception("Neplatný obrázek.");

        // Zpracování (zmenšení a ořez na 400x400)
        $src = null;
        $type = $image_info[2];
        if($type === IMAGETYPE_JPEG) $src = imagecreatefromjpeg($file_tmp);
        elseif($type === IMAGETYPE_PNG) $src = imagecreatefrompng($file_tmp);
        elseif($type === IMAGETYPE_WEBP) $src = imagecreatefromwebp($file_tmp);

        if (!$src) throw new Exception("Nepodporovaný formát.");

        $target_size = 400;
        $w = $image_info[0]; $h = $image_info[1];
        $min_side = min($w, $h);
        $dst = imagecreatetruecolor($target_size, $target_size);
        
        // Zachování průhlednosti pro WebP (pokud by někdo nahrál PNG s průhledností)
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        
        imagecopyresampled($dst, $src, 0, 0, ($w-$min_side)/2, ($h-$min_side)/2, $target_size, $target_size, $min_side, $min_side);

        // Generujeme název s příponou .webp
        $new_filename = uniqid('avatar_') . '.webp';
        
        // Uložení ve formátu WebP (kvalita 80 je ideální poměr mezi velikostí a kvalitou)
        if (imagewebp($dst, $upload_dir . $new_filename, 80)) {
            
            // Smazání starého souboru z disku, pokud existuje
            if ($old_avatar && file_exists($upload_dir . $old_avatar)) {
                unlink($upload_dir . $old_avatar);
            }
            
            $db->prepare("UPDATE users SET avatar = ? WHERE id = ?")->execute([$new_filename, $user['user_id']]);

            echo json_encode(["status" => "success", "message" => "Fotka aktualizována.", "avatar" => $new_filename]);
        } else {
            throw new Exception("Chyba při ukládání souboru.");
        }
        imagedestroy($src); imagedestroy($dst);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>