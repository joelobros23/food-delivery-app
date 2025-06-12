<?php
// store/orders.php - Handles PENDING orders
require_once "../app_config.php";
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'store') {
    header("location: ../login.php");
    exit;
}

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($link === false) { die("DB Connection Error"); }

$store_owner_id = $_SESSION['id'];
$restaurant_id = null;
$restaurant_name = "My Restaurant"; 
$pending_orders = [];

$sql_resto_info = "SELECT id, name FROM restaurants WHERE user_id = ? LIMIT 1";
if($stmt_resto = mysqli_prepare($link, $sql_resto_info)){
    mysqli_stmt_bind_param($stmt_resto, "i", $store_owner_id);
    mysqli_stmt_execute($stmt_resto);
    mysqli_stmt_bind_result($stmt_resto, $r_id, $r_name);
    if(mysqli_stmt_fetch($stmt_resto)){ $restaurant_id = $r_id; $restaurant_name = $r_name; }
    mysqli_stmt_close($stmt_resto);
}

if ($restaurant_id) {
    $sql = "SELECT o.id, o.order_date, o.total_amount, o.status, o.payment_method, u.full_name as customer_name FROM orders o JOIN users u ON o.customer_id = u.id WHERE o.restaurant_id = ? AND o.status = 'pending' ORDER BY o.order_date DESC";
    if($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $restaurant_id);
        mysqli_stmt_execute($stmt);
        $pending_orders = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
    }
}
$active_tab = 'pending';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Orders - <?php echo SITE_NAME; ?></title>
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
                <div class="space-y-4">
                    <?php if (empty($pending_orders)): ?><p class="text-gray-500 text-center py-8">No pending orders.</p><?php else: foreach($pending_orders as $order): ?><div class="order-card bg-white p-4 rounded-lg shadow-md border-l-4 border-orange-500" data-order-id="<?php echo $order['id']; ?>"><div class="flex justify-between items-center"><p class="font-bold">Order #<?php echo $order['id']; ?></p><span class="text-xs text-gray-500"><?php echo date("M d, Y, g:i a", strtotime($order['order_date'])); ?></span></div><p class="text-sm text-gray-600">Customer: <?php echo htmlspecialchars($order['customer_name']); ?></p><p class="text-sm text-gray-600 mt-1">Payment: <span class="font-medium text-gray-800"><?php echo strtoupper($order['payment_method']); ?></span></p><div class="mt-4 flex justify-between items-center"><p class="text-lg font-bold">₱<?php echo number_format($order['total_amount'], 2); ?></p><div class="space-x-2"><button class="reject-btn px-3 py-1 bg-red-500 text-white text-sm rounded-md hover:bg-red-600">Reject</button><button class="accept-btn px-3 py-1 bg-green-500 text-white text-sm rounded-md hover:bg-green-600">Accept</button></div></div></div><?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="../js/store_orders.js"></script>
    <script src="../js/script.js"></script>
</body>
</html>
