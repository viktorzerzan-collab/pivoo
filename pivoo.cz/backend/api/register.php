<?php
// backend/api/register.php
header("Access-Control-Allow-Origin: *");
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
        $file_tmp = $_FILES['avatar']['tmp_name'];
        list($w, $h, $type) = getimagesize($file_tmp);
        
        $src = null;
        if($type == IMAGETYPE_JPEG) $src = imagecreatefromjpeg($file_tmp);
        elseif($type == IMAGETYPE_PNG) $src = imagecreatefrompng($file_tmp);
        elseif($type == IMAGETYPE_WEBP) $src = imagecreatefromwebp($file_tmp);

        if ($src) {
            $target_size = 400;
            $min_side = min($w, $h);
            $dst = imagecreatetruecolor($target_size, $target_size);
            imagecopyresampled($dst, $src, 0, 0, ($w-$min_side)/2, ($h-$min_side)/2, $target_size, $target_size, $min_side, $min_side);
            
            $avatar_filename = uniqid('avatar_') . '.jpg';
            $upload_dir = '../uploads/avatars/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            imagejpeg($dst, $upload_dir . $avatar_filename, 85);
            imagedestroy($src); imagedestroy($dst);
        }
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    // Přidáno nastavení výchozího tématu do SQL INSERTu
    $query = "INSERT INTO users (username, first_name, last_name, email, birthdate, password_hash, role, avatar, theme_mode, theme_preference) 
              VALUES (?, ?, ?, ?, ?, ?, 'user', ?, 'manual', 'light')";
    $insert = $db_connection->prepare($query);
    $insert->execute([$username, $first_name, $last_name, $email, $birthdate, $password_hash, $avatar_filename]);

    echo json_encode(["status" => "success", "message" => "Registrace úspěšná."]);

} catch (Throwable $e) {
    echo json_encode(["status" => "error", "message" => "Chyba serveru."]);
}
?>