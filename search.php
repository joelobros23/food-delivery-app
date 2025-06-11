<?php
// search.php
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'customer'){
    header("location: login.php");
    exit;
}
require_once "db_connection/config.php";

$search_query = "";
$found_restaurants = [];
$found_items = [];
$searched = false;
$customer_id = $_SESSION['id'];

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['q'])) {
    $search_query = trim($_GET['q']);
    $searched = true;

    if (!empty($search_query)) {
        $param_term = "%" . $search_query . "%";
        // Search for Restaurants
        $sql_restaurants = "SELECT r.id, r.name, r.cuisine, r.banner_image_url, (SELECT AVG(rating) FROM reviews WHERE restaurant_id = r.id) as avg_rating, fr.id as favorite_restaurant_id FROM restaurants r LEFT JOIN favorite_restaurants fr ON r.id = fr.restaurant_id AND fr.customer_id = ? WHERE r.name LIKE ? OR r.cuisine LIKE ?";
        if($stmt_rest = mysqli_prepare($link, $sql_restaurants)){
            mysqli_stmt_bind_param($stmt_rest, "iss", $customer_id, $param_term, $param_term);
            mysqli_stmt_execute($stmt_rest);
            $result_rest = mysqli_stmt_get_result($stmt_rest);
            $found_restaurants = mysqli_fetch_all($result_rest, MYSQLI_ASSOC);
            mysqli_stmt_close($stmt_rest);
        }

        // UPDATED: Search for Menu Items now includes searching by cuisine and item category
        $sql_items = "
            SELECT mi.id, mi.name as item_name, mi.price, mi.description, mi.image_url as item_image, 
                   r.id as restaurant_id, r.name as restaurant_name, fi.id as favorite_item_id 
            FROM menu_items mi 
            JOIN restaurants r ON mi.restaurant_id = r.id 
            LEFT JOIN favorite_items fi ON mi.id = fi.menu_item_id AND fi.customer_id = ? 
            WHERE mi.name LIKE ? OR mi.description LIKE ? OR r.cuisine LIKE ? OR mi.category LIKE ?";

        if($stmt_items = mysqli_prepare($link, $sql_items)){
            mysqli_stmt_bind_param($stmt_items, "issss", $customer_id, $param_term, $param_term, $param_term, $param_term);
            mysqli_stmt_execute($stmt_items);
            $result_items = mysqli_stmt_get_result($stmt_items);
            $found_items = mysqli_fetch_all($result_items, MYSQLI_ASSOC);
            mysqli_stmt_close($stmt_items);
        }
    }
}

