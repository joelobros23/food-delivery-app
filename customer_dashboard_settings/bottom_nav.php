<?php
// customer_dashboard_settings/bottom_nav.php

if(!isset($active_page)) { $active_page = ''; }

$cart_count = 0;
$unread_message_count = 0;
$active_order_count = 0;

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && defined('DB_HOST')) {
    
    $nav_db_link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($nav_db_link) {
        $customer_id = $_SESSION['id'];
        
        // Get cart count
        $sql_cart = "SELECT SUM(quantity) as total FROM cart_items WHERE customer_id = ?";
        if ($stmt_cart = mysqli_prepare($nav_db_link, $sql_cart)) {
            mysqli_stmt_bind_param($stmt_cart, "i", $customer_id);
            mysqli_stmt_execute($stmt_cart);
            mysqli_stmt_bind_result($stmt_cart, $count);
            if(mysqli_stmt_fetch($stmt_cart)) { $cart_count = $count ?? 0; }
            mysqli_stmt_close($stmt_cart);
        }

        // Get unread message count
        $sql_msg = "SELECT COUNT(id) FROM messages WHERE receiver_id = ? AND is_read = 0";
         if ($stmt_msg = mysqli_prepare($nav_db_link, $sql_msg)) {
            mysqli_stmt_bind_param($stmt_msg, "i", $customer_id);
            mysqli_stmt_execute($stmt_msg);
            mysqli_stmt_bind_result($stmt_msg, $count);
            if(mysqli_stmt_fetch($stmt_msg)) { $unread_message_count = $count ?? 0; }
            mysqli_stmt_close($stmt_msg);
        }

        // --- FIX: This query now correctly counts only orders with UNSEEN status updates on initial page load ---
        $sql_orders = "SELECT COUNT(id) FROM orders WHERE customer_id = ? AND status_viewed_by_customer = 0";
         if ($stmt_orders = mysqli_prepare($nav_db_link, $sql_orders)) {
            mysqli_stmt_bind_param($stmt_orders, "i", $customer_id);
            mysqli_stmt_execute($stmt_orders);
            mysqli_stmt_bind_result($stmt_orders, $count);
            if(mysqli_stmt_fetch($stmt_orders)) { $active_order_count = $count ?? 0; }
            mysqli_stmt_close($stmt_orders);
        }
        
        mysqli_close($nav_db_link);
    }
}
?>
<!-- Mobile Bottom Navigation -->
<div id="bottom-navigation" class="md:hidden fixed inset-x-0 bottom-0 z-30 bg-white border-t border-gray-200 shadow-lg">
    <div class="flex justify-around items-center h-16">
        <a href="customer_dashboard.php" class="flex flex-col items-center justify-center w-full text-gray-600 hover:text-orange-600 <?php echo ($active_page == 'dashboard') ? 'text-orange-600' : ''; ?>"><i data-lucide="home" class="w-6 h-6 mb-1"></i><span class="text-xs font-medium">Home</span></a>
        <a href="search.php" class="flex flex-col items-center justify-center w-full text-gray-600 hover:text-orange-600 <?php echo ($active_page == 'search') ? 'text-orange-600' : ''; ?>"><i data-lucide="search" class="w-6 h-6 mb-1"></i><span class="text-xs font-medium">Search</span></a>
        <a href="cart.php" class="relative flex flex-col items-center justify-center w-full text-gray-600 hover:text-orange-600 <?php echo ($active_page == 'cart') ? 'text-orange-600' : ''; ?>">
            <i data-lucide="shopping-cart" class="w-6 h-6 mb-1"></i>
            <span class="text-xs font-medium">Cart</span>
            <?php if ($cart_count > 0): ?>
                <span id="cart-count-badge" class="absolute top-0 right-4 text-xs bg-red-500 text-white rounded-full px-1.5 py-0.5"><?php echo $cart_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="orders.php" class="relative flex flex-col items-center justify-center w-full text-gray-600 hover:text-orange-600 <?php echo ($active_page == 'orders') ? 'text-orange-600' : ''; ?>">
            <i data-lucide="history" class="w-6 h-6 mb-1"></i>
            <span class="text-xs font-medium">Orders</span>
            <span id="order-status-badge" class="absolute top-0 right-4 text-xs bg-orange-500 text-white rounded-full px-1.5 py-0.5 <?php echo ($active_order_count > 0) ? '' : 'hidden'; ?>"><?php echo $active_order_count; ?></span>
        </a>
        <a href="inbox.php" class="relative flex flex-col items-center justify-center w-full text-gray-600 hover:text-orange-600 <?php echo ($active_page == 'inbox') ? 'text-orange-600' : ''; ?>">
            <i data-lucide="inbox" class="w-6 h-6 mb-1"></i>
            <span class="text-xs font-medium">Inbox</span>
             <?php if ($unread_message_count > 0): ?>
                <span id="message-count-badge" class="absolute top-0 right-4 text-xs bg-red-500 text-white rounded-full px-1.5 py-0.5"><?php echo $unread_message_count; ?></span>
            <?php else: ?>
                <span id="message-count-badge" class="absolute top-0 right-4 text-xs bg-red-500 text-white rounded-full px-1.5 py-0.5 hidden">0</span>
             <?php endif; ?>
        </a>
    </div>
</div>

<!-- WebSocket script for real-time indicators -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const messageBadge = document.getElementById('message-count-badge');
    const orderStatusBadge = document.getElementById('order-status-badge');
    const currentUserId = <?php echo isset($_SESSION['id']) ? $_SESSION['id'] : 'null'; ?>;
    const wsUrl = "ws://localhost:8080";

    if (!currentUserId) return; 

    const conn = new WebSocket(wsUrl);

    conn.onmessage = function(e) {
        try {
            const data = JSON.parse(e.data);

            if (data.type === 'new_message_notification' && data.for_receiver_id == currentUserId) {
                if (data.new_count > 0) {
                    messageBadge.textContent = data.new_count;
                    messageBadge.classList.remove('hidden');
                } else {
                    messageBadge.classList.add('hidden');
                }
            }

            if (data.type === 'order_update_notification' && data.for_customer_id == currentUserId) {
                fetch('api/get_unseen_order_count.php')
                    .then(response => response.json())
                    .then(countData => {
                        if (countData.success && countData.count > 0) {
                            orderStatusBadge.textContent = countData.count;
                            orderStatusBadge.classList.remove('hidden');
                        } else {
                            orderStatusBadge.classList.add('hidden');
                        }
                    });
            }

        } catch (error) { /* Ignore non-JSON messages */ }
    };

    conn.onopen = function(e) { console.log("Bottom nav WebSocket connection established."); };
    conn.onerror = function(e) { console.error("Bottom nav WebSocket error:", e); };
});
</script>
