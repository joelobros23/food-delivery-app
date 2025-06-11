<?php
// customer_dashboard.php
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'customer'){
    header("location: login.php");
    exit;
}

require_once "db_connection/config.php";
$customer_id = $_SESSION['id'];

// Fetch featured restaurants...
$restaurants_sql = "
    SELECT r.id, r.name, r.cuisine, r.banner_image_url, 
           (SELECT AVG(rating) FROM reviews WHERE restaurant_id = r.id) as avg_rating,
           fr.id as favorite_restaurant_id
    FROM restaurants r
    LEFT JOIN favorite_restaurants fr ON r.id = fr.restaurant_id AND fr.customer_id = ?
    WHERE r.is_open = 1 
    ORDER BY r.id DESC 
    LIMIT 6";

$restaurants = [];
if($stmt_rest = mysqli_prepare($link, $restaurants_sql)){
    mysqli_stmt_bind_param($stmt_rest, "i", $customer_id);
    mysqli_stmt_execute($stmt_rest);
    $result = mysqli_stmt_get_result($stmt_rest);
    $restaurants = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_rest);
}

// Fetch most ordered items
$popular_items = [];
$most_ordered_sql = "
    SELECT 
        mi.id, mi.name as item_name, mi.price, mi.image_url as item_image,
        r.name as restaurant_name,
        fi.id as favorite_item_id,
        COUNT(oi.item_id) as order_count
    FROM order_items oi
    JOIN menu_items mi ON oi.item_id = mi.id
    JOIN restaurants r ON mi.restaurant_id = r.id
    LEFT JOIN favorite_items fi ON mi.id = fi.menu_item_id AND fi.customer_id = ?
    GROUP BY mi.id
    ORDER BY order_count DESC
    LIMIT 4";

if($stmt_popular = mysqli_prepare($link, $most_ordered_sql)){
    mysqli_stmt_bind_param($stmt_popular, "i", $customer_id);
    mysqli_stmt_execute($stmt_popular);
    $result_popular = mysqli_stmt_get_result($stmt_popular);
    $popular_items = mysqli_fetch_all($result_popular, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_popular);
}

// If no items have been ordered yet, fetch random items as a fallback
if (empty($popular_items)) {
    $random_items_sql = "
        SELECT
            mi.id, mi.name as item_name, mi.price, mi.image_url as item_image,
            r.name as restaurant_name,
            fi.id as favorite_item_id
        FROM menu_items mi
        JOIN restaurants r ON mi.restaurant_id = r.id
        LEFT JOIN favorite_items fi ON mi.id = fi.menu_item_id AND fi.customer_id = ?
        ORDER BY RAND()
        LIMIT 4";

    if($stmt_random = mysqli_prepare($link, $random_items_sql)){
        mysqli_stmt_bind_param($stmt_random, "i", $customer_id);
        mysqli_stmt_execute($stmt_random);
        $result_random = mysqli_stmt_get_result($stmt_random);
        $popular_items = mysqli_fetch_all($result_random, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt_random);
    }
}

// Fetch most favorited items
$most_favorited_items = [];
$most_favorited_sql = "
    SELECT
        mi.id, mi.name as item_name, mi.price, mi.image_url as item_image,
        r.name as restaurant_name,
        (SELECT id FROM favorite_items WHERE menu_item_id = mi.id AND customer_id = ?) as favorite_item_id,
        COUNT(f.id) as favorite_count
    FROM favorite_items f
    JOIN menu_items mi ON f.menu_item_id = mi.id
    JOIN restaurants r ON mi.restaurant_id = r.id
    GROUP BY mi.id
    ORDER BY favorite_count DESC
    LIMIT 4";

if($stmt_fave = mysqli_prepare($link, $most_favorited_sql)){
    mysqli_stmt_bind_param($stmt_fave, "i", $customer_id);
    mysqli_stmt_execute($stmt_fave);
    $result_fave = mysqli_stmt_get_result($stmt_fave);
    $most_favorited_items = mysqli_fetch_all($result_fave, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_fave);
}


// Fetch unique cuisine categories...
$cuisines_sql = "SELECT DISTINCT cuisine FROM restaurants WHERE cuisine IS NOT NULL AND cuisine != '' ORDER BY cuisine ASC";
$cuisines_result = mysqli_query($link, $cuisines_sql);
$cuisines = mysqli_fetch_all($cuisines_result, MYSQLI_ASSOC);

