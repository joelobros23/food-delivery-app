<?php
// update_location.php
session_start();
header('Content-Type: application/json');

// Security check: ensure user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if all required data is received
if (!isset($_POST['latitude'], $_POST['longitude'], $_POST['city'], $_POST['address'])) {
    echo json_encode(['success' => false, 'message' => 'Incomplete location data.']);
    exit;
}

require_once "app_config.php";

$customer_id = $_SESSION['id'];
$latitude = $_POST['latitude'];
$longitude = $_POST['longitude'];
$city = $_POST['city'];
$address = $_POST['address'];

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($link === false) { 
    echo json_encode(['success' => false, 'message' => 'Database connection error.']);
    exit;
}

// Prepare an update statement to save the new location details
$sql = "UPDATE users SET address = ?, city = ?, latitude = ?, longitude = ? WHERE id = ?";

if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "ssddi", $address, $city, $latitude, $longitude, $customer_id);

    if (mysqli_stmt_execute($stmt)) {
        // Successfully updated
        echo json_encode(['success' => true]);
    } else {
        // Update failed
        echo json_encode(['success' => false, 'message' => 'Failed to save location.']);
    }
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'message' => 'Database statement preparation error.']);
}

mysqli_close($link);
?>
