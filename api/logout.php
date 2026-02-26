<?php
// FILENAME: employee/api/logout.php

// Start the session to access session variables
session_start();

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// *** FIX: Corrected the redirect path ***
// Redirect the user back to the login page, which is one directory up
header('Location: ../login.html');
exit;
?>
