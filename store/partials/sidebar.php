<?php
// store/partials/sidebar.php
if(!isset($active_page)) { $active_page = ''; }

// --- Start Notification Count Logic ---
$new_order_count = 0;
$new_message_count = 0;
$store_restaurant_id = null;

// Avoid re-defining constants and functions if the file is included multiple times.
if (!defined('DB_HOST')) {
    // Adjust the path based on the location of sidebar.php relative to app_config.php
    require_once dirname(__DIR__, 2) . "/app_config.php";
}

if (!function_exists('get_db_connection_for_sidebar')) {
    function get_db_connection_for_sidebar() {
        // Use defined constants for the database connection
        return mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    }
}

$sidebar_link = get_db_connection_for_sidebar();
if ($sidebar_link && isset($_SESSION['id'])) {
    $store_user_id = $_SESSION['id'];

    // Get the restaurant_id for the logged-in user (for order notifications)
    $sql_resto_id = "SELECT id FROM restaurants WHERE user_id = ? LIMIT 1";
    if($stmt_resto_id = mysqli_prepare($sidebar_link, $sql_resto_id)) {
        mysqli_stmt_bind_param($stmt_resto_id, "i", $store_user_id);
        if (mysqli_stmt_execute($stmt_resto_id)) {
            mysqli_stmt_bind_result($stmt_resto_id, $r_id);
            if(mysqli_stmt_fetch($stmt_resto_id)) {
                $store_restaurant_id = $r_id;
            }
        }
        mysqli_stmt_close($stmt_resto_id);
    }

    // Get new order count using the restaurant_id
    if ($store_restaurant_id) {
        $sql_orders = "SELECT COUNT(id) FROM orders WHERE restaurant_id = ? AND status = 'Pending'";
        if ($stmt_orders = mysqli_prepare($sidebar_link, $sql_orders)) {
            mysqli_stmt_bind_param($stmt_orders, "i", $store_restaurant_id);
            if (mysqli_stmt_execute($stmt_orders)) {
                mysqli_stmt_bind_result($stmt_orders, $count);
                if (mysqli_stmt_fetch($stmt_orders)) { $new_order_count = $count; }
            }
            mysqli_stmt_close($stmt_orders);
        }
    }

    // Get unread message count using the user_id
    $sql_messages = "SELECT COUNT(id) FROM messages WHERE receiver_id = ? AND is_read = 0";
    if ($stmt_messages = mysqli_prepare($sidebar_link, $sql_messages)) {
        mysqli_stmt_bind_param($stmt_messages, "i", $store_user_id);
        if (mysqli_stmt_execute($stmt_messages)) {
            mysqli_stmt_bind_result($stmt_messages, $count);
            if (mysqli_stmt_fetch($stmt_messages)) { $new_message_count = $count; }
        }
        mysqli_stmt_close($stmt_messages);
    }

    mysqli_close($sidebar_link);
}
// --- End Notification Count Logic ---
?>

<!-- Global Style Fix for Bottom Bar -->
<style>
    /* This CSS rule is the global fix.
      It targets the main scrollable area of your pages and adds padding to the bottom ONLY on mobile screens (max-width: 767px).
      This pushes the content up so it is not blocked by the fixed bottom navigation bar.
      The h-16 class on the bar is 4rem. We add extra padding for better spacing.
    */
    @media (max-width: 767px) {
        main.flex-1.overflow-y-auto {
            padding-bottom: 5.5rem; /* 88px */
        }
    }
</style>

