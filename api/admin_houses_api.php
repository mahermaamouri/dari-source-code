<?php
// api/admin_houses_api.php

// --- Start Output Buffering ---
ob_start();

// --- Header and Initialization ---
header('Content-Type: application/json');
require_once __DIR__ . '/../config/init.php';

// Admin Authentication Check
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) {
    ob_clean(); // Clean buffer before sending error
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé.']);
    exit;
}

global $pdo;
if (!$pdo) {
    ob_clean(); // Clean buffer before sending error
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

$action = $_REQUEST['action'] ?? null;

// Define upload directory
define('UPLOAD_DIR_HOUSES', ROOT_PATH . '/assets/images/houses/');
if (!is_dir(UPLOAD_DIR_HOUSES)) {
    if (!mkdir(UPLOAD_DIR_HOUSES, 0775, true)) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Impossible de créer le répertoire de téléchargement.']);
        exit;
    }
}

// Helper function to safely delete a file
function deleteImageFile($filename) {
    if (!empty($filename) && file_exists(UPLOAD_DIR_HOUSES . $filename)) {
        unlink(UPLOAD_DIR_HOUSES . $filename);
    }
}

function handleImageUpload($fileInputName, $houseId = null) {
    $uploadedImagePaths = [];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $maxFileSize = 5 * 1024 * 1024; // 5 MB

    if (isset($_FILES[$fileInputName]) && is_array($_FILES[$fileInputName]['name'])) {
        $numFiles = count($_FILES[$fileInputName]['name']);
        for ($i = 0; $i < $numFiles; $i++) {
            if ($_FILES[$fileInputName]['error'][$i] === UPLOAD_ERR_OK) {
                if (!in_array($_FILES[$fileInputName]['type'][$i], $allowedTypes)) throw new Exception("Type de fichier non autorisé: " . $_FILES[$fileInputName]['type'][$i], 400);
                if ($_FILES[$fileInputName]['size'][$i] > $maxFileSize) throw new Exception("Fichier trop volumineux (Max 5MB).", 400);
                
                $fileExtension = strtolower(pathinfo($_FILES[$fileInputName]['name'][$i], PATHINFO_EXTENSION));
                $uniqueFileName = uniqid('house_' . ($houseId ?? 'temp') . '_', true) . '.' . $fileExtension;
                $uploadPath = UPLOAD_DIR_HOUSES . $uniqueFileName;

                if (move_uploaded_file($_FILES[$fileInputName]['tmp_name'][$i], $uploadPath)) {
                    $uploadedImagePaths[] = $uniqueFileName;
                } else {
                    throw new Exception("Échec du téléchargement de l'image.", 500);
                }
            } else if ($_FILES[$fileInputName]['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                // Throw an error if it's not a "no file" error
                throw new Exception("Erreur de téléchargement de fichier. Code: " . $_FILES[$fileInputName]['error'][$i], 500);
            }
        }
    }
    return $uploadedImagePaths;
}

try {
    $response_data = []; // Initialize response data array

    switch ($action) {
        case 'list':
            $stmt = $pdo->query("SELECT h.*, (SELECT GROUP_CONCAT(f.feature_name) FROM House_Amenity_Junction haj JOIN Features f ON haj.feature_id = f.feature_id WHERE haj.house_id = h.house_id) as features_summary FROM Houses h ORDER BY h.created_at DESC");
            $houses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($houses as &$house) {
                if (!empty($house['main_image_url']) && !filter_var($house['main_image_url'], FILTER_VALIDATE_URL)) {
                    $house['main_image_url'] = ASSETS_PATH . '/images/houses/' . $house['main_image_url'];
                }
            }
            unset($house);
            $response_data = ['success' => true, 'houses' => $houses];
            break;

        case 'get_features':
            $stmt = $pdo->query("SELECT feature_id, feature_name, icon_class FROM Features ORDER BY feature_name ASC");
            $features = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response_data = ['success' => true, 'features' => $features];
            break;
        
        case 'get_house_details':
            $house_id = filter_input(INPUT_GET, 'house_id', FILTER_VALIDATE_INT);
            if (!$house_id) throw new Exception("ID invalide.", 400);
            $stmt_house = $pdo->prepare("SELECT * FROM Houses WHERE house_id = :id");
            $stmt_house->execute([':id' => $house_id]);
            $house = $stmt_house->fetch(PDO::FETCH_ASSOC);
            if (!$house) throw new Exception("Propriété non trouvée.", 404);

            $stmt_images = $pdo->prepare("SELECT image_id, image_url, is_primary FROM House_Images WHERE house_id = :id ORDER BY is_primary DESC, image_id ASC");
            $stmt_images->execute([':id' => $house_id]);
            $images_data = $stmt_images->fetchAll(PDO::FETCH_ASSOC);
            foreach($images_data as &$img) {
                if (!empty($img['image_url'])) {
                    $img['full_url'] = !filter_var($img['image_url'], FILTER_VALIDATE_URL) ? ASSETS_PATH . '/images/houses/' . $img['image_url'] : $img['image_url'];
                }
            }
            unset($img);
            $house['images_data'] = $images_data;

            $stmt_features = $pdo->prepare("SELECT feature_id FROM House_Amenity_Junction WHERE house_id = :id");
            $stmt_features->execute([':id' => $house_id]);
            $house['feature_ids'] = $stmt_features->fetchAll(PDO::FETCH_COLUMN);
            $response_data = ['success' => true, 'house' => $house];
            break;

        case 'add_house':
        case 'update_house':
            $pdo->beginTransaction();
            $isUpdate = ($action === 'update_house');
            $house_id = $isUpdate ? filter_input(INPUT_POST, 'house_id', FILTER_VALIDATE_INT) : null;
            if ($isUpdate && !$house_id) throw new Exception("ID de propriété manquant pour la mise à jour.", 400);

            $title = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING));
            if (empty($title)) throw new Exception("Le titre est requis.", 400);
            
            $description = trim($_POST['description'] ?? '');

            $location = trim(filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING));
            $latitude = filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE) ?: null;
            $longitude = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE) ?: null;

            $price_per_night = filter_input(INPUT_POST, 'price_per_night', FILTER_VALIDATE_FLOAT);
            $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 1, 'max_range' => 5]]) ?: null;
            $surface_area_sqm = filter_input(INPUT_POST, 'surface_area_sqm', FILTER_VALIDATE_INT) ?: null;
            $bedrooms = filter_input(INPUT_POST, 'bedrooms', FILTER_VALIDATE_INT);
            $bathrooms = filter_input(INPUT_POST, 'bathrooms', FILTER_VALIDATE_FLOAT);
            $max_guests = filter_input(INPUT_POST, 'max_guests', FILTER_VALIDATE_INT) ?: null;
            $availability_status = filter_input(INPUT_POST, 'availability_status', FILTER_SANITIZE_STRING);
            $feature_ids_string = filter_input(INPUT_POST, 'feature_ids_string', FILTER_SANITIZE_STRING);


            if ($isUpdate) {
                $sql_house = "UPDATE Houses SET title=:title, location=:location, description=:description, price_per_night=:price, rating=:rating, surface_area_sqm=:surface, bedrooms=:bedrooms, bathrooms=:bathrooms, max_guests=:guests, availability_status=:status, latitude=:latitude, longitude=:longitude, admin_id_updated=:admin_id WHERE house_id=:id";
                $stmt_house = $pdo->prepare($sql_house);
                $stmt_house->execute([':title'=>$title, ':location'=>$location, ':description'=>$description, ':price'=>$price_per_night, ':rating' => $rating, ':surface'=>$surface_area_sqm, ':bedrooms'=>$bedrooms, ':bathrooms'=>$bathrooms, ':guests'=>$max_guests, ':status'=>$availability_status, ':latitude' => $latitude, ':longitude' => $longitude, ':admin_id'=>$_SESSION['admin_id'], ':id'=>$house_id]);
            } else {
                $sql_house = "INSERT INTO Houses (title, location, description, price_per_night, rating, surface_area_sqm, bedrooms, bathrooms, max_guests, availability_status, latitude, longitude, admin_id_created) VALUES (:title, :location, :description, :price, :rating, :surface, :bedrooms, :bathrooms, :guests, :status, :latitude, :longitude, :admin_id)";
                $stmt_house = $pdo->prepare($sql_house);
                $stmt_house->execute([':title'=>$title, ':location'=>$location, ':description'=>$description, ':price'=>$price_per_night, ':rating' => $rating, ':surface'=>$surface_area_sqm, ':bedrooms'=>$bedrooms, ':bathrooms'=>$bathrooms, ':guests'=>$max_guests, ':status'=>$availability_status, ':latitude' => $latitude, ':longitude' => $longitude, ':admin_id'=>$_SESSION['admin_id']]);
                $house_id = $pdo->lastInsertId();
            }

            $pdo->prepare("DELETE FROM House_Amenity_Junction WHERE house_id = :id")->execute([':id' => $house_id]);
            if (!empty($feature_ids_string)) {
                $feature_ids = array_filter(array_map('intval', explode(',', $feature_ids_string)));
                if (!empty($feature_ids)) {
                    $stmt_feature_assoc = $pdo->prepare("INSERT INTO House_Amenity_Junction (house_id, feature_id) VALUES (:house_id, :feature_id)");
                    foreach ($feature_ids as $feature_id) {
                        $stmt_feature_assoc->execute([':house_id' => $house_id, ':feature_id' => $feature_id]);
                    }
                }
            }
            
            $uploadedImageFilenames = handleImageUpload('images', $house_id);
            if (!empty($uploadedImageFilenames)) {
                $stmt_check_images = $pdo->prepare("SELECT COUNT(*) FROM House_Images WHERE house_id = :id");
                $stmt_check_images->execute([':id' => $house_id]);
                $isFirstImage = !$isUpdate || ($stmt_check_images->fetchColumn() == 0);
                $sql_image = "INSERT INTO House_Images (house_id, image_url, is_primary) VALUES (:house_id, :image_url, :is_primary)";
                $stmt_image = $pdo->prepare($sql_image);
                foreach ($uploadedImageFilenames as $filename) {
                    $stmt_image->execute([':house_id' => $house_id, ':image_url' => $filename, ':is_primary' => $isFirstImage ? 1 : 0]);
                    if ($isFirstImage) {
                        $pdo->prepare("UPDATE Houses SET main_image_url = :main_image WHERE house_id = :id")->execute([':main_image' => $filename, ':id' => $house_id]);
                        $isFirstImage = false;
                    }
                }
            }

            $pdo->commit();
            $response_data = ['success' => true, 'message' => 'Propriété sauvegardée avec succès!', 'house_id' => $house_id];
            break;
            
        case 'delete_house':
            $pdo->beginTransaction();
            $house_id = filter_input(INPUT_POST, 'house_id', FILTER_VALIDATE_INT);
            if (!$house_id) throw new Exception("ID de propriété invalide.", 400);
            $stmt_images = $pdo->prepare("SELECT image_url FROM House_Images WHERE house_id = :id");
            $stmt_images->execute([':id' => $house_id]);
            $images_to_delete = $stmt_images->fetchAll(PDO::FETCH_COLUMN);
            $stmt_delete = $pdo->prepare("DELETE FROM Houses WHERE house_id = :id");
            $stmt_delete->execute([':id' => $house_id]);
            if ($stmt_delete->rowCount() > 0) {
                foreach ($images_to_delete as $filename) {
                    deleteImageFile($filename);
                }
                $pdo->commit();
                $response_data = ['success' => true, 'message' => 'Propriété et images associées supprimées avec succès.'];
            } else {
                throw new Exception("Propriété non trouvée.", 404);
            }
            break;

        case 'delete_image':
            $pdo->beginTransaction();
            $image_id = filter_input(INPUT_POST, 'image_id', FILTER_VALIDATE_INT);
            if (!$image_id) throw new Exception("ID d'image invalide.", 400);
            $stmt_img = $pdo->prepare("SELECT house_id, image_url, is_primary FROM House_Images WHERE image_id = :id");
            $stmt_img->execute([':id' => $image_id]);
            $image = $stmt_img->fetch(PDO::FETCH_ASSOC);
            if (!$image) throw new Exception("Image non trouvée.", 404);
            deleteImageFile($image['image_url']);
            $pdo->prepare("DELETE FROM House_Images WHERE image_id = :id")->execute([':id' => $image_id]);
            if ($image['is_primary']) {
                $stmt_next = $pdo->prepare("SELECT image_id, image_url FROM House_Images WHERE house_id = :house_id ORDER BY image_id ASC LIMIT 1");
                $stmt_next->execute([':house_id' => $image['house_id']]);
                $next_image = $stmt_next->fetch(PDO::FETCH_ASSOC);
                $new_main_image_url = null;
                if ($next_image) {
                    $pdo->prepare("UPDATE House_Images SET is_primary = 1 WHERE image_id = :id")->execute([':id' => $next_image['image_id']]);
                    $new_main_image_url = $next_image['image_url'];
                }
                $pdo->prepare("UPDATE Houses SET main_image_url = :main_image WHERE house_id = :house_id")->execute([':main_image' => $new_main_image_url, ':house_id' => $image['house_id']]);
            }
            $pdo->commit();
            $response_data = ['success' => true, 'message' => 'Image supprimée avec succès.'];
            break;

        default:
            throw new Exception('Action non reconnue.', 400);
    }
    
    // --- Send final, clean response ---
    ob_clean();
    echo json_encode($response_data);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    ob_clean();
    http_response_code(500);
    $error_detail = DEVELOPMENT_MODE ? "PDO: " . $e->getMessage() : 'Erreur de base de données.';
    error_log("Admin Houses API PDOException: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $error_detail]);

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    ob_clean();
    $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
    http_response_code($statusCode);
    $error_detail = (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) ? "Ex: " . $e->getMessage() : $e->getMessage();
    error_log("Admin Houses API Exception: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'detail' => $error_detail]);
} finally {
    ob_end_flush();
}
?>
