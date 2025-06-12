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

        if (isset($data['type']) && $data['type'] === 'new_order_placed' && isset($data['store_id'])) {
            $this->handleNewOrder($data);
        } elseif (isset($data['message'], $data['order_id'], $data['sender_id'], $data['receiver_id'])) {
            $this->handleChatMessage($data);
        } else {
            echo "Received invalid or unroutable message from {$from->resourceId}: $msg\n";
        }
    }
    
    protected function handleNewOrder(array $data) {
        $storeId = (int)$data['store_id'];
        $orderId = (int)$data['order_id']; // The order ID from the purchase page
        echo "Received new order notification for store ID: {$storeId}\n";
        
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

        $notificationPayload = [
            'type' => 'new_order_notification',
            'for_store_id' => $storeId,
            'new_count' => $newPendingCount,
            'order_id' => $orderId // Include the order ID for the notification
        ];
        $this->broadcast(json_encode($notificationPayload));
    }

    protected function handleChatMessage(array $data) {
        $this->saveChatMessage($data);
        $data['sent_at'] = date('c');
        $this->broadcast(json_encode($data));
    }

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
