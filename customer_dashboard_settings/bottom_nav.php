<?php
// customer_dashboard_settings/bottom_nav.php

// The parent page (e.g., customer_dashboard.php) is responsible for including app_config.php.
// Including it again here causes the "Constant already defined" error.

if(!isset($active_page)) { $active_page = ''; }

$cart_count = 0;
// We need to check session status because this file is included on multiple pages.
// We also check if DB_HOST is defined to ensure the config has been loaded by the parent page.
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && defined('DB_HOST')) {
    
    // This connection is self-contained for the nav bar to prevent conflicts.
    $nav_db_link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($nav_db_link) {
        $sql_count = "SELECT SUM(quantity) as total FROM cart_items WHERE customer_id = ?";
        if ($stmt_count = mysqli_prepare($nav_db_link, $sql_count)) {
            mysqli_stmt_bind_param($stmt_count, "i", $_SESSION['id']);
            mysqli_stmt_execute($stmt_count);
            mysqli_stmt_bind_result($stmt_count, $count);
            if(mysqli_stmt_fetch($stmt_count)) {
                $cart_count = $count ?? 0;
            }
            mysqli_stmt_close($stmt_count);
        }
        // Always close the temporary connection immediately after use.
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
        <a href="orders.php" class="flex flex-col items-center justify-center w-full text-gray-600 hover:text-orange-600 <?php echo ($active_page == 'orders') ? 'text-orange-600' : ''; ?>"><i data-lucide="history" class="w-6 h-6 mb-1"></i><span class="text-xs font-medium">Orders</span></a>
        <a href="profile.php" class="flex flex-col items-center justify-center w-full text-gray-600 hover:text-orange-600 <?php echo ($active_page == 'profile') ? 'text-orange-600' : ''; ?>"><i data-lucide="user" class="w-6 h-6 mb-1"></i><span class="text-xs font-medium">Profile</span></a>
    </div>
</div>
