<?php
// admin_dashboard.php

// Ensure the session is started and user is logged in
session_start();

// Check if the user is logged in, and retrieve the username and role from the session
if (!isset($_SESSION['loggedin'])) {
    // Redirect to login if not logged in
    header('Location: ../../login.php'); // Adjust path as needed
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
    <title>LeakSense Admin Dashboard</title>
    <!-- Link to external CSS -->
    <link rel="stylesheet" href="dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <!-- Dashboard Layout -->
    <div class="dashboard">
        <div class="sidebar" id="sidebar">
            <!-- Hamburger Menu inside the sidebar -->
            <button class="hamburger" id="hamburger" onclick="toggleSidebar()">&#9776;</button>

            <h2>Monitoring</h2>
            <a href="admin_dashboard.php"><span class="icon">📊</span>Dashboard</a>
            <a href="esp32_1.php"><span class="icon">💽</span>ESP32 - 1</a>
            <a href="esp32_2.php"><span class="icon">💽</span>ESP32 - 2</a>
            <a href="reports.php"><span class="icon">📅</span>Reports</a>

            <div class="menu-section">
                <h2>Settings</h2>
                <a href=""><span class="icon">👥</span>Manage Users</a>
                <a href="threshold_management.php"><span class="icon">⚙️</span>Threshold</a>
                <a href="recipient.php"><span class="icon">⚙️</span>Recipient Setup</a>
            </div>

            <div class="menu-section">
                <h2>Welcome</h2>
                <a href="#"><span class="icon">👤</span><span style="color: red;"><?php echo htmlspecialchars($username); ?></span> - <?php echo htmlspecialchars($role); ?></a>
            </div>

            <!-- Logout Section -->
            <div class="menu-section">
                <h2>Logout</h2>
                <a href="../../logout.php"><span class="icon">🚪</span>Logout</a>
            </div>
        </div>

        <div class="main-content" id="main-content">
            <h1>Live Gas Readings Graph</h1>
            <div class="container">
                <!-- Device status section for GS1 and GS2 -->
                <div class="status-container">
                    <div class="status">Server Status: <i id="server-status" class="offline">Offline</i></div>
                    <div class="status">GS1 Status: <i id="gs1-status" class="offline">Offline</i></div>
                    <div class="status">GS2 Status: <i id="gs2-status" class="offline">Offline</i></div>
                </div>

                <h2>Latest Gas Readings</h2>
                <!-- Separate containers for GS1 and GS2 latest readings -->
                <div id="latest-readings-gs1"></div>
                <div id="latest-readings-gs2"></div>

                <canvas id="gasLevelChart" width="600" height="300"></canvas>
                
                <h2>Live Readings Table</h2>
                <div class="table-container" id="tableContainer">
                    <table id="readingsTable">
                        <thead>
                            <tr>
                                <th>Device ID</th>
                                <th>Gas Level (ppm)</th>
                                <th>Smoke Status</th>
                                <th>CO Status</th>
                                <th>LPG Status</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Rows will be populated dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Link to external JS -->
    <script src="dashboard.js"></script>

</body>
</html>
