<?php
// ZMĚNA: Omezení CORS
header("Access-Control-Allow-Origin: https://www.pivoo.cz");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once '../Database.php'; 
$database = new Database();
$db_connection = $database->getConnection();

try {
    $username = trim($_POST['username'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';

    if (empty($username) || empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($birthdate)) {
        echo json_encode(["status" => "error", "message" => "Vyplňte všechny povinné údaje."]);
        exit();
    }

    $bday = new DateTime($birthdate);
    $today = new DateTime('today');
    if ($bday->diff($today)->y < 18) {
        echo json_encode(["status" => "error", "message" => "Musí vám být 18+."]);
        exit();
    }

    $avatar_filename = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        
        // ZMĚNA: Backendová validace velikosti souboru (max 5 MB)
        if ($_FILES['avatar']['size'] > 5 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Soubor profilové fotky je příliš velký. Maximum je 5 MB."]);
            exit();
        }

        $file_tmp = $_FILES['avatar']['tmp_name'];
        $image_info = getimagesize($file_tmp);
        
        if ($image_info) {
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
                
                // Zachování průhlednosti pro WebP
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                
                imagecopyresampled($dst, $src, 0, 0, ($w-$min_side)/2, ($h-$min_side)/2, $target_size, $target_size, $min_side, $min_side);
                
                // Změna přípony na .webp
                $avatar_filename = uniqid('avatar_') . '.webp';
                $upload_dir = '../uploads/avatars/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                // Uložení jako WebP s kvalitou 80
                imagewebp($dst, $upload_dir . $avatar_filename, 80);
                
                imagedestroy($src); 
                imagedestroy($dst);
            }
        }
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $query = "INSERT INTO users (username, first_name, last_name, email, birthdate, password_hash, role, avatar, theme_mode, theme_preference) 
              VALUES (?, ?, ?, ?, ?, ?, 'user', ?, 'manual', 'light')";
    $insert = $db_connection->prepare($query);
    $insert->execute([$username, $first_name, $last_name, $email, $birthdate, $password_hash, $avatar_filename]);

    echo json_encode(["status" => "success", "message" => "Registrace úspěšná."]);

} catch (Throwable $e) {
    // ZMĚNA: Zalognutí chyb a zobrazení bezpečné zprávy pro případ duplicitních mailů atd.
    error_log("DB Error (register): " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Chyba při registraci (uživatelské jméno nebo e-mail už možná existuje)."]);
}
?>