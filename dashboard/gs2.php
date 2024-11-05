<?php
// PHP backend logic (example data for demonstration purposes)
$data = [
    "gas_readings" => [
        ["Gas Level" => 45, "Gas Detected" => "Smoke", "Timestamp" => "2024-11-01 10:00", "Alert Status" => "Pending", "Actioned By" => "User1", "Comment" => "High reading", "Response Time" => "5 mins"],
        ["Gas Level" => 38, "Gas Detected" => "LPG", "Timestamp" => "2024-11-02 11:30", "Alert Status" => "Acknowledged", "Actioned By" => "User2", "Comment" => "Monitored", "Response Time" => "10 mins"],
        ["Gas Level" => 52, "Gas Detected" => "CO", "Timestamp" => "2024-11-03 12:45", "Alert Status" => "False Alarm", "Actioned By" => "User3", "Comment" => "Sensor error", "Response Time" => "8 mins"],
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ESP32-GasSensor 2 Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background-color: #1E1E2D; color: #fff; display: flex; }
        .dashboard-container { display: flex; height: 100vh; width: 100%; }
        .sidebar { background-color: #2B2D42; width: 220px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between; }
        .sidebar h2 { color: #8D99AE; font-size: 1.5em; margin-bottom: 20px; }
        .sidebar ul { list-style: none; padding-left: 0; }
        .sidebar li { margin-bottom: 15px; }
        .sidebar a {
            text-decoration: none;
            color: #D6D8E7;
            font-size: 1em;
            display: block;
            padding: 10px;
            border-radius: 5px;
            transition: background-color 0.2s;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: #F72585;
            color: #fff;
        }
        .bottom-section { border-top: 1px solid #444; padding-top: 20px; color: #D6D8E7; text-align: left; }
        .bottom-section h3, .bottom-section h5 { color: #8D99AE; margin-bottom: 10px; }
        .bottom-section a { color: #F72585; text-decoration: none; font-weight: bold; }

        .main-dashboard { flex: 1; padding: 20px; overflow-y: auto; }
        .dashboard-header { display: flex; gap: 20px; margin-bottom: 20px; }
        .header-box { background: #3A3A5A; padding: 20px; border-radius: 10px; flex: 1; }
        .header-box h3 { color: #8D99AE; }
        
        .filter-section { margin: 20px 0; color: #8D99AE; }
        .filter-section select { padding: 5px; background-color: #3A3A5A; color: #D6D8E7; border: none; border-radius: 5px; }

        .table-container { background: #3A3A5A; padding: 20px; border-radius: 10px; }
        table { width: 100%; color: #D6D8E7; margin-top: 10px; border-collapse: collapse; }
        table th, table td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        .status-pending { color: #36A2EB; font-weight: bold; }
        .status-acknowledged { color: #FF6384; font-weight: bold; }
        .status-false-alarm { color: #FFCE56; font-weight: bold; }
        .action-link { color: #F72585; cursor: pointer; text-decoration: underline; }
        .action-link:hover { color: #FF6384; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div>
                <h2>ESP32-GasSensor 2 Dashboard</h2>
                <nav>
                    <ul>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="gs1.php">ESP32-GasSensor1</a></li>
                        <li><a href="gs2.php" class="active">ESP32-GasSensor2</a></li>
                        <li><a href="Reports.php">Reports</a></li>
                        <li><a href="manage_user.php">Manage User</a></li>
                        <li><a href="Threshold.php">Threshold Setup</a></li>
                    </ul>
                </nav>
            </div>
            <div class="bottom-section">
                <h3>USERNAME</h3>
                <h3>Role</h3>
            </div>
            <div class="bottom-section">
                <h3>Language</h3>
                <h5>ENG - FR</h5>
            </div>
            <div class="bottom-section">
                <a href="login.php">Logout</a>
            </div>
        </aside>
        
        <!-- Main Dashboard -->
        <main class="main-dashboard">
            <div class="dashboard-header">
                <div class="header-box">
                    <h3>Pending</h3>
                    <p>1</p>
                </div>
                <div class="header-box">
                    <h3>Acknowledge</h3>
                    <p>2</p>
                </div>
                <div class="header-box">
                    <h3>False Alarm</h3>
                    <p>3</p>
                </div>
            </div>

            <!-- Filter Section with Dropdown -->
            <div class="filter-section">
                <label for="statusFilter">Filter by Status:</label>
                <select id="statusFilter" onchange="filterStatus(this.value)">
                    <option value="">All</option>
                    <option value="Pending">Pending</option>
                    <option value="Acknowledged">Acknowledged</option>
                    <option value="False Alarm">False Alarm</option>
                </select>
            </div>

            <!-- Gas Readings Table -->
            <div class="table-container">
                <h3>Gas Readings</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Gas Level (ppm)</th>
                            <th>Gas Detected</th>
                            <th>Timestamp</th>
                            <th>Alert Status</th>
                            <th>Actioned By</th>
                            <th>Comment</th>
                            <th>Response Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="gasReadingsTable">
                        <?php foreach ($data["gas_readings"] as $reading): ?>
                            <tr data-status="<?php echo strtolower(str_replace(' ', '-', $reading["Alert Status"])); ?>">
                                <td><?php echo $reading["Gas Level"]; ?></td>
                                <td><?php echo $reading["Gas Detected"]; ?></td>
                                <td><?php echo $reading["Timestamp"]; ?></td>
                                <td class="<?php echo 'status-' . strtolower(str_replace(' ', '-', $reading["Alert Status"])); ?>">
                                    <?php echo $reading["Alert Status"]; ?>
                                </td>
                                <td><?php echo $reading["Actioned By"]; ?></td>
                                <td><?php echo $reading["Comment"]; ?></td>
                                <td><?php echo $reading["Response Time"]; ?></td>
                                <td>
                                    <span class="action-link">Acknowledge</span> | 
                                    <span class="action-link">False Alarm</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        function filterStatus(status) {
            const rows = document.querySelectorAll("#gasReadingsTable tr");
            rows.forEach(row => {
                if (status === "" || row.getAttribute("data-status") === status.toLowerCase().replace(" ", "-")) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }
    </script>
</body>
</html>
