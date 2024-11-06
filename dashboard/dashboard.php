<?php
session_start();

if (!isset($_SESSION['userID'])) {
    // Redirect to login page
    $_SESSION['error'] = "You must log in to access this page.";
    header("Location: ../login.php");
    exit();
}
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
        .status-detected { color: red; font-weight: bold; }  /* For Gas Detected */
        .status-not-detected { color: green; font-weight: bold; }  /* For No Gas Detected */
        .online { color: green; }  /* Online status color */
        .offline { color: red; }    /* Offline status color */

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
                        <?php if ($_SESSION['userrole'] !== 'user'): ?>
                            <li><a href="manage_user.php">Manage User</a></li>
                            <li><a href="Threshold.php">Threshold Setup</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            
            <div class="bottom-section">
                <h3>Welcome!</h3>
                <h4><?php echo htmlspecialchars($_SESSION['username']); ?></h4>
                <h4>Role: <?php echo htmlspecialchars($_SESSION['userrole']); ?></h4>
            </div>
            <div class="bottom-section">
                <h3>Language</h3>
                <li><a href="dashboard_fr.php">French</a></li>
            </div>
            <div class="bottom-section">
                <a href="../logout.php">Logout</a>
            </div>
        </aside>

        <main class="main-dashboard">
            <div class="dashboard-header">
                <div class="header-box">
                    <h3>Server Status</h3>
                    <p id="serverStatus" class="online">Online</p> <!-- Dynamic status with initial class -->
                </div>
                <div class="header-box">
                    <h3>ESP32-GasSensor 1 Status</h3>
                    <p id="sensor1Status" class="online">...</p> <!-- Dynamic status with initial class -->
                </div>
                <div class="header-box">
                    <h3>ESP32-GasSensor 2 Status</h3>
                    <p id="sensor2Status" class="online">...</p> <!-- Dynamic status with initial class -->
                </div>
            </div>
            <div class="dashboard-header">
                <div class="header-box">
                    <h3>Pending</h3>
                    <p id="pendingCount" style="color: #36A2EB;">0</p> <!-- Color for Pending -->
                </div>
                <div class="header-box">
                    <h3>Acknowledge</h3>
                    <p id="acknowledgeCount" style="color: #FF6384;">0</p> <!-- Color for Acknowledge -->
                </div>
                <div class="header-box">
                    <h3>False Alarm</h3>
                    <p id="falseAlarmCount" style="color: #FFCE56;">0</p> <!-- Color for False Alarm -->
                </div>
            </div>

            <div class="charts-container">
                <div class="chart">
                    <canvas id="livegasChart"></canvas>
                </div>
                <div class="chart">
                    <canvas id="statusChart"></canvas>
                </div>
                <div class="table-container">
                    <h3>Live Gas Table</h3>
                    <table id="gasTable">
                        <thead>
                            <tr>
                                <th>DeviceID</th>
                                <th>Gas Level (ppm)</th>
                                <th>Status</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
    const liveGasChartCtx = document.getElementById('livegasChart').getContext('2d');
    const statusChartCtx = document.getElementById('statusChart').getContext('2d');

    let liveGasChart = new Chart(liveGasChartCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                { label: 'GS1', data: [], borderColor: '#FF6384', fill: false },
                { label: 'GS2', data: [], borderColor: '#36A2EB', fill: false }
            ]
        },
        options: { responsive: true, scales: { x: { title: { display: true, text: 'Time' } }, y: { title: { display: true, text: 'Gas Level (ppm)' } } } }
    });

    let statusChart = new Chart(statusChartCtx, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Acknowledge', 'False Alarm'],
            datasets: [{
                data: [0, 0, 0], // Initial data
                backgroundColor: ['#36A2EB', '#FF6384', '#FFCE56']
            }]
        },
        options: { responsive: true }
    });

    function fetchLiveChartData() {
        fetch('../api/get_live_chart_data.php')
            .then(response => response.json())
            .then(data => {
                console.log("Live Chart Data Response:", data);
                if (data['ESP32-GasSensor1'] && data['ESP32-GasSensor1'].length > 0) {
                    liveGasChart.data.labels = data['ESP32-GasSensor1'].map(d => d.time);
                    liveGasChart.data.datasets[0].data = data['ESP32-GasSensor1'].map(d => d.ppm);
                }
                if (data['ESP32-GasSensor2'] && data['ESP32-GasSensor2'].length > 0) {
                    liveGasChart.data.datasets[1].data = data['ESP32-GasSensor2'].map(d => d.ppm);
                } else {
                    liveGasChart.data.datasets[1].data = [];
                }
                liveGasChart.update();
            })
            .catch(error => console.error("Error fetching live chart data:", error));
    }

    function fetchStatusData() {
        fetch('../api/get_status_data.php')
            .then(response => response.json())
            .then(data => {
                console.log("Status Data Response:", data);
                document.getElementById('pendingCount').innerText = data.pending;
                document.getElementById('acknowledgeCount').innerText = data.acknowledge;
                document.getElementById('falseAlarmCount').innerText = data.false_alarm;
                statusChart.data.datasets[0].data = [data.pending, data.acknowledge, data.false_alarm];
                statusChart.update();
            })
            .catch(error => console.error("Error fetching status data:", error));
    }

    function fetchLiveTableData() {
        fetch('../api/get_live_table_data.php')
            .then(response => response.json())
            .then(data => {
                console.log("Live Table Data:", data);
                const tableBody = document.querySelector("#gasTable tbody");
                tableBody.innerHTML = "";

                // Limit to 7 most recent entries
                const latestEntries = data.slice(-7);

                latestEntries.forEach(row => {
                    const statusClass = row.status === "Gas Detected" ? 'status-detected' : 'status-not-detected';
                    tableBody.innerHTML += `
                        <tr>
                            <td>${row.deviceID}</td>
                            <td>${row.ppm}</td>
                            <td class="${statusClass}">${row.status}</td>
                            <td>${row.timestamp}</td>
                        </tr>`;
                });

                // Scroll to the bottom of the table
                tableBody.scrollTop = tableBody.scrollHeight;
            })
            .catch(error => console.error("Error fetching live table data:", error));
    }

    function fetchDeviceStatus() {
        fetch('../api/get_status.php') // Updated endpoint to fetch status
            .then(response => response.json())
            .then(data => {
                console.log("Device Status Response:", data);
                document.getElementById('serverStatus').innerText = data.server; // Update Server Status
                document.getElementById('sensor1Status').innerText = data.GS1; // Update Sensor 1 Status
                document.getElementById('sensor2Status').innerText = data.GS2; // Update Sensor 2 Status

                // Change text color based on status
                document.getElementById('serverStatus').className = data.server === 'Online' ? 'online' : 'offline';
                document.getElementById('sensor1Status').className = data.GS1 === 'Online' ? 'online' : 'offline';
                document.getElementById('sensor2Status').className = data.GS2 === 'Online' ? 'online' : 'offline';
            })
            .catch(error => console.error("Error fetching device status:", error));
    }

    // Set intervals for data fetching
    setInterval(fetchLiveChartData, 3000);
    setInterval(fetchStatusData, 3000);
    setInterval(fetchLiveTableData, 3000);
    setInterval(fetchDeviceStatus, 1000); // Check device status every 5 seconds
    </script>
</body>
</html>
