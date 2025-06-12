<?php
// store/orders_completed.php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'store') { header("location: ../login.php"); exit; }
require_once "../db_connection/config.php";

$store_owner_id = $_SESSION['id'];
$restaurant_id = null;
$restaurant_name = "My Restaurant"; 
$completed_orders = [];

$sql_resto_info = "SELECT id, name FROM restaurants WHERE user_id = ? LIMIT 1";
if($stmt_resto = mysqli_prepare($link, $sql_resto_info)){
    mysqli_stmt_bind_param($stmt_resto, "i", $store_owner_id);
    mysqli_stmt_execute($stmt_resto);
    mysqli_stmt_bind_result($stmt_resto, $r_id, $r_name);
    if(mysqli_stmt_fetch($stmt_resto)){ $restaurant_id = $r_id; $restaurant_name = $r_name; }
    mysqli_stmt_close($stmt_resto);
}

if ($restaurant_id) {
    $sql = "SELECT o.id, o.order_date, o.total_amount, o.status, o.payment_method, u.full_name as customer_name FROM orders o JOIN users u ON o.customer_id = u.id WHERE o.restaurant_id = ? AND o.status IN ('delivered', 'cancelled') ORDER BY o.order_date DESC";
    if($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $restaurant_id);
        mysqli_stmt_execute($stmt);
        $completed_orders = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
    }
}
$active_tab = 'completed';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Completed Orders</title>
    <!-- Meta tags and links -->
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
                <div id="completed"><div class="bg-white rounded-lg shadow-md overflow-x-auto"><table class="min-w-full"><thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th></tr></thead><tbody class="bg-white divide-y divide-gray-200"><?php if (empty($completed_orders)): ?><tr><td colspan="6" class="text-center py-10 text-gray-500">No completed orders found.</td></tr><?php else: foreach($completed_orders as $order): ?><tr><td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?php echo $order['id']; ?></td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800"><?php echo htmlspecialchars($order['customer_name']); ?></td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date("M d, Y", strtotime($order['order_date'])); ?></td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">₱<?php echo number_format($order['total_amount'], 2); ?></td><td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800"><?php echo strtoupper($order['payment_method']); ?></td><td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $order['status'] === 'delivered' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>"><?php echo htmlspecialchars(ucfirst($order['status'])); ?></span></td></tr><?php endforeach; endif; ?></tbody></table></div></div>
            </div>
        </div>
    </div>
    <script src="../js/store_orders.js"></script>
    <script src="../js/script.js"></script>
</body>
</html>
