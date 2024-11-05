<?php
// PHP backend logic (for example purposes, here we use static data)
$data = [
    "live_gas_table" => [
        ["DeviceID" => "ESP32-GasSensor1", "Gas Level" => 45, "Status" => 0, "Timestamp" => "2024-11-01 10:00"],
        ["DeviceID" => "ESP32-GasSensor2", "Gas Level" => 38, "Status" => 1, "Timestamp" => "2024-11-02 11:30"],
        ["DeviceID" => "ESP32-GasSensor1", "Gas Level" => 52, "Status" => 0, "Timestamp" => "2024-11-03 12:45"],
    ],
    "time_labels" => ["00:00:03", "00:00:06", "00:00:09", "00:00:12", "00:00:15", "00:00:18"], 
    "sensor1_readings" => [30, 32, 29, 28, 31, 33], 
    "sensor2_readings" => [28, 30, 27, 25, 26, 29], 
    "counter" => [10, 5, 3] 
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaksense Dashboard</title>
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
        .dashboard-header { display: flex; gap: 20px; margin-bottom: 20px; }
        .header-box { background: #3A3A5A; padding: 20px; border-radius: 10px; flex: 1; }
        .header-box h3 { color: #8D99AE; }
        .charts-container { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .chart, .table-container { background: #3A3A5A; padding: 20px; border-radius: 10px; }
        table { width: 100%; color: #D6D8E7; margin-top: 10px; border-collapse: collapse; }
        table th, table td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        .status-detected { color: red; font-weight: bold; }
        .status-not-detected { color: green; font-weight: bold; }

        /* Bottom section styling */
        .bottom-section {
            border-top: 1px solid #444;
            padding-top: 20px;
            color: #D6D8E7;
            text-align: left;
        }
        .bottom-section h1, .bottom-section h3 { margin-bottom: 10px; color: #D6D8E7; }
        .bottom-section h5 { display: inline; color: #8D99AE; font-weight: normal; margin-right: 15px; }
        .bottom-section a { color: #F72585; text-decoration: none; font-weight: bold; display: inline-block; margin-top: 10px; }
        .bottom-section a:hover { text-decoration: underline; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div>
                <h2>Leaksense Dashboard</h2>
                <nav>
                    <ul>
                        <li><a href="#" class="active">Dashboard</a></li>
                        <li><a href="gs1.php">ESP32-GasSensor 1</a></li>
                        <li><a href="gs2.php">ESP32-GasSensor 2</a></li>
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

        <main class="main-dashboard">
        <div class="dashboard-header">
                <div class="header-box">
                    <h3>Server Status</h3>
                    <p>Online</p>
                </div>
                <div class="header-box">
                    <h3>ESP32-GasSensor 1 Status</h3>
                    <p>Online</p>
                </div>
                <div class="header-box">
                    <h3>ESP32-GasSensor 2 Status</h3>
                    <p>Online</p>
                </div>
            </div>
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

            <div class="charts-container">
                <!-- Line chart for live gas readings -->
                <div class="chart">
                    <canvas id="livegasChart"></canvas>
                </div>

                <!-- Donut chart for status -->
                <div class="chart">
                    <canvas id="statusChart"></canvas>
                </div>

                <!-- Live Gas Table -->
                <div class="table-container">
                    <h3>Live Gas Table</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>DeviceID</th>
                                <th>Gas Level (ppm)</th>
                                <th>Status</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data["live_gas_table"] as $row): ?>
                                <tr>
                                    <td><?php echo $row["DeviceID"]; ?></td>
                                    <td><?php echo $row["Gas Level"]; ?></td>
                                    <td class="<?php echo $row["Status"] ? 'status-detected' : 'status-not-detected'; ?>">
                                        <?php echo $row["Status"] ? 'Gas Detected' : 'No Gas Detected'; ?>
                                    </td>
                                    <td><?php echo $row["Timestamp"]; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        const timeLabels = <?php echo json_encode($data["time_labels"]); ?>;
        const sensor1Readings = <?php echo json_encode($data["sensor1_readings"]); ?>;
        const sensor2Readings = <?php echo json_encode($data["sensor2_readings"]); ?>;
        const counter = <?php echo json_encode($data["counter"]); ?>;

        // Line chart for live gas readings
        const liveGasCtx = document.getElementById('livegasChart').getContext('2d');
        const liveGasChart = new Chart(liveGasCtx, {
            type: 'line',
            data: {
                labels: timeLabels,
                datasets: [{
                    label: 'ESP32-GasSensor1',
                    data: sensor1Readings,
                    borderColor: '#FF6384',
                    fill: false
                }, {
                    label: 'ESP32-GasSensor2',
                    data: sensor2Readings,
                    borderColor: '#36A2EB',
                    fill: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: { title: { display: true, text: 'Time (3-second intervals)' } },
                    y: { title: { display: true, text: 'Gas Level (ppm)' } }
                }
            }
        });

        // Donut chart for Tickets By Type
        const statusChartCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusChartCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Acknowledge', 'False Alarm'],
                datasets: [{
                    data: counter,
                    backgroundColor: ['#36A2EB', '#FF6384', '#FFCE56']
                }]
            },
            options: { responsive: true }
        });
    </script>
</body>
</html>
