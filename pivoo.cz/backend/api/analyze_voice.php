<?php
// backend/api/analyze_voice.php
header("Access-Control-Allow-Origin: https://www.pivoo.cz");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config.php';
require_once '../Database.php';
require_once '../JwtHandler.php';

// Zabezpečení: Pouze přihlášený uživatel
$user = JwtHandler::checkUser();

if (!defined('GEMINI_API_KEY')) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "AI API klíč není nakonfigurován v config.php."]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));
if (empty($data->text)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Chybí text k analýze."]);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // 1. Získání databáze pivovarů a lokací pro párování
    $breweries_stmt = $db->query("SELECT id, name FROM breweries");
    $breweries_list = $breweries_stmt->fetchAll(PDO::FETCH_ASSOC);

    $locations_stmt = $db->query("SELECT id, name FROM locations");
    $locations_list = $locations_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Prompt pro Gemini
    $promptText = "Jsi pivní asistent. Uživatel ti nadiktoval, jaké pivo právě pije. Přepis jeho hlasu zní takto: '" . addslashes($data->text) . "'.\n\n" .
    "Tvé úkoly:\n" .
    "1. Identifikuj přesný název piva a jméno pivovaru.\n" .
    "2. Identifikuj objem (např. slova 'velké' nebo 'půllitr' znamenají 0.5; 'malé' nebo 'třetinka' = 0.3; 'šnyt' = 0.4). Vrať jako desetinné číslo.\n" .
    "3. Identifikuj počet vypitých kusů (pokud není výslovně uvedeno číslo, výchozí hodnota je 1).\n" .
    "4. Identifikuj cenu za 1 kus. POZOR: Pokud uživatel řekne celkovou útratu za více piv (např. 'dal jsem si 2 piva a platil 100'), musíš cenu vydělit počtem kusů, abys získal cenu za jedno pivo!\n" .
    "5. Identifikuj typ balení. Povolené hodnoty: 'točené', 'lahev', 'plechovka', 'pet', 'sud'. Pokud z kontextu nevyplývá nic jiného (např. je v hospodě), použij 'točené'.\n" .
    "6. Identifikuj měnu (CZK, EUR, PLN, GBP). Pokud uživatel řekne 'euro' nebo 'éčka', použij EUR.\n" .
    "7. Identifikuj název podniku (lokace), pokud ho uživatel zmínil.\n" .
    "8. Porovnej nalezený pivovar se seznamem: " . json_encode($breweries_list) . ". Pokud najdeš shodu, vrať jeho ID.\n" .
    "9. Porovnej nalezený podnik se seznamem: " . json_encode($locations_list) . ". Pokud najdeš shodu, vrať jeho ID.\n\n" .
    "DŮLEŽITÉ: Pokud pivovar v seznamu NENÍ, nastav 'brewery_id' na null a 'is_new_brewery' na true.\n\n" .
    "Odpověz STRIKTNĚ pouze validním JSONem bez jakéhokoliv dalšího textu. Zde je požadovaná struktura:\n" .
    "{\n" .
    "    \"beer_name\": \"...\",\n" .
    "    \"brewery_name\": \"...\",\n" .
    "    \"brewery_id\": null,\n" .
    "    \"is_new_brewery\": false,\n" .
    "    \"location_id\": null,\n" .
    "    \"volume\": null,\n" .
    "    \"quantity\": 1,\n" .
    "    \"price\": null,\n" .
    "    \"currency\": \"CZK\",\n" .
    "    \"packaging\": \"točené\"\n" .
    "}";

    // 3. Volání Google Gemini API
    $clean_api_key = trim(GEMINI_API_KEY);
    $api_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $clean_api_key;

    $postData = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $promptText]
                ]
            ]
        ]
    ];

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curl_error || $http_code !== 200) {
        $debug_msg = $curl_error ? "cURL chyba: " . $curl_error : "Google API ($http_code): " . $response;
        error_log("Gemini API Error (Voice): " . $debug_msg);
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Chyba API. Detail: " . $debug_msg]);
        exit();
    }

    $result = json_decode($response, true);
    
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $ai_response_text = trim($result['candidates'][0]['content']['parts'][0]['text']);
        $ai_response_text = str_replace(['```json', '```'], '', $ai_response_text);
        
        $ai_json = json_decode($ai_response_text, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            
            $db->beginTransaction();
            try {
                $brewery_id = $ai_json['brewery_id'] ?? null;
                $beer_id = null;

                if (!$brewery_id || !empty($ai_json['is_new_brewery'])) {
                    $b_name = $ai_json['brewery_name'] ?: 'Neznámý pivovar';
                    // PŘIDÁNO: Uložení autora
                    $stmt = $db->prepare("INSERT INTO breweries (name, country_id, is_approved, created_by) VALUES (?, 1, 0, ?)");
                    $stmt->execute([$b_name, $user['user_id']]);
                    
                    $brewery_id = $db->lastInsertId();
                    $ai_json['brewery_id'] = $brewery_id;
                    $ai_json['brewery_name'] = $b_name;
                }

                if ($brewery_id && !empty($ai_json['beer_name'])) {
                    $find_beer = $db->prepare("SELECT id FROM beers WHERE brewery_id = ? AND name LIKE ? LIMIT 1");
                    $find_beer->execute([$brewery_id, '%' . trim($ai_json['beer_name']) . '%']);
                    $existing_beer = $find_beer->fetch();

                    if ($existing_beer) {
                        $beer_id = $existing_beer['id'];
                    } else {
                        // PŘIDÁNO: Uložení autora
                        $stmt_beer = $db->prepare("INSERT INTO beers (name, brewery_id, is_approved, created_by) VALUES (?, ?, 0, ?)");
                        $stmt_beer->execute([$ai_json['beer_name'], $brewery_id, $user['user_id']]);
                        $beer_id = $db->lastInsertId();
                    }
                    $ai_json['beer_id'] = $beer_id;
                }

                $db->commit();
                
                echo json_encode(["status" => "success", "data" => $ai_json]);

            } catch (Exception $e) {
                $db->rollBack();
                error_log("Shadow Profile Creation Error (Voice): " . $e->getMessage());
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "Záznam byl rozpoznán, ale interní chyba brání jeho uložení."]);
            }

        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "AI vrátila neplatná data: " . $ai_response_text]);
        }
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "AI nedokázala text analyzovat."]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Vnitřní chyba při zpracování: " . $e->getMessage()]);
}
?>