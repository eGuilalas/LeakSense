<?php
// Ensure the session is started and user is authenticated
include '../../config/auth.php'; // Authenticate user
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Threshold Management</title>
    <!-- Link to external CSS -->
    <link rel="stylesheet" href="../admin/dashboard.css">
</head>
<body>

    <!-- Dashboard Layout -->
    <div class="dashboard">
        <div class="sidebar" id="sidebar">
            <!-- Hamburger Menu inside the sidebar -->
            <button class="hamburger" id="hamburger" onclick="toggleSidebar()">&#9776;</button>

            <h2>Monitoring</h2>
            <a href="../admin/admin_dashboard.php"><span class="icon">📊</span>Dashboard</a>
            <a href="../admin/esp32_1.php"><span class="icon">💽</span>ESP32 - 1</a>
            <a href="../admin/esp32_2.php"><span class="icon">💽</span>ESP32 - 2</a>
            <a href="../admin/reports.php"><span class="icon">📅</span>Reports</a>

            <div class="menu-section">
                <h2>Settings</h2>
                <a href="manage_users.php"><span class="icon">👥</span>Manage Users</a>
                <a href="threshold_management.php" class="active"><span class="icon">⚙️</span>Threshold</a>
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

        <!-- Main Content Area -->
        <div class="main-content" id="main-content">
            <h1>Threshold Management</h1>
            <div class="container thresholds-container">
                <form id="threshold-form">
                    <div id="threshold-list">
                        <!-- Threshold settings will be loaded here dynamically -->
                    </div>
                    <button type="submit">Save Changes</button>
                </form>
                <p id="response-message"></p>
            </div>
        </div>
    </div>

    <!-- Link to external JS -->
    <script src="threshold_management.js"></script>
</body>
</html>
