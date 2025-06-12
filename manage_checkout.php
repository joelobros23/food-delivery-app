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
$cart_by_restaurant = [];

// Get all items from the cart and group them by restaurant
$sql_cart = "
    SELECT 
        c.quantity, mi.id as item_id, mi.price, mi.restaurant_id
    FROM cart_items c
    JOIN menu_items mi ON c.item_id = mi.id
    WHERE c.customer_id = ?";

if ($stmt_cart = mysqli_prepare($link, $sql_cart)) {
    mysqli_stmt_bind_param($stmt_cart, "i", $customer_id);
    mysqli_stmt_execute($stmt_cart);
    $result = mysqli_stmt_get_result($stmt_cart);
    while($row = mysqli_fetch_assoc($result)) {
        $cart_by_restaurant[$row['restaurant_id']][] = $row;
    }
    mysqli_stmt_close($stmt_cart);
}

if (empty($cart_by_restaurant)) {
    $response['message'] = 'Your cart is empty.';
    echo json_encode($response);
    exit;
}

$user_address = '';
$sql_user = "SELECT address FROM users WHERE id = ?";
if($stmt_user = mysqli_prepare($link, $sql_user)){
    mysqli_stmt_bind_param($stmt_user, "i", $customer_id);
    mysqli_stmt_execute($stmt_user);
    mysqli_stmt_bind_result($stmt_user, $address);
    if(mysqli_stmt_fetch($stmt_user)){ $user_address = $address; }
    mysqli_stmt_close($stmt_user);
}

// Generate a single unique ID for this entire checkout transaction
$group_order_id = uniqid('group_', true);

mysqli_begin_transaction($link);
try {
    // Loop through each restaurant group in the cart
    foreach ($cart_by_restaurant as $restaurant_id => $items) {
        // Calculate the total for this specific restaurant's order
        $restaurant_total = 0;
        foreach ($items as $item) {
            $restaurant_total += $item['price'] * $item['quantity'];
        }
        $restaurant_total += 50.00; // Add delivery fee for this order

        // 1. Create a separate order for this restaurant
        $sql_order = "INSERT INTO orders (group_order_id, customer_id, restaurant_id, delivery_address, total_amount, payment_method, status) VALUES (?, ?, ?, ?, ?, 'cod', 'pending')";
        if ($stmt_order = mysqli_prepare($link, $sql_order)) {
            mysqli_stmt_bind_param($stmt_order, "siisd", $group_order_id, $customer_id, $restaurant_id, $user_address, $restaurant_total);
            mysqli_stmt_execute($stmt_order);
            $new_order_id = mysqli_insert_id($link);
            mysqli_stmt_close($stmt_order);
        } else { throw new Exception("Error creating order for restaurant ID: $restaurant_id."); }

        // 2. Insert items for this specific order
        $sql_order_item = "INSERT INTO order_items (order_id, item_id, quantity, price_per_item) VALUES (?, ?, ?, ?)";
        foreach ($items as $item) {
            if ($stmt_item = mysqli_prepare($link, $sql_order_item)) {
                mysqli_stmt_bind_param($stmt_item, "iiid", $new_order_id, $item['item_id'], $item['quantity'], $item['price']);
                mysqli_stmt_execute($stmt_item);
                mysqli_stmt_close($stmt_item);
            } else { throw new Exception("Error adding items to order #$new_order_id."); }
        }
    }

    // 3. Clear the entire cart for the user
    $sql_clear = "DELETE FROM cart_items WHERE customer_id = ?";
    if ($stmt_clear = mysqli_prepare($link, $sql_clear)) {
        mysqli_stmt_bind_param($stmt_clear, "i", $customer_id);
        mysqli_stmt_execute($stmt_clear);
        mysqli_stmt_close($stmt_clear);
    } else { throw new Exception("Error clearing cart."); }

    mysqli_commit($link);
    $response = ['status' => 'success', 'message' => 'Order placed successfully! Separate orders have been created for each restaurant.'];

} catch (Exception $e) {
    mysqli_rollback($link);
    $response['message'] = $e->getMessage();
}

mysqli_close($link);
echo json_encode($response);
?>
