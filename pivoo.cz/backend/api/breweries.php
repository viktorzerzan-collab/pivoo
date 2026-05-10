<?php
// backend/api/breweries.php
header("Access-Control-Allow-Origin: https://www.pivoo.cz");
header("Content-Type: application/json; charset=UTF-8");
require_once '../Database.php';
require_once '../JwtHandler.php';

$token = JwtHandler::getBearerToken();
$decoded = $token ? JwtHandler::decode($token) : null;
$userId = $decoded ? (int)$decoded['user_id'] : 0;
// PŘIDÁNO: Zjištění role
$userRole = $decoded ? $decoded['role'] : 'user';

$db = (new Database())->getConnection();

if ($db) {
    try {
        if (isset($_GET['compact']) && $_GET['compact'] == 1) {
            // ZMĚNA: Přidáno ORDER BY is_favorite DESC, aby se řadilo už v databázi
            $query = "SELECT br.id, br.name, 
                             IF(fav.id IS NOT NULL, 1, 0) as is_favorite,
                             IF(wl.id IS NOT NULL, 1, 0) as is_wishlist
                      FROM breweries br 
                      LEFT JOIN user_favorites fav ON br.id = fav.entity_id AND fav.entity_type = 'brewery' AND fav.user_id = :uid 
                      LEFT JOIN user_wishlists wl ON br.id = wl.entity_id AND wl.entity_type = 'brewery' AND wl.user_id = :uid
                      WHERE br.is_approved = 1 ORDER BY is_favorite DESC, br.name ASC";
            $stmt = $db->prepare($query);
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->execute();
            echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            exit();
        }

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 30;
        $offset = ($page - 1) * $limit;

        $search = $_GET['search'] ?? '';
        $city = $_GET['city'] ?? '';
        $country = $_GET['country'] ?? '';
        $sort = $_GET['sort'] ?? 'name_asc';

        $status = $_GET['status'] ?? 'approved';
        if ($status === 'pending' && $userRole === 'admin') {
            $whereParts = ["br.is_approved = 0"];
        } else {
            $whereParts = ["br.is_approved = 1"];
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

        applyMultiSearch($whereParts, $params, $search, "br.name", "search");
        applyMultiSearch($whereParts, $params, $city, "br.city", "city");
        applyMultiSearch($whereParts, $params, $country, "c.name_cz", "country");

        $whereSql = "WHERE " . implode(" AND ", $whereParts);

        // ZMĚNA: Odstraněny nepotřebné JOINy pro zrychlení COUNT dotazu
        $countJoins = "";
        if (trim($country) !== '') {
            $countJoins .= " LEFT JOIN countries c ON br.country_id = c.id";
        }
        $countQuery = "SELECT COUNT(br.id) as total FROM breweries br $countJoins $whereSql";
        
        $countParams = $params;
        unset($countParams[':uid']);
        $countStmt = $db->prepare($countQuery);
        $countStmt->execute($countParams);
        $totalItems = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        $totalPages = ceil($totalItems / $limit);

        $orderBy = "is_favorite DESC"; 
        switch ($sort) {
            case 'name_desc': $orderBy .= ", br.name DESC"; break;
            case 'city_asc': $orderBy .= ", br.city ASC"; break;
            case 'city_desc': $orderBy .= ", br.city DESC"; break;
            case 'rating_desc': $orderBy .= ", br.avg_rating DESC"; break;
            case 'rating_asc': $orderBy .= ", br.avg_rating ASC"; break;
            case 'newest': $orderBy .= ", br.created_at DESC"; break;
            case 'oldest': $orderBy .= ", br.created_at ASC"; break;
            case 'name_asc': 
            default: $orderBy .= ", br.name ASC"; break;
        }

        $query = "SELECT br.*, c.name_cz as country, c.code as country_code, u.username as created_by_user,
                         IF(fav.id IS NOT NULL, 1, 0) as is_favorite,
                         IF(wl.id IS NOT NULL, 1, 0) as is_wishlist
                  FROM breweries br
                  LEFT JOIN countries c ON br.country_id = c.id
                  LEFT JOIN users u ON br.created_by = u.id
                  LEFT JOIN user_favorites fav ON br.id = fav.entity_id 
                       AND fav.entity_type = 'brewery' 
                       AND fav.user_id = :uid
                  LEFT JOIN user_wishlists wl ON br.id = wl.entity_id 
                       AND wl.entity_type = 'brewery' 
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
        error_log("DB Error (breweries): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Vnitřní chyba při načítání pivovarů."]);
    }
}
?>