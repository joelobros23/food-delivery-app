<?php
// api/autosuggest.php
session_start();
header('Content-Type: application/json');

// --- FIX: Security check now includes address ---
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'customer' || !isset($_GET['term']) || !isset($_GET['address'])) {
    echo json_encode([]);
    exit;
}

require_once "../app_config.php";

$term = trim($_GET['term']);
$address = trim($_GET['address']);
$results = [];

if (empty($term) || empty($address)) {
    echo json_encode([]);
    exit;
}

$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($link === false) { 
    echo json_encode([]);
    exit;
}

$param_term = "%" . $term . "%";

// --- FIX: Search for restaurants now filters by address ---
$sql_restaurants = "SELECT name FROM restaurants WHERE address = ? AND name LIKE ?";
if ($stmt_rest = mysqli_prepare($link, $sql_restaurants)) {
    mysqli_stmt_bind_param($stmt_rest, "ss", $address, $param_term);
    mysqli_stmt_execute($stmt_rest);
    $result_rest = mysqli_stmt_get_result($stmt_rest);
    while($row = mysqli_fetch_assoc($result_rest)){
        $results[] = ['name' => $row['name'], 'type' => 'restaurant'];
    }
    mysqli_stmt_close($stmt_rest);
}

// --- FIX: Search for menu items now filters by address ---
$sql_items = "SELECT mi.name FROM menu_items mi JOIN restaurants r ON mi.restaurant_id = r.id WHERE r.address = ? AND mi.name LIKE ?";
if ($stmt_items = mysqli_prepare($link, $sql_items)) {
    mysqli_stmt_bind_param($stmt_items, "ss", $address, $param_term);
    mysqli_stmt_execute($stmt_items);
    $result_items = mysqli_stmt_get_result($stmt_items);
    while($row = mysqli_fetch_assoc($result_items)){
        $results[] = ['name' => $row['name'], 'type' => 'item'];
    }
    mysqli_stmt_close($stmt_items);
}

mysqli_close($link);

// To avoid too many suggestions, you can limit the results
$limited_results = array_slice($results, 0, 10);

echo json_encode($limited_results);
?>
