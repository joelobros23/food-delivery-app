<?php
// view_restaurant.php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'customer') {
    header("location: login.php");
    exit;
}

if (!isset($_GET["id"]) || empty(trim($_GET["id"]))) {
    header("location: customer_dashboard.php");
    exit;
}

require_once "db_connection/config.php";

$restaurant_id = trim($_GET["id"]);
$customer_id = $_SESSION['id'];
$restaurant = null;
$menu_items = [];
$reviews = [];
$can_review = false;
$has_reviewed = false; 

// --- Fetch Restaurant Details ---
$sql_resto = "
    SELECT r.*, fr.id as favorite_restaurant_id, 
           (SELECT AVG(rating) FROM reviews WHERE restaurant_id = r.id) as avg_rating,
           (SELECT COUNT(id) FROM reviews WHERE restaurant_id = r.id) as review_count
    FROM restaurants r
    LEFT JOIN favorite_restaurants fr ON r.id = fr.restaurant_id AND fr.customer_id = ?
    WHERE r.id = ?";

if ($stmt_resto = mysqli_prepare($link, $sql_resto)) {
    mysqli_stmt_bind_param($stmt_resto, "ii", $customer_id, $restaurant_id);
    mysqli_stmt_execute($stmt_resto);
    $result = mysqli_stmt_get_result($stmt_resto);
    if (mysqli_num_rows($result) == 1) {
        $restaurant = mysqli_fetch_assoc($result);
    } else {
        header("location: customer_dashboard.php"); exit;
    }
    mysqli_stmt_close($stmt_resto);
}

// --- Check if user can review (has a delivered order without a review) ---
$sql_can_review = "SELECT id FROM orders WHERE customer_id = ? AND restaurant_id = ? AND status = 'delivered' AND id NOT IN (SELECT order_id FROM reviews) LIMIT 1";
if ($stmt_can_review = mysqli_prepare($link, $sql_can_review)) {
    mysqli_stmt_bind_param($stmt_can_review, "ii", $customer_id, $restaurant_id);
    mysqli_stmt_execute($stmt_can_review);
    mysqli_stmt_store_result($stmt_can_review);
    if(mysqli_stmt_num_rows($stmt_can_review) > 0) {
        $can_review = true;
    }
    mysqli_stmt_close($stmt_can_review);
}


// --- Fetch Menu Items ---
$sql_menu = "
    SELECT mi.*, fi.id as favorite_item_id
    FROM menu_items mi
    LEFT JOIN favorite_items fi ON mi.id = fi.menu_item_id AND fi.customer_id = ?
    WHERE mi.restaurant_id = ? AND mi.is_available = 1
    ORDER BY mi.category, mi.name";

if ($stmt_menu = mysqli_prepare($link, $sql_menu)) {
    mysqli_stmt_bind_param($stmt_menu, "ii", $customer_id, $restaurant_id);
    mysqli_stmt_execute($stmt_menu);
    $result_menu = mysqli_stmt_get_result($stmt_menu);
    while($row = mysqli_fetch_assoc($result_menu)) {
        $menu_items[$row['category']][] = $row;
    }
    mysqli_stmt_close($stmt_menu);
}


// --- Fetch Reviews and check if the current user has reviewed ---
$sql_reviews = "
    SELECT rev.*, u.full_name as customer_name
    FROM reviews rev
    JOIN users u ON rev.customer_id = u.id
    WHERE rev.restaurant_id = ?
    ORDER BY rev.review_date DESC";

if($stmt_reviews = mysqli_prepare($link, $sql_reviews)) {
    mysqli_stmt_bind_param($stmt_reviews, "i", $restaurant_id);
    mysqli_stmt_execute($stmt_reviews);
    $result_reviews = mysqli_stmt_get_result($stmt_reviews);
    while($review = mysqli_fetch_assoc($result_reviews)) {
        $reviews[] = $review;
        if ($review['customer_id'] == $customer_id) {
            $has_reviewed = true;
        }
    }
    mysqli_stmt_close($stmt_reviews);
}


mysqli_close($link);

