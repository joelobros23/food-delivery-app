<?php
// customer_dashboard_settings/geocode.php
// This script acts as a proxy to bypass browser CORS restrictions when calling the Nominatim API.

header('Content-Type: application/json');

if (!isset($_GET['address']) || empty(trim($_GET['address']))) {
    echo json_encode([]);
    exit;
}

$address = trim($_GET['address']);
$url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($address) . "&limit=1";

// Nominatim requires a valid User-Agent header in requests.
// We'll set one here using cURL.
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// Set a custom User-Agent. Replace 'FoodieApp/1.0' with your app's name and version.
curl_setopt($ch, CURLOPT_USERAGENT, 'FoodieApp/1.0 (contact@example.com)'); 

$response = curl_exec($ch);
curl_close($ch);

// Pass the response from Nominatim directly back to the client.
echo $response;
?>
