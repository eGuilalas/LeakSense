<?php
// admin_dashboard.php

session_start();

// Check if the user is logged in and retrieve session data
if (!isset($_SESSION['loggedin'])) {
    header('Location: ../../login.php');
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];
$expiration_date = $_SESSION['expiration_date'] ?? 'N/A'; // Set expiration date
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LeakSense Admin Dashboard</title>
    <link rel="stylesheet" href="dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <div class="dashboard">
        <div class="sidebar" id="sidebar">
            <button class="hamburger" id="hamburger" onclick="toggleSidebar()">&#9776;</button>

            <h2>Monitoring</h2>
            <a href="#"><span class="icon">📊</span>Dashboard</a>
            <a href="esp32_1.php"><span class="icon">💽</span>ESP32 - 1</a>
            <a href="esp32_2.php"><span class="icon">💽</span>ESP32 - 2</a>
            <a href="reports.php"><span class="icon">📅</span>Reports</a>

            <div class="menu-section">
                <h2>Welcome</h2>
                <a href="#">
                    <span class="icon">👤</span>
                    <span style="color: red;"><?php echo htmlspecialchars($username); ?></span> - <?php echo htmlspecialchars($role); ?>
                </a>
                <a href="#">
                    <span style="font-size: 0.9em; color: gray; display: block; margin-top: 4px;">Expiration: <?php echo htmlspecialchars($expiration_date); ?></span>
                </a>
            </div>

            <div class="menu-section">
                <h2>Logout</h2>
                <a href="../../logout.php"><span class="icon">🚪</span>Logout</a>
            </div>
        </div>

        <div class="main-content" id="main-content">
            <h1>Live Gas Readings Dashboard</h1>

            <!-- Counters Section -->
            <div class="counters-container">
                <div class="counter pending">
                    <h3>Pending</h3>
                    <p id="pending-count">0</p>
                </div>
                <div class="counter acknowledged">
                    <h3>Acknowledged</h3>
                    <p id="acknowledged-count">0</p>
                </div>
                <div class="counter false-alarm">
                    <h3>False Alarm</h3>
                    <p id="false-alarm-count">0</p>
                </div>
                <div class="counter duplicate">
                    <h3>Duplicate ID</h3>
                    <p id="duplicate-count">0</p>
                </div>
            </div>

            <div class="container">
                <!-- Device status section for GS1 and GS2 -->
                <div class="status-container">
                    <div class="status">Server Status: <i id="server-status" class="offline">Offline</i></div>
                    <div class="status">GS1 Status: <i id="gs1-status" class="offline">Offline</i></div>
                    <div class="status">GS2 Status: <i id="gs2-status" class="offline">Offline</i></div>
                </div>

                <h2>Latest Gas Readings</h2>
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

    <script src="dashboard.js"></script>

</body>
</html>
