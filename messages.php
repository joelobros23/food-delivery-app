<?php
// messages.php (Customer View with WebSocket)
require_once "app_config.php";
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'customer') {
    header("location: login.php");
    exit;
}
if (!isset($_GET['order_id']) || !isset($_GET['receiver_id'])) {
    header("location: inbox.php");
    exit;
}

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($link === false) { die("DB Connection Error"); }

$order_id = trim($_GET['order_id']);
$sender_id = $_SESSION['id'];
$receiver_id = trim($_GET['receiver_id']); // This is the store owner's user_id
$messages = [];
$store_name = "Store";
$store_restaurant_id = null; // To store the restaurant's actual ID
$order_summary = null;

// Fetch store name and ID for the header link
$sql_store = "SELECT id, name FROM restaurants WHERE user_id = ?";
if($stmt_store = mysqli_prepare($link, $sql_store)) {
    mysqli_stmt_bind_param($stmt_store, "i", $receiver_id);
    if(mysqli_stmt_execute($stmt_store)) {
        mysqli_stmt_bind_result($stmt_store, $r_id, $s_name);
        if(mysqli_stmt_fetch($stmt_store)) {
            $store_restaurant_id = $r_id;
            $store_name = $s_name;
        }
    }
    mysqli_stmt_close($stmt_store);
}

// Fetch a quick summary of the order to display
$sql_summary = "SELECT id, order_date, total_amount FROM orders WHERE id = ?";
if($stmt_summary = mysqli_prepare($link, $sql_summary)){
    mysqli_stmt_bind_param($stmt_summary, "i", $order_id);
    if(mysqli_stmt_execute($stmt_summary)) {
        $result_summary = mysqli_stmt_get_result($stmt_summary);
        $order_summary = mysqli_fetch_assoc($result_summary);
    }
    mysqli_stmt_close($stmt_summary);
}

