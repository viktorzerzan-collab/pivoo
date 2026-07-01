<?php
require_once '../core/ApiHandler.php';
require_once '../JwtHandler.php';

$api = new ApiHandler();
// Použijeme stejnou autorizaci jako v checkin.php
$user = JwtHandler::checkUser();

$ean = isset($_GET['ean']) ? $_GET['ean'] : '';

if (empty($ean)) {
    $api->response->sendError('Chybí EAN kód.', 400);
}

try {
    $query = "
        SELECT 
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
        WHERE bb.barcode = ?
        LIMIT 1
    ";
    
    $stmt = $api->db->prepare($query);
    $stmt->execute([$ean]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $result['volume'] = (float) $result['volume'];
        
        $api->response->sendSuccess("", [
            'data' => $result
        ]);
    } else {
        // Pokud EAN neznáme, posíláme status 200, ale se stavem 'not_found', 
        // aby na to náš MagicScanner dokázal slušně zareagovat bez toho, že by vyhodil globální Toast s chybou.
        http_response_code(200);
        echo json_encode([
            'status' => 'not_found',
            'message' => 'EAN kód nebyl v databázi nalezen.'
        ]);
        exit();
    }
} catch (PDOException $e) {
    error_log("DB Error (lookup_barcode): " . $e->getMessage());
    $api->response->sendError('Chyba při hledání v databázi.', 500);
}
?>