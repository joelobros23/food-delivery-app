<?php
// orders.php

// Initialize the session
session_start();
 
// Check if the user is logged in and is a customer, otherwise redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'customer'){
    header("location: login.php");
    exit;
}

// Include config file to connect to the database
require_once "db_connection/config.php";

// --- FETCH DATA FROM DATABASE ---

// Fetch current orders (pending, preparing, out_for_delivery)
// UPDATED: Added o.payment_method to the query
$current_orders_sql = "
    SELECT o.id, r.name as restaurant_name, r.banner_image_url, o.order_date, o.total_amount, o.status, o.payment_method
    FROM orders o
    JOIN restaurants r ON o.restaurant_id = r.id
    WHERE o.customer_id = ? AND o.status IN ('pending', 'preparing', 'out_for_delivery')
    ORDER BY o.order_date DESC";

$current_orders = [];
if($stmt_current = mysqli_prepare($link, $current_orders_sql)){
    mysqli_stmt_bind_param($stmt_current, "i", $_SESSION["id"]);
    if(mysqli_stmt_execute($stmt_current)){
        $result = mysqli_stmt_get_result($stmt_current);
        $current_orders = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
    mysqli_stmt_close($stmt_current);
}

// Fetch order history (delivered, cancelled)
// UPDATED: Added o.payment_method to the query
$history_orders_sql = "
    SELECT o.id, r.name as restaurant_name, o.order_date, o.total_amount, o.status, o.payment_method
    FROM orders o
    JOIN restaurants r ON o.restaurant_id = r.id
    WHERE o.customer_id = ? AND o.status IN ('delivered', 'cancelled')
    ORDER BY o.order_date DESC";

$history_orders = [];
if($stmt_history = mysqli_prepare($link, $history_orders_sql)){
    mysqli_stmt_bind_param($stmt_history, "i", $_SESSION["id"]);
    if(mysqli_stmt_execute($stmt_history)){
        $result = mysqli_stmt_get_result($stmt_history);
        $history_orders = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
    mysqli_stmt_close($stmt_history);
}

// Close the database connection
mysqli_close($link);

// Set the active page for the sidebar
$active_page = 'orders';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Foodie</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen bg-gray-100">
        
        <?php require_once 'cdashboard_partial/sidebar.php'; ?>

        <!-- Main content -->
        <div class="flex flex-col flex-1 overflow-y-auto">
            
            <?php require_once 'cdashboard_partial/header.php'; ?>

            <!-- Content Area -->
            <div class="p-4 md:p-6 pb-20 md:pb-6">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">My Orders</h1>

                <!-- Current Orders -->
                <div class="mb-10">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Current Orders</h2>
                    <?php if (!empty($current_orders)): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php foreach($current_orders as $order): ?>
                            <div class="bg-white p-5 rounded-lg shadow-md flex space-x-4 items-center">
                                <img src="<?php echo htmlspecialchars($order['banner_image_url']); ?>" alt="<?php echo htmlspecialchars($order['restaurant_name']); ?>" class="w-20 h-20 rounded-lg object-cover">
                                <div class="flex-1">
                                    <p class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($order['restaurant_name']); ?></p>
                                    <p class="text-sm text-gray-500">Order placed: <?php echo date("M d, Y, g:i A", strtotime($order['order_date'])); ?></p>
                                    <!-- UPDATED: Display Payment Method -->
                                    <p class="text-sm text-gray-500">Payment: <span class="font-medium text-gray-700"><?php echo strtoupper($order['payment_method']); ?></span></p>
                                    <p class="text-sm font-semibold text-gray-700 mt-1">Total: ₱<?php echo number_format($order['total_amount'], 2); ?></p>
                                </div>
                                <div class="text-right">
                                     <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 mb-3">
                                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $order['status']))); ?>
                                     </span>
                                    <a href="#" class="block w-full text-center px-4 py-2 text-sm font-medium text-white bg-orange-600 rounded-lg hover:bg-orange-700">Track Order</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-10 bg-white rounded-lg shadow-md">
                            <div class="flex items-center justify-center h-16 w-16 rounded-full bg-gray-100 text-gray-500 mx-auto mb-4">
                                <i data-lucide="shopping-basket" class="w-8 h-8"></i>
                            </div>
                            <p class="text-gray-600">You have no active orders right now.</p>
                            <a href="customer_dashboard.php" class="mt-4 inline-block bg-orange-600 text-white font-bold py-2 px-5 rounded-full hover:bg-orange-700 transition-colors duration-300">
                                Order Now
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Order History -->
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Order History</h2>
                    <div class="bg-white rounded-lg shadow-md overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Restaurant</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <!-- UPDATED: Add Payment Method Header -->
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (!empty($history_orders)): ?>
                                    <?php foreach($history_orders as $order): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($order['restaurant_name']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date("M d, Y", strtotime($order['order_date'])); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <!-- UPDATED: Display Payment Method Cell -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800"><?php echo strtoupper($order['payment_method']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $order['status'] === 'delivered' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo htmlspecialchars(ucfirst($order['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="#" class="text-orange-600 hover:text-orange-900 mr-3">Reorder</a>
                                            <a href="#" class="text-gray-500 hover:text-gray-800">View Receipt</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-10 text-gray-500">You have no past orders.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
            <?php require_once 'customer_dashboard_settings/bottom_nav.php'; ?>
        </div>
    </div>
    <script src="js/script.js"></script>
</body>
</html>
