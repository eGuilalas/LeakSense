<?php
// Ensure the session is started and user is logged in
session_start();

// Check if the user is logged in, and retrieve the username and role from the session
if (!isset($_SESSION['loggedin'])) {
    header('Location: ../login.php');
    exit();
}

$username = $_SESSION['username']; // Get the logged-in username
$role = $_SESSION['role']; // Get the role from the session
?>

