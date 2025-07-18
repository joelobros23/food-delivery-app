<?php
// purchase.php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'customer') {
    header("location: login.php");
    exit;
}

if (!isset($_GET["item_id"]) || !isset($_GET["restaurant_id"])) {
    header("location: customer_dashboard.php");
    exit;
}

require_once "app_config.php";

$item_id = trim($_GET["item_id"]);
$restaurant_id = trim($_GET["restaurant_id"]);
$customer_id = $_SESSION['id'];
$item_details = null;
$user_address = '';
$order_success = false;
$new_order_id = null; // Variable to hold the new order ID for JavaScript

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($link === false) { die("DB Connection Error"); }


// Fetch item details
$sql_item = "SELECT name, price FROM menu_items WHERE id = ?";
if($stmt_item = mysqli_prepare($link, $sql_item)){
    mysqli_stmt_bind_param($stmt_item, "i", $item_id);
    mysqli_stmt_execute($stmt_item);
    $result_item = mysqli_stmt_get_result($stmt_item);
    $item_details = mysqli_fetch_assoc($result_item);
    mysqli_stmt_close($stmt_item);
}

// Fetch user's address
$sql_user = "SELECT address FROM users WHERE id = ?";
if($stmt_user = mysqli_prepare($link, $sql_user)){
    mysqli_stmt_bind_param($stmt_user, "i", $customer_id);
    mysqli_stmt_execute($stmt_user);
    mysqli_stmt_bind_result($stmt_user, $address);
    if(mysqli_stmt_fetch($stmt_user)){
        $user_address = $address;
    }
    mysqli_stmt_close($stmt_user);
}

if (is_null($item_details)) {
    header("location: customer_dashboard.php"); exit;
}

// Handle order confirmation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_order'])) {
    $total_amount = $item_details['price'];
    $delivery_address = $user_address;
    $payment_method = 'cod';

    mysqli_begin_transaction($link);
    
    try {
        $sql_order = "INSERT INTO orders (customer_id, restaurant_id, delivery_address, total_amount, status) VALUES (?, ?, ?, ?, 'pending')";
        if ($stmt_order = mysqli_prepare($link, $sql_order)) {
            mysqli_stmt_bind_param($stmt_order, "iisd", $customer_id, $restaurant_id, $delivery_address, $total_amount);
            mysqli_stmt_execute($stmt_order);
            // FIX: Capture the new order ID
            $new_order_id = mysqli_insert_id($link);
            mysqli_stmt_close($stmt_order);
        } else { throw new Exception("Error preparing order statement."); }

        $sql_order_item = "INSERT INTO order_items (order_id, item_id, quantity, price_per_item) VALUES (?, ?, 1, ?)";
        if ($stmt_order_item = mysqli_prepare($link, $sql_order_item)) {
            mysqli_stmt_bind_param($stmt_order_item, "iid", $new_order_id, $item_id, $item_details['price']);
            mysqli_stmt_execute($stmt_order_item);
            mysqli_stmt_close($stmt_order_item);
        } else { throw new Exception("Error preparing order item statement."); }

        mysqli_commit($link);
        
        $order_success = true;

    } catch (Exception $e) {
        mysqli_rollback($link);
        echo "Order failed. Please try again. Error: " . $e->getMessage();
    }
}

mysqli_close($link);
$active_page = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Purchase - Foodie</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen bg-gray-100">
        <?php require_once 'cdashboard_partial/sidebar.php'; ?>
        <div class="flex flex-col flex-1 overflow-y-auto">
            <?php require_once 'cdashboard_partial/header.php'; ?>
            <div class="p-4 md:p-6 pb-20 md:pb-6">
                <a href="javascript:history.back()" class="flex items-center text-gray-600 hover:text-orange-600 mb-6"><i data-lucide="arrow-left" class="w-5 h-5 mr-2"></i>Back to Item</a>
                <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-6 text-center">Confirm Your Order</h1>
                    <div class="border-y divide-y">
                        <div class="py-4 flex justify-between items-center">
                            <span class="font-medium text-gray-600">Item:</span>
                            <span class="font-bold text-gray-900"><?php echo htmlspecialchars($item_details['name']); ?></span>
                        </div>
                        <div class="py-4 flex justify-between items-center">
                            <span class="font-medium text-gray-600">Quantity:</span>
                            <span class="font-bold text-gray-900">1</span>
                        </div>
                         <div class="py-4 flex justify-between items-center">
                            <span class="font-medium text-gray-600">Payment Method:</span>
                            <span class="font-bold text-gray-900 flex items-center"><i data-lucide="wallet" class="w-5 h-5 mr-2 text-green-600"></i>Cash on Delivery</span>
                        </div>
                        <div class="py-4">
                            <p class="font-medium text-gray-600 mb-2">Deliver to:</p>
                            <p class="text-gray-800 p-3 bg-gray-50 rounded-md"><?php echo htmlspecialchars($user_address ?: 'No address found. Please update in settings.'); ?></p>
                        </div>
                        <div class="py-4 flex justify-between items-center text-xl">
                            <span class="font-bold text-gray-900">Total:</span>
                            <span class="font-extrabold text-orange-600">₱<?php echo number_format($item_details['price'], 2); ?></span>
                        </div>
                    </div>
                    <form action="<?php echo htmlspecialchars($_SERVER["REQUEST_URI"]); ?>" method="POST" class="mt-8">
                        <input type="hidden" name="confirm_order" value="1">
                        <button type="submit" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700">
                           Place Order
                        </button>
                    </form>
                </div>
            </div>
            <?php require_once 'customer_dashboard_settings/bottom_nav.php'; ?>
        </div>
    </div>
    <script src="js/script.js"></script>
    
    <!-- Real-time notification script -->
    <script>
        <?php if ($order_success): ?>
        
        // FIX: The function now correctly accepts both the storeId and the new orderId
        function sendNewOrderNotification(storeId, orderId) {
            if (!storeId || !orderId) {
                console.error("Cannot send notification: Store ID or Order ID is missing.");
                window.location.href = 'orders.php?purchase=success';
                return;
            }

            const wsUrl = "ws://localhost:8080";
            const notificationSocket = new WebSocket(wsUrl);

            notificationSocket.onopen = function() {
                const notificationPayload = {
                    type: 'new_order_placed',
                    store_id: parseInt(storeId),
                    order_id: parseInt(orderId) // FIX: Include the new order ID in the payload
                };
                notificationSocket.send(JSON.stringify(notificationPayload));
                
                setTimeout(function() {
                    notificationSocket.close();
                    window.location.href = 'orders.php?purchase=success';
                }, 500);
            };

            notificationSocket.onerror = function(error) {
                console.error("WebSocket error when sending notification:", error);
                window.location.href = 'orders.php?purchase=success';
            };
        }

        // FIX: Call the function with both required IDs, which are passed from PHP.
        sendNewOrderNotification(
            <?php echo json_encode($restaurant_id); ?>, 
            <?php echo json_encode($new_order_id); ?>
        );
        
        <?php endif; ?>
    </script>
</body>
</html>
