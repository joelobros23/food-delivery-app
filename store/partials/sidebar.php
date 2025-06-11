<?php
// store/partials/sidebar.php
if(!isset($active_page)) { $active_page = ''; }
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
            <a href="orders.php" class="flex items-center h-12 px-6 hover:bg-gray-700 <?php echo ($active_page == 'orders') ? 'bg-orange-600' : ''; ?>">
                <i data-lucide="shopping-cart" class="w-5 h-5 mr-3"></i><span class="text-sm font-medium">Orders</span>
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
</div>
