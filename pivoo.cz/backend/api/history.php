<?php
// backend/api/history.php
require_once '../core/ApiHandler.php';

$api = new ApiHandler();
$user = JwtHandler::checkUser();
$user_id = $user['user_id']; 

try {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
    
    if ($page < 1) $page = 1;
    if ($limit < 1) $limit = 12;
    
    $offset = ($page - 1) * $limit;

    $countQuery = "SELECT COUNT(*) as total FROM consumptions WHERE user_id = :uid";
    $countStmt = $api->db->prepare($countQuery);
    $countStmt->bindParam(':uid', $user_id, PDO::PARAM_INT);
    $countStmt->execute();
    $totalRow = $countStmt->fetch(PDO::FETCH_ASSOC);
    $totalRecords = (int)$totalRow['total'];
    
    $totalPages = ceil($totalRecords / $limit);

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
              
    $stmt = $api->db->prepare($query);
    $stmt->bindParam(':uid', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $api->response->sendSuccess("", [
        "data" => $history,
        "pagination" => [
            "current_page" => $page,
            "total_pages" => $totalPages,
            "total_records" => $totalRecords
        ]
    ]);
} catch (PDOException $e) {
    error_log("DB Error (history): " . $e->getMessage());
    $api->response->sendError("Vnitřní chyba při načítání historie.", 500);
}
?>