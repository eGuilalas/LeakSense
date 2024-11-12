<?php
session_start();

if (!isset($_SESSION['userID'])) {
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
        .status-detected { color: red; font-weight: bold; }
        .status-not-detected { color: green; font-weight: bold; }
        .online { color: green; }
        .offline { color: red; }
        .on { color: green; font-weight: bold; }
        .standby { color: orange; font-weight: bold; }

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
                            <li><a href="email_alert_report.php">Email Alert Report</a></li>
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
                    <p id="serverStatus" class="online">Online</p>
                </div>
                <div class="header-box">
                    <h3>ESP32-GasSensor 1 Status</h3>
                    <p id="sensor1Status" class="online">...</p>
                </div>
                <div class="header-box">
                    <h3>ESP32-GasSensor 2 Status</h3>
                    <p id="sensor2Status" class="online">...</p>
                </div>
                <div class="header-box">
                    <h3>ESP32-GasSensor-Fan-1 Status</h3>
                    <p id="fan1Status" class="standby">...</p>
                </div>
                <div class="header-box">
                    <h3>ESP32-GasSensor-Fan-2 Status</h3>
                    <p id="fan2Status" class="standby">...</p>
                </div>
            </div>
            <div class="dashboard-header">
                <div class="header-box">
                    <h3>Pending</h3>
                    <p id="pendingCount" style="color: #36A2EB;">0</p>
                </div>
                <div class="header-box">
                    <h3>Acknowledge</h3>
                    <p id="acknowledgeCount" style="color: #FF6384;">0</p>
                </div>
                <div class="header-box">
                    <h3>False Alarm</h3>
                    <p id="falseAlarmCount" style="color: #FFCE56;">0</p>
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
                data: [0, 0, 0],
                backgroundColor: ['#36A2EB', '#FF6384', '#FFCE56']
            }]
        },
        options: { responsive: true }
    });

    function fetchLiveChartData() {
        fetch('../api/get_live_chart_data.php')
            .then(response => response.json())
            .then(data => {
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
                document.getElementById('pendingCount').innerText = data.pending;
                document.getElementById('acknowledgeCount').innerText = data.acknowledge;
                document.getElementById('falseAlarmCount').innerText = data.false_alarm;
                statusChart.data.datasets[0].data = [data.pending, data.acknowledge, data.false_alarm];
                statusChart.update();
            })
            .catch(error => console.error("Error fetching status data:", error));
    }

    function fetchDeviceStatus() {
        fetch('../api/get_status.php')
            .then(response => response.json())
            .then(data => {
                const sensor1Online = data.GS1 === 'Online';
                const sensor2Online = data.GS2 === 'Online';

                document.getElementById('sensor1Status').innerText = data.GS1;
                document.getElementById('sensor2Status').innerText = data.GS2;

                const fan1StatusElem = document.getElementById('fan1Status');
                const fan2StatusElem = document.getElementById('fan2Status');

                if (!sensor1Online) {
                    fan1StatusElem.innerText = "Offline";
                    fan1StatusElem.className = "offline";
                }

                if (!sensor2Online) {
                    fan2StatusElem.innerText = "Offline";
                    fan2StatusElem.className = "offline";
                }

                if (sensor1Online || sensor2Online) {
                    fetchLiveTableData(sensor1Online, sensor2Online);
                }

                document.getElementById('serverStatus').className = data.server === 'Online' ? 'online' : 'offline';
                document.getElementById('sensor1Status').className = sensor1Online ? 'online' : 'offline';
                document.getElementById('sensor2Status').className = sensor2Online ? 'online' : 'offline';
            })
            .catch(error => console.error("Error fetching device status:", error));
    }

    function fetchLiveTableData(sensor1Online, sensor2Online) {
        fetch('../api/get_live_table_data.php')
            .then(response => response.json())
            .then(data => {
                const tableBody = document.querySelector("#gasTable tbody");
                tableBody.innerHTML = "";

                const latestEntries = data.slice(-7);
                let isGasDetected = false;

                latestEntries.forEach(row => {
                    const statusClass = row.status === "Gas Detected" ? 'status-detected' : 'status-not-detected';
                    tableBody.innerHTML += `
                        <tr>
                            <td>${row.deviceID}</td>
                            <td>${row.ppm}</td>
                            <td class="${statusClass}">${row.status}</td>
                            <td>${row.timestamp}</td>
                        </tr>`;
                    if (row.status === "Gas Detected") {
                        isGasDetected = true;
                    }
                });

                const fan1StatusElem = document.getElementById('fan1Status');
                const fan2StatusElem = document.getElementById('fan2Status');

                if (sensor1Online) {
                    fan1StatusElem.innerText = isGasDetected ? "On" : "Standby";
                    fan1StatusElem.className = isGasDetected ? "on" : "standby";
                }

                if (sensor2Online) {
                    fan2StatusElem.innerText = isGasDetected ? "On" : "Standby";
                    fan2StatusElem.className = isGasDetected ? "on" : "standby";
                }
            })
            .catch(error => console.error("Error fetching live table data:", error));
    }

    setInterval(fetchLiveChartData, 3000);
    setInterval(fetchStatusData, 3000);
    setInterval(fetchDeviceStatus, 3000);
    setInterval(fetchLiveTableData, 1000);
    </script>
</body>
</html>
