<?php
// store/partials/header.php
$store_name = $restaurant_name ?? 'My Store';
?>
<header class="flex items-center justify-between h-20 bg-white shadow-md px-6 sticky top-0 z-10">
    <h2 class="text-xl font-bold text-gray-800 hidden md:block"><?php echo htmlspecialchars($store_name); ?></h2>
    <div class="flex-1"></div>
    <div class="flex items-center">
        <span class="text-gray-800 text-md hidden sm:block">Welcome, <b><?php echo htmlspecialchars($_SESSION["name"]); ?></b></span>
        <img src="https://placehold.co/40x40/EFEFEF/333?text=S" alt="profile" class="w-10 h-10 rounded-full ml-3">
    </div>
</header>
