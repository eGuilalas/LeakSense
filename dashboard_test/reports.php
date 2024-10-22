<?php
session_start();
require '../db_connection.php'; // Include the database connection file

// Initialize variables
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';
$start_time = isset($_POST['start_time']) ? $_POST['start_time'] : '00:00:00';
$end_time = isset($_POST['end_time']) ? $_POST['end_time'] : '23:59:59';
$readings = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Combine date and time for SQL query
    $start_datetime = $start_date . ' ' . $start_time;
    $end_datetime = $end_date . ' ' . $end_time;

    // Fetch readings between the selected date and time range
    $stmt = $pdo->prepare("SELECT * FROM gas_readings WHERE timestamp BETWEEN :start_datetime AND :end_datetime ORDER BY timestamp ASC");
    $stmt->execute([
        'start_datetime' => $start_datetime,
        'end_datetime' => $end_datetime
    ]);
    $readings = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LeakSense Gas Readings Report</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
        }

        /* Dashboard Layout */
        .dashboard {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* Sidebar styling */
        .sidebar {
            width: 250px;
            background-color: #1e1e2f;
            color: white;
            display: flex;
            flex-direction: column;
            padding-top: 20px;
            transition: width 0.3s;
        }

        .sidebar.collapsed {
            width: 60px;
        }

        /* Hamburger Menu inside sidebar */
        .hamburger {
            font-size: 24px;
            background-color: transparent;
            border: none;
            color: white;
            cursor: pointer;
            margin-left: 20px;
            margin-bottom: 20px;
            text-align: left;
        }

        .sidebar.collapsed .hamburger {
            margin-left: 10px;
        }

        .sidebar h2 {
            color: #b2b3bf;
            font-size: 16px;
            text-transform: uppercase;
            margin-left: 20px;
            transition: opacity 0.3s;
        }

        .sidebar.collapsed h2 {
            opacity: 0;
        }

        .sidebar a {
            padding: 15px 20px;
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #b2b3bf;
            font-size: 16px;
            transition: background 0.3s, padding-left 0.3s;
        }

        .sidebar a .icon {
            margin-right: 10px;
            font-size: 18px;
        }

        .sidebar.collapsed a {
            padding-left: 10px;
            font-size: 0;
        }

        .sidebar.collapsed a .icon {
            margin-right: 0;
            font-size: 24px;
        }

        .sidebar a:hover {
            background-color: #35354e;
        }

        .menu-section {
            margin-top: 20px;
        }

        .main-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            transition: margin-left 0.3s;
        }

        .sidebar.collapsed ~ .main-content {
            margin-left: 60px;
        }

        h1 {
            text-align: center;
            color: #4a90e2;
        }

        h2 {
            color: #333;
            margin-bottom: 10px;
        }

        .container {
            max-width: 1000px;
            margin: auto;
            padding: 20px;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .table-container {
            margin: 20px 0;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #e0e0e0;
            padding: 10px;
            text-align: center;
        }

        th {
            background-color: #f0f0f0;
        }

        .print-button {
            background-color: #4a90e2;
            color: white;
            padding: 10px 20px;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
            display: inline-block;
        }

        .print-button:hover {
            background-color: #357ABD;
        }

        /* Form styling */
        form {
            margin-bottom: 20px;
            text-align: center;
        }

        label {
            font-size: 16px;
            margin-right: 10px;
        }

        input[type="date"], input[type="time"] {
            padding: 10px;
            font-size: 16px;
            border-radius: 5px;
            border: 1px solid #ccc;
            margin-right: 10px;
        }
    </style>
</head>
<body>

    <!-- Dashboard Layout -->
    <div class="dashboard">
        <div class="sidebar" id="sidebar">
            <!-- Hamburger Menu inside the sidebar -->
            <button class="hamburger" id="hamburger" onclick="toggleSidebar()">&#9776;</button>

            <h2>Monitoring</h2>
            <a href="#"><span class="icon">📊</span>Dashboard</a>
            <a href="#"><span class="icon">📅</span>Reports</a>

            <div class="menu-section">
                <h2>Admin</h2>
                <a href="#"><span class="icon">👤</span><span style="color: red;">Admin</span></a>
            </div>

            <!-- Logout Section -->
            <div class="menu-section">
                <h2>Logout</h2>
                <a href="../logout.php"><span class="icon">🚪</span>Logout</a>
            </div>
        </div>

        <div class="main-content" id="main-content">
            <h1>LeakSense Gas Readings Report</h1>

            <div class="container">
                <!-- Date and Time selection form -->
                <form method="POST" action="reports.php">
                    <label for="start_date">Start Date:</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" required>
                    
                    <label for="start_time">Start Time:</label>
                    <input type="time" id="start_time" name="start_time" value="<?php echo $start_time; ?>" required>
                    
                    <label for="end_date">End Date:</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>" required>
                    
                    <label for="end_time">End Time:</label>
                    <input type="time" id="end_time" name="end_time" value="<?php echo $end_time; ?>" required>
                    
                    <button type="submit" class="print-button">Generate Report</button>
                </form>

                <?php if (!empty($readings)): ?>
                    <h2>Gas Readings from <?php echo $start_date . ' ' . $start_time; ?> to <?php echo $end_date . ' ' . $end_time; ?></h2>
                    <div class="table-container">
                        <table>
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
                                <?php foreach ($readings as $reading): ?>
                                    <tr>
                                        <td><?php echo $reading['device_id']; ?></td>
                                        <td><?php echo $reading['gas_level']; ?></td>
                                        <td><?php echo $reading['smoke_status'] == 1 ? 'Detected' : 'Not Detected'; ?></td>
                                        <td><?php echo $reading['co_status'] == 1 ? 'Detected' : 'Not Detected'; ?></td>
                                        <td><?php echo $reading['lpg_status'] == 1 ? 'Detected' : 'Not Detected'; ?></td>
                                        <td><?php echo $reading['timestamp']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Print button to print the report -->
                    <button class="print-button" onclick="window.print()">Print Report</button>
                <?php else: ?>
                    <?php if ($_SERVER['REQUEST_METHOD'] == 'POST'): ?>
                        <p>No readings found for the selected date and time range.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Sidebar Toggle Functionality
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
            document.body.classList.toggle('collapsed');
        }
    </script>

</body>
</html>
