<?php
// store/menu.php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'store') {
    header("location: ../login.php");
    exit;
}

require_once __DIR__ . "/../app_config.php";

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($link === false) { die("DB Connection Error"); }

$store_owner_id = $_SESSION['id'];
$restaurant_id = null;
$menu_items = [];

// Fetch the restaurant ID
$sql_resto_info = "SELECT id FROM restaurants WHERE user_id = ? LIMIT 1";
if($stmt_resto = mysqli_prepare($link, $sql_resto_info)){
    mysqli_stmt_bind_param($stmt_resto, "i", $store_owner_id);
    mysqli_stmt_execute($stmt_resto);
    mysqli_stmt_bind_result($stmt_resto, $r_id);
    if(mysqli_stmt_fetch($stmt_resto)){ $restaurant_id = $r_id; }
    mysqli_stmt_close($stmt_resto);
}

// Fetch all menu items for the restaurant
if ($restaurant_id) {
    $sql_menu = "SELECT * FROM menu_items WHERE restaurant_id = ? ORDER BY category, name";
    if ($stmt_menu = mysqli_prepare($link, $sql_menu)) {
        mysqli_stmt_bind_param($stmt_menu, "i", $restaurant_id);
        mysqli_stmt_execute($stmt_menu);
        $result = mysqli_stmt_get_result($stmt_menu);
        $menu_items = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt_menu);
    }
}

mysqli_close($link);
$active_page = 'menu';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Items - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen bg-gray-100">
        <?php require_once 'partials/sidebar.php'; ?>
        <div class="flex flex-col flex-1 overflow-y-auto">
            <?php require_once 'partials/header.php'; ?>
            <div class="p-4 md:p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-800">Menu Items</h1>
                    <button id="add-item-btn" class="bg-orange-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-orange-700 flex items-center">
                        <i data-lucide="plus" class="w-5 h-5 mr-2"></i> Add New Item
                    </button>
                </div>
                
                <div class="bg-white rounded-lg shadow-md overflow-x-auto">
                    <table class="min-w-full">
                        <!-- Table Head -->
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Available</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <!-- Table Body -->
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($menu_items)): ?>
                                <tr><td colspan="5" class="text-center py-10 text-gray-500">You haven't added any menu items yet.</td></tr>
                            <?php else: foreach($menu_items as $item): ?>
                            <tr data-item-id="<?php echo $item['id']; ?>">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-12 w-12">
                                            <img class="item-image h-12 w-12 rounded-md object-cover" src="../<?php echo htmlspecialchars($item['image_url'] ?? 'uploads/menu_images/default.png'); ?>" alt="">
                                        </div>
                                        <div class="ml-4">
                                            <div class="item-name text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['name']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="item-category px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($item['category']); ?></td>
                                <td class="item-price px-6 py-4 whitespace-nowrap text-sm text-gray-500">₱<?php echo number_format($item['price'], 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" data-id="<?php echo $item['id']; ?>" class="availability-toggle sr-only peer" <?php echo $item['is_available'] ? 'checked' : ''; ?>>
                                        <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-focus:ring-2 peer-focus:ring-orange-300 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
                                    </label>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="relative inline-block text-left">
                                        <button class="item-options-btn p-2 rounded-full hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-orange-500"><i data-lucide="more-vertical" class="w-5 h-5"></i></button>
                                        <div class="item-options-menu hidden absolute right-0 mt-2 w-32 bg-white rounded-md shadow-lg border z-20 overflow-hidden"><a href="#" class="edit-btn w-full flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i data-lucide="pencil" class="w-4 h-4 mr-2 flex-shrink-0"></i><span>Edit</span></a><a href="#" class="delete-btn w-full flex items-center px-4 py-2 text-sm text-red-600 hover:bg-gray-100"><i data-lucide="trash-2" class="w-4 h-4 mr-2 flex-shrink-0"></i><span>Delete</span></a></div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Item Modal -->
    <div id="item-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-lg">
            <h3 id="modal-title" class="text-xl font-bold text-gray-900 mb-4">Add New Item</h3>
            <form id="item-form" enctype="multipart/form-data">
                <input type="hidden" name="action" id="form-action" value="add">
                <input type="hidden" name="item_id" id="item-id">
                <div class="space-y-4">
                    <!-- Image Upload Field -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Item Image</label>
                        <div class="mt-1 flex items-center space-x-4">
                            <img id="image-preview" src="https://placehold.co/100x100/F0F0F0/333?text=Dish" class="h-16 w-16 rounded-md object-cover bg-gray-100">
                            <div class="flex-1">
                                <input type="file" name="item_image" id="item-image" accept="image/jpeg, image/png, image/gif" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100">
                                <p class="text-xs text-gray-500 mt-1">PNG, JPG, GIF up to 2MB. Max 900px resolution.</p>
                            </div>
                        </div>
                    </div>
                    <!-- Other Form Fields -->
                    <div>
                        <label for="item-name" class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="name" id="item-name" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    <div>
                        <label for="item-description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" id="item-description" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                             <label for="item-category" class="block text-sm font-medium text-gray-700">Category</label>
                             <input type="text" name="category" id="item-category" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500">
                        </div>
                        <div>
                            <label for="item-price" class="block text-sm font-medium text-gray-700">Price (₱)</label>
                            <input type="number" name="price" id="item-price" step="0.01" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500">
                        </div>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_available" id="item-is-available" class="h-4 w-4 text-orange-600 border-gray-300 rounded focus:ring-orange-500">
                        <label for="item-is-available" class="ml-2 block text-sm text-gray-900">Item is available</label>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-4">
                    <button type="button" id="cancel-item-btn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">Save Item</button>
                </div>
            </form>
        </div>
    </div>
    <script src="../js/script.js"></script>
<script src="../js/store_menu.js"></script>
    <script>
        // Add this script to handle the image preview
        document.getElementById('item-image').addEventListener('change', function(event) {
            const preview = document.getElementById('image-preview');
            const file = event.target.files[0];
            if (file) {
                preview.src = URL.createObjectURL(file);
            }
        });
    </script>
</body>
</html>
