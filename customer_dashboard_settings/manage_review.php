<?php
// customer_dashboard_settings/manage_review.php
session_start();
header('Content-Type: application/json');

// Default error response
$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

// 1. Authentication Check
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $response['message'] = 'User not logged in.';
    echo json_encode($response);
    exit;
}

// 2. Action Check
if (!isset($_POST['action']) || empty($_POST['action'])) {
    $response['message'] = 'No action specified.';
    echo json_encode($response);
    exit;
}

require_once "../db_connection/config.php";
$action = $_POST['action'];
$customer_id = $_SESSION['id'];

switch ($action) {
    case 'delete':
        if (!isset($_POST['review_id']) || empty($_POST['review_id'])) {
            $response['message'] = 'Review ID not provided.';
            break;
        }
        
        $review_id = trim($_POST['review_id']);

        // Security Check: Verify the user owns this review before deleting
        $sql_verify = "SELECT id FROM reviews WHERE id = ? AND customer_id = ?";
        $can_process = false;
        if ($stmt_verify = mysqli_prepare($link, $sql_verify)) {
            mysqli_stmt_bind_param($stmt_verify, "ii", $review_id, $customer_id);
            mysqli_stmt_execute($stmt_verify);
            mysqli_stmt_store_result($stmt_verify);
            if (mysqli_stmt_num_rows($stmt_verify) == 1) {
                $can_process = true;
            }
            mysqli_stmt_close($stmt_verify);
        }

        if (!$can_process) {
            $response['message'] = 'You are not authorized to perform this action.';
            break;
        }

        // Perform Deletion
        $sql_delete = "DELETE FROM reviews WHERE id = ?";
        if ($stmt_delete = mysqli_prepare($link, $sql_delete)) {
            mysqli_stmt_bind_param($stmt_delete, "i", $review_id);
            if (mysqli_stmt_execute($stmt_delete)) {
                $response = ['status' => 'success', 'message' => 'Review deleted successfully!'];
            } else {
                $response['message'] = 'Failed to delete review.';
            }
            mysqli_stmt_close($stmt_delete);
        }
        break;

    case 'edit':
        if (!isset($_POST['review_id'], $_POST['rating'], $_POST['comment'])) {
            $response['message'] = 'Missing required review data for editing.';
            break;
        }

        $review_id = trim($_POST['review_id']);
        $rating = trim($_POST['rating']);
        $comment = trim($_POST['comment']);

        // Security Check: Verify ownership
        $sql_verify_edit = "SELECT id FROM reviews WHERE id = ? AND customer_id = ?";
        $can_edit = false;
        if ($stmt_verify_edit = mysqli_prepare($link, $sql_verify_edit)) {
            mysqli_stmt_bind_param($stmt_verify_edit, "ii", $review_id, $customer_id);
            mysqli_stmt_execute($stmt_verify_edit);
            mysqli_stmt_store_result($stmt_verify_edit);
            if (mysqli_stmt_num_rows($stmt_verify_edit) == 1) {
                $can_edit = true;
            }
            mysqli_stmt_close($stmt_verify_edit);
        }

        if (!$can_edit) {
            $response['message'] = 'You are not authorized to edit this review.';
            break;
        }
        
        // Perform Update
        $sql_update = "UPDATE reviews SET rating = ?, comment = ? WHERE id = ?";
        if ($stmt_update = mysqli_prepare($link, $sql_update)) {
            mysqli_stmt_bind_param($stmt_update, "isi", $rating, $comment, $review_id);
            if (mysqli_stmt_execute($stmt_update)) {
                 $response = [
                    'status' => 'success', 
                    'message' => 'Review updated successfully!',
                    'review' => [
                        'rating' => $rating,
                        'comment' => htmlspecialchars($comment)
                    ]
                ];
            } else {
                $response['message'] = 'Failed to update review.';
            }
            mysqli_stmt_close($stmt_update);
        }
        break;

    default:
        $response['message'] = 'Invalid action specified.';
        break;
}

mysqli_close($link);
echo json_encode($response);
?>
