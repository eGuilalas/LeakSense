<?php
// esp32_1.php

// Ensure the session is started and user is logged in
session_start();

// Check if the user is logged in, and retrieve the username and role from the session
if (!isset($_SESSION['loggedin'])) {
    header('Location: ../../login.php');
    exit();
}

$username = $_SESSION['username']; // Get the logged-in username
$role = $_SESSION['role']; // Get the role from the session
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ESP32 - 1 Readings</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>

    <div class="dashboard">
        <div class="sidebar">
            <button class="hamburger" id="hamburger" onclick="toggleSidebar()">&#9776;</button>
            <h2>Monitoring</h2>
            <a href="admin_dashboard.php"><span class="icon">💽</span>Dashboard</a>
            <a href="#"><span class="icon">💽</span>ESP32 - 1</a>
            <a href="../../logout.php"><span class="icon">🚪</span>Logout</a>
        </div>

        <div class="main-content">
            <h1>Gas Readings - ESP32 GS1</h1>

            <div class="table-container">
                <table id="readingsTableGS1">
                    <thead>
                        <tr>
                            <th>Gas Level (ppm)</th>
                            <th>Smoke Status</th>
                            <th>CO Status</th>
                            <th>LPG Status</th>
                            <th>Timestamp</th>
                            <th>Alert Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Rows will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="esp32_1.js"></script>

</body>
</html>
