<?php
// fetch_messages.php
require_once "app_config.php";
session_start();
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit;
}

if (!isset($_GET['order_id'], $_GET['last_id'])) {
    $response['message'] = 'Required data not provided.';
    echo json_encode($response);
    exit;
}

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($link === false) { die("DB Connection Error"); }

$order_id = trim($_GET['order_id']);
$last_id = (int)$_GET['last_id'];
$current_user_id = $_SESSION['id'];
$messages = [];

// Set a time limit for the script to run (e.g., 30 seconds)
set_time_limit(40);

// Long Polling loop
for ($i = 0; $i < 30; $i++) {
    $sql = "SELECT id, message, sent_at, sender_id FROM messages WHERE order_id = ? AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) AND id > ? ORDER BY sent_at ASC";
    if ($stmt = mysqli_prepare($link, $sql)) {
        // We check for messages sent to or from the current user in this order thread
        mysqli_stmt_bind_param($stmt, "iiiiii", $order_id, $current_user_id, $_GET['receiver_id'], $_GET['receiver_id'], $current_user_id, $last_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $messages = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
    }
    
    // If we found new messages, send them and exit the loop
    if (!empty($messages)) {
        $response = ['status' => 'success', 'messages' => $messages];
        // Mark these messages as read
        $sql_mark_read = "UPDATE messages SET is_read = 1 WHERE order_id = ? AND receiver_id = ?";
        if($stmt_read = mysqli_prepare($link, $sql_mark_read)) {
            mysqli_stmt_bind_param($stmt_read, "ii", $order_id, $current_user_id);
            mysqli_stmt_execute($stmt_read);
            mysqli_stmt_close($stmt_read);
        }
        break; 
    }
    
    // Wait for 1 second before checking again
    sleep(1);
}

// If the loop finishes without finding messages, send an empty success response
if (empty($messages)) {
    $response = ['status' => 'success', 'messages' => []];
}

mysqli_close($link);
echo json_encode($response);
?>
