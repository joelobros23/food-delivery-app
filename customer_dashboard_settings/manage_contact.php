<?php
// customer_dashboard_settings/manage_contact.php
session_start();
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'customer') {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit;
}

if (!isset($_POST['action']) || $_POST['action'] !== 'update_contact') {
    $response['message'] = 'Invalid action.';
    echo json_encode($response);
    exit;
}

require_once "../app_config.php";
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($link === false) { 
    $response['message'] = 'Database connection error.';
    echo json_encode($response);
    exit;
}

$customer_id = $_SESSION['id'];
$phone_number = trim($_POST['phone_number'] ?? null);
$complete_address = trim($_POST['complete_address'] ?? null);

$sql = "UPDATE users SET phone_number = ?, complete_address = ? WHERE id = ?";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "ssi", $phone_number, $complete_address, $customer_id);
    if (mysqli_stmt_execute($stmt)) {
        $response = ['status' => 'success', 'message' => 'Contact details updated successfully!'];
    } else {
        $response['message'] = 'Could not update contact details.';
    }
    mysqli_stmt_close($stmt);
} else {
    $response['message'] = 'Database statement error.';
}

mysqli_close($link);
echo json_encode($response);
?>
