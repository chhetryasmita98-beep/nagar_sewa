<?php

session_start();

// Clear all admin session data
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to admin login page
header("Location: login.php");
exit;

?>