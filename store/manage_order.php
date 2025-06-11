<?php
// store/manage_order.php
session_start();
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'store') {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit;
}

if (!isset($_POST['order_id'], $_POST['action'])) {
    $response['message'] = 'Required data not provided.';
    echo json_encode($response);
    exit;
}

require_once "../db_connection/config.php";

$order_id = trim($_POST['order_id']);
$action = trim($_POST['action']);
$store_owner_id = $_SESSION['id'];
$new_status = '';

// Determine the new status based on the action
switch ($action) {
    case 'accept':
        $new_status = 'preparing';
        break;
    case 'reject':
        $new_status = 'cancelled';
        break;
    case 'ready_for_delivery':
        $new_status = 'out_for_delivery';
        break;
    default:
        $response['message'] = 'Invalid action.';
        echo json_encode($response);
        exit;
}

// Security Check: Verify that the order belongs to this store's owner
$sql_verify = "
    SELECT o.id FROM orders o 
    JOIN restaurants r ON o.restaurant_id = r.id 
    WHERE o.id = ? AND r.user_id = ?";
$can_update = false;
if ($stmt_verify = mysqli_prepare($link, $sql_verify)) {
    mysqli_stmt_bind_param($stmt_verify, "ii", $order_id, $store_owner_id);
    mysqli_stmt_execute($stmt_verify);
    mysqli_stmt_store_result($stmt_verify);
    if (mysqli_stmt_num_rows($stmt_verify) == 1) {
        $can_update = true;
    }
    mysqli_stmt_close($stmt_verify);
}

if (!$can_update) {
    $response['message'] = 'You are not authorized to update this order.';
    echo json_encode($response);
    exit;
}

// Perform the update
$sql_update = "UPDATE orders SET status = ? WHERE id = ?";
if ($stmt_update = mysqli_prepare($link, $sql_update)) {
    mysqli_stmt_bind_param($stmt_update, "si", $new_status, $order_id);
    if (mysqli_stmt_execute($stmt_update)) {
        $response = ['status' => 'success', 'message' => 'Order status updated to ' . $new_status];
    } else {
        $response['message'] = 'Failed to update order status.';
    }
    mysqli_stmt_close($stmt_update);
}

mysqli_close($link);
echo json_encode($response);
?>
