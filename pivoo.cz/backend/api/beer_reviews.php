<?php
// backend/api/beer_reviews.php
require_once '../core/ApiHandler.php';

$api = new ApiHandler();
$beer_id = isset($_GET['beer_id']) ? $_GET['beer_id'] : null;

if ($beer_id) {
    try {
        $query = "SELECT c.id, c.rating_beer, c.consumed_at, c.note, l.name as location_name, u.username as user_name, u.avatar
                  FROM consumptions c
                  LEFT JOIN locations l ON c.location_id = l.id
                  LEFT JOIN users u ON c.user_id = u.id
                  WHERE c.beer_id = :bid AND (c.rating_beer > 0 OR c.note IS NOT NULL)
                  ORDER BY c.consumed_at DESC
                  LIMIT 15";
                  
        $stmt = $api->db->prepare($query);
        $stmt->bindParam(':bid', $beer_id);
        $stmt->execute();
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($reviews)) {
            $ids = array_column($reviews, 'id');
            $in = str_repeat('?,', count($ids) - 1) . '?';
            $photoStmt = $api->db->prepare("SELECT consumption_id, id, filename FROM consumption_photos WHERE consumption_id IN ($in)");
            $photoStmt->execute($ids);
            $photos = $photoStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $photosGrouped = [];
            foreach ($photos as $p) {
                $photosGrouped[$p['consumption_id']][] = ['id' => $p['id'], 'filename' => $p['filename']];
            }
            
            foreach ($reviews as &$r) {
                $r['photos'] = isset($photosGrouped[$r['id']]) ? $photosGrouped[$r['id']] : [];
            }
        }
        
        $api->response->sendSuccess("", ["data" => $reviews]);
    } catch (PDOException $e) {
        error_log("DB Error (beer_reviews): " . $e->getMessage());
        $api->response->sendError("Vnitřní chyba při načítání recenzí.", 500);
    }
} else {
    $api->response->sendError("Chybí ID piva.", 400);
}
?>