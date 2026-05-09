<?php
// ZMĚNA: Omezení CORS
header("Access-Control-Allow-Origin: https://www.pivoo.cz");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once '../Database.php';
require_once '../JwtHandler.php';

// ZABEZPEČENÍ: Vytáhneme uživatele z tokenu.
$user = JwtHandler::checkUser();
$user_id = $user['user_id']; 

$db = (new Database())->getConnection();

if ($db) {
    try {
        // --- PŘIDÁNO: Logika pro stránkování ---
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
        
        if ($page < 1) $page = 1;
        if ($limit < 1) $limit = 12;
        
        $offset = ($page - 1) * $limit;

        // --- PŘIDÁNO: Zjištění celkového počtu záznamů uživatele ---
        $countQuery = "SELECT COUNT(*) as total FROM consumptions WHERE user_id = :uid";
        $countStmt = $db->prepare($countQuery);
        $countStmt->bindParam(':uid', $user_id, PDO::PARAM_INT);
        $countStmt->execute();
        $totalRow = $countStmt->fetch(PDO::FETCH_ASSOC);
        $totalRecords = (int)$totalRow['total'];
        
        // Výpočet celkového počtu stránek
        $totalPages = ceil($totalRecords / $limit);

        // --- UPRAVENO: Hlavní dotaz nyní používá LIMIT a OFFSET ---
        $query = "SELECT c.*,
                         b.name as beer_name, 
                         br.name as brewery_name,
                         l.name as location_name
                  FROM consumptions c
                  JOIN beers b ON c.beer_id = b.id
                  JOIN breweries br ON b.brewery_id = br.id
                  JOIN locations l ON c.location_id = l.id
                  WHERE c.user_id = :uid
                  ORDER BY c.consumed_at DESC, c.id DESC
                  LIMIT :limit OFFSET :offset";
                  
        $stmt = $db->prepare($query);
        $stmt->bindParam(':uid', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // --- UPRAVENO: Odpověď nyní obsahuje objekt 'pagination' ---
        echo json_encode([
            "status" => "success", 
            "data" => $history,
            "pagination" => [
                "current_page" => $page,
                "total_pages" => $totalPages,
                "total_records" => $totalRecords
            ]
        ]);
    } catch (PDOException $e) {
        // Tiché logování chyby
        error_log("DB Error (history): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Vnitřní chyba při načítání historie."]);
    }
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Chyba spojení s DB."]);
}
?>