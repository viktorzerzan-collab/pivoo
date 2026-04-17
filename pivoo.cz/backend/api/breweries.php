<?php
// backend/api/breweries.php
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
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 30;
        $offset = ($page - 1) * $limit;

        $search = $_GET['search'] ?? '';
        $city = $_GET['city'] ?? '';
        $country = $_GET['country'] ?? '';
        $sort = $_GET['sort'] ?? 'name_asc';

        $whereParts = ["br.is_approved = 1"];
        $params = [':uid' => $userId];

        if ($search !== '') {
            $whereParts[] = "br.name LIKE :search";
            $params[':search'] = "%{$search}%";
        }
        if ($city !== '') {
            $whereParts[] = "br.city = :city";
            $params[':city'] = $city;
        }
        if ($country !== '') {
            $whereParts[] = "br.country_id = :country";
            $params[':country'] = (int)$country;
        }

        $whereSql = "WHERE " . implode(" AND ", $whereParts);

        $countQuery = "SELECT COUNT(DISTINCT br.id) as total FROM breweries br $whereSql";
        $countParams = $params;
        unset($countParams[':uid']);
        $countStmt = $db->prepare($countQuery);
        $countStmt->execute($countParams);
        $totalItems = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        $totalPages = ceil($totalItems / $limit);

        // BEZPEČNÉ ŘAZENÍ
        $orderBy = "is_favorite DESC"; 
        switch ($sort) {
            case 'name_desc': $orderBy .= ", br.name DESC"; break;
            case 'city_asc': $orderBy .= ", br.city ASC"; break;
            case 'city_desc': $orderBy .= ", br.city DESC"; break;
            case 'rating_desc': $orderBy .= ", avg_rating DESC"; break;
            case 'rating_asc': $orderBy .= ", avg_rating ASC"; break;
            case 'newest': $orderBy .= ", br.created_at DESC"; break;
            case 'oldest': $orderBy .= ", br.created_at ASC"; break;
            case 'name_asc': 
            default: $orderBy .= ", br.name ASC"; break;
        }

        $query = "SELECT br.*, c.name_cz as country, c.code as country_code,
                         ROUND(AVG(NULLIF(cons.rating_beer, 0)), 1) as avg_rating, 
                         COUNT(DISTINCT b.id) as total_beers_in_catalog,
                         IF(fav.id IS NOT NULL, 1, 0) as is_favorite
                  FROM breweries br
                  LEFT JOIN countries c ON br.country_id = c.id
                  LEFT JOIN beers b ON br.id = b.brewery_id
                  LEFT JOIN consumptions cons ON b.id = cons.beer_id
                  LEFT JOIN user_favorites fav ON br.id = fav.entity_id 
                       AND fav.entity_type = 'brewery' 
                       AND fav.user_id = :uid
                  $whereSql
                  GROUP BY br.id
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
        error_log("DB Error (breweries): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Vnitřní chyba při načítání pivovarů."]);
    }
}
?>