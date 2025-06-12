<?php
// cdashboard_partial/sidebar.php
if(!isset($active_page)) {
    $active_page = ''; 
}
?>
<!-- Mobile Overlay -->
<div id="sidebar-overlay" class="md:hidden fixed inset-0 bg-black bg-opacity-50 z-30 hidden"></div>

<!-- Sidebar/Drawer -->
<div id="sidebar-drawer" class="fixed inset-y-0 left-0 bg-white shadow-lg w-64 transform -translate-x-full md:translate-x-0 md:relative md:w-64 z-40 transition-transform duration-300 ease-in-out">
    <div class="flex items-center justify-between h-20 shadow-md px-6">
        <a href="customer_dashboard.php" class="text-3xl font-bold text-orange-600">Foodie</a>
        <button id="sidebar-close-btn" class="md:hidden p-1">
            <i data-lucide="x" class="w-6 h-6"></i>
        </button>
    </div>

    <!-- Mobile-Only Profile Section -->
    <div class="p-4 border-b border-gray-200 md:hidden">
        <a href="profile.php" class="flex items-center space-x-3">
            <img src="https://placehold.co/48x48/EFEFEF/333?text=<?php echo substr($_SESSION["name"], 0, 1); ?>" alt="profile" class="w-12 h-12 rounded-full">
            <div>
                <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($_SESSION["name"]); ?></p>
                <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($_SESSION["email"]); ?></p>
            </div>
        </a>
    </div>
    
    <ul class="flex flex-col py-4">
        <!-- These links are now hidden on mobile and only appear on desktop -->
        <li class="hidden md:flex">
            <a href="customer_dashboard.php" class="flex flex-row items-center h-12 px-6 hover:bg-gray-100 <?php echo ($active_page == 'dashboard') ? 'bg-orange-100 border-r-4 border-orange-500 text-orange-600' : ''; ?>">
                <span class="inline-flex items-center justify-center h-12 w-12 text-lg"><i data-lucide="home"></i></span>
                <span class="text-sm font-medium">Dashboard</span>
            </a>
        </li>
        <li class="hidden md:flex">
            <a href="search.php" class="flex flex-row items-center h-12 px-6 hover:bg-gray-100 <?php echo ($active_page == 'search') ? 'bg-orange-100 border-r-4 border-orange-500 text-orange-600' : ''; ?>">
                <span class="inline-flex items-center justify-center h-12 w-12 text-lg"><i data-lucide="search"></i></span>
                <span class="text-sm font-medium">Search</span>
            </a>
        </li>
        <li class="hidden md:flex">
            <a href="orders.php" class="flex flex-row items-center h-12 px-6 hover:bg-gray-100 <?php echo ($active_page == 'orders') ? 'bg-orange-100 border-r-4 border-orange-500 text-orange-600' : ''; ?>">
                <span class="inline-flex items-center justify-center h-12 w-12 text-lg"><i data-lucide="history"></i></span>
                <span class="text-sm font-medium">My Orders</span>
            </a>
        </li>
        
        <!-- These links appear on both mobile and desktop -->
         <li>
            <a href="inbox.php" class="flex flex-row items-center h-12 px-6 hover:bg-gray-100 <?php echo ($active_page == 'inbox') ? 'bg-orange-100 border-r-4 border-orange-500 text-orange-600' : ''; ?>">
                <span class="inline-flex items-center justify-center h-12 w-12 text-lg"><i data-lucide="inbox"></i></span>
                <span class="text-sm font-medium">Inbox</span>
            </a>
        </li>
         <li>
            <a href="favorites.php" class="flex flex-row items-center h-12 px-6 hover:bg-gray-100 <?php echo ($active_page == 'favorites') ? 'bg-orange-100 border-r-4 border-orange-500 text-orange-600' : ''; ?>">
                <span class="inline-flex items-center justify-center h-12 w-12 text-lg"><i data-lucide="heart"></i></span>
                <span class="text-sm font-medium">Favorites</span>
            </a>
        </li>
         <li>
            <a href="settings.php" class="flex flex-row items-center h-12 px-6 hover:bg-gray-100 <?php echo ($active_page == 'settings') ? 'bg-orange-100 border-r-4 border-orange-500 text-orange-600' : ''; ?>">
                <span class="inline-flex items-center justify-center h-12 w-12 text-lg"><i data-lucide="settings"></i></span>
                <span class="text-sm font-medium">Settings</span>
            </a>
        </li>
        <li>
            <a href="logout.php" class="flex flex-row items-center h-12 px-6 hover:bg-gray-100">
                <span class="inline-flex items-center justify-center h-12 w-12 text-lg"><i data-lucide="log-out"></i></span>
                <span class="text-sm font-medium">Logout</span>
            </a>
        </li>
    </ul>
</div>
