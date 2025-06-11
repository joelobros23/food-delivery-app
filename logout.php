<?php
// logout.php

// Initialize the session to access session variables
session_start();
 
// Unset all of the session variables.
$_SESSION = array();
 
// Destroy the session completely.
session_destroy();
 
// Redirect to the login page after logging out.
header("location: login.php");
exit;
?>
