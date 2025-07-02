<?php
// search.php
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'customer'){
    header("location: login.php");
    exit;
}
require_once "app_config.php";

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($link === false) { die("DB Connection Error"); }

$search_query = "";
$found_restaurants = [];
$found_items = [];
$searched = false;
$customer_id = $_SESSION['id'];

// --- Fetch user's location details ---
$user_address = null;
$user_city = null;
$sql_user_location = "SELECT address, city FROM users WHERE id = ?";
if($stmt_user = mysqli_prepare($link, $sql_user_location)){
    mysqli_stmt_bind_param($stmt_user, "i", $customer_id);
    mysqli_stmt_execute($stmt_user);
    mysqli_stmt_bind_result($stmt_user, $u_address, $u_city);
    if(mysqli_stmt_fetch($stmt_user)){
        $user_address = $u_address;
        $user_city = $u_city;
    }
    mysqli_stmt_close($stmt_user);
}


if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['q'])) {
    $search_query = trim($_GET['q']);
    $searched = true;

    if (!empty($search_query) && !empty($user_address)) {
        $param_term = "%" . $search_query . "%";
        
        $sql_restaurants = "
            SELECT r.id, r.name, r.cuisine, r.banner_image_url, 
                   (SELECT AVG(rating) FROM reviews WHERE restaurant_id = r.id) as avg_rating, 
                   fr.id as favorite_restaurant_id 
            FROM restaurants r 
            LEFT JOIN favorite_restaurants fr ON r.id = fr.restaurant_id AND fr.customer_id = ? 
            WHERE r.address = ? AND (r.name LIKE ? OR r.cuisine LIKE ?)";
            
        if($stmt_rest = mysqli_prepare($link, $sql_restaurants)){
            mysqli_stmt_bind_param($stmt_rest, "isss", $customer_id, $user_address, $param_term, $param_term);
            mysqli_stmt_execute($stmt_rest);
            $result_rest = mysqli_stmt_get_result($stmt_rest);
            $found_restaurants = mysqli_fetch_all($result_rest, MYSQLI_ASSOC);
            mysqli_stmt_close($stmt_rest);
        }

        $sql_items = "
            SELECT mi.id, mi.name as item_name, mi.price, mi.description, mi.image_url as item_image, 
                   r.id as restaurant_id, r.name as restaurant_name, fi.id as favorite_item_id 
            FROM menu_items mi 
            JOIN restaurants r ON mi.restaurant_id = r.id 
            LEFT JOIN favorite_items fi ON mi.id = fi.menu_item_id AND fi.customer_id = ? 
            WHERE r.address = ? AND (mi.name LIKE ? OR mi.description LIKE ? OR r.cuisine LIKE ? OR mi.category LIKE ?)";

        if($stmt_items = mysqli_prepare($link, $sql_items)){
            mysqli_stmt_bind_param($stmt_items, "isssss", $customer_id, $user_address, $param_term, $param_term, $param_term, $param_term);
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
    <title>Search - <?php echo SITE_NAME; ?></title>
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
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Search Food & Restaurants</h1>
                <?php if ($user_city): ?>
                    <p class="text-gray-600 mb-6">Showing results in <span class="font-semibold"><?php echo htmlspecialchars($user_city); ?></span></p>
                <?php endif; ?>

                <div class="mb-8">
                    <form action="search.php" method="GET" id="search-form" class="relative">
                        <div class="relative">
                            <input type="text" name="q" id="search-input" class="w-full pl-5 pr-20 py-4 text-lg border-2 border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent" placeholder="Enter restaurant name or dish..." value="<?php echo htmlspecialchars($search_query); ?>" autocomplete="off" autofocus <?php if(empty($user_address)) echo 'disabled'; ?>>
                            <button type="submit" class="absolute inset-y-0 right-0 flex items-center justify-center w-20 h-full text-white bg-orange-600 rounded-r-full hover:bg-orange-700 disabled:bg-gray-400" <?php if(empty($user_address)) echo 'disabled'; ?>>
                                <i data-lucide="search" class="w-6 h-6"></i>
                            </button>
                        </div>
                        <div id="autosuggest-container" class="absolute left-0 right-0 mt-1 bg-white rounded-lg shadow-lg z-10 hidden"></div>
                    </form>
                </div>

                <?php if (empty($user_address)): ?>
                    <div class="text-center py-16 bg-white rounded-lg shadow-md">
                        <i data-lucide="map-pin" class="mx-auto w-16 h-16 text-orange-500 mb-4"></i>
                        <h2 class="text-2xl font-semibold text-gray-700">Please set your location</h2>
                        <p class="text-gray-500 mt-2">Go to the dashboard to set your location and start searching.</p>
                        <a href="customer_dashboard.php" class="mt-4 inline-block bg-orange-600 text-white font-bold py-2 px-5 rounded-full hover:bg-orange-700 transition-colors duration-300">Go to Dashboard</a>
                    </div>
                <?php elseif (!$searched): ?>
                    <div class="text-center py-16">
                        <div class="flex items-center justify-center h-24 w-24 rounded-full bg-orange-100 text-orange-500 mx-auto mb-4"><i data-lucide="utensils-crossed" class="w-12 h-12"></i></div>
                        <h2 class="text-2xl font-semibold text-gray-700">Find your next favorite meal</h2>
                        <p class="text-gray-500 mt-2">Search for delicious food from the best local restaurants in your area.</p>
                    </div>
                <?php elseif (empty($found_restaurants) && empty($found_items)): ?>
                    <div class="text-center py-16">
                        <div class="flex items-center justify-center h-24 w-24 rounded-full bg-red-100 text-red-500 mx-auto mb-4"><i data-lucide="search-x" class="w-12 h-12"></i></div>
                        <h2 class="text-2xl font-semibold text-gray-700">No results for "<?php echo htmlspecialchars($search_query); ?>"</h2>
                        <p class="text-gray-500 mt-2">Try searching for something else in your area.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-10">
                        <?php if (!empty($found_restaurants)): ?>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800 mb-4">Restaurants</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php foreach($found_restaurants as $resto): ?>
                                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                                    <a href="view_restaurant.php?id=<?php echo $resto['id']; ?>" class="block">
                                        <img src="<?php echo htmlspecialchars($resto['banner_image_url']); ?>" alt="<?php echo htmlspecialchars($resto['name']); ?>" class="w-full h-48 object-cover">
                                        <div class="p-4">
                                            <h3 class="text-lg font-bold truncate"><?php echo htmlspecialchars($resto['name']); ?></h3>
                                            <p class="text-gray-600 text-sm truncate"><?php echo htmlspecialchars($resto['cuisine']); ?></p>
                                        </div>
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
                                <?php foreach($found_items as $item): ?>
                                <div class="bg-white p-4 rounded-lg shadow-md flex items-start space-x-4">
                                    <img src="<?php echo htmlspecialchars($item['item_image'] ?? 'https://placehold.co/100x100/F0F0F0/333?text=Dish'); ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>" class="w-24 h-24 rounded-lg object-cover flex-shrink-0">
                                    <div class="flex-1">
                                        <p class="font-bold text-lg"><?php echo htmlspecialchars($item['item_name']); ?></p>
                                        <p class="text-sm text-gray-500">from <span class="font-medium text-orange-600"><?php echo htmlspecialchars($item['restaurant_name']); ?></span></p>
                                        <p class="text-lg font-semibold mt-2">₱<?php echo number_format($item['price'], 2); ?></p>
                                    </div>
                                    <a href="view_item.php?id=<?php echo $item['id']; ?>" class="self-end px-4 py-2 text-sm font-medium text-white bg-orange-600 rounded-lg hover:bg-orange-700">View</a>
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
    <!-- Auto-suggest script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('search-input');
        const autosuggestContainer = document.getElementById('autosuggest-container');
        const searchForm = document.getElementById('search-form');
        const userAddress = "<?php echo addslashes($user_address); ?>";
        let debounceTimer;

        searchInput.addEventListener('keyup', function(e) {
            const query = e.target.value.trim();
            clearTimeout(debounceTimer);

            debounceTimer = setTimeout(() => {
                if (query.length < 2) {
                    autosuggestContainer.classList.add('hidden');
                    return;
                }
                
                // FIX: Send both search term and user address to the API
                fetch(`api/autosuggest.php?term=${encodeURIComponent(query)}&address=${encodeURIComponent(userAddress)}`)
                .then(r => r.json())
                .then(data => {
                    if (data.length > 0) {
                        let suggestionsHTML = '<ul class="divide-y divide-gray-100">';
                        data.forEach(item => {
                            const icon = item.type === 'restaurant' ? '<i data-lucide="store" class="w-5 h-5 mr-3 text-gray-400"></i>' : '<i data-lucide="utensils" class="w-5 h-5 mr-3 text-gray-400"></i>';
                            suggestionsHTML += `<li class="p-3 hover:bg-gray-100 cursor-pointer flex items-center" data-value="${item.name}">${icon}<span>${item.name}</span></li>`;
                        });
                        suggestionsHTML += '</ul>';
                        autosuggestContainer.innerHTML = suggestionsHTML;
                        autosuggestContainer.classList.remove('hidden');
                        lucide.createIcons();
                    } else {
                        autosuggestContainer.classList.add('hidden');
                    }
                });
            }, 300);
        });

        autosuggestContainer.addEventListener('click', function(e) {
            const listItem = e.target.closest('li');
            if (listItem) {
                searchInput.value = listItem.dataset.value;
                autosuggestContainer.classList.add('hidden');
                searchForm.submit();
            }
        });

        document.addEventListener('click', function (e) {
            if (autosuggestContainer && !autosuggestContainer.contains(e.target) && e.target !== searchInput) {
                autosuggestContainer.classList.add('hidden');
            }
        });
    });
    </script>
</body>
</html>
