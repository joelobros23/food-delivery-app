<?php
// track_order.php
require_once "app_config.php";
session_start();

// Security checks
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'customer') {
    header("location: login.php");
    exit;
}
if (!isset($_GET['id']) || empty(trim($_GET['id']))) {
    header("location: orders.php");
    exit;
}

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($link === false) { die("DB Connection Error"); }

$order_id = trim($_GET['id']);
$customer_id = $_SESSION['id'];
$order_details = null;
$order_items = [];

// Fetch order details, ensuring it belongs to the logged-in customer
$sql_order = "
    SELECT 
        o.id, o.order_date, o.total_amount, o.status, o.payment_method, o.delivery_address,
        r.name as restaurant_name
    FROM orders o
    JOIN restaurants r ON o.restaurant_id = r.id
    WHERE o.id = ? AND o.customer_id = ?";

if ($stmt = mysqli_prepare($link, $sql_order)) {
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $customer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) == 1) {
        $order_details = mysqli_fetch_assoc($result);
    } else {
        header("location: orders.php"); exit;
    }
    mysqli_stmt_close($stmt);
}

// Fetch items for this order
if ($order_details) {
    $sql_items = "
        SELECT oi.quantity, oi.price_per_item, mi.name as item_name
        FROM order_items oi
        JOIN menu_items mi ON oi.item_id = mi.id
        WHERE oi.order_id = ?";
    if($stmt_items = mysqli_prepare($link, $sql_items)) {
        mysqli_stmt_bind_param($stmt_items, "i", $order_id);
        mysqli_stmt_execute($stmt_items);
        $order_items = mysqli_fetch_all(mysqli_stmt_get_result($stmt_items), MYSQLI_ASSOC);
        mysqli_stmt_close($stmt_items);
    }
}

mysqli_close($link);

if (is_null($order_details)) { header("location: orders.php"); exit; }

$active_page = 'orders';
$all_statuses = ['pending', 'preparing', 'out_for_delivery', 'delivered'];
$current_status_index = array_search($order_details['status'], $all_statuses);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order #<?php echo $order_details['id']; ?> - <?php echo SITE_NAME; ?></title>
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
                 <a href="orders.php" class="flex items-center text-gray-600 hover:text-orange-600 mb-6"><i data-lucide="arrow-left" class="w-5 h-5 mr-2"></i>Back to Orders</a>
                <div class="max-w-4xl mx-auto">
                    <div class="bg-white rounded-lg shadow-lg p-6 md:p-8">
                        <div class="flex flex-col md:flex-row justify-between md:items-center border-b pb-6 mb-6">
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">Order #<?php echo $order_details['id']; ?></h1>
                                <p class="text-gray-500">From <?php echo htmlspecialchars($order_details['restaurant_name']); ?></p>
                            </div>
                            <div class="text-left md:text-right mt-4 md:mt-0">
                                <p class="font-bold text-lg"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $order_details['status']))); ?></p>
                                <p class="text-sm text-gray-500">Placed on <?php echo date("F d, Y", strtotime($order_details['order_date'])); ?></p>
                            </div>
                        </div>

                        <!-- Status Timeline -->
                        <div class="mb-8">
                            <?php if($order_details['status'] != 'cancelled'): ?>
                            <div class="flex justify-between items-center text-center">
                                <?php foreach($all_statuses as $index => $status): 
                                    $isCompleted = $current_status_index >= $index;
                                ?>
                                <div class="flex-1">
                                    <div class="flex justify-center items-center h-10 w-10 mx-auto rounded-full <?php echo $isCompleted ? 'bg-orange-500 text-white' : 'bg-gray-200 text-gray-500'; ?>">
                                        <?php if ($status == 'pending'): ?><i data-lucide="receipt"></i><?php endif; ?>
                                        <?php if ($status == 'preparing'): ?><i data-lucide="utensils-crossed"></i><?php endif; ?>
                                        <?php if ($status == 'out_for_delivery'): ?><i data-lucide="bike"></i><?php endif; ?>
                                        <?php if ($status == 'delivered'): ?><i data-lucide="home"></i><?php endif; ?>
                                    </div>
                                    <p class="mt-2 text-xs font-semibold <?php echo $isCompleted ? 'text-gray-800' : 'text-gray-500'; ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $status))); ?></p>
                                </div>
                                <?php if($index < count($all_statuses) - 1): ?>
                                    <div class="flex-1 -mt-6"><div class="h-1 <?php echo $current_status_index > $index ? 'bg-orange-500' : 'bg-gray-200'; ?>"></div></div>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="text-center p-6 bg-red-50 rounded-lg">
                                 <i data-lucide="x-circle" class="w-12 h-12 mx-auto text-red-500"></i>
                                 <p class="mt-2 font-bold text-red-700">Order Cancelled</p>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Order Items -->
                        <div class="border-t pt-6">
                            <h2 class="text-xl font-bold mb-4">Your Items</h2>
                            <div class="space-y-3">
                                <?php foreach($order_items as $item): ?>
                                <div class="flex justify-between items-center text-gray-700">
                                    <span><?php echo $item['quantity']; ?> x <?php echo htmlspecialchars($item['item_name']); ?></span>
                                    <span>₱<?php echo number_format($item['price_per_item'] * $item['quantity'], 2); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                             <div class="border-t my-4"></div>
                             <div class="flex justify-between font-bold text-xl text-gray-900">
                                 <span>Total</span>
                                 <span>₱<?php echo number_format($order_details['total_amount'], 2); ?></span>
                             </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php require_once 'customer_dashboard_settings/bottom_nav.php'; ?>
        </div>
    </div>
    <script src="js/script.js"></script>
</body>
</html>
