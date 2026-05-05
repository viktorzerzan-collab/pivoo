<?php
// backend/api/analyze_beer.php
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

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Nebyl přijat žádný obrázek."]);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // 1. Získání databáze pivovarů a stylů pro přesnější párování
    $breweries_stmt = $db->query("SELECT id, name FROM breweries");
    $breweries_list = $breweries_stmt->fetchAll(PDO::FETCH_ASSOC);

    $styles_stmt = $db->query("SELECT id, name FROM beer_styles");
    $styles_list = $styles_stmt->fetchAll(PDO::FETCH_ASSOC);

    $countries_stmt = $db->query("SELECT id, name_cz FROM countries");
    $countries_list = $countries_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Příprava obrázku pro API (Base64)
    $imagePath = $_FILES['image']['tmp_name'];
    $imageMime = $_FILES['image']['type'];
    
    // Whitelist mime typů
    if (!in_array($imageMime, ['image/jpeg', 'image/png', 'image/webp'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Nepodporovaný formát obrázku (pouze JPG, PNG, WEBP)."]);
        exit();
    }

    $imageData = base64_encode(file_get_contents($imagePath));

    // 3. Prompt pro Gemini
    $promptText = "Jsi pivní expert. Zanalyzuj tuto fotografii piva (etiketa, plechovka, láhev) nebo nápojového lístku.
Tvé úkoly:
1. Identifikuj přesný název piva a jméno pivovaru.
2. Identifikuj pivní styl.
3. Najdi technické parametry: EPM (Stupňovitost v °), ABV (Alkohol v %), IBU (Hořkost), EBC (Barva).
4. Zjisti, zda je pivo nefiltrované nebo nepasterizované.
5. Porovnej nalezený pivovar a styl se seznamy, které ti dodám. Pokud najdeš shodu, vrať jeho ID.

Zde je seznam existujících pivovarů: " . json_encode($breweries_list) . "
Zde je seznam existujících pivních stylů: " . json_encode($styles_list) . "
Zde je seznam zemí a jejich ID: " . json_encode($countries_list) . "

DŮLEŽITÉ: Pokud pivovar v seznamu pivovarů NENÍ, nastav 'brewery_id' na null, 'is_new_brewery' na true a do 'brewery_metadata' doplň maximum informací o pivovaru z tvých znalostí. Vyhledej přesné město, PSČ, ulici, a přiřaď správné 'country_id'.

Odpověz STRIKTNĚ pouze validním JSONem bez jakéhokoliv dalšího textu nebo markdownu. Struktura musí být:
{
    \"beer_name\": \"...\",
    \"brewery_name\": \"...\",
    \"brewery_id\": null,
    \"is_new_brewery\": false,
    \"brewery_metadata\": {
        \"city\": \"...\",
        \"zip_code\": \"...\",
        \"address\": \"...\",
        \"country_id\": 1,
        \"website\": \"...\",
        \"email\": \"...\",
        \"phone\": \"...\",
        \"lat\": 0.000000,
        \"lng\": 0.000000
    },
    \"style_id\": null,
    \"epm\": null,
    \"abv\": null,
    \"ibu\": null,
    \"ebc\": null,
    \"is_unfiltered\": false,
    \"is_unpasteurized\": false
}";

    // 4. Volání Google Gemini API
    $clean_api_key = trim(GEMINI_API_KEY);
    $api_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent?key=" . $clean_api_key;

    $postData = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $promptText],
                    [
                        "inlineData" => [
                            "mimeType" => $imageMime,
                            "data" => $imageData
                        ]
                    ]
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
        error_log("Gemini API Error: " . $debug_msg);
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Chyba API. Detail: " . $debug_msg]);
        exit();
    }

    $result = json_decode($response, true);
    
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $ai_response_text = trim($result['candidates'][0]['content']['parts'][0]['text']);
        $ai_response_text = str_replace(['```json', '
```'], '', $ai_response_text);
        
        $ai_json = json_decode($ai_response_text, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            
            // --- AUTOMATICKÉ ZALOŽENÍ CHYBĚJÍCÍCH DAT (SHADOW PROFILES) ---
            $db->beginTransaction();
            try {
                $brewery_id = $ai_json['brewery_id'] ?? null;
                $beer_id = null;

                // A) Pokud pivovar neexistuje, založíme ho jako neschválený a přidáme ID autora
                if (!$brewery_id || !empty($ai_json['is_new_brewery'])) {
                    $meta = $ai_json['brewery_metadata'] ?? [];
                    $b_name = $ai_json['brewery_name'] ?: 'Neznámý pivovar';
                    $b_city = $meta['city'] ?? null;
                    $b_country = $meta['country_id'] ?? 1;
                    $b_lat = $meta['lat'] ?? null;
                    $b_lng = $meta['lng'] ?? null;

                    $stmt = $db->prepare("INSERT INTO breweries (name, city, country_id, lat, lng, is_approved, created_by) VALUES (?, ?, ?, ?, ?, 0, ?)");
                    $stmt->execute([$b_name, $b_city, $b_country, $b_lat, $b_lng, $user['user_id']]);
                    
                    $brewery_id = $db->lastInsertId();
                    $ai_json['brewery_id'] = $brewery_id;
                    $ai_json['brewery_name'] = $b_name;
                }

                // B) Zkontrolujeme, zda existuje pivo
                if ($brewery_id && !empty($ai_json['beer_name'])) {
                    $find_beer = $db->prepare("SELECT id FROM beers WHERE brewery_id = ? AND name LIKE ? LIMIT 1");
                    // Rychlý fuzzy match na název piva
                    $find_beer->execute([$brewery_id, '%' . trim($ai_json['beer_name']) . '%']);
                    $existing_beer = $find_beer->fetch();

                    if ($existing_beer) {
                        $beer_id = $existing_beer['id'];
                    } else {
                        // Pivo neexistuje, založíme ho jako neschválené a přidáme ID autora
                        $style_id = $ai_json['style_id'] ?? null;
                        $epm = $ai_json['epm'] ?? null;
                        $abv = $ai_json['abv'] ?? null;
                        $ibu = $ai_json['ibu'] ?? null;
                        $ebc = $ai_json['ebc'] ?? null;
                        $unfiltered = !empty($ai_json['is_unfiltered']) ? 1 : 0;
                        $unpasteurized = !empty($ai_json['is_unpasteurized']) ? 1 : 0;

                        $stmt_beer = $db->prepare("INSERT INTO beers (name, brewery_id, style_id, epm, abv, ibu, ebc, is_unfiltered, is_unpasteurized, is_approved, created_by) 
                                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?)");
                        $stmt_beer->execute([$ai_json['beer_name'], $brewery_id, $style_id, $epm, $abv, $ibu, $ebc, $unfiltered, $unpasteurized, $user['user_id']]);
                        $beer_id = $db->lastInsertId();
                    }
                    $ai_json['beer_id'] = $beer_id;
                }

                $db->commit();
                
                echo json_encode(["status" => "success", "data" => $ai_json]);

            } catch (Exception $e) {
                $db->rollBack();
                error_log("Shadow Profile Creation Error: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "Záznam byl rozpoznán, ale interní chyba brání jeho uložení."]);
            }

        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "AI vrátila neplatná data: " . $ai_response_text]);
        }
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "AI nedokázala obrázek analyzovat."]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Vnitřní chyba při zpracování obrázku: " . $e->getMessage()]);
}
?>