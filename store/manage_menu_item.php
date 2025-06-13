<?php
// store/manage_menu_item.php
session_start();
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'store') {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit;
}

if (!isset($_POST['action'])) {
    $response['message'] = 'No action specified.';
    echo json_encode($response);
    exit;
}

require_once "../app_config.php";

$action = $_POST['action'];
$store_owner_id = $_SESSION['id'];
$restaurant_id = null;

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($link === false) { 
    $response['message'] = 'Database connection failed.';
    echo json_encode($response);
    exit;
}

// Fetch the restaurant ID for the logged-in store owner
$sql_resto_info = "SELECT id FROM restaurants WHERE user_id = ? LIMIT 1";
if($stmt_resto = mysqli_prepare($link, $sql_resto_info)){
    mysqli_stmt_bind_param($stmt_resto, "i", $store_owner_id);
    mysqli_stmt_execute($stmt_resto);
    mysqli_stmt_bind_result($stmt_resto, $r_id);
    if(mysqli_stmt_fetch($stmt_resto)){ $restaurant_id = $r_id; }
    mysqli_stmt_close($stmt_resto);
}

if(!$restaurant_id){
    $response['message'] = 'Could not find an associated restaurant for your account.';
    echo json_encode($response);
    exit;
}

// --- Image Upload and Resize Function ---
function handle_image_upload($file, $item_id) {
    if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/menu_images/';
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
        
        $file_name = basename($file['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $new_file_name = "item_" . $item_id . '_' . time() . '.' . $file_ext;
        $target_path = $upload_dir . $new_file_name;
        
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_ext, $allowed_types)) {
            return ['error' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.'];
        }

        // --- Image Resizing Logic ---
        $max_resolution = 900;
        list($width, $height, $type) = getimagesize($file['tmp_name']);

        if ($width > $max_resolution || $height > $max_resolution) {
            $ratio = $width / $height;
            if ($ratio > 1) { // Landscape
                $new_width = $max_resolution;
                $new_height = $max_resolution / $ratio;
            } else { // Portrait or square
                $new_height = $max_resolution;
                $new_width = $max_resolution * $ratio;
            }
            $src = ($type == IMAGETYPE_JPEG) ? imagecreatefromjpeg($file['tmp_name']) : (($type == IMAGETYPE_PNG) ? imagecreatefrompng($file['tmp_name']) : imagecreatefromgif($file['tmp_name']));
            $dst = imagecreatetruecolor($new_width, $new_height);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            
            if ($type == IMAGETYPE_JPEG) { imagejpeg($dst, $target_path, 85); }
            elseif ($type == IMAGETYPE_PNG) { imagepng($dst, $target_path, 9); }
            else { imagegif($dst, $target_path); }
            imagedestroy($dst);
            imagedestroy($src);
        } else {
            if (!move_uploaded_file($file['tmp_name'], $target_path)) {
                return ['error' => 'Failed to move uploaded file.'];
            }
        }
        return ['success' => 'uploads/menu_images/' . $new_file_name];
    }
    return ['error' => 'No new image uploaded or an upload error occurred.'];
}


