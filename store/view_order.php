<?php
// store/view_order.php
require_once "../app_config.php";
session_start();

// Security checks
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'store') {
    header("location: ../login.php");
    exit;
}
if (!isset($_GET['id']) || empty(trim($_GET['id']))) {
    header("location: store/orders.php");
    exit;
}

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($link === false) { die("DB Connection Error"); }

$order_id = trim($_GET['id']);
$store_owner_id = $_SESSION['id'];
$order_details = null;
$order_items = [];
$all_items_prepared = false; 

// Fetch order details
$sql_order = "
    SELECT 
        o.id, o.customer_id, o.order_date, o.total_amount, o.status, o.payment_method, o.delivery_address,
        u.full_name as customer_name
    FROM orders o
    JOIN restaurants r ON o.restaurant_id = r.id
    JOIN users u ON o.customer_id = u.id
    WHERE o.id = ? AND r.user_id = ?";

if ($stmt = mysqli_prepare($link, $sql_order)) {
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $store_owner_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) == 1) {
        $order_details = mysqli_fetch_assoc($result);
    } else {
        header("location: store/orders.php"); exit;
    }
    mysqli_stmt_close($stmt);
}

// Fetch items for this order and check if all are prepared
if ($order_details) {
    $sql_items = "SELECT oi.id as order_item_id, oi.quantity, oi.price_per_item, oi.is_prepared, mi.name as item_name FROM order_items oi JOIN menu_items mi ON oi.item_id = mi.id WHERE oi.order_id = ?";
    if($stmt_items = mysqli_prepare($link, $sql_items)) {
        mysqli_stmt_bind_param($stmt_items, "i", $order_id);
        mysqli_stmt_execute($stmt_items);
        $result_items = mysqli_stmt_get_result($stmt_items);
        $prepared_count = 0;
        while($item = mysqli_fetch_assoc($result_items)) {
            $order_items[] = $item;
            if ($item['is_prepared']) { $prepared_count++; }
        }
        if (count($order_items) > 0 && count($order_items) == $prepared_count) {
            $all_items_prepared = true;
        }
        mysqli_stmt_close($stmt_items);
    }
} else {
    header("location: store/orders.php");
    exit;
}

