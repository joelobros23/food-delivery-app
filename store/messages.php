<?php
// store/messages.php
require_once "../app_config.php";
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'store') {
    header("location: ../login.php");
    exit;
}
if (!isset($_GET['order_id']) || !isset($_GET['receiver_id'])) {
    header("location: index.php");
    exit;
}

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($link === false) { die("DB Connection Error"); }

$order_id = trim($_GET['order_id']);
$sender_id = $_SESSION['id'];
$receiver_id = trim($_GET['receiver_id']);
$messages = [];

// Fetch conversation history
$sql = "
    SELECT m.message, m.sent_at, m.sender_id, u_sender.full_name as sender_name
    FROM messages m
    JOIN users u_sender ON m.sender_id = u_sender.id
    WHERE m.order_id = ?
    ORDER BY m.sent_at ASC";

if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $messages = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}
mysqli_close($link);
$active_page = 'orders';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message for Order #<?php echo htmlspecialchars($order_id); ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen bg-gray-100">
        <?php require_once 'partials/sidebar.php'; ?>
        <div class="flex flex-col flex-1 h-full">
            <?php require_once 'partials/header.php'; ?>
            <div class="flex-1 flex flex-col p-4 md:p-6 overflow-hidden">
                <a href="view_order.php?id=<?php echo $order_id; ?>" class="flex items-center text-gray-600 hover:text-orange-600 mb-4 font-medium"><i data-lucide="arrow-left" class="w-5 h-5 mr-2"></i>Back to Order Details</a>
                <div class="bg-white rounded-lg shadow-lg flex flex-col flex-1">
                    <div class="p-4 border-b">
                        <h1 class="text-xl font-bold text-gray-900">Conversation for Order #<?php echo htmlspecialchars($order_id); ?></h1>
                    </div>
                    <div id="message-container" class="flex-1 p-6 space-y-4 overflow-y-auto">
                        <!-- Messages will be loaded here -->
                        <?php foreach($messages as $msg): ?>
                            <div class="flex <?php echo ($msg['sender_id'] == $sender_id) ? 'justify-end' : 'justify-start'; ?>">
                                <div class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg <?php echo ($msg['sender_id'] == $sender_id) ? 'bg-orange-500 text-white' : 'bg-gray-200 text-gray-800'; ?>">
                                    <p class="text-sm"><?php echo htmlspecialchars($msg['message']); ?></p>
                                    <p class="text-xs mt-1 opacity-75"><?php echo date("g:i a", strtotime($msg['sent_at'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="p-4 bg-gray-50 border-t">
                        <form id="message-form" class="flex items-center space-x-3">
                            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                            <input type="hidden" name="receiver_id" value="<?php echo $receiver_id; ?>">
                            <input type="text" name="message" id="message-input" class="flex-1 block w-full px-4 py-2 border border-gray-300 rounded-full shadow-sm focus:ring-orange-500 focus:border-orange-500" placeholder="Type your message...">
                            <button type="submit" class="bg-orange-600 text-white rounded-full p-3 hover:bg-orange-700">
                                <i data-lucide="send" class="w-5 h-5"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const messageContainer = document.getElementById('message-container');
            const messageForm = document.getElementById('message-form');
            const messageInput = document.getElementById('message-input');

            // Scroll to the bottom of the chat on load
            messageContainer.scrollTop = messageContainer.scrollHeight;

            messageForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                if (messageInput.value.trim() === '') return;

                fetch('send_message.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        const messageHTML = `
                            <div class="flex justify-end">
                                <div class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg bg-orange-500 text-white">
                                    <p class="text-sm">${data.message.text}</p>
                                    <p class="text-xs mt-1 opacity-75">${data.message.time}</p>
                                </div>
                            </div>`;
                        messageContainer.insertAdjacentHTML('beforeend', messageHTML);
                        messageContainer.scrollTop = messageContainer.scrollHeight;
                        messageForm.reset();
                    } else {
                        alert("Error: " + data.message);
                    }
                });
            });
        });
    </script>
</body>
</html>
