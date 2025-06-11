<?php
// customer_dashboard_settings/add_review.php
session_start();
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $response['message'] = 'You must be logged in to leave a review.';
    echo json_encode($response);
    exit;
}

// Check for required POST data
if (!isset($_POST['restaurant_id'], $_POST['rating'], $_POST['comment'])) {
    $response['message'] = 'Missing required review data.';
    echo json_encode($response);
    exit;
}

require_once "../db_connection/config.php";

$customer_id = $_SESSION['id'];
$restaurant_id = trim($_POST['restaurant_id']);
$rating = trim($_POST['rating']);
$comment = trim($_POST['comment']);

// --- Validation ---
if (empty($restaurant_id) || empty($rating)) {
    $response['message'] = 'Rating and restaurant information are required.';
    echo json_encode($response);
    exit;
}

if ($rating < 1 || $rating > 5) {
    $response['message'] = 'Invalid rating value.';
    echo json_encode($response);
    exit;
}

// --- Check if the user has a delivered order that has not been reviewed yet ---
$sql_order_check = "
    SELECT id FROM orders 
    WHERE customer_id = ? 
    AND restaurant_id = ? 
    AND status = 'delivered' 
    AND id NOT IN (SELECT order_id FROM reviews)
    ORDER BY order_date DESC
    LIMIT 1";

$order_id_to_review = null;
if ($stmt_check = mysqli_prepare($link, $sql_order_check)) {
    mysqli_stmt_bind_param($stmt_check, "ii", $customer_id, $restaurant_id);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_bind_result($stmt_check, $order_id);
    if (mysqli_stmt_fetch($stmt_check)) {
        $order_id_to_review = $order_id;
    }
    mysqli_stmt_close($stmt_check);
}

if (is_null($order_id_to_review)) {
    $response['message'] = 'You must have a delivered order to review this restaurant.';
    echo json_encode($response);
    exit;
}

// --- Insert the review ---
$sql_insert = "INSERT INTO reviews (order_id, customer_id, restaurant_id, rating, comment) VALUES (?, ?, ?, ?, ?)";
if ($stmt_insert = mysqli_prepare($link, $sql_insert)) {
    mysqli_stmt_bind_param($stmt_insert, "iiiis", $order_id_to_review, $customer_id, $restaurant_id, $rating, $comment);
    if (mysqli_stmt_execute($stmt_insert)) {
        $response = [
            'status' => 'success',
            'message' => 'Review submitted successfully!',
            'review' => [
                'customer_name' => $_SESSION['name'],
                'rating' => $rating,
                'comment' => htmlspecialchars($comment),
                'review_date' => date("M d, Y")
            ]
        ];
    } else {
        $response['message'] = 'Failed to submit review.';
    }
    mysqli_stmt_close($stmt_insert);
}

mysqli_close($link);
echo json_encode($response);
?>
