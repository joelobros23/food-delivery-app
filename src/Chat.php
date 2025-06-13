<?php
namespace YourApp;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface {
    protected $clients;
    private $db;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        require_once dirname(__DIR__) . '/app_config.php';
        $this->db = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->db->connect_error) {
            echo "Database connection failed: " . $this->db->connect_error . "\n";
        } else {
            echo "Database connected successfully.\n";
        }
        echo "Chat & Notification server started...\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection opened! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);

        if (isset($data['type'])) {
            switch($data['type']) {
                case 'new_order_placed':
                    $this->handleNewOrder($data);
                    break;
                case 'order_status_update':
                    $this->handleOrderStatusUpdate($data);
                    break;
                default:
                    if (isset($data['message'])) {
                        $this->handleChatMessage($data);
                    } else {
                         echo "Received unroutable message: $msg\n";
                    }
            }
        } elseif (isset($data['message'])) {
             $this->handleChatMessage($data);
        }
    }
    
    // --- Notification Handlers ---

    protected function handleNewOrder(array $data) {
        $storeId = (int)$data['store_id'];
        $orderId = (int)$data['order_id'];
        
        $newPendingCount = 0;
        $sql = "SELECT COUNT(id) FROM orders WHERE restaurant_id = ? AND status = 'Pending'";
        if ($stmt = $this->db->prepare($sql)) {
            $stmt->bind_param("i", $storeId);
            if ($stmt->execute()) {
                $stmt->bind_result($count);
                if ($stmt->fetch()) { $newPendingCount = $count; }
            }
            $stmt->close();
        }

        $this->broadcast(json_encode(['type' => 'new_order_notification', 'for_store_id' => $storeId, 'new_count' => $newPendingCount, 'order_id' => $orderId]));
    }

    protected function handleChatMessage(array $data) {
        $receiverId = (int)$data['receiver_id'];
        $orderId = (int)$data['order_id'];
        
        // 1. Save the chat message
        $this->saveChatMessage($data);

        // 2. Broadcast the full chat message for the chat window UI
        $data['sent_at'] = date('c');
        $this->broadcast(json_encode($data));
        
        // 3. Get the new unread message count for the receiver
        $newMessageCount = 0;
        $sql = "SELECT COUNT(id) FROM messages WHERE receiver_id = ? AND is_read = 0";
        if ($stmt = $this->db->prepare($sql)) {
            $stmt->bind_param("i", $receiverId);
            if ($stmt->execute()) {
                $stmt->bind_result($count);
                if ($stmt->fetch()) { $newMessageCount = $count; }
            }
            $stmt->close();
        }

        // 4. Broadcast a separate notification for the sidebar indicator
        $this->broadcast(json_encode([
            'type' => 'new_message_notification',
            'for_receiver_id' => $receiverId,
            'new_count' => $newMessageCount,
            'order_id' => $orderId
        ]));
    }

    protected function handleOrderStatusUpdate(array $data) {
        $customerId = (int)$data['customer_id'];
        $orderId = (int)$data['order_id'];
        $newStatus = htmlspecialchars($data['new_status']);

        $this->broadcast(json_encode([
            'type' => 'order_update_notification',
            'for_customer_id' => $customerId,
            'order_id' => $orderId,
            'new_status' => $newStatus
        ]));
    }
    
    // --- Utility Functions ---

    protected function broadcast($msg) {
        foreach ($this->clients as $client) {
            $client->send($msg);
        }
    }
    
    protected function saveChatMessage(array $data) {
        $sql = "INSERT INTO messages (order_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)";
        if ($stmt = $this->db->prepare($sql)) {
            $stmt->bind_param("iiis", $data['order_id'], $data['sender_id'], $data['receiver_id'], $data['message']);
            $stmt->execute();
            $stmt->close();
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}