mysqli_close($link);
$active_page = 'orders';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Order #<?php echo $order_details['id']; ?> - <?php echo SITE_NAME; ?></title>
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
                <a href="orders_active.php" class="flex items-center text-gray-600 hover:text-orange-600 mb-6 font-medium"><i data-lucide="arrow-left" class="w-5 h-5 mr-2"></i>Back to Active Orders</a>
                <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-6 md:p-8">
                    <div class="flex justify-between items-start border-b pb-4">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Order #<?php echo $order_details['id']; ?></h1>
                            <p class="text-sm text-gray-500">Placed: <?php echo date("M d, Y, g:i a", strtotime($order_details['order_date'])); ?></p>
                        </div>
                        <span id="order-status-badge" class="px-3 py-1 text-sm font-semibold rounded-full <?php echo $order_details['status'] == 'preparing' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'; ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $order_details['status']))); ?></span>
                    </div>
                    <div class="py-4 border-b">
                        <div class="flex justify-between items-center">
                             <h2 class="font-semibold text-gray-800 mb-2">Customer Details</h2>
                             <a href="messages.php?order_id=<?php echo $order_details['id']; ?>&receiver_id=<?php echo $order_details['customer_id']; ?>" class="flex items-center text-sm font-medium text-orange-600 hover:underline"><i data-lucide="message-circle" class="w-4 h-4 mr-1"></i>Message Customer</a>
                        </div>
                        <p class="text-gray-700"><?php echo htmlspecialchars($order_details['customer_name']); ?></p>
                        <p class="text-gray-500 text-sm"><?php echo htmlspecialchars($order_details['delivery_address']); ?></p>
                    </div>
                    <div class="py-4">
                        <h2 class="font-semibold text-gray-800 mb-2">Order Items</h2>
                        <div class="space-y-3">
                            <?php foreach($order_items as $item): ?>
                            <div class="flex justify-between items-center text-gray-700">
                                <div class="item-text-container"><span class="<?php if($item['is_prepared']) echo 'line-through text-gray-400'; ?>"><?php echo $item['quantity']; ?> x <?php echo htmlspecialchars($item['item_name']); ?></span></div>
                                <div class="flex items-center space-x-4">
                                     <span>₱<?php echo number_format($item['price_per_item'] * $item['quantity'], 2); ?></span>
                                     <label class="relative inline-flex items-center cursor-pointer"><input type="checkbox" data-order-item-id="<?php echo $item['order_item_id']; ?>" class="item-status-toggle sr-only peer" <?php if($item['is_prepared']) echo 'checked'; ?>><div class="w-24 h-8 bg-gray-300 rounded-full peer peer-checked:bg-green-500 transition-colors duration-300"></div><div class="absolute top-1 left-1 bg-white border-gray-300 border rounded-full h-6 w-6 transition-transform duration-300 peer-checked:translate-x-16"></div><span class="absolute text-xs font-bold text-white left-4 top-1/2 -translate-y-1/2 opacity-0 peer-checked:opacity-100 transition-opacity">Done</span><span class="absolute text-xs font-bold text-gray-600 right-3 top-1/2 -translate-y-1/2 opacity-100 peer-checked:opacity-0 transition-opacity">Undone</span></label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                         <div class="border-t my-4"></div>
                         <div class="flex justify-between font-bold text-xl text-gray-900"><span>Total</span><span>₱<?php echo number_format($order_details['total_amount'], 2); ?></span></div>
                    </div>
                    <div id="send-to-rider-container" class="mt-6 pt-6 border-t <?php if (!($order_details['status'] == 'preparing' && $all_items_prepared)) echo 'hidden'; ?>"><button class="ready-btn w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700" data-order-id="<?php echo $order_details['id']; ?>"><i data-lucide="send" class="w-5 h-5 mr-2"></i>Send to Rider</button></div>
                </div>
            </div>
        </div>
    </div>
    <script src="../js/script.js"></script>
    <script src="../js/store_view_order.js"></script>
          <script>
        document.addEventListener('DOMContentLoaded', function() {
            const statusBadge = document.getElementById('order-status-badge');
            const sendToRiderContainer = document.getElementById('send-to-rider-container');

            // --- ITEM STATUS TOGGLE LOGIC ---
            document.body.addEventListener('change', function(e) {
                if (e.target.classList.contains('item-status-toggle')) {
                    const toggle = e.target;
                    const orderItemId = toggle.dataset.orderItemId;
                    const itemRowText = toggle.closest('.flex.justify-between').querySelector('.item-text-container span');
                    const formData = new FormData();
                    formData.append('order_item_id', orderItemId);

                    fetch('manage_item_status.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            itemRowText.classList.toggle('line-through', toggle.checked);
                            itemRowText.classList.toggle('text-gray-400', toggle.checked);
                            const allToggles = document.querySelectorAll('.item-status-toggle');
                            const allChecked = Array.from(allToggles).every(t => t.checked);
                            sendToRiderContainer.classList.toggle('hidden', !allChecked);
                        } else {
                            toggle.checked = !toggle.checked;
                            alert("Error: " + data.message);
                        }
                    });
                }
            });
            
            // --- SEND TO RIDER BUTTON LOGIC ---
            document.body.addEventListener('click', function(e) {
                const readyBtn = e.target.closest('.ready-btn');
                if (readyBtn) {
                    const orderId = readyBtn.dataset.orderId;
                    const formData = new FormData();
                    formData.append('order_id', orderId);
                    formData.append('action', 'ready_for_delivery');
                    
                    // Show loading state
                    readyBtn.innerHTML = '<i data-lucide="loader" class="w-5 h-5 mr-2 animate-spin"></i> Sending...';
                    readyBtn.disabled = true;
                    lucide.createIcons();
                    
                    fetch('manage_order.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Change button to "Waiting for rider" text
                            sendToRiderContainer.innerHTML = '<p class="text-center font-semibold text-purple-800">Waiting for rider...</p>';
                            // Update status badge at the top
                            statusBadge.textContent = 'Out for Delivery';
                            statusBadge.className = 'px-3 py-1 text-sm font-semibold rounded-full bg-purple-100 text-purple-800';
                        } else {
                            alert('Error: ' + data.message);
                            // Restore button
                            readyBtn.innerHTML = '<i data-lucide="send" class="w-5 h-5 mr-2"></i> Send to Rider';
                            readyBtn.disabled = false;
                            lucide.createIcons();
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>
