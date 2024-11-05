<?php
// Example PHP data for demonstration (replace with database queries in production)
$data = [
    "report_data" => [
        ["ID" => 1, "Device ID" => "ESP32-GasSensor1", "Gas Level" => 45, "Gas Detected" => "Smoke", "Alert Status" => "Pending", "Acknowledged By" => "User1", "Response Time" => "5 mins", "Comments" => "Investigating", "Timestamp" => "2024-11-01 10:00"],
        ["ID" => 2, "Device ID" => "ESP32-GasSensor2", "Gas Level" => 38, "Gas Detected" => "LPG", "Alert Status" => "Acknowledged", "Acknowledged By" => "User2", "Response Time" => "10 mins", "Comments" => "Resolved", "Timestamp" => "2024-11-02 11:30"],
        ["ID" => 3, "Device ID" => "ESP32-GasSensor1", "Gas Level" => 52, "Gas Detected" => "CO", "Alert Status" => "False Alarm", "Acknowledged By" => "User3", "Response Time" => "8 mins", "Comments" => "Sensor issue", "Timestamp" => "2024-11-03 12:45"],
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Leaksense Dashboard</title>
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
        
        .main-dashboard { flex: 1; padding: 20px; overflow-y: auto; }
        .filter-section { background: #3A3A5A; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .filter-section h3 { color: #8D99AE; margin-bottom: 15px; }
        .filter-group { display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
        .filter-group label { color: #D6D8E7; }
        .filter-group select, .filter-group input {
            padding: 5px;
            background-color: #2B2D42;
            color: #D6D8E7;
            border: 1px solid #444;
            border-radius: 5px;
            outline: none;
        }
        .button-group { display: flex; gap: 10px; margin-top: 15px; }
        .button-group button {
            padding: 8px 12px;
            background-color: #F72585;
            border: none;
            border-radius: 5px;
            color: #fff;
            cursor: pointer;
        }
        .button-group button:hover { background-color: #FF4571; }

        .table-container { background: #3A3A5A; padding: 20px; border-radius: 10px; }
        table { width: 100%; color: #D6D8E7; margin-top: 10px; border-collapse: collapse; }
        table th, table td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        .status-pending { color: #FFA500; font-weight: bold; }
        .status-acknowledged { color: #36A2EB; font-weight: bold; }
        .status-false-alarm { color: #FF6384; font-weight: bold; }

        /* Bottom section styling */
        .bottom-section {
            border-top: 1px solid #444;
            padding-top: 20px;
            color: #D6D8E7;
            text-align: left;
        }
        .bottom-section h3, .bottom-section h5 { margin-bottom: 10px; color: #D6D8E7; }
        .bottom-section a { color: #F72585; text-decoration: none; font-weight: bold; display: inline-block; margin-top: 10px; }
        .bottom-section a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div>
                <h2>Leaksense Dashboard</h2>
                <nav>
                    <ul>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="gs1.php">ESP32-GasSensor1</a></li>
                        <li><a href="gs2.php">ESP32-GasSensor2</a></li>
                        <li><a href="Reports.php" class="active">Reports</a></li>
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

        <main class="main-dashboard">
            <!-- Filter Section -->
            <div class="filter-section">
                <h3>Filter Reports</h3>
                <div class="filter-group">
                    <label>Device ID:</label>
                    <select>
                        <option>All Devices</option>
                        <option>ESP32-GasSensor1</option>
                        <option>ESP32-GasSensor2</option>
                    </select>

                    <label>Gas Type:</label>
                    <select>
                        <option>All Types</option>
                        <option>Smoke</option>
                        <option>CO</option>
                        <option>LPG</option>
                    </select>

                    <label>Start Date:</label>
                    <input type="date">

                    <label>Start Time:</label>
                    <input type="time">

                    <label>End Date:</label>
                    <input type="date">

                    <label>End Time:</label>
                    <input type="time">

                    <label>Alert Status:</label>
                    <select>
                        <option>Any</option>
                        <option>Pending</option>
                        <option>Acknowledged</option>
                        <option>False Alarm</option>
                    </select>

                    <label>Acknowledged By:</label>
                    <select>
                        <option>Any User</option>
                        <option>User1</option>
                        <option>User2</option>
                        <option>User3</option>
                    </select>
                </div>
                <div class="button-group">
                    <button>Apply Filters</button>
                    <button>Reset Filters</button>
                    <button>Print Report</button>
                </div>
            </div>

            <!-- Report Table Section -->
            <div class="table-container">
                <h3>Report Table</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Device ID</th>
                            <th>Gas Level (ppm)</th>
                            <th>Gas Detected</th>
                            <th>Alert Status</th>
                            <th>Acknowledged By</th>
                            <th>Response Time</th>
                            <th>Comments</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data["report_data"] as $row): ?>
                            <tr>
                                <td><?php echo $row["ID"]; ?></td>
                                <td><?php echo $row["Device ID"]; ?></td>
                                <td><?php echo $row["Gas Level"]; ?></td>
                                <td><?php echo $row["Gas Detected"]; ?></td>
                                <td class="<?php echo strtolower(str_replace(' ', '-', 'status-' . $row["Alert Status"])); ?>">
                                    <?php echo $row["Alert Status"]; ?>
                                </td>
                                <td><?php echo $row["Acknowledged By"]; ?></td>
                                <td><?php echo $row["Response Time"]; ?></td>
                                <td><?php echo $row["Comments"]; ?></td>
                                <td><?php echo $row["Timestamp"]; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
