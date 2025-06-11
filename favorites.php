<?php
// favorites.php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'customer') {
    header("location: login.php");
    exit;
}
require_once "db_connection/config.php";
$customer_id = $_SESSION['id'];

// Fetch user's favorite menu items
$sql_items = "SELECT mi.id, mi.name as item_name, mi.price, mi.image_url as item_image, r.id as restaurant_id, r.name as restaurant_name FROM menu_items mi JOIN favorite_items f ON mi.id = f.menu_item_id JOIN restaurants r ON mi.restaurant_id = r.id WHERE f.customer_id = ? ORDER BY f.created_at DESC";
$favorite_items = [];
if ($stmt_items = mysqli_prepare($link, $sql_items)) {
    mysqli_stmt_bind_param($stmt_items, "i", $customer_id);
    mysqli_stmt_execute($stmt_items);
    $result_items = mysqli_stmt_get_result($stmt_items);
    $favorite_items = mysqli_fetch_all($result_items, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_items);
}

// Fetch user's favorite restaurants
$sql_restos = "SELECT r.id, r.name, r.cuisine, r.banner_image_url, (SELECT AVG(rating) FROM reviews WHERE restaurant_id = r.id) as avg_rating FROM restaurants r JOIN favorite_restaurants f ON r.id = f.restaurant_id WHERE f.customer_id = ? ORDER BY f.created_at DESC";
$favorite_restaurants = [];
if ($stmt_restos = mysqli_prepare($link, $sql_restos)) {
    mysqli_stmt_bind_param($stmt_restos, "i", $customer_id);
    mysqli_stmt_execute($stmt_restos);
    $result_restos = mysqli_stmt_get_result($stmt_restos);
    $favorite_restaurants = mysqli_fetch_all($result_restos, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_restos);
}

mysqli_close($link);
$active_page = 'favorites';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Favorites - Foodie</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen bg-gray-100">
        <?php require_once 'cdashboard_partial/sidebar.php'; ?>
        <div class="flex flex-col flex-1 overflow-y-auto">
            <?php require_once 'cdashboard_partial/header.php'; ?>
            <div class="p-4 md:p-6 pb-20 md:pb-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-800">My Favorites</h1>
                     <div class="text-sm text-gray-500 text-right">
                        <p><?php echo date("l, F j, Y"); ?></p>
                        <p>Kabankalan, Western Visayas</p>
                    </div>
                </div>
                <!-- Tabs -->
                <div class="mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                            <button id="tab-foods" class="tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm text-orange-600 border-orange-500">Favorite Foods</button>
                            <button id="tab-restaurants" class="tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">Favorite Restaurants</button>
                        </nav>
                    </div>
                </div>
                <!-- Tab Content for Foods -->
                <div id="content-foods" class="tab-content">
                    <?php if (!empty($favorite_items)): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach($favorite_items as $item): ?>
                            <div class="bg-white rounded-lg shadow-md overflow-hidden transform hover:-translate-y-1 transition-transform duration-300"><a href="view_item.php?id=<?php echo $item['id']; ?>" class="block"><img src="<?php echo htmlspecialchars($item['item_image'] ?? 'https://placehold.co/600x400/F0F0F0/333?text=Dish'); ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>" class="w-full h-48 object-cover"></a><div class="p-4"><a href="view_item.php?id=<?php echo $item['id']; ?>"><h3 class="text-lg font-bold mb-1 truncate"><?php echo htmlspecialchars($item['item_name']); ?></h3></a><p class="text-gray-600 text-sm mb-2 truncate">from <a href="view_restaurant.php?id=<?php echo $item['restaurant_id']; ?>" class="text-orange-600 hover:underline"><?php echo htmlspecialchars($item['restaurant_name']); ?></a></p><p class="text-lg font-semibold text-gray-800">₱<?php echo number_format($item['price'], 2); ?></p></div></div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-16 bg-white rounded-lg shadow-md"><div class="flex items-center justify-center h-24 w-24 rounded-full bg-gray-100 text-gray-500 mx-auto mb-4"><i data-lucide="heart" class="w-12 h-12"></i></div><h2 class="text-2xl font-semibold text-gray-700">You have no favorite foods yet.</h2><p class="text-gray-500 mt-2">Click the heart icon on a food item to save it here.</p><a href="search.php" class="mt-6 inline-block bg-orange-600 text-white font-bold py-2 px-5 rounded-full hover:bg-orange-700 transition-colors duration-300">Find Foods</a></div>
                    <?php endif; ?>
                </div>
                <!-- Tab Content for Restaurants -->
                <div id="content-restaurants" class="tab-content hidden">
                    <?php if (!empty($favorite_restaurants)): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                             <?php foreach($favorite_restaurants as $resto): ?>
                            <a href="view_restaurant.php?id=<?php echo $resto['id']; ?>" class="block bg-white rounded-lg shadow-md overflow-hidden transform hover:-translate-y-1 transition-transform duration-300"><img src="<?php echo htmlspecialchars($resto['banner_image_url']); ?>" alt="<?php echo htmlspecialchars($resto['name']); ?>" class="w-full h-48 object-cover"><div class="p-4"><h3 class="text-lg font-bold mb-1 truncate"><?php echo htmlspecialchars($resto['name']); ?></h3><p class="text-gray-600 text-sm mb-2 truncate"><?php echo htmlspecialchars($resto['cuisine']); ?></p><div class="flex items-center justify-between text-sm text-gray-800"><span class="flex items-center"><i data-lucide="star" class="w-4 h-4 text-yellow-500 fill-current mr-1"></i><b><?php echo ($resto['avg_rating']) ? number_format($resto['avg_rating'], 1) : 'New'; ?></b></span></div></div></a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-16 bg-white rounded-lg shadow-md"><div class="flex items-center justify-center h-24 w-24 rounded-full bg-gray-100 text-gray-500 mx-auto mb-4"><i data-lucide="store" class="w-12 h-12"></i></div><h2 class="text-2xl font-semibold text-gray-700">You have no favorite restaurants yet.</h2><p class="text-gray-500 mt-2">Click the heart icon on a restaurant to save it here.</p><a href="customer_dashboard.php" class="mt-6 inline-block bg-orange-600 text-white font-bold py-2 px-5 rounded-full hover:bg-orange-700 transition-colors duration-300">Find Restaurants</a></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php require_once 'customer_dashboard_settings/bottom_nav.php'; ?>
        </div>
    </div>
    <script src="js/script.js"></script>
</body>
</html>
