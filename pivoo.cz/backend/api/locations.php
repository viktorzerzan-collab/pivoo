<?php
// backend/api/locations.php
header("Access-Control-Allow-Origin: https://www.pivoo.cz");
header("Content-Type: application/json; charset=UTF-8");
require_once '../Database.php';
require_once '../JwtHandler.php';

$token = JwtHandler::getBearerToken();
$decoded = $token ? JwtHandler::decode($token) : null;
$userId = $decoded ? (int)$decoded['user_id'] : 0;

$db = (new Database())->getConnection();

if ($db) {
    try {
        // 1. Získání parametrů z URL
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 30;
        $offset = ($page - 1) * $limit;

        $search = $_GET['search'] ?? '';
        $city = $_GET['city'] ?? '';
        $country = $_GET['country'] ?? '';
        $sort = $_GET['sort'] ?? 'name_asc';

        // 2. Sestavení dynamických WHERE podmínek
        $whereParts = ["l.is_approved = 1"];
        
        // OPRAVA: Odfiltrování typu 'jine' (Doma, Venku atd.) pro katalog.
        // Záznamy se zobrazí pouze pokud je v URL explicitně include_all=1.
        if (!isset($_GET['include_all']) || $_GET['include_all'] !== '1') {
            $whereParts[] = "l.type != 'jine'";
        }
        
        $params = [':uid' => $userId];

        if ($search !== '') {
            $whereParts[] = "l.name LIKE :search";
            $params[':search'] = "%{$search}%";
        }
        if ($city !== '') {
            $whereParts[] = "l.city = :city";
            $params[':city'] = $city;
        }
        if ($country !== '') {
            $whereParts[] = "l.country_id = :country";
            $params[':country'] = (int)$country;
        }

        $whereSql = "WHERE " . implode(" AND ", $whereParts);

        // 3. Zjištění celkového počtu záznamů (pro paginaci)
        $countQuery = "SELECT COUNT(DISTINCT l.id) as total 
                       FROM locations l
                       $whereSql";
        
        $countParams = $params;
        unset($countParams[':uid']); 
        
        $countStmt = $db->prepare($countQuery);
        $countStmt->execute($countParams);
        $totalItems = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        $totalPages = ceil($totalItems / $limit);

        // 4. Nastavení řazení (Whitelist přístup pro bezpečnost)
        $orderBy = "is_favorite DESC"; 
        switch ($sort) {
            case 'name_desc': $orderBy .= ", l.name DESC"; break;
            case 'city_asc': $orderBy .= ", l.city ASC"; break;
            case 'city_desc': $orderBy .= ", l.city DESC"; break;
            case 'rating_desc': $orderBy .= ", avg_rating DESC"; break;
            case 'rating_asc': $orderBy .= ", avg_rating ASC"; break;
            case 'newest': $orderBy .= ", l.created_at DESC"; break;
            case 'oldest': $orderBy .= ", l.created_at ASC"; break;
            case 'name_asc': 
            default: 
                $orderBy .= ", l.name ASC"; break;
        }

        // 5. Hlavní dotaz
        $query = "SELECT l.*, c.name_cz as country, c.code as country_code,
                         ROUND(AVG(NULLIF(cons.rating_care, 0)), 1) as avg_rating, 
                         COUNT(cons.id) as total_visits,
                         IF(fav.id IS NOT NULL, 1, 0) as is_favorite
                  FROM locations l
                  LEFT JOIN countries c ON l.country_id = c.id
                  LEFT JOIN consumptions cons ON l.id = cons.location_id
                  LEFT JOIN user_favorites fav ON l.id = fav.entity_id 
                       AND fav.entity_type = 'location' 
                       AND fav.user_id = :uid
                  $whereSql
                  GROUP BY l.id
                  ORDER BY $orderBy
                  LIMIT :limit OFFSET :offset";
                  
        $stmt = $db->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            "status" => "success", 
            "data" => $locations,
            "pagination" => [
                "total" => (int)$totalItems,
                "total_pages" => (int)$totalPages,
                "current_page" => $page,
                "limit" => $limit
            ]
        ]);
    } catch (PDOException $e) {
        error_log("DB Error (locations): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Vnitřní chyba při načítání lokací."]);
    }
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Chyba databáze."]);
}
?>