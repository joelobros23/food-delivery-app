<?php
// store/partials/sidebar.php
if(!isset($active_page)) { $active_page = ''; }

$new_order_count = 0;
$store_restaurant_id = null;

if (!defined('DB_HOST')) {
    require_once dirname(__DIR__, 2) . "/app_config.php";
}

if (!function_exists('get_db_connection_for_sidebar')) {
    function get_db_connection_for_sidebar() {
        return mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    }
}

$sidebar_link = get_db_connection_for_sidebar();
if ($sidebar_link && isset($_SESSION['id'])) {
    $store_user_id = $_SESSION['id'];
    
    $sql_resto_id = "SELECT id FROM restaurants WHERE user_id = ? LIMIT 1";
    if($stmt_resto_id = mysqli_prepare($sidebar_link, $sql_resto_id)) {
        mysqli_stmt_bind_param($stmt_resto_id, "i", $store_user_id);
        if (mysqli_stmt_execute($stmt_resto_id)) {
            mysqli_stmt_bind_result($stmt_resto_id, $r_id);
            if(mysqli_stmt_fetch($stmt_resto_id)) { $store_restaurant_id = $r_id; }
        }
        mysqli_stmt_close($stmt_resto_id);
    }

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
    
    mysqli_close($sidebar_link);
}
?>
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
             <a href="inbox.php" class="flex items-center h-12 px-6 hover:bg-gray-700 <?php echo ($active_page == 'inbox') ? 'bg-orange-600' : ''; ?>">
                <i data-lucide="inbox" class="w-5 h-5 mr-3"></i><span class="text-sm font-medium">Inbox</span>
            </a>
        </li>
        <li>
            <a href="menu.php" class="flex items-center h-12 px-6 hover:bg-gray-700 <?php echo ($active_page == 'menu') ? 'bg-orange-600' : ''; ?>">
                <i data-lucide="utensils" class="w-5 h-5 mr-3"></i><span class="text-sm font-medium">Menu Items</span>
            </a>
        </li>
        <li>
            <a href="reviews.php" class="flex items-center h-12 px-6 hover:bg-gray-700 <?php echo ($active_page == 'reviews') ? 'bg-orange-600' : ''; ?>">
                <i data-lucide="star" class="w-5 h-5 mr-3"></i><span class="text-sm font-medium">Reviews</span>
            </a>
        </li>
        <li>
            <a href="#" class="flex items-center h-12 px-6 hover:bg-gray-700"><i data-lucide="settings" class="w-5 h-5 mr-3"></i><span class="text-sm font-medium">Store Settings</span></a>
        </li>
        <li>
            <a href="../logout.php" class="flex items-center h-12 px-6 hover:bg-gray-700"><i data-lucide="log-out" class="w-5 h-5 mr-3"></i><span class="text-sm font-medium">Logout</span></a>
        </li>
    </ul>
    <!-- Notification Permission Button -->
    <div class="px-6 py-4 mt-auto">
        <button id="enable-notifications-btn" class="w-full flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-gray-700 rounded-lg hover:bg-gray-600">
            <i data-lucide="bell" class="w-4 h-4 mr-2"></i>
            <span>Enable Notifications</span>
        </button>
    </div>
</div>

<!-- WebSocket and Device Notification script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const orderBadge = document.getElementById('order-notification-badge');
    const enableNotificationsBtn = document.getElementById('enable-notifications-btn');
    const currentRestaurantId = <?php echo isset($store_restaurant_id) ? $store_restaurant_id : 'null'; ?>;
    const wsUrl = "ws://localhost:8080";

    // --- Device Notification Logic ---
    function requestNotificationPermission() {
        if (!("Notification" in window)) {
            alert("This browser does not support desktop notification");
        } else if (Notification.permission !== "denied") {
            Notification.requestPermission().then(function (permission) {
                if (permission === "granted") {
                    console.log("Notification permission granted.");
                    new Notification("Notifications Enabled!", {
                        body: "You will now receive alerts for new orders.",
                        icon: "https://placehold.co/48x48/orange/white?text=🔔" // A placeholder icon
                    });
                }
            });
        }
    }

    function showNewOrderNotification(data) {
        if (Notification.permission !== "granted") {
            return; // Exit if permission is not granted
        }

        const title = "New Order Received!";
        const options = {
            body: `You have a new pending order (#${data.order_id}). Click to view.`,
            icon: 'https://placehold.co/48x48/orange/white?text=📦', // A placeholder icon
            tag: 'new-order' // Prevents spamming notifications
        };

        const notification = new Notification(title, options);
        
        // Make the notification clickable, taking the user to the orders page
        notification.onclick = function() {
            window.open('orders.php', '_blank');
        };
    }
    
    enableNotificationsBtn.addEventListener('click', requestNotificationPermission);


    // --- WebSocket Logic ---
    if (!currentRestaurantId || !orderBadge) {
        return; 
    }

    const conn = new WebSocket(wsUrl);

    conn.onmessage = function(e) {
        try {
            const data = JSON.parse(e.data);

            if (data.type === 'new_order_notification' && data.for_store_id == currentRestaurantId) {
                console.log('New order notification received:', data);
                
                // 1. Update the sidebar badge
                if (data.new_count > 0) {
                    orderBadge.textContent = data.new_count;
                    orderBadge.classList.remove('hidden');
                } else {
                    orderBadge.classList.add('hidden');
                }
                
                // 2. Trigger the device notification
                showNewOrderNotification(data);
            }

        } catch (error) {
            // This is likely a chat message, which we can ignore
        }
    };

    conn.onopen = function(e) { console.log("Sidebar WebSocket connection established for restaurant ID: " + currentRestaurantId); };
    conn.onerror = function(e) { console.error("Sidebar WebSocket error:", e); };
});
</script>
