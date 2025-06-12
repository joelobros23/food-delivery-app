<?php
// store/reviews.php
session_start();
// Security check
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'store') {
    header("location: ../login.php");
    exit;
}

// FIX: Use the correct path for the main app config file.
require_once __DIR__ . "/../app_config.php";

// FIX: Establish DB connection after including the config.
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($link === false) { die("DB Connection Error"); }

$store_owner_id = $_SESSION['id'];
$restaurant_id = null;
$restaurant_name = "My Restaurant"; 
$reviews = [];
$stats = ['avg_rating' => 0, 'review_count' => 0];

// Fetch the restaurant ID and name
$sql_resto_info = "SELECT id, name FROM restaurants WHERE user_id = ? LIMIT 1";
if($stmt_resto = mysqli_prepare($link, $sql_resto_info)){
    mysqli_stmt_bind_param($stmt_resto, "i", $store_owner_id);
    mysqli_stmt_execute($stmt_resto);
    mysqli_stmt_bind_result($stmt_resto, $r_id, $r_name);
    if(mysqli_stmt_fetch($stmt_resto)){
        $restaurant_id = $r_id;
        $restaurant_name = $r_name;
    }
    mysqli_stmt_close($stmt_resto);
}

// Fetch all reviews and stats for the restaurant
if ($restaurant_id) {
    // Fetch review stats
    $sql_stats = "SELECT AVG(rating) as avg_rating, COUNT(id) as review_count FROM reviews WHERE restaurant_id = ?";
    if($stmt_stats = mysqli_prepare($link, $sql_stats)){
        mysqli_stmt_bind_param($stmt_stats, "i", $restaurant_id);
        mysqli_stmt_execute($stmt_stats);
        $result = mysqli_stmt_get_result($stmt_stats);
        $fetched_stats = mysqli_fetch_assoc($result);
        if($fetched_stats) {
            $stats = $fetched_stats;
        }
        mysqli_stmt_close($stmt_stats);
    }

    // Fetch all reviews
    $sql_reviews = "
        SELECT rev.rating, rev.comment, rev.review_date, u.full_name as customer_name
        FROM reviews rev
        JOIN users u ON rev.customer_id = u.id
        WHERE rev.restaurant_id = ?
        ORDER BY rev.review_date DESC";
    
    if ($stmt_reviews = mysqli_prepare($link, $sql_reviews)) {
        mysqli_stmt_bind_param($stmt_reviews, "i", $restaurant_id);
        mysqli_stmt_execute($stmt_reviews);
        $result_reviews = mysqli_stmt_get_result($stmt_reviews);
        $reviews = mysqli_fetch_all($result_reviews, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt_reviews);
    }
}

mysqli_close($link);
$active_page = 'reviews';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Reviews - <?php echo SITE_NAME; ?></title>
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
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Customer Reviews</h1>
                <p class="text-gray-600 mb-6">See what your customers are saying about your restaurant.</p>

                 <!-- Overall Rating -->
                <div class="bg-white p-6 rounded-lg shadow-md mb-8 flex items-center space-x-6">
                    <div class="flex-shrink-0">
                        <p class="text-5xl font-bold text-gray-900"><?php echo number_format($stats['avg_rating'] ?? 0, 1); ?></p>
                        <p class="text-sm text-gray-500 text-center">out of 5</p>
                    </div>
                    <div>
                         <div class="flex items-center">
                            <?php for($i = 0; $i < 5; $i++): ?>
                                <i data-lucide="star" class="w-6 h-6 <?php echo $i < round($stats['avg_rating'] ?? 0) ? 'text-yellow-400 fill-current' : 'text-gray-300'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="mt-1 text-gray-600">Based on <?php echo $stats['review_count'] ?? 0; ?> reviews.</p>
                    </div>
                </div>

                <!-- Reviews List -->
                <div class="space-y-6">
                    <?php if (empty($reviews)): ?>
                        <div class="text-center py-16 bg-white rounded-lg shadow-md">
                            <p class="text-gray-500">You have no reviews yet.</p>
                        </div>
                    <?php else: foreach($reviews as $review): ?>
                    <div class="bg-white p-5 rounded-lg shadow-md">
                        <div class="flex items-center justify-between">
                            <p class="font-bold text-gray-800"><?php echo htmlspecialchars($review['customer_name']); ?></p>
                            <span class="text-xs text-gray-500"><?php echo date("F d, Y", strtotime($review['review_date'])); ?></span>
                        </div>
                        <div class="flex items-center my-2">
                            <?php for($i = 0; $i < 5; $i++): ?>
                                <i data-lucide="star" class="w-5 h-5 <?php echo $i < $review['rating'] ? 'text-yellow-400 fill-current' : 'text-gray-300'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="text-gray-600"><?php echo htmlspecialchars($review['comment']); ?></p>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="../js/script.js"></script>
</body>
</html>
