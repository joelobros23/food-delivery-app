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

require_once "../db_connection/config.php";

$action = $_POST['action'];
$store_owner_id = $_SESSION['id'];
$restaurant_id = null;

// Fetch the restaurant ID for the logged-in store owner
$sql_resto_info = "SELECT id FROM restaurants WHERE user_id = ? LIMIT 1";
if($stmt_resto = mysqli_prepare($link, $sql_resto_info)){
    mysqli_stmt_bind_param($stmt_resto, "i", $store_owner_id);
    mysqli_stmt_execute($stmt_resto);
    mysqli_stmt_bind_result($stmt_resto, $r_id);
    if(mysqli_stmt_fetch($stmt_resto)){
        $restaurant_id = $r_id;
    }
    mysqli_stmt_close($stmt_resto);
}

if(!$restaurant_id){
    $response['message'] = 'Could not find an associated restaurant for your account.';
    echo json_encode($response);
    exit;
}


switch ($action) {
    case 'add':
        if (empty($_POST['name']) || empty($_POST['category']) || !isset($_POST['price'])) { $response['message'] = 'Name, category, and price are required.'; break; }
        $name = trim($_POST['name']); $description = trim($_POST['description'] ?? ''); $category = trim($_POST['category']); $price = trim($_POST['price']); $image_url = trim($_POST['image_url'] ?? ''); $is_available = isset($_POST['is_available']) ? 1 : 0;
        $sql = "INSERT INTO menu_items (restaurant_id, name, description, category, price, image_url, is_available) VALUES (?, ?, ?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "isssdss", $restaurant_id, $name, $description, $category, $price, $image_url, $is_available);
            if (mysqli_stmt_execute($stmt)) {
                $new_id = mysqli_insert_id($link);
                $response = ['status' => 'success', 'message' => 'Item added!', 'item' => ['id' => $new_id, 'name' => $name, 'description' => $description, 'category' => $category, 'price' => $price, 'image_url' => $image_url, 'is_available' => $is_available]];
            } else { $response['message'] = 'Database error.'; }
            mysqli_stmt_close($stmt);
        }
        break;

    case 'fetch':
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
        $item_id = trim($_POST['item_id']); $name = trim($_POST['name']); $description = trim($_POST['description'] ?? ''); $category = trim($_POST['category']); $price = trim($_POST['price']); $image_url = trim($_POST['image_url'] ?? ''); $is_available = isset($_POST['is_available']) ? 1 : 0;
        $sql = "UPDATE menu_items SET name = ?, description = ?, category = ?, price = ?, image_url = ?, is_available = ? WHERE id = ? AND restaurant_id = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssdssii", $name, $description, $category, $price, $image_url, $is_available, $item_id, $restaurant_id);
            if(mysqli_stmt_execute($stmt)){
                 $response = ['status' => 'success', 'message' => 'Item updated!', 'item' => ['id' => $item_id, 'name' => $name, 'description' => $description, 'category' => $category, 'price' => $price, 'image_url' => $image_url, 'is_available' => $is_available]];
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
