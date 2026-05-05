<?php
// backend/api/beers.php
header("Access-Control-Allow-Origin: https://www.pivoo.cz");
header("Content-Type: application/json; charset=UTF-8");
require_once '../Database.php';
require_once '../JwtHandler.php';

$token = JwtHandler::getBearerToken();
$decoded = $token ? JwtHandler::decode($token) : null;
$userId = $decoded ? (int)$decoded['user_id'] : 0;
// PŘIDÁNO: Zjištění role pro administrátorské zobrazení
$userRole = $decoded ? $decoded['role'] : 'user';

$db = (new Database())->getConnection();

if ($db) {
    try {
        if (isset($_GET['compact']) && $_GET['compact'] == 1) {
            $query = "SELECT b.id, b.name, b.brewery_id, 
                             IF(fav.id IS NOT NULL, 1, 0) as is_favorite,
                             IF(wl.id IS NOT NULL, 1, 0) as is_wishlist
                      FROM beers b 
                      LEFT JOIN user_favorites fav ON b.id = fav.entity_id AND fav.entity_type = 'beer' AND fav.user_id = :uid 
                      LEFT JOIN user_wishlists wl ON b.id = wl.entity_id AND wl.entity_type = 'beer' AND wl.user_id = :uid
                      WHERE b.is_approved = 1 ORDER BY b.name ASC";
            $stmt = $db->prepare($query);
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->execute();
            echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            exit();
        }

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
        $offset = ($page - 1) * $limit;

        $search = $_GET['search'] ?? '';
        $brewery = $_GET['brewery'] ?? '';
        $style = $_GET['style'] ?? '';
        $country = $_GET['country'] ?? '';
        
        $epm_min = isset($_GET['epm_min']) && $_GET['epm_min'] !== '' ? (float)$_GET['epm_min'] : null;
        $epm_max = isset($_GET['epm_max']) && $_GET['epm_max'] !== '' ? (float)$_GET['epm_max'] : null;
        $abv_min = isset($_GET['abv_min']) && $_GET['abv_min'] !== '' ? (float)$_GET['abv_min'] : null;
        $abv_max = isset($_GET['abv_max']) && $_GET['abv_max'] !== '' ? (float)$_GET['abv_max'] : null;
        $ibu_min = isset($_GET['ibu_min']) && $_GET['ibu_min'] !== '' ? (int)$_GET['ibu_min'] : null;
        $ibu_max = isset($_GET['ibu_max']) && $_GET['ibu_max'] !== '' ? (int)$_GET['ibu_max'] : null;

        $sort = $_GET['sort'] ?? 'name_asc';
        
        // PŘIDÁNO: Filtrování podle stavu schválení (pouze admin může vidět pending)
        $status = $_GET['status'] ?? 'approved';
        if ($status === 'pending' && $userRole === 'admin') {
            $whereParts = ["b.is_approved = 0"];
        } else {
            $whereParts = ["b.is_approved = 1"];
        }

        $params = [':uid' => $userId];

        function applyMultiSearch(&$whereParts, &$params, $inputValue, $dbColumn, $paramPrefix) {
            if (trim($inputValue) !== '') {
                $values = array_filter(array_map('trim', explode(',', $inputValue)));
                if (!empty($values)) {
                    $conditions = [];
                    foreach (array_values($values) as $index => $val) {
                        $paramKey = ":{$paramPrefix}_{$index}";
                        $conditions[] = "$dbColumn LIKE $paramKey";
                        $params[$paramKey] = "%{$val}%";
                    }
                    $whereParts[] = "(" . implode(" OR ", $conditions) . ")";
                }
            }
        }

        applyMultiSearch($whereParts, $params, $search, "b.name", "search");
        applyMultiSearch($whereParts, $params, $brewery, "br.name", "brewery");
        applyMultiSearch($whereParts, $params, $country, "c.name_cz", "country");

        if ($style !== '') {
            $whereParts[] = "b.style_id = :style";
            $params[':style'] = (int)$style;
        }

        if ($epm_min !== null) { $whereParts[] = "b.epm >= :epm_min"; $params[':epm_min'] = $epm_min; }
        if ($epm_max !== null) { $whereParts[] = "b.epm <= :epm_max"; $params[':epm_max'] = $epm_max; }
        if ($abv_min !== null) { $whereParts[] = "b.abv >= :abv_min"; $params[':abv_min'] = $abv_min; }
        if ($abv_max !== null) { $whereParts[] = "b.abv <= :abv_max"; $params[':abv_max'] = $abv_max; }
        if ($ibu_min !== null) { $whereParts[] = "b.ibu >= :ibu_min"; $params[':ibu_min'] = $ibu_min; }
        if ($ibu_max !== null) { $whereParts[] = "b.ibu <= :ibu_max"; $params[':ibu_max'] = $ibu_max; }

        $whereSql = "WHERE " . implode(" AND ", $whereParts);

        // ZMĚNA: Přidán JOIN i do COUNT dotazu kvůli konzistenci a hledání (pokud by se hledalo dle autora)
        $countQuery = "SELECT COUNT(DISTINCT b.id) as total FROM beers b LEFT JOIN breweries br ON b.brewery_id = br.id LEFT JOIN countries c ON br.country_id = c.id LEFT JOIN users u ON b.created_by = u.id $whereSql";
        $countParams = $params;
        unset($countParams[':uid']); 
        $countStmt = $db->prepare($countQuery);
        $countStmt->execute($countParams);
        $totalItems = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        $totalPages = ceil($totalItems / $limit);

        $orderBy = "is_favorite DESC"; 
        switch ($sort) {
            case 'name_desc': $orderBy .= ", b.name DESC"; break;
            case 'brewery_asc': $orderBy .= ", br.name ASC"; break;
            case 'brewery_desc': $orderBy .= ", br.name DESC"; break;
            case 'style_asc': $orderBy .= ", bs.name ASC"; break;
            case 'style_desc': $orderBy .= ", bs.name DESC"; break;
            case 'rating_desc': $orderBy .= ", b.avg_rating DESC"; break;
            case 'rating_asc': $orderBy .= ", b.avg_rating ASC"; break;
            case 'abv_desc': $orderBy .= ", b.abv DESC"; break;
            case 'abv_asc': $orderBy .= ", b.abv ASC"; break;
            case 'epm_desc': $orderBy .= ", b.epm DESC"; break;
            case 'epm_asc': $orderBy .= ", b.epm ASC"; break;
            case 'ibu_desc': $orderBy .= ", b.ibu DESC"; break;
            case 'ibu_asc': $orderBy .= ", b.ibu ASC"; break;
            case 'newest': $orderBy .= ", b.created_at DESC"; break;
            case 'oldest': $orderBy .= ", b.created_at ASC"; break;
            case 'name_asc': 
            default: $orderBy .= ", b.name ASC"; break;
        }

        // PŘIDÁNO: Připojení jména autora (created_by_user)
        $query = "SELECT b.*, br.name as brewery_name, br.lat as brewery_lat, br.lng as brewery_lng, c.name_cz as brewery_country, c.code as brewery_country_code, bs.name as style, u.username as created_by_user,
                         IF(fav.id IS NOT NULL, 1, 0) as is_favorite,
                         IF(wl.id IS NOT NULL, 1, 0) as is_wishlist
                  FROM beers b
                  LEFT JOIN breweries br ON b.brewery_id = br.id
                  LEFT JOIN countries c ON br.country_id = c.id
                  LEFT JOIN beer_styles bs ON b.style_id = bs.id
                  LEFT JOIN users u ON b.created_by = u.id
                  LEFT JOIN user_favorites fav ON b.id = fav.entity_id 
                       AND fav.entity_type = 'beer' 
                       AND fav.user_id = :uid
                  LEFT JOIN user_wishlists wl ON b.id = wl.entity_id 
                       AND wl.entity_type = 'beer' 
                       AND wl.user_id = :uid
                  $whereSql
                  ORDER BY $orderBy
                  LIMIT :limit OFFSET :offset";
                  
        $stmt = $db->prepare($query);
        foreach ($params as $key => $value) { $stmt->bindValue($key, $value); }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        echo json_encode([
            "status" => "success", 
            "data" => $stmt->fetchAll(PDO::FETCH_ASSOC),
            "pagination" => ["total" => (int)$totalItems, "total_pages" => (int)$totalPages, "current_page" => $page, "limit" => $limit]
        ]);
    } catch (PDOException $e) {
        error_log("DB Error (beers): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Vnitřní chyba při načítání piv."]);
    }
}
?>