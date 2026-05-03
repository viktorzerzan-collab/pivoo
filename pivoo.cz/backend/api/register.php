<?php
// ZMĚNA: Omezení CORS na konkrétní doménu pro vyšší bezpečnost
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
    
    // Přidáno zpracování výchozí měny s fallbackem na CZK
    $default_currency = strtoupper(trim($_POST['default_currency'] ?? 'CZK'));
    $allowed_currencies = ['CZK', 'EUR', 'PLN', 'GBP'];
    
    if (!in_array($default_currency, $allowed_currencies)) {
        $default_currency = 'CZK';
    }

    if (empty($username) || empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($birthdate)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Vyplňte všechny povinné údaje."]);
        exit();
    }

    // Validace formátu e-mailu
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Neplatný formát e-mailové adresy."]);
        exit();
    }

    // Validace síly hesla (min 8 znaků, číslo, speciální znak)
    if (strlen($password) < 8 || !preg_match('/[0-9]/', $password) || !preg_match('/[^a-zA-Z0-9]/', $password)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Heslo musí mít alespoň 8 znaků, obsahovat číslo a speciální znak."]);
        exit();
    }

    // Bezpečné parsování data narození a kontrola věku
    try {
        $bday = new DateTime($birthdate);
        $today = new DateTime('today');
        if ($bday->diff($today)->y < 18) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Musí vám být 18+."]);
            exit();
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Neplatný formát data narození."]);
        exit();
    }

    $avatar_filename = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        
        // Validace velikosti souboru (max 5 MB)
        if ($_FILES['avatar']['size'] > 5 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Soubor profilové fotky je příliš velký."]);
            exit();
        }

        $file_tmp = $_FILES['avatar']['tmp_name'];
        $image_info = getimagesize($file_tmp);
        
        // Zabezpečení: Kontrola reálného MIME typu a whitelist přípon
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
                
                // Zachování průhlednosti
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                
                imagecopyresampled($dst, $src, 0, 0, ($w-$min_side)/2, ($h-$min_side)/2, $target_size, $target_size, $min_side, $min_side);
                
                // Generování unikátního názvu a vynucení přípony .webp (překódování odstraní skrytý kód)
                $avatar_filename = uniqid('avatar_') . '.webp';
                $upload_dir = '../uploads/avatars/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                imagewebp($dst, $upload_dir . $avatar_filename, 80);
                
                imagedestroy($src); 
                imagedestroy($dst);
            } else {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Nepodařilo se zpracovat obrázek."]);
                exit();
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Nepodporovaný formát obrázku (pouze JPG, PNG, WEBP)."]);
            exit();
        }
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Kontrola duplicity uživatele/emailu předem (předchází úniku informací z PDOException)
    $check_stmt = $db_connection->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $check_stmt->execute([$username, $email]);
    if ($check_stmt->fetch()) {
        http_response_code(409);
        echo json_encode(["status" => "error", "message" => "Uživatelské jméno nebo e-mail již existuje."]);
        exit();
    }

    // Upravený dotaz pro vložení default_currency
    $query = "INSERT INTO users (username, first_name, last_name, email, birthdate, password_hash, role, avatar, theme_mode, theme_preference, default_currency) 
              VALUES (?, ?, ?, ?, ?, ?, 'user', ?, 'manual', 'light', ?)";
    $insert = $db_connection->prepare($query);
    $insert->execute([$username, $first_name, $last_name, $email, $birthdate, $password_hash, $avatar_filename, $default_currency]);

    echo json_encode(["status" => "success", "message" => "Registrace úspěšná."]);

} catch (Throwable $e) {
    error_log("Registration Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Omlouváme se, při registraci nastala chyba."]);
}
?>