mysqli_close($link);
$active_page = 'search';
function truncate($text, $length) {
    return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search - Foodie</title>
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
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Search for Food or Restaurants</h1>
                <div class="mb-8">
                    <form action="search.php" method="GET" id="search-form" class="relative">
                        <div class="relative"><input type="text" name="q" id="search-input" class="w-full pl-5 pr-20 py-4 text-lg border-2 border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent" placeholder="Enter restaurant name or dish..." value="<?php echo htmlspecialchars($search_query); ?>" autocomplete="off" autofocus><button type="submit" class="absolute inset-y-0 right-0 flex items-center justify-center w-20 h-full text-white bg-orange-600 rounded-r-full hover:bg-orange-700"><i data-lucide="search" class="w-6 h-6"></i></button></div>
                        <div id="autosuggest-container" class="absolute left-0 right-0 mt-1 bg-white rounded-lg shadow-lg z-10 hidden"></div>
                    </form>
                </div>
                <?php if (!$searched): ?>
                    <div class="text-center py-16"><div class="flex items-center justify-center h-24 w-24 rounded-full bg-orange-100 text-orange-500 mx-auto mb-4"><i data-lucide="utensils-crossed" class="w-12 h-12"></i></div><h2 class="text-2xl font-semibold text-gray-700">Find your next favorite meal</h2><p class="text-gray-500 mt-2">Search for delicious food from the best local restaurants.</p></div>
                <?php elseif (empty($found_restaurants) && empty($found_items)): ?>
                    <div class="text-center py-16"><div class="flex items-center justify-center h-24 w-24 rounded-full bg-red-100 text-red-500 mx-auto mb-4"><i data-lucide="search-x" class="w-12 h-12"></i></div><h2 class="text-2xl font-semibold text-gray-700">No results for "<?php echo htmlspecialchars($search_query); ?>"</h2><p class="text-gray-500 mt-2">Try searching for something else.</p></div>
                <?php else: ?>
                    <div class="space-y-10">
                        <?php if (!empty($found_restaurants)): ?>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800 mb-4">Restaurants</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php foreach($found_restaurants as $resto): $is_resto_favorited = !is_null($resto['favorite_restaurant_id']); ?>
                                <div class="bg-white rounded-lg shadow-md overflow-hidden transform hover:-translate-y-1 transition-transform duration-300 group">
                                    <a href="view_restaurant.php?id=<?php echo $resto['id']; ?>" class="block">
                                        <div class="relative"><img src="<?php echo htmlspecialchars($resto['banner_image_url']); ?>" alt="<?php echo htmlspecialchars($resto['name']); ?>" class="w-full h-48 object-cover"><button class="favorite-restaurant-btn absolute top-3 right-3 bg-white bg-opacity-70 rounded-full p-2 text-gray-600 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity z-10" data-restaurant-id="<?php echo $resto['id']; ?>"><i data-lucide="heart" class="w-5 h-5 <?php echo $is_resto_favorited ? 'fill-red-500 text-red-500' : ''; ?>"></i></button></div>
                                        <div class="p-4"><h3 class="text-lg font-bold mb-1 truncate"><?php echo htmlspecialchars($resto['name']); ?></h3><p class="text-gray-600 text-sm mb-2 truncate"><?php echo htmlspecialchars($resto['cuisine']); ?></p><div class="flex items-center justify-between text-sm text-gray-800"><span class="flex items-center"><i data-lucide="star" class="w-4 h-4 text-yellow-500 fill-current mr-1"></i><b><?php echo ($resto['avg_rating']) ? number_format($resto['avg_rating'], 1) : 'New'; ?></b></span></div></div>
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($found_items)): ?>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800 mb-4">Dishes</h2>
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <?php foreach($found_items as $item): $is_item_favorited = !is_null($item['favorite_item_id']); ?>
                                <div class="bg-white p-4 rounded-lg shadow-md flex space-x-4 items-center relative">
                                    <button class="favorite-item-btn absolute top-3 right-3 bg-white bg-opacity-70 rounded-full p-2 text-gray-600 hover:text-red-500 z-10" data-item-id="<?php echo $item['id']; ?>"><i data-lucide="heart" class="w-5 h-5 <?php echo $is_item_favorited ? 'fill-red-500 text-red-500' : ''; ?>"></i></button>
                                    <a href="view_item.php?id=<?php echo $item['id']; ?>" class="flex items-start space-x-4 w-full">
                                        <img src="<?php echo htmlspecialchars($item['item_image'] ?? 'https://placehold.co/100x100/F0F0F0/333?text=Dish'); ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>" class="w-20 h-20 md:w-24 md:h-24 rounded-lg object-cover flex-shrink-0">
                                        <div class="flex-1 overflow-hidden"><p class="font-bold text-lg text-gray-800 truncate"><?php echo htmlspecialchars($item['item_name']); ?></p><p class="text-sm text-gray-500">from <span class="text-orange-600 font-medium"><?php echo htmlspecialchars($item['restaurant_name']); ?></span></p><p class="text-sm text-gray-600 mt-2"><?php echo htmlspecialchars(truncate($item['description'], 90)); ?></p></div>
                                        <div class="text-right flex-shrink-0"><p class="text-lg font-semibold text-gray-700">₱<?php echo number_format($item['price'], 2); ?></p></div>
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php require_once 'customer_dashboard_settings/bottom_nav.php'; ?>
        </div>
    </div>
    <script src="js/script.js"></script>
</body>
</html>
