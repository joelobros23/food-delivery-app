<?php
// app_config.php
// Central configuration file for the Foodie application.
// This is the ONLY file you need to edit for site-wide settings.

// --- Site Settings ---
define('SITE_NAME', 'Foodie');

// --- Database Credentials ---
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'food_delivery_db');

// --- API Keys ---
define('GEMINI_API_KEY', 'AIzaSyDaNS63l9P-ASSZ3ky0oqBVAo0KvaqWlyI');

// Enable error reporting for debugging during development
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
