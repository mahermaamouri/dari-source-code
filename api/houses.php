<?php
// api/houses.php
// This public API handles all data fetching for the user-facing parts of the website.

ob_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/init.php';

global $pdo;
if (!$pdo) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

$action = $_GET['action'] ?? null;

try {
    if ($action === 'getAll') {
        // --- BASE QUERY ---
        $sql = "SELECT DISTINCT
                    h.house_id, h.title, h.location, h.price_per_night, 
                    h.bedrooms, h.bathrooms, h.surface_area_sqm, 
                    h.main_image_url, h.rating, h.created_at, h.min_stay_nights,
                    (SELECT GROUP_CONCAT(CONCAT_WS('::', f.feature_name, f.icon_class) SEPARATOR ';;') 
                     FROM House_Amenity_Junction haj_feat
                     JOIN Features f ON haj_feat.feature_id = f.feature_id
                     WHERE haj_feat.house_id = h.house_id
                    ) AS features_list,
                    (
                        COUNT(CASE WHEN ha.event_type = 'house_view' THEN 1 END) + 
                        (COUNT(CASE WHEN ha.event_type = 'house_click' THEN 1 END) * 5)
                    ) as popularity_score
                FROM Houses h
                LEFT JOIN House_Analytics ha ON h.house_id = ha.house_id";
        
        $whereClauses = [];
        $params = [];
        
        if (!isset($_GET['bedrooms_similar'])) {
            $whereClauses[] = "h.availability_status = 'available'";
        }
        
        // --- Filtering Logic ---
        if (isset($_GET['bedrooms']) && !empty($_GET['bedrooms'])) {
            $bedrooms = explode(',', $_GET['bedrooms']);
            $bedConditions = [];
            foreach($bedrooms as $i => $bed) {
                $paramName = ":bed" . $i;
                if (strpos($bed, '+') !== false) {
                    $bedConditions[] = "h.bedrooms >= " . $paramName;
                    $params[$paramName] = (int)$bed;
                } else {
                    $bedConditions[] = "h.bedrooms = " . $paramName;
                     $params[$paramName] = (int)$bed;
                }
            }
            if(!empty($bedConditions)) {
                 $whereClauses[] = "(" . implode(" OR ", $bedConditions) . ")";
            }
        }

        if (isset($_GET['bathrooms']) && !empty($_GET['bathrooms'])) {
             $bathrooms = explode(',', $_GET['bathrooms']);
             $bathConditions = [];
             foreach($bathrooms as $i => $bath) {
                $paramName = ":bath" . $i;
                if (strpos($bath, '+') !== false) {
                    $bathConditions[] = "h.bathrooms >= " . $paramName;
                    $params[$paramName] = (int)$bath;
                } else {
                    $bathConditions[] = "h.bathrooms = " . $paramName;
                    $params[$paramName] = (int)$bath;
                }
            }
             if(!empty($bathConditions)) {
                 $whereClauses[] = "(" . implode(" OR ", $bathConditions) . ")";
            }
        }
        
        if (!empty($_GET['amenities']) && is_array($_GET['amenities'])) {
            $amenities = array_filter(array_map('trim', $_GET['amenities']));
            if (count($amenities) > 0) {
                $subQueryParts = [];
                foreach ($amenities as $index => $amenity) {
                    $paramName = ":amenity_check_" . $index;
                    $subQueryParts[] = "EXISTS (SELECT 1 FROM House_Amenity_Junction haj_s JOIN Features f_s ON haj_s.feature_id = f_s.feature_id WHERE haj_s.house_id = h.house_id AND f_s.feature_name = {$paramName})";
                    $params[$paramName] = $amenity;
                }
                $whereClauses[] = "(" . implode(" AND ", $subQueryParts) . ")";
            }
        }

        if (!empty($_GET['startDate']) && !empty($_GET['endDate'])) {
            $whereClauses[] = "NOT EXISTS (
                SELECT 1 FROM Reservations r
                WHERE r.house_id = h.house_id
                AND r.status = 'confirmed'
                AND r.start_date < :endDate_res
                AND r.end_date > :startDate_res
            ) AND NOT EXISTS (
                 SELECT 1 FROM House_Availability ha_check
                 WHERE ha_check.house_id = h.house_id
                 AND ha_check.status = 'unavailable'
                 AND ha_check.available_date >= :startDate_avail
                 AND ha_check.available_date < :endDate_avail
            )";
            $params[':startDate_res'] = $_GET['startDate'];
            $params[':endDate_res'] = $_GET['endDate'];
            $params[':startDate_avail'] = $_GET['startDate'];
            $params[':endDate_avail'] = $_GET['endDate'];
        }
        
        if (isset($_GET['bedrooms_similar']) && isset($_GET['exclude_id'])) {
            $bedrooms_similar = (int)$_GET['bedrooms_similar'];
            $exclude_id = (int)$_GET['exclude_id'];
            if ($bedrooms_similar > 0 && $exclude_id > 0) {
                $whereClauses[] = "h.bedrooms = :bedrooms_similar AND h.house_id != :exclude_id";
                $params[':bedrooms_similar'] = $bedrooms_similar;
                $params[':exclude_id'] = $exclude_id;
            }
        }

        $minPrice = filter_input(INPUT_GET, 'minPrice', FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);
        $maxPrice = filter_input(INPUT_GET, 'maxPrice', FILTER_VALIDATE_INT, ['options' => ['default' => 99999]]);
        
        $whereClauses[] = "h.price_per_night BETWEEN :minPrice AND :maxPrice";
        $params[':minPrice'] = $minPrice;
        $params[':maxPrice'] = $maxPrice;
        
        if (count($whereClauses) > 0) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }
        
        $sql .= " GROUP BY h.house_id, h.title, h.location, h.price_per_night, h.bedrooms, h.bathrooms, h.surface_area_sqm, h.main_image_url, h.rating, h.created_at, h.min_stay_nights";

        $sortBy = $_GET['sortBy'] ?? 'popularity';
        switch ($sortBy) {
            case 'price_asc':
                $sql .= " ORDER BY h.price_per_night ASC";
                break;
            case 'price_desc':
                $sql .= " ORDER BY h.price_per_night DESC";
                break;
            case 'newest':
                $sql .= " ORDER BY h.created_at DESC";
                break;
            case 'popularity':
            default:
                $sql .= " ORDER BY popularity_score DESC, h.created_at DESC";
                break;
        }
        
        if (isset($_GET['limit']) && (int)$_GET['limit'] > 0) {
            $limit = (int)$_GET['limit'];
            $sql .= " LIMIT " . $limit;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $houses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format data for the frontend
        foreach ($houses as &$house) {
            $house['features'] = $house['features_list'];
            unset($house['features_list']);
            
            $imageUrl = $house['main_image_url'] ?? null;
            if (empty($imageUrl)) {
                $img_stmt = $pdo->prepare("SELECT image_url FROM House_Images WHERE house_id = :house_id ORDER BY is_primary DESC, image_id ASC LIMIT 1");
                $img_stmt->execute(['house_id' => $house['house_id']]);
                $image_record = $img_stmt->fetch(PDO::FETCH_ASSOC);
                $imageUrl = $image_record['image_url'] ?? null;
            }

            if (!empty($imageUrl)) {
                $house['image'] = !filter_var($imageUrl, FILTER_VALIDATE_URL) ? ASSETS_PATH . '/images/houses/' . $imageUrl : $imageUrl;
            } else {
                $house['image'] = null;
            }

            $house['id'] = $house['house_id'];
            $house['price'] = $house['price_per_night'];
            $house['surface'] = $house['surface_area_sqm'];
        }
        unset($house);

        $response_data = $houses;

} elseif ($action === 'getById') {
        $house_id = (int)($_GET['id'] ?? 0);
        if ($house_id <= 0) throw new Exception('Invalid house ID.', 400);
        
        $sql = "SELECT h.*, 
                   (SELECT GROUP_CONCAT(CONCAT_WS('::', f.feature_name, f.icon_class) SEPARATOR ';;') 
                    FROM House_Amenity_Junction haj 
                    JOIN Features f ON haj.feature_id = f.feature_id 
                    WHERE haj.house_id = h.house_id) AS features_list 
                FROM Houses h 
                WHERE h.house_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $house_id]);
        $house = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($house) {
            $img_stmt = $pdo->prepare("SELECT image_url, alt_text FROM House_Images WHERE house_id = :id ORDER BY is_primary DESC, image_id ASC");
            $img_stmt->execute(['id' => $house['house_id']]);
            $house['images_data'] = array_map(function($img) {
                if (!empty($img['image_url'])) {
                    $img['image_url'] = !filter_var($img['image_url'], FILTER_VALIDATE_URL) ? ASSETS_PATH . '/images/houses/' . $img['image_url'] : $img['image_url'];
                }
                return $img;
            }, $img_stmt->fetchAll(PDO::FETCH_ASSOC));

            $mainImageUrl = $house['main_image_url'] ?? ($house['images_data'][0]['image_url'] ?? null);
            if (!empty($mainImageUrl)) {
                 $house['image'] = !filter_var($mainImageUrl, FILTER_VALIDATE_URL) ? ASSETS_PATH . '/images/houses/' . $mainImageUrl : $mainImageUrl;
            } else {
                $house['image'] = null;
            }
            
            $house['id'] = $house['house_id'];
            $house['price'] = $house['price_per_night'];
            $house['surface'] = $house['surface_area_sqm'];
            $house['features'] = $house['features_list'];
            unset($house['features_list']);

            // ✅ NEW: Fetch confirmed reservations to disable dates in the calendar
            $reservations_stmt = $pdo->prepare("SELECT start_date, end_date FROM Reservations WHERE house_id = :house_id AND status = 'confirmed'");
            $reservations_stmt->execute([':house_id' => $house_id]);
            $house['confirmed_reservations'] = $reservations_stmt->fetchAll(PDO::FETCH_ASSOC);


            $response_data = $house;
        } else {
            throw new Exception('House not found.', 404);
        }

    } elseif ($action === 'get_availability_for_house') {
        $house_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$house_id) throw new Exception("ID manquant.", 400);
        
        // Fetch custom daily prices and statuses
        $stmt_avail = $pdo->prepare("SELECT available_date, price, status FROM House_Availability WHERE house_id = ? AND available_date >= CURDATE()");
        $stmt_avail->execute([$house_id]);
        $availability_rows = $stmt_avail->fetchAll(PDO::FETCH_ASSOC);

        $availability = [];
        $unavailable_by_admin = [];
        foreach($availability_rows as $row) {
            // Store custom prices
            $availability[$row['available_date']] = [
                'price' => $row['price']
            ];
            // Store manually closed days
            if ($row['status'] === 'unavailable') {
                $unavailable_by_admin[] = $row['available_date'];
            }
        }
        
        // Fetch confirmed reservations
        $stmt_reservations = $pdo->prepare("SELECT start_date, end_date FROM Reservations WHERE house_id = ? AND status = 'confirmed'");
        $stmt_reservations->execute([$house_id]);
        $reservations = $stmt_reservations->fetchAll(PDO::FETCH_ASSOC);

        $response_data = [
            'custom_prices' => $availability, 
            'reservations' => $reservations,
            'unavailable_by_admin' => $unavailable_by_admin
        ];

    } elseif ($action === 'get_price_quote') {
        $house_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $start_date_str = $_GET['start_date'] ?? null;
        $end_date_str = $_GET['end_date'] ?? null;

        if (!$house_id || !$start_date_str || !$end_date_str) {
            throw new Exception("Paramètres manquants pour le calcul du prix.", 400);
        }
        
        $start_date = new DateTime($start_date_str);
        $end_date = new DateTime($end_date_str);
        $total_price = 0;
        $nights = 0;
        
        // Fetch all custom prices for the range in one go
        $stmt_custom = $pdo->prepare("SELECT available_date, price FROM House_Availability WHERE house_id = ? AND available_date >= ? AND available_date < ?");
        $stmt_custom->execute([$house_id, $start_date->format('Y-m-d'), $end_date->format('Y-m-d')]);
        $custom_prices = $stmt_custom->fetchAll(PDO::FETCH_KEY_PAIR);

        // Get the default price once
        $stmt_house = $pdo->prepare("SELECT price_per_night FROM Houses WHERE house_id = ?");
        $stmt_house->execute([$house_id]);
        $default_price = $stmt_house->fetchColumn();

        $current_date = clone $start_date;
        while ($current_date < $end_date) {
            $date_key = $current_date->format('Y-m-d');
            // Use custom price if it exists, otherwise use default
            $total_price += $custom_prices[$date_key] ?? $default_price;
            
            $nights++;
            $current_date->modify('+1 day');
        }
        
        $response_data = ['success' => true, 'total_price' => $total_price, 'nights' => $nights];

    } else {
        throw new Exception('Action non reconnue.', 400);
    }

    ob_clean();
    echo json_encode($response_data);

} catch (Exception $e) {
    ob_clean();
    $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
    http_response_code($statusCode);
    error_log("Public Houses API Exception: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    ob_end_flush();
}