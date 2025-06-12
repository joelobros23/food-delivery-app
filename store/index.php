<?php
// store/index.php
session_start();

// FIX: Consistently include the main app config from the project root.
require_once __DIR__ . "/../app_config.php";

// Security check: ensure user is logged in and is a store owner
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'store') {
    header("location: ../login.php");
    exit;
}

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($link === false) { die("DB Connection Error"); }


$store_owner_id = $_SESSION['id'];
$restaurant_id = null;
$restaurant_name = "My Restaurant"; // Default name

// Fetch the restaurant ID and name associated with the store owner
$sql_resto_info = "SELECT id, name FROM restaurants WHERE user_id = ? LIMIT 1";
if($stmt_resto = mysqli_prepare($link, $sql_resto_info)){
    mysqli_stmt_bind_param($stmt_resto, "i", $store_owner_id);
    mysqli_stmt_execute($stmt_resto);
    mysqli_stmt_bind_result($stmt_resto, $r_id, $r_name);
    if(mysqli_stmt_fetch($stmt_resto)){
        $restaurant_id = $r_id;
        $restaurant_name = $r_name;
    }
    mysqli_stmt_close($stmt_resto);
}

// Initialize stats array
$stats = ['total_reviews' => 0, 'total_orders' => 0, 'pending_orders' => 0];
$recent_orders = [];

if ($restaurant_id) {
    // Fetch order statistics
    $sql_order_stats = "
        SELECT 
            COUNT(id) as total_orders,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders
        FROM orders
        WHERE restaurant_id = ?";
    
    if($stmt_order_stats = mysqli_prepare($link, $sql_order_stats)) {
        mysqli_stmt_bind_param($stmt_order_stats, "i", $restaurant_id);
        mysqli_stmt_execute($stmt_order_stats);
        $result = mysqli_stmt_get_result($stmt_order_stats);
        $order_stats = mysqli_fetch_assoc($result);
        if ($order_stats) {
            $stats['total_orders'] = $order_stats['total_orders'];
            $stats['pending_orders'] = $order_stats['pending_orders'];
        }
        mysqli_stmt_close($stmt_order_stats);
    }

    // Fetch review statistics
    $sql_review_stats = "SELECT COUNT(id) as total_reviews FROM reviews WHERE restaurant_id = ?";
    if($stmt_review_stats = mysqli_prepare($link, $sql_review_stats)){
        mysqli_stmt_bind_param($stmt_review_stats, "i", $restaurant_id);
        mysqli_stmt_execute($stmt_review_stats);
        $result = mysqli_stmt_get_result($stmt_review_stats);
        $review_stats = mysqli_fetch_assoc($result);
        if ($review_stats) {
            $stats['total_reviews'] = $review_stats['total_reviews'];
        }
        mysqli_stmt_close($stmt_review_stats);
    }


    // Fetch recent orders
    $sql_orders = "
        SELECT o.id, o.order_date, o.total_amount, o.status, u.full_name as customer_name
        FROM orders o
        JOIN users u ON o.customer_id = u.id
        WHERE o.restaurant_id = ?
        ORDER BY o.order_date DESC
        LIMIT 5";

    if($stmt_orders = mysqli_prepare($link, $sql_orders)) {
        mysqli_stmt_bind_param($stmt_orders, "i", $restaurant_id);
        mysqli_stmt_execute($stmt_orders);
        $recent_orders = mysqli_fetch_all(mysqli_stmt_get_result($stmt_orders), MYSQLI_ASSOC);
        mysqli_stmt_close($stmt_orders);
    }
}

mysqli_close($link);
$active_page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Dashboard - Foodie</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen bg-gray-100">
        <?php require_once 'partials/sidebar.php'; ?>
        <div class="flex flex-col flex-1 overflow-y-auto">
            <?php require_once 'partials/header.php'; ?>
            <div class="p-4 md:p-6">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Dashboard</h1>
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Reviews</p>
                            <p class="text-3xl font-bold text-gray-900"><?php echo number_format($stats['total_reviews'] ?? 0); ?></p>
                        </div>
                        <div class="bg-yellow-100 text-yellow-600 p-3 rounded-full"><i data-lucide="star" class="w-6 h-6"></i></div>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Orders</p>
                            <p class="text-3xl font-bold text-gray-900"><?php echo number_format($stats['total_orders'] ?? 0); ?></p>
                        </div>
                        <div class="bg-blue-100 text-blue-600 p-3 rounded-full"><i data-lucide="shopping-cart" class="w-6 h-6"></i></div>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Pending Orders</p>
                            <p class="text-3xl font-bold text-gray-900"><?php echo number_format($stats['pending_orders'] ?? 0); ?></p>
                        </div>
                        <div class="bg-orange-100 text-orange-600 p-3 rounded-full"><i data-lucide="loader" class="w-6 h-6"></i></div>
                    </div>
                </div>

                <!-- Recent Orders Table -->
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Recent Orders</h2>
                    <div class="bg-white rounded-lg shadow-md overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (!empty($recent_orders)): foreach($recent_orders as $order): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?php echo $order['id']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date("M d, Y", strtotime($order['order_date'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php 
                                        switch($order['status']) {
                                            case 'delivered': echo 'bg-green-100 text-green-800'; break;
                                            case 'cancelled': echo 'bg-red-100 text-red-800'; break;
                                            default: echo 'bg-yellow-100 text-yellow-800';
                                        }
                                    ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $order['status']))); ?></span></td>
                                </tr>
                                <?php endforeach; else: ?>
                                <tr><td colspan="5" class="text-center py-10 text-gray-500">No recent orders found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../js/script.js"></script>
</body>
</html>