switch ($action) {
    case 'add':
        if (empty($_POST['name']) || empty($_POST['category']) || !isset($_POST['price'])) { $response['message'] = 'Name, category, and price are required.'; break; }
        
        $name = trim($_POST['name']); $description = trim($_POST['description'] ?? ''); $category = trim($_POST['category']); $price = trim($_POST['price']); $is_available = isset($_POST['is_available']) ? 1 : 0;
        $image_path = 'uploads/menu_images/default.png'; // Default image
        
        $sql = "INSERT INTO menu_items (restaurant_id, name, description, category, price, image_url, is_available) VALUES (?, ?, ?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "isssdss", $restaurant_id, $name, $description, $category, $price, $image_path, $is_available);
            if (mysqli_stmt_execute($stmt)) {
                $new_id = mysqli_insert_id($link);

                if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
                    $upload_result = handle_image_upload($_FILES['item_image'], $new_id);
                    if (isset($upload_result['success'])) {
                        $image_path = $upload_result['success'];
                        $update_sql = "UPDATE menu_items SET image_url = ? WHERE id = ?";
                        if ($update_stmt = mysqli_prepare($link, $update_sql)) {
                            mysqli_stmt_bind_param($update_stmt, "si", $image_path, $new_id);
                            mysqli_stmt_execute($update_stmt);
                            mysqli_stmt_close($update_stmt);
                        }
                    } else { $response['image_error'] = $upload_result['error']; }
                }

                $response = ['status' => 'success', 'message' => 'Item added!', 'item' => ['id' => $new_id, 'name' => $name, 'description' => $description, 'category' => $category, 'price' => $price, 'image_url' => $image_path, 'is_available' => $is_available]];
            } else { $response['message'] = 'Database error.'; }
            mysqli_stmt_close($stmt);
        }
        break;

    case 'fetch': // This case is used by the Edit button
        if (empty($_POST['item_id'])) { $response['message'] = 'Item ID not provided.'; break; }
        $item_id = trim($_POST['item_id']);
        $sql = "SELECT * FROM menu_items WHERE id = ? AND restaurant_id = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $item_id, $restaurant_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($item = mysqli_fetch_assoc($result)) {
                $response = ['status' => 'success', 'item' => $item];
            } else { $response['message'] = 'Item not found or you do not have permission to edit it.'; }
            mysqli_stmt_close($stmt);
        }
        break;

    case 'edit':
         if (empty($_POST['item_id']) || empty($_POST['name']) || empty($_POST['category']) || !isset($_POST['price'])) { $response['message'] = 'Required fields are missing.'; break; }
        
        $item_id = trim($_POST['item_id']); $name = trim($_POST['name']); $description = trim($_POST['description'] ?? ''); $category = trim($_POST['category']); $price = trim($_POST['price']); $is_available = isset($_POST['is_available']) ? 1 : 0;

        $image_path_sql = "SELECT image_url FROM menu_items WHERE id = ? AND restaurant_id = ?";
        if($img_stmt = mysqli_prepare($link, $image_path_sql)){
            mysqli_stmt_bind_param($img_stmt, "ii", $item_id, $restaurant_id);
            mysqli_stmt_execute($img_stmt);
            mysqli_stmt_bind_result($img_stmt, $current_image_path);
            mysqli_stmt_fetch($img_stmt);
            mysqli_stmt_close($img_stmt);
        }
        
        $image_path = $current_image_path;

        if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] == UPLOAD_ERR_OK) {
            $upload_result = handle_image_upload($_FILES['item_image'], $item_id);
            if(isset($upload_result['success'])){
                $image_path = $upload_result['success'];
                if ($current_image_path && $current_image_path != 'uploads/menu_images/default.png' && file_exists('../' . $current_image_path)) {
                    unlink('../' . $current_image_path);
                }
            } else { $response['image_error'] = $upload_result['error']; }
        }
        
        $sql = "UPDATE menu_items SET name = ?, description = ?, category = ?, price = ?, image_url = ?, is_available = ? WHERE id = ? AND restaurant_id = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssdssii", $name, $description, $category, $price, $image_path, $is_available, $item_id, $restaurant_id);
            if(mysqli_stmt_execute($stmt)){
                 $response = ['status' => 'success', 'message' => 'Item updated!', 'item' => ['id' => $item_id, 'name' => $name, 'description' => $description, 'category' => $category, 'price' => $price, 'image_url' => $image_path, 'is_available' => $is_available]];
            } else { $response['message'] = 'Failed to update item.'; }
            mysqli_stmt_close($stmt);
        }
        break;

        case 'delete':
        if (empty($_POST['item_id'])) { $response['message'] = 'Item ID not provided.'; break; }
        $item_id = trim($_POST['item_id']);
        $sql = "DELETE FROM menu_items WHERE id = ? AND restaurant_id = ?";
        if($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $item_id, $restaurant_id);
            if(mysqli_stmt_execute($stmt)){
                if(mysqli_stmt_affected_rows($stmt) > 0){
                    $response = ['status' => 'success', 'message' => 'Item deleted!'];
                } else {
                    $response['message'] = 'Item not found or you do not have permission.';
                }
            } else { $response['message'] = 'Failed to delete item.'; }
            mysqli_stmt_close($stmt);
        }
        break;

    default:
        $response['message'] = 'Invalid action.';
        break;
}

mysqli_close($link);
echo json_encode($response);
?>
