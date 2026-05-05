<?php
// ZMĚNA: Přísnější CORS politika, povolen pouze přístup z vlastní domény
header("Access-Control-Allow-Origin: https://www.pivoo.cz");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../Database.php';
require_once '../JwtHandler.php';

// UZAMČENÍ ENDPOINTU a získání informací o adminovi
$user = JwtHandler::checkAdmin();

$database = new Database();
$db = $database->getConnection();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->name) && !empty($data->brewery_id) && !empty($data->style_id)) {
    try {
        // PŘIDÁNO: Uložení autora
        $query = "INSERT INTO beers (
                    name, brewery_id, style_id, epm, abv, 
                    ibu, ebc, hops, malts, fermentation, tags, is_unfiltered, is_unpasteurized,
                    is_approved, created_by
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)";
        
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
            $data->name, $data->brewery_id, $data->style_id, $epm, $abv,
            $ibu, $ebc, $hops, $malts, $fermentation, $tags, $is_unfiltered, $is_unpasteurized, $user['user_id']
        ])) {
            $new_id = $db->lastInsertId();
            echo json_encode(["status" => "success", "message" => "Pivo bylo úspěšně přidáno do katalogu.", "id" => $new_id]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Pivo se nepodařilo uložit."]);
        }
    } catch (PDOException $e) {
        error_log("DB Error (add_beer): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Vnitřní chyba serveru při komunikaci s databází."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Název piva, pivovar a styl jsou povinné údaje."]);
}
?>