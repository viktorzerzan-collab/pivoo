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
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 30;
        $offset = ($page - 1) * $limit;

        $search = $_GET['search'] ?? '';
        $city = $_GET['city'] ?? '';
        $country = $_GET['country'] ?? '';
        $sort = $_GET['sort'] ?? 'name_asc';

        $whereParts = ["loc.is_approved = 1"];
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

        applyMultiSearch($whereParts, $params, $search, "loc.name", "search");
        applyMultiSearch($whereParts, $params, $city, "loc.city", "city");
        applyMultiSearch($whereParts, $params, $country, "c.name_cz", "country");

        $whereSql = "WHERE " . implode(" AND ", $whereParts);

        $countQuery = "SELECT COUNT(DISTINCT loc.id) as total FROM locations loc LEFT JOIN countries c ON loc.country_id = c.id $whereSql";
        $countParams = $params;
        unset($countParams[':uid']);
        $countStmt = $db->prepare($countQuery);
        $countStmt->execute($countParams);
        $totalItems = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        $totalPages = ceil($totalItems / $limit);

        $orderBy = "is_favorite DESC"; 
        switch ($sort) {
            case 'name_desc': $orderBy .= ", loc.name DESC"; break;
            case 'city_asc': $orderBy .= ", loc.city ASC"; break;
            case 'city_desc': $orderBy .= ", loc.city DESC"; break;
            case 'rating_desc': $orderBy .= ", avg_rating DESC"; break;
            case 'rating_asc': $orderBy .= ", avg_rating ASC"; break;
            case 'newest': $orderBy .= ", loc.created_at DESC"; break;
            case 'oldest': $orderBy .= ", loc.created_at ASC"; break;
            case 'name_asc': 
            default: $orderBy .= ", loc.name ASC"; break;
        }

        $query = "SELECT loc.*, c.name_cz as country, c.code as country_code,
                         ROUND(AVG(NULLIF(cons.rating_beer, 0)), 1) as avg_rating, 
                         COUNT(cons.id) as total_visits,
                         IF(fav.id IS NOT NULL, 1, 0) as is_favorite
                  FROM locations loc
                  LEFT JOIN countries c ON loc.country_id = c.id
                  LEFT JOIN consumptions cons ON loc.id = cons.location_id
                  LEFT JOIN user_favorites fav ON loc.id = fav.entity_id 
                       AND fav.entity_type = 'location' 
                       AND fav.user_id = :uid
                  $whereSql
                  GROUP BY loc.id
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
        error_log("DB Error (locations): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Vnitřní chyba při načítání podniků."]);
    }
}
?>