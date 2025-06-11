<?php
// store/orders.php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'store') {
    header("location: ../login.php");
    exit;
}

require_once "../db_connection/config.php";

$store_owner_id = $_SESSION['id'];
$restaurant_id = null;
$restaurant_name = "My Restaurant"; 

// Fetch the restaurant ID and name
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

// Fetch all orders for the restaurant, categorized
$orders = [
    'pending' => [],
    'active' => [],
    'completed' => []
];

if ($restaurant_id) {
    $sql_orders = "
        SELECT o.id, o.order_date, o.total_amount, o.status, u.full_name as customer_name, u.address as customer_address
        FROM orders o
        JOIN users u ON o.customer_id = u.id
        WHERE o.restaurant_id = ?
        ORDER BY FIELD(o.status, 'pending', 'preparing', 'out_for_delivery', 'delivered', 'cancelled'), o.order_date DESC";

    if($stmt_orders = mysqli_prepare($link, $sql_orders)) {
        mysqli_stmt_bind_param($stmt_orders, "i", $restaurant_id);
        mysqli_stmt_execute($stmt_orders);
        $result = mysqli_stmt_get_result($stmt_orders);
        while ($row = mysqli_fetch_assoc($result)) {
            if ($row['status'] == 'pending') {
                $orders['pending'][] = $row;
            } elseif (in_array($row['status'], ['preparing', 'out_for_delivery'])) {
                $orders['active'][] = $row;
            } else {
                $orders['completed'][] = $row;
            }
        }
        mysqli_stmt_close($stmt_orders);
    }
}

mysqli_close($link);
$active_page = 'orders';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Foodie</title>
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
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Manage Orders</h1>
                
                <!-- Tabs -->
                <div class="mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                            <button data-target="pending" class="tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm text-orange-600 border-orange-500">Pending <span class="bg-red-500 text-white text-xs rounded-full px-2 py-0.5 ml-1"><?php echo count($orders['pending']); ?></span></button>
                            <button data-target="active" class="tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">Active <span class="bg-blue-500 text-white text-xs rounded-full px-2 py-0.5 ml-1"><?php echo count($orders['active']); ?></span></button>
                            <button data-target="completed" class="tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">Completed</button>
                        </nav>
                    </div>
                </div>

                <!-- Orders Content -->
                <div id="pending" class="tab-content space-y-4">
                    <?php if (empty($orders['pending'])): ?>
                        <p class="text-gray-500 text-center py-8">No pending orders.</p>
                    <?php else: foreach($orders['pending'] as $order): ?>
                        <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-orange-500">
                             <div class="flex justify-between items-center"><p class="font-bold">Order #<?php echo $order['id']; ?></p><span class="text-xs text-gray-500"><?php echo date("M d, Y, g:i a", strtotime($order['order_date'])); ?></span></div>
                             <p class="text-sm text-gray-600">Customer: <?php echo htmlspecialchars($order['customer_name']); ?></p>
                             <div class="mt-4 flex justify-between items-center">
                                <p class="text-lg font-bold">₱<?php echo number_format($order['total_amount'], 2); ?></p>
                                <div class="space-x-2">
                                    <button class="px-3 py-1 bg-red-500 text-white text-sm rounded-md hover:bg-red-600">Reject</button>
                                    <button class="px-3 py-1 bg-green-500 text-white text-sm rounded-md hover:bg-green-600">Accept</button>
                                </div>
                             </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>

                 <div id="active" class="tab-content hidden space-y-4">
                     <?php if (empty($orders['active'])): ?>
                        <p class="text-gray-500 text-center py-8">No active orders.</p>
                    <?php else: foreach($orders['active'] as $order): ?>
                        <div class="bg-white p-4 rounded-lg shadow-md">
                             <div class="flex justify-between items-center"><p class="font-bold">Order #<?php echo $order['id']; ?></p><span class="px-3 py-1 text-xs font-semibold rounded-full <?php echo $order['status'] == 'preparing' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'; ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $order['status']))); ?></span></div>
                             <p class="text-sm text-gray-600">Customer: <?php echo htmlspecialchars($order['customer_name']); ?></p>
                             <div class="mt-4 flex justify-between items-center">
                                <p class="text-lg font-bold">₱<?php echo number_format($order['total_amount'], 2); ?></p>
                                <?php if($order['status'] == 'preparing'): ?>
                                <button class="px-3 py-1 bg-orange-500 text-white text-sm rounded-md hover:bg-orange-600">Ready for Delivery</button>
                                <?php endif; ?>
                             </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>

                 <div id="completed" class="tab-content hidden">
                     <div class="bg-white rounded-lg shadow-md overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th></tr></thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($orders['completed'])): ?>
                                    <tr><td colspan="5" class="text-center py-10 text-gray-500">No completed orders found.</td></tr>
                                <?php else: foreach($orders['completed'] as $order): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?php echo $order['id']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date("M d, Y", strtotime($order['order_date'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $order['status'] === 'delivered' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>"><?php echo htmlspecialchars(ucfirst($order['status'])); ?></span></td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab-btn');
            const contents = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    tabs.forEach(t => { t.classList.remove('text-orange-600', 'border-orange-500'); t.classList.add('text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300'); });
                    tab.classList.add('text-orange-600', 'border-orange-500');
                    tab.classList.remove('text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                    contents.forEach(content => content.classList.add('hidden'));
                    document.getElementById(tab.dataset.target).classList.remove('hidden');
                });
            });
        });
    </script>
    <script src="../js/script.js"></script>
</body>
</html>
