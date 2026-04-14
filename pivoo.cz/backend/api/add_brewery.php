<?php
header("Access-Control-Allow-Origin: *");
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
            $file_tmp = $_FILES['logoFile']['tmp_name'];
            $image_info = getimagesize($file_tmp);
            
            if ($image_info) {
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
                    
                    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_WEBP) {
                        imagealphablending($dst, false);
                        imagesavealpha($dst, true);
                        $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
                        imagefilledrectangle($dst, 0, 0, $target_size, $target_size, $transparent);
                    }

                    imagecopyresampled($dst, $src, 0, 0, ($w-$min_side)/2, ($h-$min_side)/2, $target_size, $target_size, $min_side, $min_side);
                    
                    $ext = ($type == IMAGETYPE_PNG) ? '.png' : '.jpg';
                    $logo_filename = uniqid('logo_') . $ext;
                    $upload_dir = '../uploads/logos/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                    
                    if ($type == IMAGETYPE_PNG) imagepng($dst, $upload_dir . $logo_filename);
                    else imagejpeg($dst, $upload_dir . $logo_filename, 85);
                    
                    imagedestroy($src); imagedestroy($dst);
                }
            }
        }

        $query = "INSERT INTO breweries (name, city, country_id, address, zip_code, email, phone, website, is_approved, logo) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)";
        $stmt = $db->prepare($query);
        
        $city = !empty($data->city) ? $data->city : null;
        $country_id = !empty($data->country_id) ? (int)$data->country_id : 1; // 1 = CZ
        $address = !empty($data->address) ? $data->address : null;
        $zip_code = !empty($data->zip_code) ? $data->zip_code : null;
        $email = !empty($data->email) ? $data->email : null;
        $phone = !empty($data->phone) ? $data->phone : null;
        $website = !empty($data->website) ? $data->website : null;

        if ($stmt->execute([$data->name, $city, $country_id, $address, $zip_code, $email, $phone, $website, $logo_filename])) {
            echo json_encode(["status" => "success", "message" => "Pivovar byl úspěšně přidán do katalogu."]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Pivovar se nepodařilo uložit."]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Chyba databáze: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Název pivovaru je povinný."]);
}
?>