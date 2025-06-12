<?php
// inbox.php
require_once "app_config.php";
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'customer') {
    header("location: login.php");
    exit;
}

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($link === false) { die("DB Connection Error"); }

$customer_id = $_SESSION['id'];
$conversations = [];

// Fetch all unique conversations for the customer.
// A conversation is defined by all messages related to a single order.
$sql = "
    SELECT 
        m.order_id,
        r.name as restaurant_name,
        r.user_id as store_owner_id,
        (SELECT message FROM messages WHERE order_id = m.order_id ORDER BY sent_at DESC LIMIT 1) as last_message,
        (SELECT sent_at FROM messages WHERE order_id = m.order_id ORDER BY sent_at DESC LIMIT 1) as last_message_time,
        (SELECT COUNT(*) FROM messages WHERE order_id = m.order_id AND receiver_id = ? AND is_read = 0) as unread_count
    FROM messages m
    JOIN orders o ON m.order_id = o.id
    JOIN restaurants r ON o.restaurant_id = r.id
    WHERE m.sender_id = ? OR m.receiver_id = ?
    GROUP BY m.order_id
    ORDER BY last_message_time DESC";

if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "iii", $customer_id, $customer_id, $customer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $conversations = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}
mysqli_close($link);
$active_page = 'inbox';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inbox - <?php echo SITE_NAME; ?></title>
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
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Inbox</h1>

                <div class="bg-white rounded-lg shadow-md">
                    <div class="divide-y divide-gray-200">
                        <?php if (empty($conversations)): ?>
                            <div class="text-center py-16">
                                <p class="text-gray-500">You have no messages yet.</p>
                            </div>
                        <?php else: foreach($conversations as $convo): ?>
                        <a href="messages.php?order_id=<?php echo $convo['order_id']; ?>&receiver_id=<?php echo $convo['store_owner_id']; ?>" class="block p-4 hover:bg-gray-50">
                            <div class="flex justify-between items-center">
                                <p class="font-bold text-gray-800"><?php echo htmlspecialchars($convo['restaurant_name']); ?></p>
                                <span class="text-xs text-gray-500"><?php echo date("M d", strtotime($convo['last_message_time'])); ?></span>
                            </div>
                            <div class="flex justify-between items-center mt-1">
                                <p class="text-sm text-gray-600 truncate pr-4"><?php echo htmlspecialchars($convo['last_message']); ?></p>
                                <?php if ($convo['unread_count'] > 0): ?>
                                <span class="bg-red-500 text-white text-xs font-bold rounded-full px-2 py-1"><?php echo $convo['unread_count']; ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
            <?php require_once 'customer_dashboard_settings/bottom_nav.php'; ?>
        </div>
    </div>
    <script src="js/script.js"></script>
</body>
</html>