// Fetch conversation history
$sql_messages = "SELECT message, sent_at, sender_id FROM messages WHERE order_id = ? ORDER BY sent_at ASC";
if ($stmt_messages = mysqli_prepare($link, $sql_messages)) {
    mysqli_stmt_bind_param($stmt_messages, "i", $order_id);
    if(mysqli_stmt_execute($stmt_messages)) {
        $result = mysqli_stmt_get_result($stmt_messages);
        $messages = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
    mysqli_stmt_close($stmt_messages);
}

// Mark messages as read
$sql_mark_read = "UPDATE messages SET is_read = 1 WHERE order_id = ? AND receiver_id = ?";
if($stmt_read = mysqli_prepare($link, $sql_mark_read)) {
    mysqli_stmt_bind_param($stmt_read, "ii", $order_id, $sender_id);
    mysqli_stmt_execute($stmt_read);
    mysqli_stmt_close($stmt_read);
}

mysqli_close($link);
$active_page = 'inbox';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message with <?php echo htmlspecialchars($store_name); ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen bg-gray-100">
        <?php require_once 'cdashboard_partial/sidebar.php'; ?>
        <div class="flex flex-col flex-1 h-full">
            <?php require_once 'cdashboard_partial/header.php'; ?>
            <div class="flex-1 flex flex-col p-4 md:p-6 overflow-hidden min-h-0">
                <a href="inbox.php" class="flex items-center text-gray-600 hover:text-orange-600 mb-4 font-medium"><i data-lucide="arrow-left" class="w-5 h-5 mr-2"></i>Back to Inbox</a>
                <div class="bg-white rounded-lg shadow-lg flex flex-col flex-1 min-h-0">
                    <!-- The header is now a clickable link -->
                    <a href="view_restaurant.php?id=<?php echo $store_restaurant_id; ?>" class="p-4 border-b block hover:bg-gray-50">
                        <h1 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($store_name); ?></h1>
                        <p class="text-sm text-gray-500">Regarding Order #<?php echo htmlspecialchars($order_id); ?></p>
                    </a>
                    <div id="message-container" class="flex-1 p-6 space-y-4 overflow-y-auto">
                        <!-- Order Summary -->
                        <div class="p-3 bg-gray-100 rounded-lg border border-gray-200"><p class="font-semibold">Order #<?php echo htmlspecialchars($order_summary['id'] ?? $order_id); ?></p><p class="text-xs text-gray-500">Placed: <?php echo date("M d, Y", strtotime($order_summary['order_date'] ?? 'now')); ?></p><div class="mt-2 flex justify-between items-center"><span class="font-bold text-lg">₱<?php echo number_format($order_summary['total_amount'] ?? 0, 2); ?></span><a href="track_order.php?id=<?php echo $order_id; ?>" class="px-3 py-1 bg-white text-gray-800 text-sm rounded-md border hover:bg-gray-50">View Details</a></div></div>
                        
                        <!-- Historical Messages -->
                        <?php foreach($messages as $msg): ?>
                            <div class="flex <?php echo ($msg['sender_id'] == $sender_id) ? 'justify-end' : 'justify-start'; ?>">
                                <div class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg <?php echo ($msg['sender_id'] == $sender_id) ? 'bg-orange-500 text-white' : 'bg-gray-200 text-gray-800'; ?>">
                                    <p class="text-sm"><?php echo htmlspecialchars($msg['message']); ?></p>
                                    <p class="text-xs mt-1 opacity-75 text-right"><?php echo date("g:i a", strtotime($msg['sent_at'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <!-- Message Input Form -->
                    <div class="p-4 bg-gray-50 border-t">
                        <form id="message-form" class="flex items-center space-x-3">
                            <input type="text" id="message-input" class="flex-1 block w-full px-4 py-2 border border-gray-300 rounded-full shadow-sm focus:ring-orange-500 focus:border-orange-500" placeholder="Type your message..." autocomplete="off">
                            <button type="submit" class="bg-orange-600 text-white rounded-full p-3 hover:bg-orange-700"><i data-lucide="send" class="w-5 h-5"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const messageContainer = document.getElementById('message-container');
            const messageForm = document.getElementById('message-form');
            const messageInput = document.getElementById('message-input');
            
            const orderId = "<?php echo $order_id; ?>";
            const senderId = <?php echo $sender_id; ?>;
            const receiverId = <?php echo $receiver_id; ?>;
            const wsUrl = "ws://localhost:8080";

            messageContainer.scrollTop = messageContainer.scrollHeight;
            
            const conn = new WebSocket(wsUrl);

            conn.onopen = function(e) { console.log("Connection established successfully!"); };
            conn.onclose = function(e) { console.log("Connection closed."); };
            conn.onerror = function(e) { console.error("WebSocket error:", e); };

            conn.onmessage = function(e) {
                const data = JSON.parse(e.data);

                if (data.order_id == orderId && data.sender_id != senderId) {
                    const messageHTML = `
                        <div class="flex justify-start">
                            <div class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg bg-gray-200 text-gray-800">
                                <p class="text-sm">${escapeHTML(data.message)}</p>
                                <p class="text-xs mt-1 opacity-75 text-right">${new Date(data.sent_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</p>
                            </div>
                        </div>`;
                    messageContainer.insertAdjacentHTML('beforeend', messageHTML);
                    messageContainer.scrollTop = messageContainer.scrollHeight;
                }
            };

            messageForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const messageText = messageInput.value.trim();

                if (messageText === '') { return; }

                const data = {
                    message: messageText,
                    order_id: orderId,
                    sender_id: senderId,
                    receiver_id: receiverId
                };

                conn.send(JSON.stringify(data));

                const sentMessageHTML = `
                    <div class="flex justify-end">
                        <div class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg bg-orange-500 text-white">
                            <p class="text-sm">${escapeHTML(messageText)}</p>
                            <p class="text-xs mt-1 opacity-75 text-right">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</p>
                        </div>
                    </div>`;
                messageContainer.insertAdjacentHTML('beforeend', sentMessageHTML);
                messageContainer.scrollTop = messageContainer.scrollHeight;
                
                messageForm.reset();
                messageInput.focus();
            });

            function escapeHTML(str) {
                var p = document.createElement('p');
                p.appendChild(document.createTextNode(str));
                return p.innerHTML;
            }
        });
    </script>
</body>
</html>
