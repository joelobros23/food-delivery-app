<?php
// customer_dashboard_settings/autosuggest.php

// No need to start a session here as this is just a data endpoint
header('Content-Type: application/json');

// Check if a search term is provided
if (!isset($_GET['term']) || empty(trim($_GET['term']))) {
    echo json_encode([]);
    exit;
}

// Include config file to connect to the database
require_once "../db_connection/config.php";

$search_term = trim($_GET['term']);
$suggestions = [];

if (!empty($search_term)) {
    $param_term = "%" . $search_term . "%";

    // Prepare a unified query to get suggestions from both restaurants and menu items
    $sql = "
        (SELECT name, 'restaurant' as type FROM restaurants WHERE name LIKE ? ORDER BY name LIMIT 3)
        UNION
        (SELECT name, 'dish' as type FROM menu_items WHERE name LIKE ? ORDER BY name LIMIT 4)
    ";

    if($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $param_term, $param_term);
        
        if(mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while($row = mysqli_fetch_assoc($result)) {
                $suggestions[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// Close the database connection
mysqli_close($link);

// Return the suggestions as a JSON object
echo json_encode($suggestions);
?>
