<?php
// profile.php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'customer') {
    header("location: login.php");
    exit;
}

require_once "app_config.php";
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($link === false) { die("DB Connection Error"); }

$customer_id = $_SESSION['id'];
$activities = [];
$user_details = [];

// --- NEW: Fetch phone number and complete address ---
$sql_user = "SELECT phone_number, complete_address FROM users WHERE id = ?";
if($stmt_user = mysqli_prepare($link, $sql_user)){
    mysqli_stmt_bind_param($stmt_user, "i", $customer_id);
    mysqli_stmt_execute($stmt_user);
    $result_user = mysqli_stmt_get_result($stmt_user);
    $user_details = mysqli_fetch_assoc($result_user);
    mysqli_stmt_close($stmt_user);
}

// Fetch recent activities (last 10 favorites and reviews)
$sql_activities = "
    (SELECT 'favorite_item' as type, mi.name as content, f.created_at as date, r.name as context 
     FROM favorite_items f 
     JOIN menu_items mi ON f.menu_item_id = mi.id 
     JOIN restaurants r ON mi.restaurant_id = r.id
     WHERE f.customer_id = ?)
    UNION
    (SELECT 'favorite_restaurant' as type, r.name as content, f.created_at as date, '' as context
     FROM favorite_restaurants f
     JOIN restaurants r ON f.restaurant_id = r.id
     WHERE f.customer_id = ?)
    UNION
    (SELECT 'review' as type, rev.comment as content, rev.review_date as date, r.name as context
     FROM reviews rev
     JOIN restaurants r ON rev.restaurant_id = r.id
     WHERE rev.customer_id = ?)
    ORDER BY date DESC
    LIMIT 10";

if($stmt = mysqli_prepare($link, $sql_activities)) {
    mysqli_stmt_bind_param($stmt, "iii", $customer_id, $customer_id, $customer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $activities = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}

mysqli_close($link);
$active_page = 'profile'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Foodie</title>
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
                
                <div class="max-w-4xl mx-auto space-y-8">
                    <div class="bg-white p-6 rounded-lg shadow-md text-center">
                        <img src="https://placehold.co/128x128/EFEFEF/333?text=<?php echo substr($_SESSION["name"], 0, 1); ?>" alt="profile" class="w-32 h-32 rounded-full mx-auto mb-4 border-4 border-white ring-2 ring-orange-500">
                        <h1 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($_SESSION["name"]); ?></h1>
                        <p class="text-gray-500"><?php echo htmlspecialchars($_SESSION["email"]); ?></p>
                        
                        <p class="mt-2 text-sm text-gray-500 flex items-center justify-center">
                            <i data-lucide="phone" class="w-4 h-4 mr-2"></i>
                            <span><?php echo htmlspecialchars($user_details['phone_number'] ?? 'Phone not set'); ?></span>
                        </p>
                        <p class="mt-1 text-sm text-gray-500 flex items-center justify-center">
                            <i data-lucide="home" class="w-4 h-4 mr-2"></i>
                            <span><?php echo htmlspecialchars($user_details['complete_address'] ?? 'Address not set'); ?></span>
                        </p>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-md">
                         <h2 class="text-xl font-bold text-gray-800 mb-4">My Activities</h2>
                         <div class="space-y-6">
                             <?php if (!empty($activities)): foreach($activities as $activity): ?>
                             <div class="flex space-x-4">
                                 <div class="flex-shrink-0">
                                    <span class="flex items-center justify-center h-10 w-10 rounded-full bg-orange-100 text-orange-600">
                                        <?php if($activity['type'] == 'review'): ?><i data-lucide="message-square"></i>
                                        <?php else: ?><i data-lucide="heart"></i><?php endif; ?>
                                    </span>
                                 </div>
                                 <div class="flex-1">
                                     <p class="text-sm text-gray-800">
                                         <?php 
                                            if($activity['type'] == 'review') {
                                                echo "You left a review for <b>" . htmlspecialchars($activity['context']) . "</b>: <em>\"" . htmlspecialchars(substr($activity['content'], 0, 50)) . "...\"</em>";
                                            } elseif($activity['type'] == 'favorite_item') {
                                                echo "You favorited <b>" . htmlspecialchars($activity['content']) . "</b> from " . htmlspecialchars($activity['context']) . ".";
                                            } elseif($activity['type'] == 'favorite_restaurant') {
                                                echo "You favorited the restaurant <b>" . htmlspecialchars($activity['content']) . "</b>.";
                                            }
                                         ?>
                                     </p>
                                     <p class="text-xs text-gray-400 mt-1"><?php echo date("F j, Y, g:i a", strtotime($activity['date'])); ?></p>
                                 </div>
                             </div>
                             <?php endforeach; else: ?>
                             <p class="text-gray-500">No recent activity to show.</p>
                             <?php endif; ?>
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
