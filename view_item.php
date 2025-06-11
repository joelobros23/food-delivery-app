<?php
// view_item.php
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'customer'){
    header("location: login.php");
    exit;
}

if(!isset($_GET["id"]) || empty(trim($_GET["id"]))){
    header("location: customer_dashboard.php");
    exit;
}

require_once "db_connection/config.php"; 

$item_id = trim($_GET["id"]);
$customer_id = $_SESSION['id'];
$item_details = null;

// UPDATED: The SQL query now also fetches the total favorite count for the item.
$sql = "
    SELECT 
        mi.id, mi.name as item_name, mi.description, mi.price, mi.image_url as item_image,
        r.id as restaurant_id, r.name as restaurant_name, r.address as restaurant_address,
        (SELECT COUNT(*) FROM favorite_items WHERE menu_item_id = mi.id) as favorite_count,
        f.id as favorite_id
    FROM menu_items mi
    JOIN restaurants r ON mi.restaurant_id = r.id
    LEFT JOIN favorite_items f ON mi.id = f.menu_item_id AND f.customer_id = ?
    WHERE mi.id = ?";

if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "ii", $customer_id, $item_id);
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        if(mysqli_num_rows($result) == 1){
            $item_details = mysqli_fetch_assoc($result);
        } else {
            header("location: customer_dashboard.php"); exit;
        }
    } else {
        echo "Oops! Something went wrong executing the query.";
    }
    mysqli_stmt_close($stmt);
} else {
    echo "Oops! Something went wrong preparing the query.";
}

mysqli_close($link);

if(is_null($item_details)){
    header("location: customer_dashboard.php");
    exit;
}
$is_favorited = !is_null($item_details['favorite_id']);
$active_page = 'search';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($item_details['item_name']); ?> - Foodie</title>
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
                 <a href="javascript:history.back()" class="flex items-center text-gray-600 hover:text-orange-600 mb-6"><i data-lucide="arrow-left" class="w-5 h-5 mr-2"></i>Back</a>
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="md:flex">
                        <div class="md:w-1/2">
                            <img src="<?php echo htmlspecialchars($item_details['item_image'] ?? 'https://placehold.co/600x600/F0F0F0/333?text=Delicious+Food'); ?>" alt="<?php echo htmlspecialchars($item_details['item_name']); ?>" class="w-full h-64 md:h-full object-cover">
                        </div>
                        <div class="p-6 md:p-8 md:w-1/2 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between">
                                    <p class="text-sm text-gray-500">From <a href="view_restaurant.php?id=<?php echo $item_details['restaurant_id']; ?>" class="font-semibold text-orange-600 hover:underline"><?php echo htmlspecialchars($item_details['restaurant_name']); ?></a></p>
                                    <button class="favorite-item-btn rounded-full p-2 text-gray-600 hover:text-red-500" data-item-id="<?php echo $item_details['id']; ?>">
                                        <i data-lucide="heart" class="w-6 h-6 <?php echo $is_favorited ? 'fill-red-500 text-red-500' : ''; ?>"></i>
                                    </button>
                                </div>
                                <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2"><?php echo htmlspecialchars($item_details['item_name']); ?></h1>
                                
                                <!-- NEW: Favorite Count Display -->
                                <?php if ($item_details['favorite_count'] > 0): ?>
                                    <p class="text-sm text-gray-500 mt-2 flex items-center">
                                        <i data-lucide="heart" class="w-4 h-4 mr-2 text-red-500 fill-current"></i>
                                        Favorited by <?php echo $item_details['favorite_count']; ?> <?php echo ($item_details['favorite_count'] == 1) ? 'person' : 'people'; ?>
                                    </p>
                                <?php endif; ?>

                                <p class="text-gray-700 mt-4"><?php echo nl2br(htmlspecialchars($item_details['description'])); ?></p>
                            </div>
                            <div class="mt-8">
                                <p class="text-4xl font-extrabold text-gray-900">₱<?php echo number_format($item_details['price'], 2); ?></p>
                                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <button class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-orange-600 bg-orange-100 hover:bg-orange-200">Add to Cart</button>
                                    <button class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700">Buy Now</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
             <?php require_once 'customer_dashboard_settings/bottom_nav.php'; ?>
        </div>
    </div>
    <script src="js/script.js"></script>
</body>
</html>
