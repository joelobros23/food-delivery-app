<?php
// cart.php
require_once "app_config.php";
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'customer') {
    header("location: login.php");
    exit;
}

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($link === false) { die("DB Connection Error"); }

$customer_id = $_SESSION['id'];
$cart_items = [];
$subtotal = 0;

// Fetch all cart items for the customer
$sql = "
    SELECT 
        c.id as cart_id, c.quantity,
        mi.id as item_id, mi.name as item_name, mi.price, mi.image_url,
        r.name as restaurant_name
    FROM cart_items c
    JOIN menu_items mi ON c.item_id = mi.id
    JOIN restaurants r ON mi.restaurant_id = r.id
    WHERE c.customer_id = ?
    ORDER BY r.name ASC, mi.name ASC";

if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $customer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $cart_items = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}

// Calculate subtotal
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

mysqli_close($link);
$active_page = 'cart';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart - <?php echo SITE_NAME; ?></title>
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
            <div class="p-4 md:p-6 pb-40 md:pb-6">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">My Cart</h1>

                <?php if (empty($cart_items)): ?>
                    <div class="text-center py-16 bg-white rounded-lg shadow-md">
                        <div class="flex items-center justify-center h-24 w-24 rounded-full bg-gray-100 text-gray-500 mx-auto mb-4"><i data-lucide="shopping-cart" class="w-12 h-12"></i></div>
                        <h2 class="text-2xl font-semibold text-gray-700">Your cart is empty</h2>
                        <p class="text-gray-500 mt-2">Looks like you haven't added anything yet.</p>
                        <a href="customer_dashboard.php" class="mt-6 inline-block bg-orange-600 text-white font-bold py-2 px-5 rounded-full hover:bg-orange-700 transition-colors duration-300">Start Shopping</a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md space-y-4">
                            <h2 class="text-xl font-bold text-gray-800 border-b pb-4">Order Summary</h2>
                            <?php foreach($cart_items as $item): ?>
                            <div class="cart-item-row flex items-center space-x-4 py-2 border-b last:border-b-0" data-cart-id="<?php echo $item['cart_id']; ?>">
                                <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'https://placehold.co/100x100/F0F0F0/333?text=Dish'); ?>" class="w-16 h-16 rounded-lg object-cover">
                                <div class="flex-1">
                                    <p class="font-bold"><?php echo htmlspecialchars($item['item_name']); ?></p>
                                    <p class="text-sm text-gray-500">from <?php echo htmlspecialchars($item['restaurant_name']); ?></p>
                                    <p class="font-semibold text-orange-600 text-sm">₱<?php echo number_format($item['price'], 2); ?></p>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <span class="font-bold w-8 text-center quantity-display">x <?php echo $item['quantity']; ?></span>
                                </div>
                                <p class="font-bold w-24 text-right item-total">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="lg:col-span-1">
                             <div class="bg-white p-6 rounded-lg shadow-md sticky top-24">
                                 <h2 class="text-xl font-bold text-gray-800 mb-4">Total</h2>
                                 <div class="space-y-2">
                                     <div class="flex justify-between"><span class="text-gray-600">Subtotal</span><span id="subtotal" class="font-semibold">₱<?php echo number_format($subtotal, 2); ?></span></div>
                                     <div class="flex justify-between"><span class="text-gray-600">Delivery fee</span><span class="font-semibold">₱50.00</span></div>
                                     <div class="border-t my-2"></div>
                                     <div class="flex justify-between text-lg"><span class="font-bold">Total</span><span id="total" class="font-extrabold text-orange-600">₱<?php echo number_format($subtotal + 50, 2); ?></span></div>
                                 </div>
                                 <div class="mt-6 space-y-3">
                                     <button id="checkout-btn" class="w-full flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700">Proceed to Checkout</button>
                                     <a href="customer_dashboard.php" class="w-full block text-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 hover:bg-gray-50">Add more items</a>
                                 </div>
                             </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php require_once 'customer_dashboard_settings/bottom_nav.php'; ?>
        </div>
    </div>
    
    <!-- Payment Modal -->
    <div id="payment-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm">
            <div class="flex justify-between items-center mb-4"><h3 class="text-lg font-bold text-gray-900">Choose Payment Method</h3><button id="close-modal-btn" class="p-1 rounded-full hover:bg-gray-200"><i data-lucide="x" class="w-5 h-5"></i></button></div>
            <div class="space-y-3">
                <button class="payment-option-btn w-full text-left p-3 border rounded-lg hover:border-orange-500 hover:bg-orange-50">Credit Card (Not Available)</button>
                <button class="payment-option-btn w-full text-left p-3 border rounded-lg hover:border-orange-500 hover:bg-orange-50">GCash (Not Available)</button>
                <button id="cod-btn" class="payment-option-btn w-full text-left p-3 border rounded-lg hover:border-orange-500 hover:bg-orange-50">Cash on Delivery</button>
            </div>
        </div>
    </div>
    <script src="js/cart.js"></script>
    <script src="js/script.js"></script>
</body>
</html>
