<?php
// customer_dashboard_settings/bottom_nav.php
// This is the mobile-only bottom navigation bar.
// It expects a variable $active_page to be set before being included.
if(!isset($active_page)) {
    $active_page = ''; 
}
?>
<!-- Mobile Bottom Navigation -->
<div id="bottom-navigation" class="md:hidden fixed inset-x-0 bottom-0 z-30 bg-white border-t border-gray-200 shadow-lg">
    <div class="flex justify-around items-center h-16">
        <a href="customer_dashboard.php" class="flex flex-col items-center justify-center w-full text-gray-600 hover:text-orange-600 <?php echo ($active_page == 'dashboard') ? 'text-orange-600' : ''; ?>">
            <i data-lucide="home" class="w-6 h-6 mb-1"></i>
            <span class="text-xs font-medium">Home</span>
        </a>
        <a href="search.php" class="flex flex-col items-center justify-center w-full text-gray-600 hover:text-orange-600 <?php echo ($active_page == 'search') ? 'text-orange-600' : ''; ?>">
            <i data-lucide="search" class="w-6 h-6 mb-1"></i>
            <span class="text-xs font-medium">Search</span>
        </a>
        <a href="orders.php" class="flex flex-col items-center justify-center w-full text-gray-600 hover:text-orange-600 <?php echo ($active_page == 'orders') ? 'text-orange-600' : ''; ?>">
            <i data-lucide="history" class="w-6 h-6 mb-1"></i>
            <span class="text-xs font-medium">Orders</span>
        </a>
        <a href="profile.php" class="flex flex-col items-center justify-center w-full text-gray-600 hover:text-orange-600 <?php echo ($active_page == 'profile') ? 'text-orange-600' : ''; ?>">
            <i data-lucide="user" class="w-6 h-6 mb-1"></i>
            <span class="text-xs font-medium">Profile</span>
        </a>
    </div>
</div>
