<?php
// manage_messages.php
require_once "app_config.php";
session_start();
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit;
}

if (!isset($_REQUEST['action'])) { // Use $_REQUEST to handle both GET and POST
    $response['message'] = 'No action specified.';
    echo json_encode($response);
    exit;
}

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($link === false) { $response['message'] = 'DB Connection Error'; echo json_encode($response); exit; }

$action = $_REQUEST['action'];
$current_user_id = $_SESSION['id'];

if ($action === 'send') {
    if (!isset($_POST['order_id'], $_POST['receiver_id'], $_POST['message']) || empty(trim($_POST['message']))) {
        $response['message'] = 'Required data not provided for sending.';
    } else {
        $order_id = trim($_POST['order_id']);
        $receiver_id = trim($_POST['receiver_id']);
        $message = trim($_POST['message']);

        $sql = "INSERT INTO messages (order_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "iiis", $order_id, $current_user_id, $receiver_id, $message);
            if (mysqli_stmt_execute($stmt)) {
                $response = ['status' => 'success', 'message' => ['text' => htmlspecialchars($message), 'time' => date('g:i a')]];
            } else {
                $response['message'] = 'Failed to send message.';
            }
            mysqli_stmt_close($stmt);
        }
    }
} elseif ($action === 'fetch') {
    if (!isset($_GET['order_id'])) {
        $response['message'] = 'Order ID not provided for fetching.';
    } else {
        $order_id = trim($_GET['order_id']);
        $messages = [];
        
        $sql = "SELECT message, sent_at, sender_id FROM messages WHERE order_id = ? AND receiver_id = ? AND is_read = 0 ORDER BY sent_at ASC";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $order_id, $current_user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $messages = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_stmt_close($stmt);

            if (!empty($messages)) {
                $sql_mark_read = "UPDATE messages SET is_read = 1 WHERE order_id = ? AND receiver_id = ?";
                if($stmt_read = mysqli_prepare($link, $sql_mark_read)) {
                    mysqli_stmt_bind_param($stmt_read, "ii", $order_id, $current_user_id);
                    mysqli_stmt_execute($stmt_read);
                    mysqli_stmt_close($stmt_read);
                }
            }
            $response = ['status' => 'success', 'messages' => $messages];
        }
    }
}

mysqli_close($link);
echo json_encode($response);
?>
