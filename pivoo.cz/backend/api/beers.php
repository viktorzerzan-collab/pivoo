<?php
// backend/api/beers.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../Database.php';
require_once '../JwtHandler.php';

// Pokusíme se identifikovat uživatele z tokenu (pokud je přihlášen)
$token = JwtHandler::getBearerToken();
$decoded = $token ? JwtHandler::decode($token) : null;
$userId = $decoded ? $decoded['user_id'] : 0;

$db = (new Database())->getConnection();

if ($db) {
    // 1. Získání parametrů z URL (od frontendu)
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
    $offset = ($page - 1) * $limit;

    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $brewery = isset($_GET['brewery']) ? $_GET['brewery'] : '';
    $style = isset($_GET['style']) ? $_GET['style'] : '';
    $country = isset($_GET['country']) ? $_GET['country'] : '';
    
    $epm_min = isset($_GET['epm_min']) && $_GET['epm_min'] !== '' ? (float)$_GET['epm_min'] : null;
    $epm_max = isset($_GET['epm_max']) && $_GET['epm_max'] !== '' ? (float)$_GET['epm_max'] : null;
    $abv_min = isset($_GET['abv_min']) && $_GET['abv_min'] !== '' ? (float)$_GET['abv_min'] : null;
    $abv_max = isset($_GET['abv_max']) && $_GET['abv_max'] !== '' ? (float)$_GET['abv_max'] : null;
    $ibu_min = isset($_GET['ibu_min']) && $_GET['ibu_min'] !== '' ? (int)$_GET['ibu_min'] : null;
    $ibu_max = isset($_GET['ibu_max']) && $_GET['ibu_max'] !== '' ? (int)$_GET['ibu_max'] : null;

    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';

    // 2. Sestavení dynamických WHERE podmínek
    $whereParts = ["b.is_approved = 1"];
    $params = [':uid' => $userId];

    if ($search !== '') {
        $whereParts[] = "b.name LIKE :search";
        $params[':search'] = "%{$search}%";
    }
    if ($brewery !== '') {
        $whereParts[] = "b.brewery_id = :brewery";
        $params[':brewery'] = $brewery;
    }
    if ($style !== '') {
        $whereParts[] = "b.style_id = :style";
        $params[':style'] = $style;
    }
    if ($country !== '') {
        $whereParts[] = "br.country_id = :country";
        $params[':country'] = $country;
    }

    if ($epm_min !== null) { $whereParts[] = "b.epm >= :epm_min"; $params[':epm_min'] = $epm_min; }
    if ($epm_max !== null) { $whereParts[] = "b.epm <= :epm_max"; $params[':epm_max'] = $epm_max; }
    if ($abv_min !== null) { $whereParts[] = "b.abv >= :abv_min"; $params[':abv_min'] = $abv_min; }
    if ($abv_max !== null) { $whereParts[] = "b.abv <= :abv_max"; $params[':abv_max'] = $abv_max; }
    if ($ibu_min !== null) { $whereParts[] = "b.ibu >= :ibu_min"; $params[':ibu_min'] = $ibu_min; }
    if ($ibu_max !== null) { $whereParts[] = "b.ibu <= :ibu_max"; $params[':ibu_max'] = $ibu_max; }

    $whereSql = "WHERE " . implode(" AND ", $whereParts);

    // 3. Zjištění celkového počtu záznamů (pro paginaci na frontendu)
    $countQuery = "SELECT COUNT(DISTINCT b.id) as total 
                   FROM beers b
                   LEFT JOIN breweries br ON b.brewery_id = br.id
                   $whereSql";
    
    // Pro count query nepotřebujeme uid parametr (používá se jen pro oblíbené položky)
    $countParams = $params;
    unset($countParams[':uid']); 
    
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($countParams);
    $totalItems = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalItems / $limit);

    // 4. Nastavení řazení (oblíbené chceme vždy nahoře)
    $orderBy = "is_favorite DESC"; 
    switch ($sort) {
        case 'name_desc': $orderBy .= ", b.name DESC"; break;
        case 'brewery_asc': $orderBy .= ", br.name ASC"; break;
        case 'brewery_desc': $orderBy .= ", br.name DESC"; break;
        case 'style_asc': $orderBy .= ", bs.name ASC"; break;
        case 'style_desc': $orderBy .= ", bs.name DESC"; break;
        case 'rating_desc': $orderBy .= ", avg_rating DESC"; break;
        case 'rating_asc': $orderBy .= ", avg_rating ASC"; break;
        case 'abv_desc': $orderBy .= ", b.abv DESC"; break;
        case 'abv_asc': $orderBy .= ", b.abv ASC"; break;
        case 'epm_desc': $orderBy .= ", b.epm DESC"; break;
        case 'epm_asc': $orderBy .= ", b.epm ASC"; break;
        case 'ibu_desc': $orderBy .= ", b.ibu DESC"; break;
        case 'ibu_asc': $orderBy .= ", b.ibu ASC"; break;
        case 'newest': $orderBy .= ", b.created_at DESC"; break;
        case 'oldest': $orderBy .= ", b.created_at ASC"; break;
        case 'name_asc': 
        default: 
            $orderBy .= ", b.name ASC"; break;
    }

    // 5. Hlavní dotaz pro načtení piva na dané stránce
    $query = "SELECT b.*, br.name as brewery_name, c.name_cz as brewery_country, c.code as brewery_country_code, bs.name as style,
                     ROUND(AVG(NULLIF(cons.rating_beer, 0)), 1) as avg_rating, 
                     COUNT(cons.id) as total_checkins,
                     IF(fav.id IS NOT NULL, 1, 0) as is_favorite
              FROM beers b
              LEFT JOIN breweries br ON b.brewery_id = br.id
              LEFT JOIN countries c ON br.country_id = c.id
              LEFT JOIN beer_styles bs ON b.style_id = bs.id
              LEFT JOIN consumptions cons ON b.id = cons.beer_id
              LEFT JOIN user_favorites fav ON b.id = fav.entity_id 
                   AND fav.entity_type = 'beer' 
                   AND fav.user_id = :uid
              $whereSql
              GROUP BY b.id
              ORDER BY $orderBy
              LIMIT :limit OFFSET :offset";
              
    $stmt = $db->prepare($query);
    
    // Bind parametrů (PDO pro limit a offset vyžaduje vysloveně INT)
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $beers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Vrátíme data včetně metadat o stránkování
    echo json_encode([
        "status" => "success", 
        "data" => $beers,
        "pagination" => [
            "total" => (int)$totalItems,
            "total_pages" => (int)$totalPages,
            "current_page" => $page,
            "limit" => $limit
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Chyba databáze."]);
}
?>