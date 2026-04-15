<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/Database.php';

// Nastavení logovacího souboru (uloží se do stejné složky jako tento skript)
$logFile = __DIR__ . '/gps_update_log.txt';

// Funkce pro zápis do logu
function writeToLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    // Přidáme nakonec souboru nový řádek s časovým razítkem
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Zjistíme, kde jsme skončili (výchozí je 0)
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = 10; 

try {
    $db = (new Database())->getConnection();
    
    // Zapsání startu do logu, pokud jedeme od začátku
    if ($offset === 0) {
        writeToLog("--- NOVÉ SPUŠTĚNÍ SKRIPTU ---");
    }

    $stmt = $db->prepare("SELECT b.id, b.name, b.address, b.city, c.name_cz as country 
                          FROM breweries b 
                          LEFT JOIN countries c ON b.country_id = c.id 
                          ORDER BY b.id ASC 
                          LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $breweries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($breweries) === 0) {
        $msg = "Hotovo! Všechny pivovary jsou aktualizovány.";
        writeToLog($msg);
        echo "<h1 style='color: green;'>🎉 {$msg}</h1>";
        echo "<p>Podívej se do souboru <strong>gps_update_log.txt</strong> ve složce backend pro kompletní přehled.</p>";
        exit;
    }

    echo "<h2>Zpracovávám záznamy " . ($offset + 1) . " až " . ($offset + count($breweries)) . " (celkem jich je 235)...</h2>";
    echo "<ul style='font-family: monospace; font-size: 16px;'>";

    foreach ($breweries as $brewery) {
        $searchParts = [];
        if (!empty($brewery['address'])) $searchParts[] = $brewery['address'];
        if (!empty($brewery['city'])) $searchParts[] = $brewery['city'];
        if (!empty($brewery['country'])) $searchParts[] = $brewery['country'];
        
        if (empty($searchParts)) {
            $searchParts[] = $brewery['name'];
        }

        $searchQuery = urlencode(implode(', ', $searchParts));
        $url = "https://nominatim.openstreetmap.org/search?q={$searchQuery}&format=json&limit=1";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "PivooCz-DataFixer/1.0 (v.zerzan@outlook.com)");
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response !== false && $httpCode == 200) {
            $data = json_decode($response, true);
            if (!empty($data)) {
                $lat = $data[0]['lat'];
                $lng = $data[0]['lon'];

                $updateStmt = $db->prepare("UPDATE breweries SET lat = :lat, lng = :lng WHERE id = :id");
                $updateStmt->execute([':lat' => $lat, ':lng' => $lng, ':id' => $brewery['id']]);
                
                $msg = "[OK] {$brewery['name']}: Získáno {$lat}, {$lng}";
                writeToLog($msg);
                echo "<li><span style='color: green;'>✔</span> {$msg}</li>";
            } else {
                $msg = "[VAROVÁNÍ] {$brewery['name']}: Adresa nenalezena (hledáno: " . implode(', ', $searchParts) . ")";
                writeToLog($msg);
                echo "<li><span style='color: orange;'>⚠</span> {$msg}</li>";
            }
        } else {
            $msg = "[CHYBA] {$brewery['name']}: Chyba připojení k mapám (HTTP Kód: {$httpCode}, cURL Chyba: {$curlError})";
            writeToLog($msg);
            echo "<li><span style='color: red;'>✖</span> {$msg}</li>";
        }

        sleep(2);
    }
    
    echo "</ul>";

    $nextOffset = $offset + $limit;
    
    echo "<h3>Hotovo, za 3 vteřiny jdeme na další dávku... Nesahej na to! 😎</h3>";
    
    echo "<script>
            setTimeout(function() {
                window.location.href = '?offset={$nextOffset}';
            }, 3000);
          </script>";

} catch (Exception $e) {
    $msg = "Kritická chyba: " . $e->getMessage();
    writeToLog($msg);
    echo "Došlo k chybě: " . $e->getMessage();
}
?>