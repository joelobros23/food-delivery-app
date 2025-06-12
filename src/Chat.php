<?php
namespace YourApp;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

/**
 * Chat is our main WebSocket application class.
 * It now handles structured JSON data for messages and saves them to the database.
 */
class Chat implements MessageComponentInterface {
    protected $clients;
    private $db;

    /**
     * Constructor
     * Initializes client storage and establishes a database connection.
     */
    public function __construct() {
        $this->clients = new \SplObjectStorage;

        // The server runner script that starts this chat is in the project root.
        // Therefore, we need to include app_config.php from one directory up from this file's location.
        require_once dirname(__DIR__) . '/app_config.php';

        // Establish a persistent database connection for the life of the server
        $this->db = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($this->db->connect_error) {
            echo "Database connection failed: " . $this->db->connect_error . "\n";
            // In a production environment, you might want to stop the server if the DB is down.
        } else {
            echo "Database connected successfully.\n";
        }

        echo "Chat server started and waiting for connections...\n";
    }

    /**
     * onOpen is called when a new client has connected.
     *
     * @param ConnectionInterface $conn The new connection object
     */
    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection opened! ({$conn->resourceId})\n";

        // To build a true private messaging system, you would need to map this
        // connection to a user ID. This is typically done by passing a user's
        // session ID or an auth token as a query parameter in the WebSocket URL,
        // e.g., ws://localhost:8080?userId=123
        // You would then store it like: $conn->userId = 123;
    }

    /**
     * onMessage is called when a message is received from a client.
     * It saves the message to the database and then broadcasts it to other clients.
     *
     * @param ConnectionInterface $from The connection from which the message was sent
     * @param string $msg The JSON message received
     */
    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);

        // Validate that we have all the required data from the client
        if (!$data || !isset($data['message'], $data['order_id'], $data['sender_id'], $data['receiver_id'])) {
            echo "Received invalid or incomplete message from {$from->resourceId}: $msg\n";
            return;
        }

        // --- 1. Save the message to the database ---
        $this->saveMessage($data);

        // --- 2. Prepare the message for broadcast ---
        // Add a server-side timestamp to ensure all clients are in sync
        $data['sent_at'] = date('c');

        echo sprintf(
            "Processing message from sender_id %d for order_id %s\n",
            $data['sender_id'],
            $data['order_id']
        );

        $broadcastMsg = json_encode($data);

        // --- 3. Broadcast the message ---
        // The front-end JS is already filtering messages, so we can broadcast to all.
        // For a more efficient system with many users, you would look up the specific
        // connection for the receiver_id and send only to them.
        foreach ($this->clients as $client) {
            // We are sending to all clients, including the sender,
            // to rely on the frontend for display logic.
             $client->send($broadcastMsg);
        }
    }

    /**
     * onClose is called when a client has disconnected.
     *
     * @param ConnectionInterface $conn The connection that has disconnected
     */
    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    /**
     * onError is called when an error occurs.
     *
     * @param ConnectionInterface $conn The connection that experienced the error
     * @param \Exception $e The exception that was thrown
     */
    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred on connection {$conn->resourceId}: {$e->getMessage()}\n";
        $conn->close();
    }
    
    /**
     * Saves a message to the database using a prepared statement.
     *
     * @param array $data The associative array of message data from the client.
     */
    protected function saveMessage(array $data) {
        // Check for a valid database connection
        if (!$this->db || $this->db->connect_error) {
            echo "Cannot save message, database connection is not available.\n";
            return;
        }

        $message = $data['message'];
        $order_id = (int)$data['order_id'];
        $sender_id = (int)$data['sender_id'];
        $receiver_id = (int)$data['receiver_id'];

        $sql = "INSERT INTO messages (order_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)";

        // Use a prepared statement to prevent SQL injection
        if ($stmt = $this->db->prepare($sql)) {
            $stmt->bind_param("iiis", $order_id, $sender_id, $receiver_id, $message);
            
            if ($stmt->execute()) {
                echo "Message successfully saved to DB for order_id: $order_id\n";
            } else {
                echo "DB execute error: " . $stmt->error . "\n";
            }
            $stmt->close();
        } else {
            echo "DB prepare error: " . $this->db->error . "\n";
        }
    }
}
