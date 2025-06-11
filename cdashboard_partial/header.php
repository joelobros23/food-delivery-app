<?php
// cdashboard_partial/sidebar.php
$first_name = htmlspecialchars(explode(' ', $_SESSION["name"])[0]);
?>
<!-- Header -->
<div class="flex items-center justify-between h-20 bg-white shadow-md px-4 md:px-6 sticky top-0 z-20">
    <!-- Mobile Menu Button -->
    <button id="sidebar-open-btn" class="md:hidden p-2">
        <i data-lucide="menu" class="w-6 h-6"></i>
    </button>

    <!-- Spacer to push profile button to the right on desktop -->
    <div class="flex-1"></div>

    <!-- Profile Dropdown (hidden on mobile) -->
    <div class="relative hidden md:block">
        <button id="profile-button" class="flex items-center space-x-2 relative focus:outline-none p-2">
            <span class="text-gray-800 text-md hidden sm:block">Hi, <b><?php echo $first_name; ?></b></span>
            <img src="https://placehold.co/40x40/EFEFEF/333333?text=<?php echo substr($_SESSION["name"], 0, 1); ?>" alt="profile" class="w-8 h-8 sm:w-10 sm:h-10 rounded-full">
        </button>

        <!-- Dropdown Menu -->
        <div id="profile-dropdown" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-xl overflow-hidden">
            <div class="px-4 py-3"><p class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($_SESSION["name"]); ?></p><p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($_SESSION["email"]); ?></p></div>
            <div class="border-t border-gray-100"></div>
            <a href="profile.php" class="flex items-center px-4 py-3 text-sm text-gray-600 hover:bg-gray-100"><i data-lucide="user-cog" class="w-5 h-5 mr-3"></i>Profile & Settings</a>
            <a href="orders.php" class="flex items-center px-4 py-3 text-sm text-gray-600 hover:bg-gray-100"><i data-lucide="history" class="w-5 h-5 mr-3"></i>Orders</a>
            <div class="border-t border-gray-100"></div>
            <a href="logout.php" class="flex items-center px-4 py-3 text-sm text-red-600 hover:bg-gray-100"><i data-lucide="log-out" class="w-5 h-5 mr-3"></i>Logout</a>
        </div>
    </div>
</div>
