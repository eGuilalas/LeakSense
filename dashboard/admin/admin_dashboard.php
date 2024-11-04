<?php
// admin_dashboard.php

session_start();

if (!isset($_SESSION['loggedin'])) {
    header('Location: ../../login.php');
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LeakSense Admin Dashboard</title>
    <link rel="stylesheet" href="dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Style for sidebar and main dashboard layout */
        .dashboard { display: flex; }
        .sidebar { width: 250px; }
        .main-content { flex: 1; padding: 20px; }
        /* Dropdown for options */
        .dropdown { position: relative; display: inline-block; }
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
        }
        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }
        .dropdown-content a:hover { background-color: #f1f1f1; }
        .dropdown:hover .dropdown-content { display: block; }
    </style>
</head>
<body>

    <div class="dashboard">
        <div class="sidebar" id="sidebar">
            <button class="hamburger" id="hamburger" onclick="toggleSidebar()">&#9776;</button>

            <h2>Monitoring</h2>
            <a href="admin_dashboard.php"><span class="icon">📊</span>Dashboard</a>
            <a href="esp32_1.php"><span class="icon">💽</span>ESP32 - 1</a>
            <a href="esp32_2.php"><span class="icon">💽</span>ESP32 - 2</a>
            <a href="reports.php"><span class="icon">📅</span>Reports</a>

            <div class="menu-section">
                <h2>Settings</h2>
                <a href="manage_users.php"><span class="icon">👥</span>Manage Users</a>
                <a href="threshold_management.php"><span class="icon">⚙️</span>Threshold</a>
                <a href="recipient.php"><span class="icon">⚙️</span>Recipient Setup</a>
            </div>

            <div class="menu-section">
                <h2>Welcome</h2>
                <a href="#"><span class="icon">👤</span><span style="color: red;"><?php echo htmlspecialchars($username); ?></span> - <?php echo htmlspecialchars($role); ?></a>
            </div>

            <div class="menu-section dropdown">
                <h2>Language</h2>
                <a href="#"><span class="icon">⚙️</span>Options</a>
                <div class="dropdown-content">
                    <a href="admin_dashboard.php" onclick="translateDashboard('en')">English</a>
                    <a href="admin_dashboard_fr.php" onclick="translateDashboard('fr')">French</a>
                </div>
            </div>

            <div class="menu-section">
                <h2>Logout</h2>
                <a href="../../logout.php"><span class="icon">🚪</span>Logout</a>
            </div>
        </div>

        <div class="main-content" id="main-content">
            <h1>Live Gas Readings Dashboard</h1>
            <div class="counters-container">
                <div class="counter pending"><h3>Pending</h3><p id="pending-count">0</p></div>
                <div class="counter acknowledged"><h3>Acknowledged</h3><p id="acknowledged-count">0</p></div>
                <div class="counter false-alarm"><h3>False Alarm</h3><p id="false-alarm-count">0</p></div>
                <div class="counter duplicate"><h3>Duplicate ID</h3><p id="duplicate-count">0</p></div>
            </div>

            <div class="container">
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
                            <tr><th>Device ID</th><th>Gas Level (ppm)</th><th>Smoke Status</th><th>CO Status</th><th>LPG Status</th><th>Timestamp</th></tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
            document.body.classList.toggle('collapsed');
        }

        const ctxGasLevel = document.getElementById('gasLevelChart').getContext('2d');
        const gasLevelChart = new Chart(ctxGasLevel, {
            type: 'line',
            data: { labels: [], datasets: [
                { label: 'GS1 Gas Level (ppm)', data: [], borderColor: 'rgba(75, 192, 192, 1)', backgroundColor: 'rgba(75, 192, 192, 0.2)', borderWidth: 1 },
                { label: 'GS2 Gas Level (ppm)', data: [], borderColor: 'rgba(255, 99, 132, 1)', backgroundColor: 'rgba(255, 99, 132, 0.2)', borderWidth: 1 }
            ] },
            options: {
                scales: { y: { beginAtZero: true, title: { display: true, text: 'Gas Level (ppm)' } },
                          x: { title: { display: true, text: 'Time' } } }
            }
        });

        function isDeviceOnline(timestamp) {
            const currentTime = new Date().getTime();
            const readingTime = new Date(timestamp).getTime();
            const timeDiff = (currentTime - readingTime) / 1000;
            return timeDiff <= 300;
        }

        function getStatusTextAndColor(smokeStatus, coStatus, lpgStatus) {
            return smokeStatus == '1' || coStatus == '1' || lpgStatus == '1'
                ? { text: 'Gas Detected!', color: 'red' }
                : { text: 'No Gas Detected', color: 'green' };
        }

        function fetchServerStatus() {
            fetch('../../config/server_status.php')
                .then(response => response.json())
                .then(data => {
                    const serverStatusElement = document.getElementById('server-status');
                    serverStatusElement.textContent = data.status === "online" ? "Online ✔️" : "Offline ❌";
                    serverStatusElement.className = data.status === "online" ? "online" : "offline";
                })
                .catch(error => console.error("Error fetching server status:", error));
        }

        function fetchAndDisplayCounters() {
            fetch('../../config/get_alert_responses.php')
                .then(response => response.json())
                .then(data => {
                    let pendingCount = 0, acknowledgedCount = 0, falseAlarmCount = 0, duplicateCount = 0;
                    const uniqueIds = new Set();

                    data.forEach(reading => {
                        const { response_type, reading_id } = reading;
                        if (response_type === 'acknowledged') acknowledgedCount++;
                        else if (response_type === 'false_alarm') falseAlarmCount++;
                        else pendingCount++;

                        if (uniqueIds.has(reading_id)) duplicateCount++;
                        else uniqueIds.add(reading_id);
                    });

                    document.getElementById('pending-count').innerText = pendingCount;
                    document.getElementById('acknowledged-count').innerText = acknowledgedCount;
                    document.getElementById('false-alarm-count').innerText = falseAlarmCount;
                    document.getElementById('duplicate-count').innerText = duplicateCount;
                })
                .catch(error => console.error('Error fetching counters:', error));
        }

        function fetchLatestReadings() {
            Promise.all([
                fetch('../../config/get_latest_reading.php?device_id=GS1'),
                fetch('../../config/get_latest_reading.php?device_id=GS2')
            ])
            .then(responses => Promise.all(responses.map(res => res.json())))
            .then(data => {
                data.forEach(reading => {
                    const deviceElement = reading.device_id === 'GS1' ? 'latest-readings-gs1' : 'latest-readings-gs2';
                    const status = getStatusTextAndColor(reading.smoke_status, reading.co_status, reading.lpg_status);
                    document.getElementById(deviceElement).innerHTML = `
                        <span style="color: blue;">Device: ${reading.device_id}</span>, 
                        <span style="color: blue;">Gas Level: ${reading.gas_level} ppm</span>, 
                        <span style="color: ${status.color};">Status: ${status.text}</span>, 
                        <span style="color: gray;">Time: ${new Date(reading.timestamp).toLocaleString()}</span>
                    `;
                    
                    const deviceStatusElement = document.getElementById(`${reading.device_id.toLowerCase()}-status`);
                    const isOnline = isDeviceOnline(reading.timestamp);
                    deviceStatusElement.className = isOnline ? 'online' : 'offline';
                    deviceStatusElement.textContent = isOnline ? 'Online ✔️' : 'Offline ❌';

                    updateTable(reading);
                });
            })
            .catch(error => console.error('Error fetching latest readings:', error));
        }

        function updateTable(reading) {
            const tableBody = document.getElementById('readingsTable').getElementsByTagName('tbody')[0];
            const newRow = tableBody.insertRow();
            const smokeStatus = reading.smoke_status == '1' ? '<span style="color:red">Gas Detected!</span>' : '<span style="color:green">No Gas Detected</span>';
            const coStatus = reading.co_status == '1' ? '<span style="color:red">Gas Detected!</span>' : '<span style="color:green">No Gas Detected</span>';
            const lpgStatus = reading.lpg_status == '1' ? '<span style="color:red">Gas Detected!</span>' : '<span style="color:green">No Gas Detected</span>';

            newRow.innerHTML = `
                <td>${reading.device_id}</td>
                <td>${reading.gas_level} ppm</td>
                <td>${smokeStatus}</td>
                <td>${coStatus}</td>
                <td>${lpgStatus}</td>
                <td>${new Date(reading.timestamp).toLocaleString()}</td>
            `;
        }

        function fetchGraphReadings() {
            fetch('../../config/get_readings.php')
                .then(response => response.json())
                .then(data => {
                    const last10Readings = data.slice(-10).reverse();
                    gasLevelChart.data.labels = last10Readings.map(entry => new Date(entry.timestamp).toLocaleString());
                    gasLevelChart.data.datasets[0].data = last10Readings.filter(entry => entry.device_id === 'GS1').map(entry => entry.gas_level);
                    gasLevelChart.data.datasets[1].data = last10Readings.filter(entry => entry.device_id === 'GS2').map(entry => entry.gas_level);
                    gasLevelChart.update();
                })
                .catch(error => console.error('Error fetching graph readings:', error));
        }

        fetchServerStatus();
        setInterval(fetchServerStatus, 5000);
        fetchAndDisplayCounters();
        setInterval(fetchAndDisplayCounters, 5000);
        fetchLatestReadings();
        setInterval(fetchLatestReadings, 3000);
        fetchGraphReadings();
        setInterval(fetchGraphReadings, 3000);
    </script>

</body>
</html>
