<?php
// store/inbox.php
session_start();

// Security check: ensure user is logged in and is a store owner
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'store') {
    header("location: ../login.php");
    exit;
}

require_once __DIR__ . "/../app_config.php";

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($link === false) { die("DB Connection Error"); }

$store_owner_id = $_SESSION['id'];
$inbox_threads = [];

// This complex query does the following:
// 1. Joins messages, orders, and users tables.
// 2. Groups messages by order ID to create conversation threads.
// 3. For each thread, it gets the customer's name, the last message content, 
//    the timestamp of the last message, and a count of unread messages.
$sql = "
    SELECT 
        m.order_id,
        u.full_name AS customer_name,
        (SELECT message FROM messages WHERE order_id = m.order_id ORDER BY sent_at DESC LIMIT 1) AS last_message,
        (SELECT sent_at FROM messages WHERE order_id = m.order_id ORDER BY sent_at DESC LIMIT 1) AS last_message_time,
        (SELECT id FROM users WHERE id = o.customer_id) AS customer_id,
        SUM(CASE WHEN m.receiver_id = ? AND m.is_read = 0 THEN 1 ELSE 0 END) AS unread_count
    FROM messages m
    JOIN orders o ON m.order_id = o.id
    JOIN users u ON o.customer_id = u.id
    WHERE m.receiver_id = ? OR m.sender_id = ?
    GROUP BY m.order_id
    ORDER BY last_message_time DESC
";

if($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "iii", $store_owner_id, $store_owner_id, $store_owner_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $inbox_threads = mysqli_fetch_all($result, MYSQLI_ASSOC);
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
    <title>Inbox - Store Panel</title>
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
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Inbox</h1>
                
                <div class="bg-white rounded-lg shadow-md">
                    <div class="divide-y divide-gray-200">
                        <?php if (empty($inbox_threads)): ?>
                            <p class="text-center text-gray-500 py-12">You have no messages.</p>
                        <?php else: foreach($inbox_threads as $thread): ?>
                            <a href="messages.php?order_id=<?php echo $thread['order_id']; ?>&receiver_id=<?php echo $thread['customer_id']; ?>" class="block p-4 hover:bg-gray-50">
                                <div class="flex justify-between items-center">
                                    <p class="font-bold text-gray-900"><?php echo htmlspecialchars($thread['customer_name']); ?></p>
                                    <span class="text-xs text-gray-500"><?php echo date("M d", strtotime($thread['last_message_time'])); ?></span>
                                </div>
                                <p class="text-sm text-gray-600 mt-1">
                                    Order #<?php echo $thread['order_id']; ?>: 
                                    <span class="<?php echo ($thread['unread_count'] > 0) ? 'font-bold text-gray-800' : 'text-gray-500'; ?>">
                                        <?php echo htmlspecialchars(substr($thread['last_message'], 0, 50)) . (strlen($thread['last_message']) > 50 ? '...' : ''); ?>
                                    </span>
                                </p>
                                <?php if($thread['unread_count'] > 0): ?>
                                    <div class="flex justify-end mt-2">
                                        <span class="px-2 py-1 text-xs font-bold text-white bg-red-500 rounded-full"><?php echo $thread['unread_count']; ?></span>
                                    </div>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../js/script.js"></script>
</body>
</html>
