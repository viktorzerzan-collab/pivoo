<?php
// ZMĚNA: Omezení CORS
header("Access-Control-Allow-Origin: https://www.pivoo.cz");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once '../Database.php';
require_once '../JwtHandler.php';

// ZABEZPEČENÍ
$user = JwtHandler::checkUser();

$db = (new Database())->getConnection();
$data = json_decode(file_get_contents("php://input"));

// Zjištění akce (výchozí je změna hesla pro zachování zpětné kompatibility)
$action = isset($data->action) ? $data->action : 'change_password';

// --- AKCE: AKTUALIZACE MĚNY ---
if ($action === 'update_currency') {
    $currency = isset($data->default_currency) ? strtoupper(trim($data->default_currency)) : null;
    $allowed = ['CZK', 'EUR', 'PLN', 'GBP'];
    
    if ($currency && in_array($currency, $allowed)) {
        try {
            $update = $db->prepare("UPDATE users SET default_currency = ? WHERE id = ?");
            if ($update->execute([$currency, $user['user_id']])) {
                echo json_encode(["status" => "success", "message" => "Výchozí měna byla uložena."]);
            } else {
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "Chyba při ukládání měny do databáze."]);
            }
        } catch (Exception $e) {
            error_log("DB Error (update_profile currency): " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Interní chyba serveru."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Neplatná měna."]);
    }
    exit();
}

// --- AKCE: AKTUALIZACE VZHLEDU ---
if ($action === 'update_theme') {
    $mode = isset($data->theme_mode) ? $data->theme_mode : null;
    $pref = isset($data->theme_preference) ? $data->theme_preference : null;

    if ($mode || $pref) {
        try {
            // Aktualizujeme to, co nám frontend poslal (může poslat obojí, nebo jen jedno)
            if ($mode && $pref) {
                $update = $db->prepare("UPDATE users SET theme_mode = ?, theme_preference = ? WHERE id = ?");
                $success = $update->execute([$mode, $pref, $user['user_id']]);
            } else if ($mode) {
                $update = $db->prepare("UPDATE users SET theme_mode = ? WHERE id = ?");
                $success = $update->execute([$mode, $user['user_id']]);
            } else if ($pref) {
                $update = $db->prepare("UPDATE users SET theme_preference = ? WHERE id = ?");
                $success = $update->execute([$pref, $user['user_id']]);
            }

            if ($success) {
                echo json_encode(["status" => "success", "message" => "Nastavení vzhledu uloženo."]);
            } else {
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "Chyba při ukládání do databáze."]);
            }
        } catch (Exception $e) {
            // ZMĚNA: Skrytí chyby
            error_log("DB Error (update_profile theme): " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Interní chyba serveru."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Chybí data pro aktualizaci."]);
    }
    exit();
}

// --- AKCE: ZMĚNA HESLA ---
if ($action === 'change_password' && !empty($data->old_password) && !empty($data->new_password)) {
    $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$user['user_id']]);
    $dbUser = $stmt->fetch();

    if ($dbUser && password_verify($data->old_password, $dbUser['password_hash'])) {
        if (strlen($data->new_password) < 8 || !preg_match('/[0-9]/', $data->new_password) || !preg_match('/[^a-zA-Z0-9]/', $data->new_password)) {
            echo json_encode(["status" => "error", "message" => "Nové heslo musí mít alespoň 8 znaků, obsahovat číslo a speciální znak."]);
            exit();
        }

        try {
            $new_hash = password_hash($data->new_password, PASSWORD_DEFAULT);
            $update = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            
            if ($update->execute([$new_hash, $user['user_id']])) {
                echo json_encode(["status" => "success", "message" => "Heslo bylo úspěšně změněno."]);
            } else {
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "Chyba při ukládání do databáze."]);
            }
        } catch (Exception $e) {
            // ZMĚNA: Skrytí chyby
            error_log("DB Error (update_profile password): " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Interní chyba serveru."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Původní heslo není správné."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Vyplňte všechna pole."]);
}
?>