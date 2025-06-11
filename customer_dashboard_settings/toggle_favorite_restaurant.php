<?php
// cdashboard_partial/sidebar.php

session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit;
}

// Check if restaurant ID is provided
if (!isset($_POST['restaurant_id']) || empty($_POST['restaurant_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Restaurant ID not provided.']);
    exit;
}

require_once "../db_connection/config.php";

$customer_id = $_SESSION['id'];
$restaurant_id = $_POST['restaurant_id'];

// Check if the restaurant is already favorited
$sql_check = "SELECT id FROM favorite_restaurants WHERE customer_id = ? AND restaurant_id = ?";
if ($stmt_check = mysqli_prepare($link, $sql_check)) {
    mysqli_stmt_bind_param($stmt_check, "ii", $customer_id, $restaurant_id);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);

    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        // --- It is favorited, so unfavorite it (DELETE) ---
        $sql_delete = "DELETE FROM favorite_restaurants WHERE customer_id = ? AND restaurant_id = ?";
        if ($stmt_delete = mysqli_prepare($link, $sql_delete)) {
            mysqli_stmt_bind_param($stmt_delete, "ii", $customer_id, $restaurant_id);
            if (mysqli_stmt_execute($stmt_delete)) {
                echo json_encode(['status' => 'success', 'action' => 'unfavorited']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Could not remove favorite.']);
            }
            mysqli_stmt_close($stmt_delete);
        }
    } else {
        // --- It is not favorited, so favorite it (INSERT) ---
        $sql_insert = "INSERT INTO favorite_restaurants (customer_id, restaurant_id) VALUES (?, ?)";
        if ($stmt_insert = mysqli_prepare($link, $sql_insert)) {
            mysqli_stmt_bind_param($stmt_insert, "ii", $customer_id, $restaurant_id);
            if (mysqli_stmt_execute($stmt_insert)) {
                echo json_encode(['status' => 'success', 'action' => 'favorited']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Could not add favorite.']);
            }
            mysqli_stmt_close($stmt_insert);
        }
    }
    mysqli_stmt_close($stmt_check);
}

mysqli_close($link);
?>
