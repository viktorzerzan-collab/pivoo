<?php
// backend/api/toggle_favorite.php
require_once '../core/ApiHandler.php';

$api = new ApiHandler();
$user = JwtHandler::checkUser();

$entity_id = $api->request->getIntParam('entity_id');
$entity_type = $api->request->getParam('entity_type');

if (!$entity_id || !$entity_type) {
    $api->response->sendError("Chybí parametry požadavku.", 400);
}

try {
    $query = "SELECT id FROM user_favorites WHERE user_id = ? AND entity_id = ? AND entity_type = ?";
    $stmt = $api->db->prepare($query);
    $stmt->execute([$user['user_id'], $entity_id, $entity_type]);
    $favorite = $stmt->fetch();

    if ($favorite) {
        $delete = $api->db->prepare("DELETE FROM user_favorites WHERE id = ?");
        $delete->execute([$favorite['id']]);
        $api->response->sendSuccess("Odebráno z oblíbených.", ["is_favorite" => false]);
    } else {
        $insert = $api->db->prepare("INSERT INTO user_favorites (user_id, entity_id, entity_type) VALUES (?, ?, ?)");
        $insert->execute([$user['user_id'], $entity_id, $entity_type]);
        $api->response->sendSuccess("Přidáno do oblíbených.", ["is_favorite" => true]);
    }
} catch (PDOException $e) {
    error_log("DB Error (toggle_favorite): " . $e->getMessage());
    $api->response->sendError("Vnitřní chyba při ukládání do oblíbených.", 500);
}
?>