<?php
// view_item.php
require_once "app_config.php";
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'customer'){
    header("location: login.php");
    exit;
}

if(!isset($_GET["id"]) || empty(trim($_GET["id"]))){
    header("location: customer_dashboard.php");
    exit;
}

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($link === false) { die("DB Connection Error"); }

$item_id = trim($_GET["id"]);
$customer_id = $_SESSION['id'];
$item_details = null;
$user_contact_details = null;

// Fetch item details
$sql_item = "SELECT mi.id, mi.name as item_name, mi.description, mi.price, mi.image_url as item_image, r.id as restaurant_id, r.name as restaurant_name, (SELECT COUNT(*) FROM favorite_items WHERE menu_item_id = mi.id) as favorite_count, f.id as favorite_id FROM menu_items mi JOIN restaurants r ON mi.restaurant_id = r.id LEFT JOIN favorite_items f ON mi.id = f.menu_item_id AND f.customer_id = ? WHERE mi.id = ?";
if($stmt_item = mysqli_prepare($link, $sql_item)){
    mysqli_stmt_bind_param($stmt_item, "ii", $customer_id, $item_id);
    mysqli_stmt_execute($stmt_item);
    $result_item = mysqli_stmt_get_result($stmt_item);
    if(mysqli_num_rows($result_item) == 1){ $item_details = mysqli_fetch_assoc($result_item); } 
    else { header("location: customer_dashboard.php"); exit; }
    mysqli_stmt_close($stmt_item);
}

// Fetch user's contact details to check if they are complete
$sql_user = "SELECT phone_number, complete_address FROM users WHERE id = ?";
if($stmt_user = mysqli_prepare($link, $sql_user)){
    mysqli_stmt_bind_param($stmt_user, "i", $customer_id);
    mysqli_stmt_execute($stmt_user);
    $result_user = mysqli_stmt_get_result($stmt_user);
    $user_contact_details = mysqli_fetch_assoc($result_user);
    mysqli_stmt_close($stmt_user);
}

mysqli_close($link);

