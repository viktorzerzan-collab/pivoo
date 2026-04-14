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
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Chybí autorizační token."]);
            exit();
        }

        $decoded = self::decode($token);
        if (!$decoded || $decoded['role'] !== 'admin') {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Neautorizovaný přístup. Pouze pro administrátory."]);
            exit();
        }

        // Dodatečná kontrola BANu přímo v databázi pro zajištění okamžitého odhlášení
        self::verifyUserStatus($decoded['user_id']);

        return $decoded;
    }

    // BEZPEČNOSTNÍ BRÁNA PRO BĚŽNÉHO UŽIVATELE
    public static function checkUser() {
        $token = self::getBearerToken();
        if (!$token) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Chybí autorizační token. Přihlaste se."]);
            exit();
        }

        $decoded = self::decode($token);
        if (!$decoded) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Neplatný nebo expirovaný token. Zkuste se přihlásit znovu."]);
            exit();
        }

        // Dodatečná kontrola BANu přímo v databázi pro zajištění okamžitého odhlášení
        self::verifyUserStatus($decoded['user_id']);

        return $decoded;
    }

    // Pomocná metoda pro ověření stavu účtu v DB (zda nedostal BAN)
    private static function verifyUserStatus($userId) {
        $db = (new Database())->getConnection();
        if ($db) {
            $stmt = $db->prepare("SELECT is_banned FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if ($user && $user['is_banned'] == 1) {
                // Kód 403 Forbidden se odešle na frontend
                http_response_code(403);
                echo json_encode(["status" => "error", "message" => "Váš účet byl zablokován administrátorem."]);
                exit();
            }
        }
    }
}
?>