<?php
// db_connection/config.php - Database configuration

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- DATABASE CREDENTIALS ---
// Replace with your actual database details
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Your database username
define('DB_PASSWORD', ''); // Your database password
define('DB_NAME', 'food_delivery_db'); // Your database name

// --- ATTEMPT TO CONNECT TO MYSQL DATABASE ---
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// --- CHECK CONNECTION ---
if($link === false){
    // Die and show error if connection fails.
    // In a production environment, you would log this error and show a generic message.
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
?>
