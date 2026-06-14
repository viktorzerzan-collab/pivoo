<?php
require_once '../core/ApiHandler.php';

$api = new ApiHandler();
// Ochrana - pouze pro administrátory
$api->requireAdmin();

try {
    $query = "
        SELECT 
            bb.barcode AS id,
            bb.barcode AS ean,
            bb.volume,
            bb.packaging,
            b.id AS beer_id,
            b.name AS beer_name,
            br.id AS brewery_id,
            br.name AS brewery_name
        FROM beer_barcodes bb
        JOIN beers b ON bb.beer_id = b.id
        JOIN breweries br ON b.brewery_id = br.id
        ORDER BY br.name ASC, b.name ASC, bb.volume ASC
    ";
    
    $stmt = $api->db->query($query);
    $barcodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($barcodes as &$barcode) {
        $barcode['volume'] = (float) $barcode['volume'];
    }
    
    $api->response->sendSuccess("", ["data" => $barcodes]);
} catch (Exception $e) {
    error_log("DB Error (barcodes): " . $e->getMessage());
    $api->response->sendError("Vnitřní chyba při načítání kódů.", 500);
}
?>