if(is_null($item_details)){ header("location: customer_dashboard.php"); exit; }
$is_favorited = !is_null($item_details['favorite_id']);
$active_page = 'search';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($item_details['item_name']); ?> - <?php echo SITE_NAME; ?></title>
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
                        <div class="md:w-1/2"><img src="<?php echo htmlspecialchars($item_details['item_image'] ?? 'https://placehold.co/600x600/F0F0F0/333?text=Delicious+Food'); ?>" alt="<?php echo htmlspecialchars($item_details['item_name']); ?>" class="w-full h-64 md:h-full object-cover"></div>
                        <div class="p-6 md:p-8 md:w-1/2 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between"><p class="text-sm text-gray-500">From <a href="view_restaurant.php?id=<?php echo $item_details['restaurant_id']; ?>" class="font-semibold text-orange-600 hover:underline"><?php echo htmlspecialchars($item_details['restaurant_name']); ?></a></p><button class="favorite-item-btn rounded-full p-2 text-gray-600 hover:text-red-500" data-item-id="<?php echo $item_details['id']; ?>"><i data-lucide="heart" class="w-6 h-6 <?php echo $is_favorited ? 'fill-red-500 text-red-500' : ''; ?>"></i></button></div>
                                <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mt-2"><?php echo htmlspecialchars($item_details['item_name']); ?></h1>
                                <?php if ($item_details['favorite_count'] > 0): ?><p class="text-sm text-gray-500 mt-2 flex items-center"><i data-lucide="heart" class="w-4 h-4 mr-2 text-red-500 fill-current"></i>Favorited by <?php echo $item_details['favorite_count']; ?> <?php echo ($item_details['favorite_count'] == 1) ? 'person' : 'people'; ?></p><?php endif; ?>
                                <p class="text-gray-700 mt-4"><?php echo nl2br(htmlspecialchars($item_details['description'])); ?></p>
                            </div>
                            <div class="mt-8">
                                <p class="text-4xl font-extrabold text-gray-900">₱<?php echo number_format($item_details['price'], 2); ?></p>
                                <div class="mt-4">
                                    <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                                    <input type="number" id="quantity" name="quantity" value="1" min="1" max="10" class="w-20 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500">
                                </div>
                                <div id="cart-message" class="text-sm font-semibold mt-4"></div>
                                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <button id="add-to-cart-btn" data-item-id="<?php echo $item_details['id']; ?>" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-orange-600 bg-orange-100 hover:bg-orange-200">Add to Cart</button>
                                    <button id="buy-now-btn" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700">Buy Now</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
             <?php require_once 'customer_dashboard_settings/bottom_nav.php'; ?>
        </div>
    </div>

    <!-- FIX: Restored the Payment Modal -->
    <div id="payment-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm">
            <div class="flex justify-between items-center mb-4">
                 <h3 class="text-lg font-bold text-gray-900">Choose Payment Method</h3>
                 <button id="close-modal-btn" class="p-1 rounded-full hover:bg-gray-200"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="space-y-3">
                <button class="payment-option-btn w-full text-left p-3 border rounded-lg hover:border-orange-500 hover:bg-orange-50">Credit Card (Not Available)</button>
                <button class="payment-option-btn w-full text-left p-3 border rounded-lg hover:border-orange-500 hover:bg-orange-50">GCash (Not Available)</button>
                <a href="purchase.php?item_id=<?php echo $item_details['id']; ?>&restaurant_id=<?php echo $item_details['restaurant_id']; ?>" class="block w-full text-left p-3 border rounded-lg hover:border-orange-500 hover:bg-orange-50">Cash on Delivery</a>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const addToCartBtn = document.getElementById('add-to-cart-btn');
            const buyNowBtn = document.getElementById('buy-now-btn');
            const quantityInput = document.getElementById('quantity');
            const cartMessageDiv = document.getElementById('cart-message');
            const paymentModal = document.getElementById('payment-modal');
            const closeModalBtn = document.getElementById('close-modal-btn');
            
            const hasPhoneNumber = <?php echo json_encode(!empty($user_contact_details['phone_number'])); ?>;
            const hasCompleteAddress = <?php echo json_encode(!empty($user_contact_details['complete_address'])); ?>;

            function redirectToContactDetails() {
                cartMessageDiv.innerHTML = `<span class="text-red-600 flex items-center"><i data-lucide="alert-circle" class="w-5 h-5 mr-2"></i>Please complete your contact details first. Redirecting...</span>`;
                lucide.createIcons();
                setTimeout(() => { window.location.href = 'contact_details.php'; }, 2000);
            }

            addToCartBtn.addEventListener('click', () => {
                if (!hasPhoneNumber || !hasCompleteAddress) {
                    redirectToContactDetails();
                    return;
                }

                const itemId = addToCartBtn.dataset.itemId;
                const quantity = quantityInput.value;
                cartMessageDiv.textContent = '';
                const formData = new FormData();
                formData.append('item_id', itemId);
                formData.append('quantity', quantity);

                fetch('manage_cart.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        cartMessageDiv.innerHTML = `<span class="flex items-center text-green-600"><i data-lucide="check-circle" class="w-5 h-5 mr-2"></i> ${data.message}</span>`;
                        lucide.createIcons();
                    } else {
                        cartMessageDiv.innerHTML = `<span class="text-red-600">${data.message}</span>`;
                    }
                });
            });
            
            // FIX: This now checks details before showing the modal
            buyNowBtn.addEventListener('click', () => {
                if (!hasPhoneNumber || !hasCompleteAddress) {
                    redirectToContactDetails();
                    return;
                }
                paymentModal.classList.remove('hidden');
                lucide.createIcons(); // Redraw icons in the modal if needed
            });
            
            closeModalBtn.addEventListener('click', () => {
                paymentModal.classList.add('hidden');
            });
            
            paymentModal.addEventListener('click', (e) => {
                if(e.target === paymentModal) {
                    paymentModal.classList.add('hidden');
                }
            });

            document.querySelectorAll('.payment-option-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    if (btn.textContent.includes('Not Available')) {
                        e.preventDefault();
                        alert('This payment method is not available for this store.');
                    }
                });
            });

        });
    </script>
</body>
</html>
