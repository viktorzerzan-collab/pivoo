<?php
// ZMĚNA: Skryto vypisování varování (v produkci nezapínat zobrazení chyb)
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// ZMĚNA: Omezení CORS
header("Access-Control-Allow-Origin: https://www.pivoo.cz");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../Database.php';
require_once '../JwtHandler.php';

// Pomocná funkce pro bezpečné získání IP adresy klienta
function getClientIp() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $data = json_decode(file_get_contents("php://input"));

    if (!isset($data->username) || !isset($data->password)) {
        echo json_encode(["status" => "error", "message" => "Zadejte jméno a heslo."]);
        exit();
    }

    $username = trim($data->username);
    $password = $data->password;
    $ip_address = getClientIp();

    // 1. KONTROLA BRUTE-FORCE ÚTOKŮ
    $check_stmt = $db->prepare("SELECT attempts, last_attempt FROM login_attempts WHERE ip_address = ?");
    $check_stmt->execute([$ip_address]);
    $attempt_data = $check_stmt->fetch();

    if ($attempt_data) {
        $attempts = (int)$attempt_data['attempts'];
        $last_attempt_time = strtotime($attempt_data['last_attempt']);
        $time_diff = time() - $last_attempt_time;

        // Pokud má uživatel z této IP adresy 5 a více pokusů a neuběhlo 15 minut (900 sekund)
        if ($attempts >= 5 && $time_diff < 900) {
            $minutes_left = ceil((900 - $time_diff) / 60);
            echo json_encode(["status" => "error", "message" => "Příliš mnoho neúspěšných pokusů. Zkuste to znovu za $minutes_left minut."]);
            exit();
        }
    }

    // 2. OVĚŘENÍ UŽIVATELE
    $query = "SELECT id, username, first_name, last_name, password_hash, role, avatar, theme_mode, theme_preference, is_banned 
              FROM users WHERE username = ? OR email = ? LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        
        // KONTROLA BANU
        if (isset($user['is_banned']) && $user['is_banned'] == 1) {
            echo json_encode(["status" => "error", "message" => "Váš účet byl zablokován administrátorem."]);
            exit();
        }

        // PŘIHLÁŠENÍ ÚSPĚŠNÉ -> Vymazání záznamů o neúspěšných pokusech pro tuto IP
        $clear_stmt = $db->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
        $clear_stmt->execute([$ip_address]);

        $userData = [
            "id" => $user['id'],
            "username" => $user['username'],
            "first_name" => $user['first_name'],
            "last_name" => $user['last_name'],
            "role" => $user['role'],
            "avatar" => $user['avatar'],
            "theme_mode" => $user['theme_mode'] ?? 'manual',
            "theme_preference" => $user['theme_preference'] ?? 'light'
        ];

        $token = JwtHandler::encode([
            "user_id" => $user['id'],
            "role" => $user['role']
        ]);

        echo json_encode([
            "status" => "success",
            "message" => "Přihlášení úspěšné.",
            "user" => $userData,
            "token" => $token
        ]);
    } else {
        // PŘIHLÁŠENÍ SELHALO -> Záznam pokusu do databáze
        if ($attempt_data) {
            $time_diff = time() - strtotime($attempt_data['last_attempt']);
            if ($time_diff >= 900) {
                // Pokud uběhlo víc než 15 minut od posledního trestu, resetujeme počítadlo na 1
                $fail_stmt = $db->prepare("UPDATE login_attempts SET attempts = 1, last_attempt = NOW() WHERE ip_address = ?");
                $fail_stmt->execute([$ip_address]);
            } else {
                // Jinak jen přičteme pokus
                $fail_stmt = $db->prepare("UPDATE login_attempts SET attempts = attempts + 1, last_attempt = NOW() WHERE ip_address = ?");
                $fail_stmt->execute([$ip_address]);
            }
        } else {
            // První neúspěšný pokus z této IP adresy vůbec
            $fail_stmt = $db->prepare("INSERT INTO login_attempts (ip_address, attempts, last_attempt) VALUES (?, 1, NOW())");
            $fail_stmt->execute([$ip_address]);
        }

        echo json_encode(["status" => "error", "message" => "Neplatné přihlašovací údaje."]);
    }
} catch (Throwable $e) {
    // ZMĚNA: Skrytí skutečné chyby
    error_log("DB Error (login): " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Vnitřní chyba serveru při přihlášení."]);
}
?>