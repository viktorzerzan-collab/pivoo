<?php
// backend/api/test.php

// Nastavíme, že náš skript vrací data ve formátu JSON (aby tomu Vue pak rozumělo)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../Database.php';

$database = new Database();
$db = $database->getConnection();

if ($db) {
    // Zkusíme vytáhnout všechny uživatele (zatím tam nikdo není, ale dotaz proběhne)
    $stmt = $db->query("SELECT * FROM users");
    $users = $stmt->fetchAll();

    echo json_encode([
        "status" => "success",
        "message" => "Aplikace ožívá! Databáze je úspěšně připojena. 🍻",
        "data" => $users
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Sakra, někde se stala chyba. Databáze neodpovídá."
    ]);
}