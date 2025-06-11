<?php
// customer_dashboard_settings/manage_settings.php
session_start();
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $response['message'] = 'User not logged in.';
    echo json_encode($response);
    exit;
}

if (!isset($_POST['action']) || empty($_POST['action'])) {
    $response['message'] = 'No action specified.';
    echo json_encode($response);
    exit;
}

require_once "../db_connection/config.php";
$action = $_POST['action'];
$customer_id = $_SESSION['id'];

switch ($action) {
    case 'update_personal':
        if (!isset($_POST['full_name'], $_POST['address'])) {
            $response['message'] = 'Missing personal details.';
            break;
        }
        $full_name = trim($_POST['full_name']);
        $address = trim($_POST['address']);
        $latitude = !empty($_POST['latitude']) ? trim($_POST['latitude']) : null;
        $longitude = !empty($_POST['longitude']) ? trim($_POST['longitude']) : null;
        
        $sql = "UPDATE users SET full_name = ?, address = ?, latitude = ?, longitude = ? WHERE id = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssddi", $full_name, $address, $latitude, $longitude, $customer_id);
            if(mysqli_stmt_execute($stmt)){
                $_SESSION["name"] = $full_name; // Update session name
                $response = ['status' => 'success', 'message' => 'Personal details updated successfully!'];
            } else {
                $response['message'] = 'Could not update personal details.';
            }
            mysqli_stmt_close($stmt);
        }
        break;

    case 'update_security':
        if (!isset($_POST['current_password'], $_POST['new_password'])) {
            $response['message'] = 'Missing password fields.';
            break;
        }
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];

        if (empty($current_password) || empty($new_password)) {
            $response['message'] = 'Please fill out all password fields.';
            break;
        }
        if (strlen($new_password) < 6) {
             $response['message'] = 'New password must have at least 6 characters.';
            break;
        }

        $sql_fetch = "SELECT password FROM users WHERE id = ?";
        if($stmt_fetch = mysqli_prepare($link, $sql_fetch)) {
            mysqli_stmt_bind_param($stmt_fetch, "i", $customer_id);
            mysqli_stmt_execute($stmt_fetch);
            mysqli_stmt_bind_result($stmt_fetch, $hashed_password);
            mysqli_stmt_fetch($stmt_fetch);
            mysqli_stmt_close($stmt_fetch);

            if(password_verify($current_password, $hashed_password)) {
                $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql_update = "UPDATE users SET password = ? WHERE id = ?";
                if($stmt_update = mysqli_prepare($link, $sql_update)){
                    mysqli_stmt_bind_param($stmt_update, "si", $new_hashed_password, $customer_id);
                    if(mysqli_stmt_execute($stmt_update)){
                         $response = ['status' => 'success', 'message' => 'Password updated successfully!'];
                    } else {
                         $response['message'] = 'Could not update password.';
                    }
                    mysqli_stmt_close($stmt_update);
                }
            } else {
                 $response['message'] = 'Incorrect current password.';
            }
        }
        break;

    case 'update_privacy':
        $show_activities = isset($_POST['show_activities']) ? 1 : 0;
        $sql = "UPDATE users SET show_activities = ? WHERE id = ?";
         if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $show_activities, $customer_id);
            if(mysqli_stmt_execute($stmt)){
                $response = ['status' => 'success', 'message' => 'Privacy settings updated!'];
            } else {
                $response['message'] = 'Could not update privacy settings.';
            }
            mysqli_stmt_close($stmt);
        }
        break;

    default:
        $response['message'] = 'Invalid action specified.';
        break;
}

mysqli_close($link);
echo json_encode($response);
?>
