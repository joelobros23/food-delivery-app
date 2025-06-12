<?php
// store/manage_item_status.php
require_once "../app_config.php";
session_start();
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

// Security checks
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'store') {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit;
}
if (!isset($_POST['order_item_id'])) {
    $response['message'] = 'Required data not provided.';
    echo json_encode($response);
    exit;
}

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($link === false) {
    $response['message'] = 'Database connection failed.';
    echo json_encode($response);
    exit; 
}

$order_item_id = trim($_POST['order_item_id']);
$store_owner_id = $_SESSION['id'];

// Security Check: Verify that the order item belongs to an order from this store owner's restaurant
$sql_verify = "
    SELECT oi.id 
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN restaurants r ON o.restaurant_id = r.id
    WHERE oi.id = ? AND r.user_id = ?";
$can_update = false;
if ($stmt_verify = mysqli_prepare($link, $sql_verify)) {
    mysqli_stmt_bind_param($stmt_verify, "ii", $order_item_id, $store_owner_id);
    mysqli_stmt_execute($stmt_verify);
    mysqli_stmt_store_result($stmt_verify);
    if (mysqli_stmt_num_rows($stmt_verify) == 1) {
        $can_update = true;
    }
    mysqli_stmt_close($stmt_verify);
}

if (!$can_update) {
    $response['message'] = 'You are not authorized to update this item.';
    echo json_encode($response);
    exit;
}

// Toggle the 'is_prepared' status
$sql_toggle = "UPDATE order_items SET is_prepared = NOT is_prepared WHERE id = ?";
if ($stmt_toggle = mysqli_prepare($link, $sql_toggle)) {
    mysqli_stmt_bind_param($stmt_toggle, "i", $order_item_id);
    if (mysqli_stmt_execute($stmt_toggle)) {
        $response['status'] = 'success';
        $response['message'] = 'Item status updated.';
    } else {
        $response['message'] = 'Failed to update item status.';
    }
    mysqli_stmt_close($stmt_toggle);
}

mysqli_close($link);
echo json_encode($response);
?>
