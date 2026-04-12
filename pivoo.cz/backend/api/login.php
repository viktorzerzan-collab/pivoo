<?php
// Zapnutí chyb pro ladění
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../Database.php';

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

    // Najdeme uživatele podle jména nebo e-mailu
    $query = "SELECT id, username, first_name, last_name, password_hash, role FROM users WHERE username = ? OR email = ? LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Heslo sedí - vytvoříme pole s daty pro frontend (bez hashe!)
        $userData = [
            "id" => $user['id'],
            "username" => $user['username'],
            "first_name" => $user['first_name'],
            "last_name" => $user['last_name'],
            "role" => $user['role']
        ];

        echo json_encode([
            "status" => "success",
            "message" => "Přihlášení úspěšné.",
            "user" => $userData
        ]);
    } else {
        // Buď uživatel neexistuje, nebo nesedí heslo (pro bezpečnost neříkáme co přesně)
        echo json_encode(["status" => "error", "message" => "Neplatné přihlašovací údaje."]);
    }

} catch (Throwable $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Chyba serveru: " . $e->getMessage()
    ]);
}