<!-- Desktop Sidebar -->
<div class="hidden md:flex flex-col w-64 bg-gray-800 text-white">
    <div class="flex items-center justify-center h-20 shadow-md bg-gray-900">
        <a href="index.php" class="text-3xl font-bold text-white">Store Panel</a>
    </div>
    <ul class="flex flex-col py-4">
        <li>
            <a href="index.php" class="flex items-center h-12 px-6 hover:bg-gray-700 <?php echo ($active_page == 'dashboard') ? 'bg-orange-600' : ''; ?>">
                <i data-lucide="layout-dashboard" class="w-5 h-5 mr-3"></i><span class="text-sm font-medium">Dashboard</span>
            </a>
        </li>
        <li>
            <a href="orders.php" class="flex items-center justify-between h-12 px-6 hover:bg-gray-700 <?php echo ($active_page == 'orders') ? 'bg-orange-600' : ''; ?>">
                <div class="flex items-center">
                    <i data-lucide="shopping-cart" class="w-5 h-5 mr-3"></i><span class="text-sm font-medium">Orders</span>
                </div>
                <span id="order-notification-badge" class="px-2 py-0.5 ml-auto text-xs font-semibold text-white bg-red-500 rounded-full <?php echo ($new_order_count > 0) ? '' : 'hidden'; ?>"><?php echo $new_order_count; ?></span>
            </a>
        </li>
        <li>
             <a href="inbox.php" class="flex items-center justify-between h-12 px-6 hover:bg-gray-700 <?php echo ($active_page == 'inbox') ? 'bg-orange-600' : ''; ?>">
                <div class="flex items-center">
                    <i data-lucide="inbox" class="w-5 h-5 mr-3"></i><span class="text-sm font-medium">Inbox</span>
                </div>
                <span id="message-notification-badge" class="px-2 py-0.5 ml-auto text-xs font-semibold text-white bg-red-500 rounded-full <?php echo ($new_message_count > 0) ? '' : 'hidden'; ?>"><?php echo $new_message_count; ?></span>
            </a>
        </li>
        <li>
            <a href="menu.php" class="flex items-center h-12 px-6 hover:bg-gray-700 <?php echo ($active_page == 'menu') ? 'bg-orange-600' : ''; ?>">
                <i data-lucide="utensils" class="w-5 h-5 mr-3"></i><span class="text-sm font-medium">Menu Items</span>
            </a>
        </li>
        <li>
            <a href="profile.php" class="flex items-center h-12 px-6 hover:bg-gray-700 <?php echo ($active_page == 'profile') ? 'bg-orange-600' : ''; ?>">
                <i data-lucide="user-circle" class="w-5 h-5 mr-3"></i><span class="text-sm font-medium">Profile</span>
            </a>
        </li>
        <li>
            <a href="../logout.php" class="flex items-center h-12 px-6 hover:bg-gray-700"><i data-lucide="log-out" class="w-5 h-5 mr-3"></i><span class="text-sm font-medium">Logout</span></a>
        </li>
    </ul>
    <div class="px-6 py-4 mt-auto">
        <button id="enable-notifications-btn" class="w-full flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-gray-700 rounded-lg hover:bg-gray-600">
            <i data-lucide="bell" class="w-4 h-4 mr-2"></i>
            <span>Enable Notifications</span>
        </button>
    </div>
</div>

