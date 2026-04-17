<?php
// ZMĚNA: Omezení CORS
header("Access-Control-Allow-Origin: https://www.pivoo.cz");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once '../Database.php';
require_once '../JwtHandler.php';
JwtHandler::checkAdmin();

$db = (new Database())->getConnection();

$data = !empty($_POST) ? (object) $_POST : json_decode(file_get_contents("php://input"));

if (!empty($data->name)) {
    try {
        $logo_filename = null;

        if (isset($_FILES['logoFile']) && $_FILES['logoFile']['error'] === UPLOAD_ERR_OK) {
            // ZMĚNA: Backendová validace velikosti souboru (max 5 MB)
            if ($_FILES['logoFile']['size'] > 5 * 1024 * 1024) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Soubor loga je příliš velký. Maximum je 5 MB."]);
                exit();
            }

            $file_tmp = $_FILES['logoFile']['tmp_name'];
            $image_info = getimagesize($file_tmp);
            
            if ($image_info) {
                $w = $image_info[0]; 
                $h = $image_info[1];
                $type = $image_info[2];
                $src = null;

                if($type == IMAGETYPE_JPEG) $src = imagecreatefromjpeg($file_tmp);
                elseif($type == IMAGETYPE_PNG) $src = imagecreatefrompng($file_tmp);
                elseif($type == IMAGETYPE_WEBP) $src = imagecreatefromwebp($file_tmp);

                if ($src) {
                    $target_size = 400;
                    $min_side = min($w, $h);
                    $dst = imagecreatetruecolor($target_size, $target_size);
                    
                    // Vždy zachováme průhlednost (pro WebP)
                    imagealphablending($dst, false);
                    imagesavealpha($dst, true);
                    
                    // Průhledné pozadí místo černého, pokud by zdrojový obrázek nedosahoval okrajů
                    $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
                    imagefilledrectangle($dst, 0, 0, $target_size, $target_size, $transparent);

                    imagecopyresampled($dst, $src, 0, 0, ($w-$min_side)/2, ($h-$min_side)/2, $target_size, $target_size, $min_side, $min_side);
                    
                    // Změna přípony na .webp
                    $logo_filename = uniqid('logo_') . '.webp';
                    $upload_dir = '../uploads/logos/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                    
                    // Uložení jako WebP s kvalitou 80
                    imagewebp($dst, $upload_dir . $logo_filename, 80);
                    
                    imagedestroy($src); 
                    imagedestroy($dst);
                }
            }
        }

        // Přidány sloupce lat, lng a nově opening_hours do dotazu
        $query = "INSERT INTO breweries (name, city, country_id, address, zip_code, email, phone, website, lat, lng, opening_hours, is_approved, logo) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)";
        $stmt = $db->prepare($query);
        
        $city = !empty($data->city) ? $data->city : null;
        $country_id = !empty($data->country_id) ? (int)$data->country_id : 1;
        $address = !empty($data->address) ? $data->address : null;
        $zip_code = !empty($data->zip_code) ? $data->zip_code : null;
        $email = !empty($data->email) ? $data->email : null;
        $phone = !empty($data->phone) ? $data->phone : null;
        $website = !empty($data->website) ? $data->website : null;
        $lat = !empty($data->lat) ? $data->lat : null;
        $lng = !empty($data->lng) ? $data->lng : null;
        $opening_hours = !empty($data->opening_hours) ? $data->opening_hours : null;

        if ($stmt->execute([$data->name, $city, $country_id, $address, $zip_code, $email, $phone, $website, $lat, $lng, $opening_hours, $logo_filename])) {
            echo json_encode(["status" => "success", "message" => "Pivovar byl úspěšně přidán do katalogu."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Pivovar se nepodařilo uložit."]);
        }
    } catch (PDOException $e) {
        // ZMĚNA: Skrytí chybové hlášky
        error_log("DB Error (add_brewery): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Vnitřní chyba databáze při ukládání pivovaru."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Název pivovaru je povinný."]);
}
?>