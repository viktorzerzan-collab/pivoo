<?php
// backend/api/toggle_wishlist.php
require_once '../core/ApiHandler.php';

$api = new ApiHandler();
$user = JwtHandler::checkUser();

$entity_id = $api->request->getIntParam('entity_id');
$entity_type = $api->request->getParam('entity_type');

if (!$entity_id || !$entity_type) {
    $api->response->sendError("Chybí parametry požadavku.", 400);
}

try {
    $query = "SELECT id FROM user_wishlists WHERE user_id = ? AND entity_id = ? AND entity_type = ?";
    $stmt = $api->db->prepare($query);
    $stmt->execute([$user['user_id'], $entity_id, $entity_type]);
    $wishlist = $stmt->fetch();

    if ($wishlist) {
        $delete = $api->db->prepare("DELETE FROM user_wishlists WHERE id = ?");
        $delete->execute([$wishlist['id']]);
        $api->response->sendSuccess("Odebráno z wishlistu.", ["is_wishlist" => false]);
    } else {
        $insert = $api->db->prepare("INSERT INTO user_wishlists (user_id, entity_id, entity_type) VALUES (?, ?, ?)");
        $insert->execute([$user['user_id'], $entity_id, $entity_type]);
        $api->response->sendSuccess("Přidáno do wishlistu.", ["is_wishlist" => true]);
    }
} catch (PDOException $e) {
    error_log("DB Error (toggle_wishlist): " . $e->getMessage());
    $api->response->sendError("Vnitřní chyba při ukládání do wishlistu.", 500);
}
?>