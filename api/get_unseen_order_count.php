<?php
// api/get_unseen_order_count.php
session_start();
header('Content-Type: application/json');

// Default response
$response = ['success' => false, 'count' => 0];

// Security check: ensure user is a logged-in customer
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'customer') {
    echo json_encode($response);
    exit;
}

require_once "../app_config.php";

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($link === false) {
    echo json_encode($response);
    exit;
}

$customer_id = $_SESSION['id'];
$unseen_count = 0;

// This query counts only the orders with status updates the customer has not yet viewed
$sql = "SELECT COUNT(id) FROM orders WHERE customer_id = ? AND status_viewed_by_customer = 0";

if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $customer_id);
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_bind_result($stmt, $count);
        if (mysqli_stmt_fetch($stmt)) {
            $unseen_count = $count;
        }
        $response = ['success' => true, 'count' => $unseen_count];
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($link);

echo json_encode($response);
?>
