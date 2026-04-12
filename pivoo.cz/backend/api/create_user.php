<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../Database.php';

$database = new Database();
$db = $database->getConnection();

if ($db) {
    // Bezpečné zahešování hesla "pivko123"
    $heslo = password_hash("pivko123", PASSWORD_BCRYPT);
    
    $query = "INSERT INTO users (username, first_name, last_name, email, password_hash, role) 
              VALUES ('beerlover69', 'Karel', 'Novák', 'karel@pivoo.cz', :heslo, 'user')";
              
    $stmt = $db->prepare($query);
    $stmt->bindParam(':heslo', $heslo);
    
    if($stmt->execute()) {
        echo json_encode(["message" => "Uživatel beerlover69 (Karel Novák) byl úspěšně vytvořen! Heslo je: pivko123"]);
    } else {
        echo json_encode(["message" => "Chyba při vytváření uživatele."]);
    }
}