<?php
// backend/api/login.php
require_once '../core/ApiHandler.php';

function getClientIp() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

$api = new ApiHandler();

try {
    $username = $api->request->getParam('username');
    $password = $api->request->getParam('password');

    if (!$username || !$password) {
        $api->response->sendError("Zadejte jméno a heslo.", 400);
    }

    $username = trim($username);
    $ip_address = getClientIp();

    // 1. KONTROLA BRUTE-FORCE ÚTOKŮ
    $check_stmt = $api->db->prepare("SELECT attempts, last_attempt FROM login_attempts WHERE ip_address = ?");
    $check_stmt->execute([$ip_address]);
    $attempt_data = $check_stmt->fetch();

    if ($attempt_data) {
        $attempts = (int)$attempt_data['attempts'];
        $last_attempt_time = strtotime($attempt_data['last_attempt']);
        $time_diff = time() - $last_attempt_time;

        if ($attempts >= 5 && $time_diff < 900) {
            $minutes_left = ceil((900 - $time_diff) / 60);
            $api->response->sendError("Příliš mnoho neúspěšných pokusů. Zkuste to znovu za $minutes_left minut.", 429);
        }
    }

    // 2. OVĚŘENÍ UŽIVATELE
    $query = "SELECT id, username, first_name, last_name, password_hash, role, avatar, theme_mode, theme_preference, is_banned, default_currency 
              FROM users WHERE username = ? OR email = ? LIMIT 1";
    $stmt = $api->db->prepare($query);
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        
        // KONTROLA BANU
        if (isset($user['is_banned']) && $user['is_banned'] == 1) {
            $api->response->sendError("Váš účet byl zablokován administrátorem.", 403);
        }

        // Vymazání záznamů o neúspěšných pokusech pro tuto IP
        $clear_stmt = $api->db->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
        $clear_stmt->execute([$ip_address]);

        $userData = [
            "id" => $user['id'],
            "username" => $user['username'],
            "first_name" => $user['first_name'],
            "last_name" => $user['last_name'],
            "role" => $user['role'],
            "avatar" => $user['avatar'],
            "theme_mode" => $user['theme_mode'] ?? 'manual',
            "theme_preference" => $user['theme_preference'] ?? 'light',
            "default_currency" => $user['default_currency'] ?? 'CZK'
        ];

        $token = JwtHandler::encode([
            "user_id" => $user['id'],
            "role" => $user['role']
        ]);

        $api->response->sendSuccess("Přihlášení úspěšné.", [
            "user" => $userData,
            "token" => $token
        ]);
    } else {
        // PŘIHLÁŠENÍ SELHALO
        if ($attempt_data) {
            $time_diff = time() - strtotime($attempt_data['last_attempt']);
            if ($time_diff >= 900) {
                $fail_stmt = $api->db->prepare("UPDATE login_attempts SET attempts = 1, last_attempt = NOW() WHERE ip_address = ?");
                $fail_stmt->execute([$ip_address]);
            } else {
                $fail_stmt = $api->db->prepare("UPDATE login_attempts SET attempts = attempts + 1, last_attempt = NOW() WHERE ip_address = ?");
                $fail_stmt->execute([$ip_address]);
            }
        } else {
            $fail_stmt = $api->db->prepare("INSERT INTO login_attempts (ip_address, attempts, last_attempt) VALUES (?, 1, NOW())");
            $fail_stmt->execute([$ip_address]);
        }

        $api->response->sendError("Neplatné přihlašovací údaje.", 401);
    }
} catch (Throwable $e) {
    error_log("DB Error (login): " . $e->getMessage());
    $api->response->sendError("Vnitřní chyba serveru při přihlášení.", 500);
}
?>