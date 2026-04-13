<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../Database.php';
require_once '../JwtHandler.php';

// ZABEZPEČENÍ!
JwtHandler::checkAdmin();

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id) && !empty($data->name)) {
    try {
        $query = "UPDATE beers 
                  SET name = ?, brewery_id = ?, style = ?, epm = ?, abv = ?,
                      ibu = ?, ebc = ?, hops = ?, malts = ?, fermentation = ?, tags = ?,
                      is_unfiltered = ?, is_unpasteurized = ?
                  WHERE id = ?";
                  
        $stmt = $db->prepare($query);
        
        $epm = (isset($data->epm) && $data->epm !== '') ? $data->epm : null;
        $abv = (isset($data->abv) && $data->abv !== '') ? $data->abv : null;
        $ibu = (isset($data->ibu) && $data->ibu !== '') ? $data->ibu : null;
        $ebc = (isset($data->ebc) && $data->ebc !== '') ? $data->ebc : null;
        $hops = (isset($data->hops) && $data->hops !== '') ? $data->hops : null;
        $malts = (isset($data->malts) && $data->malts !== '') ? $data->malts : null;
        $fermentation = (isset($data->fermentation) && $data->fermentation !== '') ? $data->fermentation : null;
        $tags = (isset($data->tags) && $data->tags !== '') ? $data->tags : null;
        $is_unfiltered = (isset($data->is_unfiltered) && $data->is_unfiltered) ? 1 : 0;
        $is_unpasteurized = (isset($data->is_unpasteurized) && $data->is_unpasteurized) ? 1 : 0;

        if ($stmt->execute([
            $data->name, $data->brewery_id, $data->style, $epm, $abv,
            $ibu, $ebc, $hops, $malts, $fermentation, $tags, $is_unfiltered, $is_unpasteurized,
            $data->id
        ])) {
            echo json_encode(["status" => "success", "message" => "Pivo v katalogu bylo úspěšně upraveno."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Nepodařilo se upravit pivo."]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Chyba databáze: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Chybí povinná data (ID nebo název)."]);
}
?>