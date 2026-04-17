<?php
// backend/JwtHandler.php

// Načteme bezpečný konfigurační soubor, který ignoruje Git
require_once 'config.php'; 
// Načteme Database.php pro dodatečné ověření BANu v DB
require_once 'Database.php';

class JwtHandler {

    // Zašifruje data do tokenu
    public static function encode($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        // Platnost tokenu je 24 hodin
        $payload['exp'] = time() + (24 * 60 * 60); 
        $payload = json_encode($payload);

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, SECRET_KEY, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    // Rozšifruje token a ověří, jestli ho nikdo nezměnil
    public static function decode($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return false;

        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $parts;

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, SECRET_KEY, true);
        $validSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        if (hash_equals($validSignature, $base64UrlSignature)) {
            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64UrlPayload)), true);
            // Kontrola, jestli token nevypršel
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return false; 
            }
            return $payload;
        }
        return false;
    }

    // Najde token v hlavičkách požadavku (ve "fetch" z Vue)
    public static function getBearerToken() {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { 
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    // HLAVNÍ BEZPEČNOSTNÍ BRÁNA PRO ADMINA
    public static function checkAdmin() {
        $token = self::getBearerToken();
        if (!$token) {
            self::sendError(401, "Chybí autorizační token.");
        }

        $decoded = self::decode($token);
        if (!$decoded || $decoded['role'] !== 'admin') {
            self::sendError(401, "Neautorizovaný přístup. Pouze pro administrátory.");
        }

        // Dodatečná kontrola BANu přímo v databázi pro zajištění okamžitého odhlášení
        self::verifyUserStatus($decoded['user_id']);

        return $decoded;
    }

    // BEZPEČNOSTNÍ BRÁNA PRO BĚŽNÉHO UŽIVATELE
    public static function checkUser() {
        $token = self::getBearerToken();
        if (!$token) {
            self::sendError(401, "Chybí autorizační token. Přihlaste se.");
        }

        $decoded = self::decode($token);
        if (!$decoded) {
            self::sendError(401, "Neplatný nebo expirovaný token. Zkuste se přihlásit znovu.");
        }

        // Dodatečná kontrola BANu přímo v databázi pro zajištění okamžitého odhlášení
        self::verifyUserStatus($decoded['user_id']);

        return $decoded;
    }

    // Pomocná metoda pro ověření stavu účtu v DB (zda nedostal BAN)
    private static function verifyUserStatus($userId) {
        try {
            $db = (new Database())->getConnection();
            if (!$db) {
                // Fail-closed: Pokud se nelze spojit s DB, zamítneme požadavek.
                self::sendError(500, "Nelze ověřit stav uživatele (chyba databáze).");
            }

            $stmt = $db->prepare("SELECT is_banned FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if ($user && $user['is_banned'] == 1) {
                self::sendError(403, "Váš účet byl zablokován administrátorem.");
            }
            
            if (!$user) {
                // Uživatel byl smazán z DB, ale token je stále platný
                self::sendError(401, "Tento uživatelský účet již neexistuje.");
            }
        } catch (Exception $e) {
            // Při jakékoliv chybě DB (např. spadení spojení během dotazu) zamítneme přístup
            self::sendError(500, "Kritická chyba při ověřování zabezpečení.");
        }
    }

    // Sjednocená metoda pro odesílání chyb s hlavičkami
    private static function sendError($code, $message) {
        header("Content-Type: application/json; charset=UTF-8");
        http_response_code($code);
        echo json_encode(["status" => "error", "message" => $message]);
        exit();
    }
}
?>