if (is_null($restaurant)) {
    header("location: customer_dashboard.php");
    exit;
}
$is_restaurant_favorited = !is_null($restaurant['favorite_restaurant_id']);
$active_page = ''; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($restaurant['name']); ?> - Foodie</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .rating .star { cursor: pointer; color: #d1d5db; }
        .rating .star.selected, .rating:hover .star:hover, .rating .star:hover ~ .star { color: #f59e0b; }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen bg-gray-100">
        <?php require_once 'cdashboard_partial/sidebar.php'; ?>
        <div class="flex flex-col flex-1 overflow-y-auto">
            <?php require_once 'cdashboard_partial/header.php'; ?>
            <!-- Content Area -->
            <div class="p-4 md:p-6 pb-20 md:pb-6">
                <!-- Restaurant Header -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-8">
                    <img src="<?php echo htmlspecialchars($restaurant['banner_image_url'] ?? 'https://placehold.co/1200x400/F0F0F0/333?text=Restaurant'); ?>" alt="<?php echo htmlspecialchars($restaurant['name']); ?>" class="w-full h-48 md:h-64 object-cover">
                    <div class="p-6">
                        <div class="flex flex-col md:flex-row justify-between items-start">
                            <div>
                                <h1 class="text-4xl font-bold text-gray-900"><?php echo htmlspecialchars($restaurant['name']); ?></h1>
                                <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($restaurant['cuisine']); ?></p>
                                <p class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars($restaurant['address']); ?></p>
                                <div class="flex items-center mt-2 space-x-4">
                                    <span class="flex items-center text-gray-800"><i data-lucide="star" class="w-5 h-5 text-yellow-500 fill-current mr-1"></i> <b><?php echo ($restaurant['avg_rating']) ? number_format($restaurant['avg_rating'], 1) : 'New'; ?></b> <span class="text-gray-500 ml-1">(<?php echo $restaurant['review_count']; ?> reviews)</span></span>
                                    <span class="flex items-center text-gray-800"><i data-lucide="clock" class="w-5 h-5 text-gray-500 mr-1"></i> <?php echo htmlspecialchars($restaurant['operating_hours']); ?></span>
                                </div>
                            </div>
                            <button class="favorite-restaurant-btn mt-4 md:mt-0 flex items-center space-x-2 px-4 py-2 border rounded-lg hover:bg-gray-100" data-restaurant-id="<?php echo $restaurant['id']; ?>">
                                <i data-lucide="heart" class="w-6 h-6 <?php echo $is_restaurant_favorited ? 'fill-red-500 text-red-500' : 'text-gray-500'; ?>"></i>
                                <span class="font-medium"><?php echo $is_restaurant_favorited ? 'Favorited' : 'Favorite'; ?></span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Menu & Reviews Tabs -->
                <div>
                     <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                            <button id="tab-menu" class="tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm text-orange-600 border-orange-500">Menu</button>
                            <button id="tab-reviews" class="tab-btn whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">Reviews</button>
                        </nav>
                    </div>
                    <!-- Menu Content -->
                    <div id="content-menu" class="tab-content mt-6">
                         <?php if(!empty($menu_items)): foreach($menu_items as $category => $items): ?>
                            <div class="mb-8"><h2 class="text-2xl font-bold text-gray-800 mb-4 capitalize"><?php echo htmlspecialchars($category); ?></h2><div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <?php foreach($items as $item): $is_item_favorited = !is_null($item['favorite_item_id']); ?>
                                <div class="bg-white p-4 rounded-lg shadow-md flex space-x-4 items-center relative"><button class="favorite-item-btn absolute top-3 right-3 bg-white bg-opacity-70 rounded-full p-2 text-gray-600 hover:text-red-500 z-10" data-item-id="<?php echo $item['id']; ?>"><i data-lucide="heart" class="w-5 h-5 <?php echo $is_item_favorited ? 'fill-red-500 text-red-500' : ''; ?>"></i></button><a href="view_item.php?id=<?php echo $item['id']; ?>" class="flex-shrink-0"><img src="<?php echo htmlspecialchars($item['image_url'] ?? 'https://placehold.co/100x100/F0F0F0/333?text=Dish'); ?>" class="w-24 h-24 rounded-lg object-cover"></a><div class="flex-1 overflow-hidden"><a href="view_item.php?id=<?php echo $item['id']; ?>"><p class="font-bold text-lg text-gray-800 truncate"><?php echo htmlspecialchars($item['name']); ?></p></a><p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars(substr($item['description'], 0, 70)) . (strlen($item['description']) > 70 ? '...' : ''); ?></p><p class="text-lg font-semibold text-gray-700 mt-2">₱<?php echo number_format($item['price'], 2); ?></p></div></div>
                                <?php endforeach; ?>
                            </div></div>
                        <?php endforeach; else: ?><div class="text-center py-16 bg-white rounded-lg shadow-md"><p>This restaurant has not added any menu items yet.</p></div><?php endif; ?>
                    </div>

                     <!-- Reviews Content -->
                    <div id="content-reviews" class="tab-content mt-6 hidden">
                         <!-- Review Form Container -->
                         <div class="bg-white p-5 rounded-lg shadow-md mb-8">
                             <?php if ($can_review): ?>
                                 <div id="review-form-container">
                                     <h3 class="text-xl font-bold mb-4">Write a Review</h3>
                                     <form id="review-form">
                                         <input type="hidden" name="restaurant_id" value="<?php echo $restaurant['id']; ?>">
                                         <div class="mb-4">
                                             <label class="block text-gray-700 font-medium mb-2">Your Rating</label>
                                             <div class="rating flex items-center space-x-1 text-3xl" id="add-rating-stars">
                                                 <i class="star selected" data-value="1">&#9733;</i><i class="star selected" data-value="2">&#9733;</i><i class="star selected" data-value="3">&#9733;</i><i class="star selected" data-value="4">&#9733;</i><i class="star selected" data-value="5">&#9733;</i>
                                             </div>
                                             <input type="hidden" name="rating" id="rating-value" value="5">
                                         </div>
                                         <div class="mb-4">
                                             <label for="comment" class="block text-gray-700 font-medium mb-2">Your Comment</label>
                                             <textarea name="comment" id="comment" rows="4" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-orange-500 focus:border-orange-500" placeholder="Share your experience..."></textarea>
                                         </div>
                                         <div class="text-right">
                                             <button type="submit" class="bg-orange-600 text-white font-bold py-2 px-6 rounded-lg hover:bg-orange-700">Submit Review</button>
                                         </div>
                                     </form>
                                     <div id="review-message" class="mt-4"></div>
                                 </div>
                             <?php elseif (!$has_reviewed): ?>
                                 <div class="text-center p-4 bg-gray-50 rounded-lg">
                                     <p class="text-gray-600">To leave a review, you must first have a delivered order from this restaurant.</p>
                                 </div>
                             <?php endif; ?>
                         </div>

                         <div id="reviews-list" class="space-y-6">
                            <?php if(!empty($reviews)): foreach($reviews as $review): ?>
                            <div class="bg-white p-5 rounded-lg shadow-md relative" data-review-id="<?php echo $review['id']; ?>">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="font-bold text-gray-800"><?php echo htmlspecialchars($review['customer_name']); ?></p>
                                        <div class="flex items-center my-2 star-display" data-rating="<?php echo $review['rating']; ?>">
                                            <?php for($i = 0; $i < 5; $i++): ?><i data-lucide="star" class="w-5 h-5 <?php echo $i < $review['rating'] ? 'text-yellow-500 fill-current' : 'text-gray-300'; ?>"></i><?php endfor; ?>
                                        </div>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <span class="text-xs text-gray-500"><?php echo date("M d, Y", strtotime($review['review_date'])); ?></span>
                                        <?php if ($review['customer_id'] == $customer_id): ?>
                                            <div class="relative inline-block ml-2">
                                                <button class="review-options-btn p-1 rounded-full hover:bg-gray-100">
                                                    <i data-lucide="more-vertical" class="w-5 h-5"></i>
                                                </button>
                                                <div class="review-options-menu hidden absolute right-0 mt-2 w-32 bg-white rounded-md shadow-lg border z-10">
                                                    <a href="#" class="edit-review-btn flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i data-lucide="pencil" class="w-4 h-4 mr-2"></i>Edit</a>
                                                    <a href="#" class="delete-review-btn flex items-center px-4 py-2 text-sm text-red-600 hover:bg-gray-100"><i data-lucide="trash-2" class="w-4 h-4 mr-2"></i>Delete</a>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <p class="text-gray-600 mt-2 comment-display"><?php echo htmlspecialchars($review['comment']); ?></p>
                            </div>
                            <?php endforeach; else: ?>
                            <div id="no-reviews-placeholder" class="text-center py-16 bg-white rounded-lg shadow-md"><p>No reviews for this restaurant yet.</p></div>
                            <?php endif; ?>
                         </div>
                    </div>
                </div>
            </div>
            <?php require_once 'customer_dashboard_settings/bottom_nav.php'; ?>
        </div>
    </div>
    
    <!-- Modals -->
    <div id="delete-confirm-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center"><div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm"><h3 class="text-lg font-bold text-gray-900 mb-4">Confirm Deletion</h3><p class="text-gray-600 mb-6">Are you sure you want to delete this review? This action cannot be undone.</p><div class="flex justify-end space-x-4"><button id="cancel-delete-btn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">Cancel</button><button id="confirm-delete-btn" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Delete</button></div></div></div>
    <div id="edit-review-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center"><div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md"><h3 class="text-xl font-bold mb-4">Edit Your Review</h3><form id="edit-review-form"><input type="hidden" name="review_id" id="edit-review-id"><div class="mb-4"><label class="block text-gray-700 font-medium mb-2">Your Rating</label><div class="rating flex items-center space-x-1 text-3xl" id="edit-rating-stars"><i class="star" data-value="1">&#9733;</i><i class="star" data-value="2">&#9733;</i><i class="star" data-value="3">&#9733;</i><i class="star" data-value="4">&#9733;</i><i class="star" data-value="5">&#9733;</i></div><input type="hidden" name="rating" id="edit-rating-value"></div><div class="mb-4"><label for="edit-comment" class="block text-gray-700 font-medium mb-2">Your Comment</label><textarea name="comment" id="edit-comment" rows="4" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-orange-500 focus:border-orange-500"></textarea></div><div id="edit-review-message" class="text-red-500 text-sm mb-4"></div><div class="flex justify-end space-x-4"><button type="button" id="cancel-edit-btn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">Cancel</button><button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">Save Changes</button></div></form></div></div>

    <script>
        // All page-specific JavaScript for view_restaurant.php
        document.addEventListener('DOMContentLoaded', function() {
            // RESTORED: Tab switching logic
            const tabs = document.querySelectorAll('.tab-btn');
            const contents = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    tabs.forEach(t => { t.classList.remove('text-orange-600', 'border-orange-500'); t.classList.add('text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300'); });
                    tab.classList.add('text-orange-600', 'border-orange-500');
                    tab.classList.remove('text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                    contents.forEach(content => content.classList.add('hidden'));
                    document.getElementById('content-' + tab.id.split('-')[1]).classList.remove('hidden');
                });
            });

            // Star Rating Helper
            function setupStarRating(containerId) {
                const ratingContainer = document.getElementById(containerId);
                if (!ratingContainer) return;
                const stars = ratingContainer.querySelectorAll('.star');
                const ratingInput = ratingContainer.nextElementSibling;
                stars.forEach(star => {
                    star.addEventListener('click', () => {
                        ratingInput.value = star.dataset.value;
                        stars.forEach(s => { s.classList.toggle('selected', s.dataset.value <= ratingInput.value); });
                    });
                });
            }
            setupStarRating('add-rating-stars');
            setupStarRating('edit-rating-stars');
            
            function setStarRating(containerId, rating) {
                const container = document.getElementById(containerId);
                if (!container) return;
                container.nextElementSibling.value = rating;
                container.querySelectorAll('.star').forEach(s => { s.classList.toggle('selected', s.dataset.value <= rating); });
            }

            // Review form submission
            const reviewForm = document.getElementById('review-form');
            if(reviewForm) {
                reviewForm.addEventListener('submit', function(e) { e.preventDefault(); const formData = new FormData(this); const messageDiv = document.getElementById('review-message'); messageDiv.textContent = ''; if (formData.get('rating') === '0') { messageDiv.innerHTML = `<p class="text-red-600">Please select a rating.</p>`; return; } fetch('customer_dashboard_settings/add_review.php', { method: 'POST', body: formData }).then(response => response.json()).then(data => { if(data.status === 'success') { messageDiv.innerHTML = `<p class="text-green-600">${data.message}</p>`; reviewForm.reset(); document.querySelectorAll('#add-rating-stars .star').forEach(s => s.classList.add('selected')); document.getElementById('rating-value').value = 5; const reviewList = document.getElementById('reviews-list'); const newReviewHTML = `<div class="bg-white p-5 rounded-lg shadow-md border-l-4 border-orange-400" data-review-id="${data.review.id}"><div class="flex items-start justify-between"><div><p class="font-bold text-gray-800">${data.review.customer_name}</p><div class="flex items-center my-2 star-display" data-rating="${data.review.rating}">${[...Array(5)].map((_, i) => `<i data-lucide="star" class="w-5 h-5 ${i < data.review.rating ? 'text-yellow-500 fill-current' : 'text-gray-300'}"></i>`).join('')}</div></div><div class="text-right flex-shrink-0"><span class="text-xs text-gray-500">${data.review.review_date}</span></div></div><p class="text-gray-600 mt-2 comment-display">${data.review.comment}</p></div>`; reviewList.insertAdjacentHTML('afterbegin', newReviewHTML); lucide.createIcons(); document.getElementById('review-form-container').style.display = 'none'; document.getElementById('no-reviews-placeholder')?.remove(); } else { messageDiv.innerHTML = `<p class="text-red-600">${data.message}</p>`; } }); });
            }

            // Consolidated Event Listener for dynamic content
            document.addEventListener('click', function(e) {
                const optionsBtn = e.target.closest('.review-options-btn');
                if (optionsBtn) { e.stopPropagation(); document.querySelectorAll('.review-options-menu').forEach(menu => { if (menu !== optionsBtn.nextElementSibling) menu.classList.add('hidden'); }); optionsBtn.nextElementSibling.classList.toggle('hidden'); return; }
                if (!e.target.closest('.review-options-menu')) { document.querySelectorAll('.review-options-menu').forEach(menu => menu.classList.add('hidden')); }
                const deleteBtn = e.target.closest('.delete-review-btn');
                if (deleteBtn) { e.preventDefault(); reviewToDelete = deleteBtn.closest('.bg-white[data-review-id]'); deleteModal.classList.remove('hidden'); }
                const editBtn = e.target.closest('.edit-review-btn');
                if(editBtn) { e.preventDefault(); reviewToEdit = editBtn.closest('.bg-white[data-review-id]'); const reviewId = reviewToEdit.dataset.reviewId; const currentRating = reviewToEdit.querySelector('.star-display').dataset.rating; const currentComment = reviewToEdit.querySelector('.comment-display').textContent; document.getElementById('edit-review-id').value = reviewId; document.getElementById('edit-comment').value = currentComment; setStarRating('edit-rating-stars', currentRating); editModal.classList.remove('hidden'); }
            });

            // Modal Logic
            const deleteModal = document.getElementById('delete-confirm-modal');
            const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
            const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
            let reviewToDelete = null;

            cancelDeleteBtn.addEventListener('click', () => deleteModal.classList.add('hidden'));
            confirmDeleteBtn.addEventListener('click', () => {
                if (!reviewToDelete) return;
                const reviewId = reviewToDelete.dataset.reviewId;
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('review_id', reviewId); 
                fetch('customer_dashboard_settings/manage_review.php', { method: 'POST', body: formData })
                .then(r => r.json()).then(d => {
                    if (d.status === 'success') { reviewToDelete.remove(); } 
                    else { alert(d.message); }
                    deleteModal.classList.add('hidden');
                });
            });

            const editModal = document.getElementById('edit-review-modal');
            const editForm = document.getElementById('edit-review-form');
            const cancelEditBtn = document.getElementById('cancel-edit-btn');
            let reviewToEdit = null;

            cancelEditBtn.addEventListener('click', () => editModal.classList.add('hidden'));
            editForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'edit');
                fetch('customer_dashboard_settings/manage_review.php', { method: 'POST', body: formData })
                .then(r => r.json()).then(data => {
                    if(data.status === 'success') {
                        const commentDisplay = reviewToEdit.querySelector('.comment-display');
                        const starDisplay = reviewToEdit.querySelector('.star-display');
                        commentDisplay.textContent = data.review.comment;
                        starDisplay.dataset.rating = data.review.rating;
                        let starHTML = '';
                        for(let i = 0; i < 5; i++) {
                            starHTML += `<i data-lucide="star" class="w-5 h-5 ${i < data.review.rating ? 'text-yellow-500 fill-current' : 'text-gray-300'}"></i>`;
                        }
                        starDisplay.innerHTML = starHTML;
                        lucide.createIcons();
                        editModal.classList.add('hidden');
                    } else {
                        document.getElementById('edit-review-message').textContent = data.message;
                    }
                });
            });
        });
    </script>
    <script src="js/script.js"></script>
</body>
</html>