<!-- Mobile Bottom Navigation Bar -->
<div class="fixed bottom-0 left-0 z-50 w-full h-16 bg-white border-t border-gray-200 md:hidden">
    <div class="grid h-full max-w-lg grid-cols-5 mx-auto font-medium">
        <a href="index.php" class="inline-flex flex-col items-center justify-center px-5 hover:bg-gray-50 group <?php echo ($active_page == 'dashboard') ? 'text-orange-600' : 'text-gray-500 hover:text-gray-900'; ?>">
            <i data-lucide="layout-dashboard" class="w-6 h-6 mb-1"></i>
            <span class="text-xs">Dashboard</span>
        </a>
        <a href="orders.php" class="relative inline-flex flex-col items-center justify-center px-5 hover:bg-gray-50 group <?php echo ($active_page == 'orders') ? 'text-orange-600' : 'text-gray-500 hover:text-gray-900'; ?>">
            <i data-lucide="shopping-cart" class="w-6 h-6 mb-1"></i>
            <span class="text-xs">Orders</span>
            <span id="mobile-order-notification-badge" class="absolute top-1 right-3 px-1.5 py-0.5 text-[10px] font-semibold text-white bg-red-500 rounded-full <?php echo ($new_order_count > 0) ? '' : 'hidden'; ?>"><?php echo $new_order_count; ?></span>
        </a>
        <a href="inbox.php" class="relative inline-flex flex-col items-center justify-center px-5 hover:bg-gray-50 group <?php echo ($active_page == 'inbox') ? 'text-orange-600' : 'text-gray-500 hover:text-gray-900'; ?>">
            <i data-lucide="inbox" class="w-6 h-6 mb-1"></i>
            <span class="text-xs">Inbox</span>
            <span id="mobile-message-notification-badge" class="absolute top-1 right-3 px-1.5 py-0.5 text-[10px] font-semibold text-white bg-red-500 rounded-full <?php echo ($new_message_count > 0) ? '' : 'hidden'; ?>"><?php echo $new_message_count; ?></span>
        </a>
        <a href="menu.php" class="inline-flex flex-col items-center justify-center px-5 hover:bg-gray-50 group <?php echo ($active_page == 'menu') ? 'text-orange-600' : 'text-gray-500 hover:text-gray-900'; ?>">
            <i data-lucide="utensils" class="w-6 h-6 mb-1"></i>
            <span class="text-xs">Menu</span>
        </a>
        <a href="profile.php" class="inline-flex flex-col items-center justify-center px-5 hover:bg-gray-50 group <?php echo ($active_page == 'profile') ? 'text-orange-600' : 'text-gray-500 hover:text-gray-900'; ?>">
            <i data-lucide="user-circle" class="w-6 h-6 mb-1"></i>
            <span class="text-xs">Profile</span>
        </a>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Badge Elements ---
    const orderBadge = document.getElementById('order-notification-badge');
    const messageBadge = document.getElementById('message-notification-badge');
    const mobileOrderBadge = document.getElementById('mobile-order-notification-badge');
    const mobileMessageBadge = document.getElementById('mobile-message-notification-badge');
    
    // --- Notification Button ---
    const enableNotificationsBtn = document.getElementById('enable-notifications-btn');

    // --- Session Variables from PHP ---
    const currentRestaurantId = <?php echo isset($store_restaurant_id) ? $store_restaurant_id : 'null'; ?>;
    const currentUserId = <?php echo isset($_SESSION['id']) ? $_SESSION['id'] : 'null'; ?>;
    const wsUrl = "ws://localhost:8080";

    if (!currentUserId) return;

    // --- Notification Permission ---
    function requestNotificationPermission() {
        if (!("Notification" in window)) {
            console.log("This browser does not support desktop notification");
            return;
        }
        if (Notification.permission !== "denied") {
            Notification.requestPermission().then(permission => {
                if (permission === "granted") {
                    new Notification("Notifications Enabled!", { body: "You will now receive alerts for new orders and messages." });
                }
            });
        }
    }
    // The button only exists in the desktop sidebar, check if it exists before adding listener.
    if(enableNotificationsBtn) {
        enableNotificationsBtn.addEventListener('click', requestNotificationPermission);
    }
    
    function showNotification(title, options) {
        if (Notification.permission === "granted") {
            new Notification(title, options);
        }
    }

    // --- Helper function to update notification badges ---
    function updateBadge(badgeElement, count) {
        if (!badgeElement) return; // Guard against null elements
        
        if (count > 0) {
            badgeElement.textContent = count;
            badgeElement.classList.remove('hidden');
        } else {
            badgeElement.classList.add('hidden');
        }
    }

    // --- WebSocket Connection ---
    const conn = new WebSocket(wsUrl);

    conn.onmessage = function(e) {
        try {
            const data = JSON.parse(e.data);

            // New Order Notification
            if (data.type === 'new_order_notification' && data.for_store_id == currentRestaurantId) {
                updateBadge(orderBadge, data.new_count);
                updateBadge(mobileOrderBadge, data.new_count);
                showNotification("New Order Received!", { body: `You have a new pending order (#${data.order_id}).`, tag: 'new-order'});
            }

            // New Message Notification
            if (data.type === 'new_message_notification' && data.for_receiver_id == currentUserId) {
                updateBadge(messageBadge, data.new_count);
                updateBadge(mobileMessageBadge, data.new_count);
                showNotification("New Message Received!", { body: `You have a new message regarding order #${data.order_id}.`, tag: `new-message-${data.order_id}`});
            }

        } catch (error) { 
            // console.error("Error parsing WebSocket message:", error); 
        }
    };

    conn.onopen = function(e) { console.log("Sidebar WebSocket connection established."); };
    conn.onerror = function(e) { console.error("Sidebar WebSocket error:", e); };
});
</script>
