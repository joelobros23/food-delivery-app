<?php
// send_message.php (for customer)
require_once "app_config.php";
session_start();
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

// Security: User must be logged in.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit;
}

// Check for required data
if (!isset($_POST['order_id'], $_POST['receiver_id'], $_POST['message']) || empty(trim($_POST['message']))) {
    $response['message'] = 'Required data not provided.';
    echo json_encode($response);
    exit;
}

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($link === false) { die("DB Connection Error"); }

$order_id = trim($_POST['order_id']);
$sender_id = $_SESSION['id'];
$receiver_id = trim($_POST['receiver_id']);
$message = trim($_POST['message']);

// Security Check: Ensure the logged-in customer is part of this order before sending a message.
$sql_verify = "SELECT id FROM orders WHERE id = ? AND customer_id = ?";
$can_send = false;
if ($stmt_verify = mysqli_prepare($link, $sql_verify)) {
    mysqli_stmt_bind_param($stmt_verify, "ii", $order_id, $sender_id);
    mysqli_stmt_execute($stmt_verify);
    mysqli_stmt_store_result($stmt_verify);
    if (mysqli_stmt_num_rows($stmt_verify) == 1) {
        $can_send = true;
    }
    mysqli_stmt_close($stmt_verify);
}

if (!$can_send) {
    $response['message'] = 'You are not authorized to send messages for this order.';
    echo json_encode($response);
    exit;
}

// Insert the message into the database
$sql = "INSERT INTO messages (order_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "iiis", $order_id, $sender_id, $receiver_id, $message);
    if(mysqli_stmt_execute($stmt)) {
        $response = [
            'status' => 'success',
            'message' => [
                'text' => htmlspecialchars($message),
                'time' => date('g:i a')
            ]
        ];
    } else {
        $response['message'] = 'Failed to send message.';
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($link);
echo json_encode($response);
?>
