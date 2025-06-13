<?php
// generate_description.php
session_start();
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

// Security: Only allow logged-in store owners to use this feature.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'store') {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit;
}

if (!isset($_POST['item_name']) || empty(trim($_POST['item_name']))) {
    $response['message'] = 'Item name is required.';
    echo json_encode($response);
    exit;
}

$item_name = trim($_POST['item_name']);
$existing_description = isset($_POST['existing_description']) ? trim($_POST['existing_description']) : '';
$api_key = 'AIzaSyAhz-vMTfd4NjDreB-qz0mjGVBVYvNon00'; 
$api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $api_key;

// --- CORRECTED: More direct and explicit prompts for the AI ---
$prompt = '';
if (!empty($existing_description)) {
    // Prompt to enhance existing text
    $prompt = "Rewrite and enhance this food description for a menu item named \"$item_name\": \"$existing_description\". Make the new version more appetizing and engaging for a Philippine food delivery app. The output must be a single, natural-sounding description, 2-3 sentences long. Do not add any headings, options, or any text other than the description itself.";
} else {
    // Prompt to create a new, longer description
    $prompt = "Generate a single, natural-sounding, and appetizing food description for a menu item called \"$item_name\". The description should be suitable for a food delivery app menu in the Philippines. It must be 2-3 sentences long. Do not provide options, headings, or any text other than the description itself.";
}

$payload = json_encode([
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt]
            ]
        ]
    ]
]);

// Use cURL to make the API request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$api_response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 200) {
    $result = json_decode($api_response, true);
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        // Clean up the response, sometimes the AI includes quotes or markdown
        $description = $result['candidates'][0]['content']['parts'][0]['text'];
        $description = trim(str_replace(['"', '*'], '', $description));
        $response = ['status' => 'success', 'description' => $description];
    } else {
        $response['message'] = 'Could not parse the AI response.';
        $response['details'] = $result; 
    }
} else {
    $response['message'] = "API request failed with status code: $http_code";
    $response['details'] = json_decode($api_response, true);
}

echo json_encode($response);
?>
