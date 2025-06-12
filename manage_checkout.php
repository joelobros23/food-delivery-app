<?php
// manage_checkout.php
require_once "app_config.php";
session_start();
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'customer') {
    $response['message'] = 'Please log in to place an order.';
    echo json_encode($response);
    exit;
}

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($link === false) { 
    $response['message'] = 'Database connection failed.';
    echo json_encode($response);
    exit; 
}

$customer_id = $_SESSION['id'];

// Get all items from the user's cart
$cart_items = [];
$sql_cart = "
    SELECT c.quantity, mi.id as item_id, mi.price, mi.restaurant_id
    FROM cart_items c
    JOIN menu_items mi ON c.item_id = mi.id
    WHERE c.customer_id = ?";

if ($stmt_cart = mysqli_prepare($link, $sql_cart)) {
    mysqli_stmt_bind_param($stmt_cart, "i", $customer_id);
    mysqli_stmt_execute($stmt_cart);
    $result = mysqli_stmt_get_result($stmt_cart);
    $cart_items = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_cart);
}

if (empty($cart_items)) {
    $response['message'] = 'Your cart is empty.';
    echo json_encode($response);
    exit;
}

// For this example, we assume all items in the cart are from the same restaurant.
// A more advanced cart would handle multiple restaurants.
$restaurant_id = $cart_items[0]['restaurant_id'];
$total_amount = 0;
foreach ($cart_items as $item) {
    $total_amount += $item['price'] * $item['quantity'];
}

// Add delivery fee
$total_amount += 50.00; 

// Fetch user address
$user_address = '';
$sql_user = "SELECT address FROM users WHERE id = ?";
if($stmt_user = mysqli_prepare($link, $sql_user)){
    mysqli_stmt_bind_param($stmt_user, "i", $customer_id);
    mysqli_stmt_execute($stmt_user);
    mysqli_stmt_bind_result($stmt_user, $address);
    if(mysqli_stmt_fetch($stmt_user)){ $user_address = $address; }
    mysqli_stmt_close($stmt_user);
}

// Start a transaction to ensure all queries succeed or none do.
mysqli_begin_transaction($link);

try {
    // 1. Insert into 'orders' table
    $sql_order = "INSERT INTO orders (customer_id, restaurant_id, delivery_address, total_amount, payment_method, status) VALUES (?, ?, ?, ?, 'cod', 'pending')";
    if ($stmt_order = mysqli_prepare($link, $sql_order)) {
        mysqli_stmt_bind_param($stmt_order, "iisd", $customer_id, $restaurant_id, $user_address, $total_amount);
        mysqli_stmt_execute($stmt_order);
        $order_id = mysqli_insert_id($link);
        mysqli_stmt_close($stmt_order);
    } else { throw new Exception("Error creating order."); }

    // 2. Insert each cart item into 'order_items' table
    $sql_order_item = "INSERT INTO order_items (order_id, item_id, quantity, price_per_item) VALUES (?, ?, ?, ?)";
    foreach ($cart_items as $item) {
        if ($stmt_item = mysqli_prepare($link, $sql_order_item)) {
            mysqli_stmt_bind_param($stmt_item, "iiid", $order_id, $item['item_id'], $item['quantity'], $item['price']);
            mysqli_stmt_execute($stmt_item);
            mysqli_stmt_close($stmt_item);
        } else { throw new Exception("Error adding items to order."); }
    }

    // 3. Clear the user's cart
    $sql_clear = "DELETE FROM cart_items WHERE customer_id = ?";
    if ($stmt_clear = mysqli_prepare($link, $sql_clear)) {
        mysqli_stmt_bind_param($stmt_clear, "i", $customer_id);
        mysqli_stmt_execute($stmt_clear);
        mysqli_stmt_close($stmt_clear);
    } else { throw new Exception("Error clearing cart."); }

    // If all queries were successful, commit the transaction
    mysqli_commit($link);
    $response = ['status' => 'success', 'message' => 'Order placed successfully!'];

} catch (Exception $e) {
    mysqli_rollback($link);
    $response['message'] = $e->getMessage();
}

mysqli_close($link);
echo json_encode($response);
?>
