<?php
// Zapnutí vypisování chyb pro odhalení jakéhokoliv problému
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Ošetření preflight dotazů (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 1. ZÁSADNÍ OPRAVA: Správné napojení na vaši OOP třídu Database
require_once '../Database.php'; 
$database = new Database();
$db_connection = $database->getConnection();

// Ochranný štít proti pádům PHP
try {
    $data = json_decode(file_get_contents("php://input"));

    // 2. Kontrola, zda dorazila všechna data z Vue formuláře
    if (!isset($data->username) || !isset($data->first_name) || !isset($data->last_name) || !isset($data->email) || !isset($data->password) || !isset($data->birthdate)) {
        echo json_encode(["status" => "error", "message" => "Vyplňte všechny povinné údaje."]);
        exit();
    }

    $username = trim($data->username);
    $first_name = trim($data->first_name);
    $last_name = trim($data->last_name);
    $email = trim($data->email);
    $password = $data->password;
    $birthdate = $data->birthdate;

    // 3. Kontrola věku (18+)
    $bday = new DateTime($birthdate);
    $today = new DateTime('today');
    $age = $bday->diff($today)->y;

    if ($age < 18) {
        echo json_encode(["status" => "error", "message" => "Přístup odepřen. Aplikace Pivoo.cz je určena pouze pro starší 18 let."]);
        exit();
    }

    // 4. Kontrola síly hesla
    if (strlen($password) < 8 || !preg_match('/[0-9]/', $password) || !preg_match('/[^a-zA-Z0-9]/', $password)) {
        echo json_encode(["status" => "error", "message" => "Heslo musí mít alespoň 8 znaků, obsahovat číslo a speciální znak."]);
        exit();
    }

    // 5. Kontrola unikátnosti E-MAILU a USERNAME přes vaši databázi
    $stmt = $db_connection->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    if ($stmt->fetch()) {
        echo json_encode(["status" => "error", "message" => "Tento e-mail nebo uživatelské jméno už v systému existuje."]);
        exit();
    }

    // 6. Bezpečné hashování hesla
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // 7. Uložení do databáze (přesně podle vašich sloupců z phpMyAdminu)
    $insert = $db_connection->prepare("INSERT INTO users (username, first_name, last_name, email, birthdate, password_hash, role) VALUES (?, ?, ?, ?, ?, ?, 'user')");
    $insert->execute([$username, $first_name, $last_name, $email, $birthdate, $password_hash]);

    echo json_encode(["status" => "success", "message" => "Registrace proběhla úspěšně! Nyní se můžete přihlásit."]);

// Throwable chytí úplně VŠECHNY chyby a vypíše je do konzole prohlížeče
} catch (Throwable $e) {
    echo json_encode([
        "status" => "error", 
        "message" => "Kritická chyba serveru: " . $e->getMessage() . " na řádku " . $e->getLine()
    ]);
}
?>