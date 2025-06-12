<?php
// websocket_server.php

// This is the main server runner script.
// You will run this file from your command line to start the WebSocket server.
// Example: > php websocket_server.php

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
// We no longer need the 'use YourApp\Chat;' statement here.

// Autoload all the dependencies from Composer
require dirname(__FILE__) . '/vendor/autoload.php';

// Explicitly include the Chat class file. This is the most reliable way
// to ensure PHP can find the class if the autoloader is not configured correctly.
require dirname(__FILE__) . '/src/Chat.php';

// The port you want your WebSocket server to listen on
$port = 8080;

echo "Starting chat server on port {$port}...\n";

// Create and configure the server
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            // Instantiate the Chat class using its fully qualified namespace.
            // The leading backslash is important.
            new \YourApp\Chat()
        )
    ),
    $port
);

// Run the server
$server->run();
