<?php
// backend/api/update_brewery.php
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

if (!empty($data->id) && !empty($data->name)) {
    try {
        $new_logo_filename = null;
        $upload_dir = '../uploads/logos/';

        if (isset($_FILES['logoFile']) && $_FILES['logoFile']['error'] === UPLOAD_ERR_OK) {
            if ($_FILES['logoFile']['size'] > 5 * 1024 * 1024) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Soubor loga je příliš velký."]);
                exit();
            }

            // Zjištění starého loga pro smazání
            $stmt_old = $db->prepare("SELECT logo FROM breweries WHERE id = ?");
            $stmt_old->execute([$data->id]);
            $oldBrewery = $stmt_old->fetch();
            
            $file_tmp = $_FILES['logoFile']['tmp_name'];
            $image_info = getimagesize($file_tmp);
            $allowed_types = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];
            
            if ($image_info && in_array($image_info[2], $allowed_types)) {
                $w = $image_info[0]; $h = $image_info[1];
                $type = $image_info[2];
                $src = null;
                
                if($type == IMAGETYPE_JPEG) $src = imagecreatefromjpeg($file_tmp);
                elseif($type == IMAGETYPE_PNG) $src = imagecreatefrompng($file_tmp);
                elseif($type == IMAGETYPE_WEBP) $src = imagecreatefromwebp($file_tmp);

                if ($src) {
                    $target_size = 400;
                    $min_side = min($w, $h);
                    $dst = imagecreatetruecolor($target_size, $target_size);
                    
                    imagealphablending($dst, false);
                    imagesavealpha($dst, true);
                    $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
                    imagefilledrectangle($dst, 0, 0, $target_size, $target_size, $transparent);

                    imagecopyresampled($dst, $src, 0, 0, ($w-$min_side)/2, ($h-$min_side)/2, $target_size, $target_size, $min_side, $min_side);
                    
                    $new_logo_filename = uniqid('logo_') . '.webp';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                    
                    if (imagewebp($dst, $upload_dir . $new_logo_filename, 80)) {
                        // Smazání původního loga, pokud existuje
                        if ($oldBrewery && $oldBrewery['logo'] && file_exists($upload_dir . $oldBrewery['logo'])) {
                            unlink($upload_dir . $oldBrewery['logo']);
                        }
                    }
                    
                    imagedestroy($src); imagedestroy($dst);
                }
            }
        }

        $city = !empty($data->city) ? $data->city : null;
        $country_id = !empty($data->country_id) ? (int)$data->country_id : null;
        $address = !empty($data->address) ? $data->address : null;
        $zip_code = !empty($data->zip_code) ? $data->zip_code : null;
        $email = !empty($data->email) ? $data->email : null;
        $phone = !empty($data->phone) ? $data->phone : null;
        $website = !empty($data->website) ? $data->website : null;
        $lat = !empty($data->lat) ? (float)$data->lat : null;
        $lng = !empty($data->lng) ? (float)$data->lng : null;
        $opening_hours = !empty($data->opening_hours) ? $data->opening_hours : null;

        if ($new_logo_filename) {
            $query = "UPDATE breweries SET name=?, city=?, country_id=?, address=?, zip_code=?, email=?, phone=?, website=?, lat=?, lng=?, opening_hours=?, logo=? WHERE id=?";
            $params = [$data->name, $city, $country_id, $address, $zip_code, $email, $phone, $website, $lat, $lng, $opening_hours, $new_logo_filename, $data->id];
        } else {
            $query = "UPDATE breweries SET name=?, city=?, country_id=?, address=?, zip_code=?, email=?, phone=?, website=?, lat=?, lng=?, opening_hours=? WHERE id=?";
            $params = [$data->name, $city, $country_id, $address, $zip_code, $email, $phone, $website, $lat, $lng, $opening_hours, $data->id];
        }

        $stmt = $db->prepare($query);
        if ($stmt->execute($params)) {
            echo json_encode(["status" => "success", "message" => "Pivovar byl aktualizován."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Chyba při aktualizaci pivovaru."]);
        }
    } catch (PDOException $e) {
        error_log("DB Error (update_brewery): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Vnitřní chyba databáze."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "ID a Název jsou povinné."]);
}
?>