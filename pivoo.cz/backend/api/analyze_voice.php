<?php
// backend/api/analyze_voice.php
require_once '../core/ApiHandler.php';
require_once '../config.php';

$api = new ApiHandler();
$user = JwtHandler::checkUser();

if (!defined('GEMINI_API_KEY')) {
    $api->response->sendError("AI API klíč není nakonfigurován v config.php.", 500);
}

$text = $api->request->getParam('text');

if (!$text) {
    $api->response->sendError("Chybí text k analýze.", 400);
}

try {
    $breweries_stmt = $api->db->query("SELECT id, name FROM breweries");
    $breweries_list = $breweries_stmt->fetchAll(PDO::FETCH_ASSOC);

    $locations_stmt = $api->db->query("SELECT id, name FROM locations");
    $locations_list = $locations_stmt->fetchAll(PDO::FETCH_ASSOC);

    // PŘIDÁNO: Získání pivních stylů pro hlasové vyhledávání/doplnění
    $styles_stmt = $api->db->query("SELECT id, name FROM beer_styles");
    $styles_list = $styles_stmt->fetchAll(PDO::FETCH_ASSOC);

    $promptText = "Jsi pivní asistent a expert. Uživatel ti nadiktoval, jaké pivo právě pije. Přepis jeho hlasu zní takto: '" . addslashes($text) . "'.\n\n" .
    "TVÉ ÚKOLY:\n" .
    "1. Identifikuj přesný název piva a jméno pivovaru.\n" .
    "2. Pokud uživatel jasně jmenuje konkrétní pivo (např. 'Radegast Ratar'), využij své globální znalosti pivního experta a doplň i jeho technické parametry: pivní styl, EPM (Stupňovitost v °), ABV (Alkohol v %), IBU (Hořkost), EBC (Barva), a zda je nefiltrované/nepasterizované, i když je uživatel v nahrávce výslovně nezmínil.\n" .
    "3. Identifikuj objem (např. slova 'velké' nebo 'půllitr' znamenají 0.5; 'malé' nebo 'třetinka' = 0.3; 'šnyt' = 0.4). Vrať jako desetinné číslo.\n" .
    "4. Identifikuj počet vypitých kusů (pokud není výslovně uvedeno číslo, výchozí hodnota je 1).\n" .
    "5. Identifikuj cenu za 1 kus. POZOR: Pokud uživatel řekne celkovou útratu za více piv (např. 'dal jsem si 2 piva a platil 100'), musíš cenu vydělit počtem kusů, abys získal cenu za jedno pivo!\n" .
    "6. Identifikuj typ balení. Povolené hodnoty: 'draft', 'bottle', 'can', 'pet', 'keg', 'mini_keg'. Pokud z kontextu nevyplývá nic jiného (např. je v hospodě), použij 'draft'.\n" .
    "7. Identifikuj měnu (CZK, EUR, PLN, GBP). Pokud uživatel řekne 'euro' nebo 'éčka', použij EUR.\n" .
    "8. Identifikuj název podniku (lokace), pokud ho uživatel zmínil.\n" .
    "9. Porovnej nalezený pivovar se seznamem pivovarů. Pokud najdeš shodu, vrať jeho ID.\n" .
    "10. Porovnej nalezený podnik se seznamem lokací. Pokud najdeš shodu, vrať jeho ID.\n" .
    "11. Porovnej odvozený pivní styl se seznamem stylů. Pokud najdeš shodu, vrať jeho style_id.\n" .
    "12. Identifikuj hodnocení piva (1-5) a hodnocení podniku/obsluhy (1-5), pokud je uživatel zmínil. Jinak vrať null.\n\n" .
    "Zde je seznam existujících pivovarů: " . json_encode($breweries_list) . "\n" .
    "Zde je seznam existujících podniků/lokací: " . json_encode($locations_list) . "\n" .
    "Zde je seznam existujících pivních stylů: " . json_encode($styles_list) . "\n\n" .
    "DŮLEŽITÉ VALIDAČNÍ PRAVIDLO:\n" .
    "Pokud je přepis hlasu příliš obecný (např. 'pivo ve sklenici, má pěnu, je dobré, stálo 30 kč...') a nelze z něj jednoznačně identifikovat konkrétní značku, pivovar nebo specifický název piva, NESNAŽ SE pivo domýšlet ani dosazovat univerzální hodnoty. V takovém případě ignoruj celou strukturu níže a odpověz POUZE JSONem obsahujícím klíč \"error\" s popisem, že zadání je příliš obecné na to, aby bylo možné pivo rozpoznat.\n\n" .
    "DŮLEŽITÉ: Pokud pivovar v seznamu NENÍ, ale pivo je jasně identifikovatelné, nastav 'brewery_id' na null a 'is_new_brewery' na true.\n\n" .
    "Požadovaná struktura standardní odpovědi:\n" .
    "{\n" .
    "    \"beer_name\": \"...\",\n" .
    "    \"brewery_name\": \"...\",\n" .
    "    \"brewery_id\": null,\n" .
    "    \"is_new_brewery\": false,\n" .
    "    \"location_id\": null,\n" .
    "    \"style_id\": null,\n" .
    "    \"epm\": null,\n" .
    "    \"abv\": null,\n" .
    "    \"ibu\": null,\n" .
    "    \"ebc\": null,\n" .
    "    \"is_unfiltered\": false,\n" .
    "    \"is_unpasteurized\": false,\n" .
    "    \"volume\": null,\n" .
    "    \"quantity\": 1,\n" .
    "    \"price\": null,\n" .
    "    \"currency\": \"CZK\",\n" .
    "    \"packaging\": \"draft\",\n" .
    "    \"rating_beer\": null,\n" .
    "    \"rating_care\": null\n" .
    "}";

    $clean_api_key = trim(GEMINI_API_KEY);
    $api_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent?key=" . $clean_api_key;

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
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curl_error || $http_code !== 200) {
        $debug_msg = $curl_error ? "cURL chyba: " . $curl_error : "Google API ($http_code): " . $response;
        error_log("Gemini API Error (Voice): " . $debug_msg);
        $api->response->sendError("Chyba API. Detail: " . $debug_msg, 500);
    }

    $result = json_decode($response, true);
    
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $ai_response_text = trim($result['candidates'][0]['content']['parts'][0]['text']);
        $ai_response_text = str_replace(['```json', '```'], '', $ai_response_text);
        
        $ai_json = json_decode($ai_response_text, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            
            // KONTROLA VALIDACE: Pokud AI vrátila chybu o příliš obecném popisu, zablokujeme proces
            if (!empty($ai_json['error'])) {
                $api->response->sendError($ai_json['error'], 400);
            }

            $api->db->beginTransaction();
            try {
                $brewery_id = $ai_json['brewery_id'] ?? null;
                $beer_id = null;

                if (!$brewery_id || !empty($ai_json['is_new_brewery'])) {
                    $b_name = $ai_json['brewery_name'] ?: 'Neznámý pivovar';
                    $stmt = $api->db->prepare("INSERT INTO breweries (name, country_id, is_approved, created_by) VALUES (?, 1, 0, ?)");
                    $stmt->execute([$b_name, $user['user_id']]);
                    
                    $brewery_id = $api->db->lastInsertId();
                    $ai_json['brewery_id'] = $brewery_id;
                    $ai_json['brewery_name'] = $b_name;
                }

                if ($brewery_id && !empty($ai_json['beer_name'])) {
                    $find_beer = $api->db->prepare("SELECT id FROM beers WHERE brewery_id = ? AND name LIKE ? LIMIT 1");
                    $find_beer->execute([$brewery_id, '%' . trim($ai_json['beer_name']) . '%']);
                    $existing_beer = $find_beer->fetch();

                    if ($existing_beer) {
                        $beer_id = $existing_beer['id'];
                    } else {
                        // ROZŠÍŘENO: Ukládáme kompletní bohatá metadata piva vyhledaná z globální paměti AI
                        $style_id = $ai_json['style_id'] ?? null;
                        $epm = $ai_json['epm'] ?? null;
                        $abv = $ai_json['abv'] ?? null;
                        $ibu = $ai_json['ibu'] ?? null;
                        $ebc = $ai_json['ebc'] ?? null;
                        $unfiltered = !empty($ai_json['is_unfiltered']) ? 1 : 0;
                        $unpasteurized = !empty($ai_json['is_unpasteurized']) ? 1 : 0;

                        $stmt_beer = $api->db->prepare("INSERT INTO beers (name, brewery_id, style_id, epm, abv, ibu, ebc, is_unfiltered, is_unpasteurized, is_approved, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?)");
                        $stmt_beer->execute([$ai_json['beer_name'], $brewery_id, $style_id, $epm, $abv, $ibu, $ebc, $unfiltered, $unpasteurized, $user['user_id']]);
                        $beer_id = $api->db->lastInsertId();
                    }
                    $ai_json['beer_id'] = $beer_id;
                }

                $api->db->commit();
                $api->response->sendSuccess("", ["data" => $ai_json]);

            } catch (Exception $e) {
                $api->db->rollBack();
                error_log("Shadow Profile Creation Error (Voice): " . $e->getMessage());
                $api->response->sendError("Záznam byl rozpoznán, ale interní chyba brání jeho uložení.", 500);
            }

        } else {
            $api->response->sendError("AI vrátila neplatná data: " . $ai_response_text, 500);
        }
    } else {
        $api->response->sendError("AI nedokázala text analyzovat.", 500);
    }

} catch (Exception $e) {
    $api->response->sendError("Vnitřní chyba při zpracování: " . $e->getMessage(), 500);
}
?>