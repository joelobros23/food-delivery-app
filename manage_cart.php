<?php
// manage_cart.php
require_once "app_config.php";
session_start();
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'customer') {
    $response['message'] = 'Please log in to add items to your cart.';
    echo json_encode($response);
    exit;
}

if (!isset($_POST['item_id'], $_POST['quantity'])) {
    $response['message'] = 'Item details not provided.';
    echo json_encode($response);
    exit;
}

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($link === false) { die("DB Connection Error"); }

$customer_id = $_SESSION['id'];
$item_id = (int)$_POST['item_id'];
$quantity = (int)$_POST['quantity'];

if ($quantity < 1) {
    $response['message'] = 'Invalid quantity.';
    echo json_encode($response);
    exit;
}

// Check if the item already exists in the cart for this user
$sql_check = "SELECT id, quantity FROM cart_items WHERE customer_id = ? AND item_id = ?";
if ($stmt_check = mysqli_prepare($link, $sql_check)) {
    mysqli_stmt_bind_param($stmt_check, "ii", $customer_id, $item_id);
    mysqli_stmt_execute($stmt_check);
    $result = mysqli_stmt_get_result($stmt_check);
    
    if (mysqli_num_rows($result) > 0) {
        // --- Item exists, UPDATE the quantity ---
        $existing_item = mysqli_fetch_assoc($result);
        $new_quantity = $existing_item['quantity'] + $quantity;
        $sql_update = "UPDATE cart_items SET quantity = ? WHERE id = ?";
        if ($stmt_update = mysqli_prepare($link, $sql_update)) {
            mysqli_stmt_bind_param($stmt_update, "ii", $new_quantity, $existing_item['id']);
            mysqli_stmt_execute($stmt_update);
            $response = ['status' => 'success', 'message' => 'Cart updated!'];
        }
    } else {
        // --- Item does not exist, INSERT a new row ---
        $sql_insert = "INSERT INTO cart_items (customer_id, item_id, quantity) VALUES (?, ?, ?)";
        if ($stmt_insert = mysqli_prepare($link, $sql_insert)) {
            mysqli_stmt_bind_param($stmt_insert, "iii", $customer_id, $item_id, $quantity);
            mysqli_stmt_execute($stmt_insert);
            $response = ['status' => 'success', 'message' => 'Item added to cart!'];
        }
    }
    mysqli_stmt_close($stmt_check);
}

// Get the new total cart count
$total_items = 0;
$sql_count = "SELECT SUM(quantity) as total FROM cart_items WHERE customer_id = ?";
if ($stmt_count = mysqli_prepare($link, $sql_count)) {
    mysqli_stmt_bind_param($stmt_count, "i", $customer_id);
    mysqli_stmt_execute($stmt_count);
    mysqli_stmt_bind_result($stmt_count, $count);
    if(mysqli_stmt_fetch($stmt_count)) {
        $total_items = $count ?? 0;
    }
    $response['cart_count'] = $total_items;
    mysqli_stmt_close($stmt_count);
}

mysqli_close($link);
echo json_encode($response);
?>
