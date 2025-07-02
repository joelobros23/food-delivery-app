<?php
// store/orders_active.php
require_once "../app_config.php";
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'store') { header("location: ../login.php"); exit; }

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($link === false) { die("DB Connection Error"); }

$store_owner_id = $_SESSION['id'];
$restaurant_id = null;
$restaurant_name = "My Restaurant"; 
$active_orders = [];

$sql_resto_info = "SELECT id, name FROM restaurants WHERE user_id = ? LIMIT 1";
if($stmt_resto = mysqli_prepare($link, $sql_resto_info)){
    mysqli_stmt_bind_param($stmt_resto, "i", $store_owner_id);
    mysqli_stmt_execute($stmt_resto);
    mysqli_stmt_bind_result($stmt_resto, $r_id, $r_name);
    if(mysqli_stmt_fetch($stmt_resto)){ $restaurant_id = $r_id; $restaurant_name = $r_name; }
    mysqli_stmt_close($stmt_resto);
}

if ($restaurant_id) {
    // UPDATED: This query now fetches counts of total vs prepared items for each order.
    $sql = "
        SELECT 
            o.id, o.order_date, o.total_amount, o.status, o.payment_method, 
            u.full_name as customer_name,
            (SELECT COUNT(oi.id) FROM order_items oi WHERE oi.order_id = o.id) as total_items,
            (SELECT COUNT(oi.id) FROM order_items oi WHERE oi.order_id = o.id AND oi.is_prepared = 1) as prepared_items
        FROM orders o 
        JOIN users u ON o.customer_id = u.id 
        WHERE o.restaurant_id = ? AND o.status IN ('preparing', 'out_for_delivery') 
        ORDER BY o.order_date DESC";

    if($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $restaurant_id);
        mysqli_stmt_execute($stmt);
        $active_orders = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
    }
}
$active_tab = 'active';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Active Orders - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script><script src="https://unpkg.com/lucide@latest"></script><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="../style.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen bg-gray-100">
        <?php require_once 'partials/sidebar.php'; ?>
        <div class="flex flex-col flex-1 overflow-y-auto">
            <?php require_once 'partials/header.php'; ?>
            <div class="p-4 md:p-6">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Manage Orders</h1>
                <?php require_once 'partials/order_tabs.php'; ?>
                <div id="active" class="space-y-4">
                    <?php if (empty($active_orders)): ?><p class="no-orders-msg text-gray-500 text-center py-8">No active orders.</p><?php else: foreach($active_orders as $order): 
                        $all_items_done = ($order['total_items'] > 0 && $order['total_items'] == $order['prepared_items']);
                    ?>
                    <div class="order-card bg-white p-4 rounded-lg shadow-md" data-order-id="<?php echo $order['id']; ?>">
                        <div class="flex justify-between items-center">
                            <p class="font-bold">Order #<?php echo $order['id']; ?></p>
                            <p class="status-badge text-sm font-semibold <?php 
                                if($order['status'] == 'out_for_delivery') {
                                    echo 'text-purple-800';
                                } else {
                                    echo $all_items_done ? 'text-green-800' : 'text-blue-800';
                                }
                            ?>">
                                <?php
                                    if ($order['status'] == 'preparing') {
                                        echo $all_items_done ? 'All Items Done' : 'Preparing';
                                    } else {
                                        echo 'Out for Delivery';
                                    }
                                ?>
                            </p>
                        </div>
                        <p class="text-sm text-gray-600">Customer: <?php echo htmlspecialchars($order['customer_name']); ?></p>
                        <p class="text-sm text-gray-600 mt-1">Payment: <span class="font-medium text-gray-800"><?php echo strtoupper($order['payment_method']); ?></span></p>
                        <div class="mt-4 border-t pt-4 flex justify-between items-center">
                            <a href="view_order.php?id=<?php echo $order['id']; ?>" class="px-3 py-1 bg-gray-200 text-gray-800 text-sm rounded-md hover:bg-gray-300">View Order</a>
                            <div class="actions-container text-right">
                                <?php if($order['status'] == 'preparing'): ?>
                                    <?php if($all_items_done): ?>
                                        <button class="ready-btn px-3 py-1 bg-orange-500 text-white text-sm rounded-md hover:bg-orange-600">Ready for Delivery</button>
                                    <?php else: ?>
                                        <p class="text-sm font-semibold text-gray-700">Prepare and wait for the rider to pick-up</p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="text-sm font-semibold text-purple-800">Waiting for rider</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
        <div style="padding-bottom:100px;" class="md:hidden"></div>
    </div>
     <script src="../js/store_orders.js"></script>
    <script src="../js/script.js"></script>
</body>
</html>