mysqli_close($link);
$active_page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Foodie</title>
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
            <!-- Add padding-bottom to prevent content from being hidden by the bottom nav -->
            <div class="p-4 md:p-6 pb-20 md:pb-6">
                <div class="bg-gradient-to-r from-orange-600 to-red-500 text-white p-6 md:p-8 rounded-lg shadow-lg mb-8">
                    <h1 class="text-2xl md:text-3xl font-bold">Good morning, <?php echo htmlspecialchars(explode(' ', $_SESSION["name"])[0]); ?>!</h1>
                    <p class="text-sm md:text-base">It's time for a treat. What are you craving today?</p>
                </div>
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Categories</h2>
                    <div class="flex space-x-3 sm:space-x-4 overflow-x-auto pb-4 -mx-1 px-1">
                        <?php foreach($cuisines as $cuisine): ?>
                        <a href="search.php?q=<?php echo urlencode($cuisine['cuisine']); ?>" class="flex-shrink-0 px-4 py-2 text-sm font-semibold text-gray-700 bg-white rounded-full shadow-sm hover:bg-orange-500 hover:text-white transition-colors duration-200"><?php echo htmlspecialchars($cuisine['cuisine']); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Featured Restaurants</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach($restaurants as $resto): 
                            $is_favorited = !is_null($resto['favorite_restaurant_id']);
                        ?>
                        <div class="bg-white rounded-lg shadow-md overflow-hidden transform hover:-translate-y-1 transition-transform duration-300 group">
                            <a href="view_restaurant.php?id=<?php echo $resto['id']; ?>" class="block">
                                <div class="relative">
                                    <img src="<?php echo htmlspecialchars($resto['banner_image_url']); ?>" alt="<?php echo htmlspecialchars($resto['name']); ?>" class="w-full h-48 object-cover">
                                    <button class="favorite-restaurant-btn absolute top-3 right-3 bg-white bg-opacity-70 rounded-full p-2 text-gray-600 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity z-10" data-restaurant-id="<?php echo $resto['id']; ?>">
                                        <i data-lucide="heart" class="w-5 h-5 <?php echo $is_favorited ? 'fill-red-500 text-red-500' : ''; ?>"></i>
                                    </button>
                                </div>
                                <div class="p-4">
                                    <h3 class="text-lg font-bold mb-1 truncate"><?php echo htmlspecialchars($resto['name']); ?></h3>
                                    <p class="text-gray-600 text-sm mb-2 truncate"><?php echo htmlspecialchars($resto['cuisine']); ?></p>
                                    <div class="flex items-center justify-between text-sm text-gray-800">
                                        <span class="flex items-center"><i data-lucide="star" class="w-4 h-4 text-yellow-500 fill-current mr-1"></i><b><?php echo ($resto['avg_rating']) ? number_format($resto['avg_rating'], 1) : 'New'; ?></b></span>
                                        <span class="flex items-center"><i data-lucide="clock" class="w-4 h-4 text-gray-500 mr-1"></i>25-35 min</span>
                                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">Order Now</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Popular Items</h2>
                     <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach($popular_items as $item): 
                            $is_item_favorited = !is_null($item['favorite_item_id']);
                        ?>
                        <div class="bg-white p-4 rounded-lg shadow-md flex space-x-4 items-center relative">
                            <button class="favorite-item-btn absolute top-3 right-3 bg-white bg-opacity-70 rounded-full p-2 text-gray-600 hover:text-red-500 z-10" data-item-id="<?php echo $item['id']; ?>">
                                <i data-lucide="heart" class="w-5 h-5 <?php echo $is_item_favorited ? 'fill-red-500 text-red-500' : ''; ?>"></i>
                            </button>
                            <a href="view_item.php?id=<?php echo $item['id']; ?>" class="flex items-start space-x-4 w-full">
                                <img src="<?php echo htmlspecialchars($item['item_image'] ?? 'https://placehold.co/100x100/F0F0F0/333?text=Dish'); ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>" class="w-20 h-20 md:w-24 md:h-24 rounded-lg object-cover flex-shrink-0">
                                <div class="flex-1 overflow-hidden">
                                    <p class="font-bold text-lg text-gray-800 truncate"><?php echo htmlspecialchars($item['item_name']); ?></p>
                                    <p class="text-sm text-gray-500">from <span class="text-orange-600 font-medium"><?php echo htmlspecialchars($item['restaurant_name']); ?></span></p>
                                    <p class="text-lg font-semibold text-gray-700 mt-2">₱<?php echo number_format($item['price'], 2); ?></p>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                 <!-- NEW SECTION -->
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">The Most Favorites</h2>
                    <?php if (!empty($most_favorited_items)): ?>
                     <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach($most_favorited_items as $item): 
                            $is_item_favorited = !is_null($item['favorite_item_id']);
                        ?>
                        <div class="bg-white p-4 rounded-lg shadow-md flex space-x-4 items-center relative">
                            <button class="favorite-item-btn absolute top-3 right-3 bg-white bg-opacity-70 rounded-full p-2 text-gray-600 hover:text-red-500 z-10" data-item-id="<?php echo $item['id']; ?>">
                                <i data-lucide="heart" class="w-5 h-5 <?php echo $is_item_favorited ? 'fill-red-500 text-red-500' : ''; ?>"></i>
                            </button>
                            <a href="view_item.php?id=<?php echo $item['id']; ?>" class="flex items-start space-x-4 w-full">
                                <img src="<?php echo htmlspecialchars($item['item_image'] ?? 'https://placehold.co/100x100/F0F0F0/333?text=Dish'); ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>" class="w-20 h-20 md:w-24 md:h-24 rounded-lg object-cover flex-shrink-0">
                                <div class="flex-1 overflow-hidden">
                                    <p class="font-bold text-lg text-gray-800 truncate"><?php echo htmlspecialchars($item['item_name']); ?></p>
                                    <p class="text-sm text-gray-500">from <span class="text-orange-600 font-medium"><?php echo htmlspecialchars($item['restaurant_name']); ?></span></p>
                                    <p class="text-lg font-semibold text-gray-700 mt-2">₱<?php echo number_format($item['price'], 2); ?></p>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                        <div class="text-center py-10 bg-white rounded-lg shadow-md">
                            <p class="text-gray-500">No items yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
             <?php require_once 'customer_dashboard_settings/bottom_nav.php'; ?>
        </div>
    </div>
    <script src="js/script.js"></script>
</body>
</html>
