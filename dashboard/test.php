<?php
// Ensure the session is started and user is logged in
session_start();

// Check if the user is logged in, and retrieve the username and role from the session
if (!isset($_SESSION['loggedin'])) {
    // Redirect to login if not logged in
    header('Location: ../login.php');
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
    <title>Professional Dashboard Layout</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
            color: #333;
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

        .dropdown a {
            padding-left: 40px;
            font-size: 14px;
        }

        /* Main content styling */
        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
            transition: margin-left 0.3s;
            background-color: #f4f4f9;
        }

        .sidebar.collapsed ~ .main-content {
            margin-left: 60px;
        }

        h1 {
            text-align: center;
            color: #4a90e2;
            margin-bottom: 30px;
            font-size: 2rem;
        }

        h2 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.4rem;
            font-weight: 600;
        }

        .container {
            max-width: 1000px;
            margin: auto;
            padding: 20px;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }

        .status-container {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
        }

        .status {
            display: flex;
            align-items: center;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .status i {
            margin-left: 8px;
            font-size: 18px;
        }

        .online {
            color: green;
        }

        .offline {
            color: red;
        }

        canvas {
            max-width: 100%;
            border-radius: 8px;
            background-color: #f9f9f9;
        }

        .table-container {
            max-height: 300px;
            overflow-y: auto;
            margin: 20px 0;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            position: relative;
        }

        th, td {
            border: 1px solid #e0e0e0;
            padding: 10px;
            text-align: center;
            font-size: 1rem;
        }

        th {
            background-color: #f0f0f0;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .status-detected {
            color: red;
            font-weight: bold;
        }

        .status-not-detected {
            color: green;
            font-weight: normal;
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
            <a href="reports.php"><span class="icon">📅</span>Reports</a>

            <div class="menu-section">
                <h2>RUM</h2>
                <a href="manage_users.php"><span class="icon">👥</span>Manage Users</a>
                <a href="recipient.php"><span class="icon">⚙️</span>Recipient Setup</a>
            </div>

            <div class="menu-section">
                <h2>Welcome</h2>
                <a href="#"><span class="icon">👤</span><span style="color: red;"><?php echo htmlspecialchars($username); ?></span> - <?php echo htmlspecialchars($role); ?></a>
            </div>

            <!-- Logout Section -->
            <div class="menu-section">
                <h2>Logout</h2>
                <a href="../logout.php"><span class="icon">🚪</span>Logout</a>
            </div>
        </div>

        <div class="main-content" id="main-content">
            <h1>Live Gas Readings Graph</h1>
            <div class="container">
                <!-- Device status section for GS1 and GS2 -->
                <div class="status-container">
                    <div class="status">GS1 Status: <i id="gs1-status" class="offline">Offline</i></div>
                    <div class="status">GS2 Status: <i id="gs2-status" class="offline">Offline</i></div>
                </div>

                <h2>Latest Gas Readings</h2>
                <p id="latest-readings">Fetching latest readings...</p>
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

    <script>
        // Sidebar Toggle Functionality
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
            document.body.classList.toggle('collapsed');
        }

        // Function to check if a device is online (i.e., last reading is within the last minute)
        function isDeviceOnline(timestamp) {
            const currentTime = new Date().getTime();
            const readingTime = new Date(timestamp).getTime();
            const timeDiff = (currentTime - readingTime) / 1000; // in seconds
            return timeDiff <= 60; // Consider online if the last reading was within the last 60 seconds
        }

        // Chart for gas levels
        const ctxGasLevel = document.getElementById('gasLevelChart').getContext('2d');
        const gasLevelChart = new Chart(ctxGasLevel, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'GS1 Gas Level (ppm)',
                        data: [],
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderWidth: 1,
                    },
                    {
                        label: 'GS2 Gas Level (ppm)',
                        data: [],
                        borderColor: 'rgba(255, 99, 132, 1)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderWidth: 1,
                    }
                ]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Gas Level (ppm)',
                        },
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Time',
                        },
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return `${tooltipItem.dataset.label}: ${tooltipItem.raw} ppm`;
                            }
                        }
                    }
                }
            }
        });

        const tableBody = document.getElementById('readingsTable').getElementsByTagName('tbody')[0];

        function fetchLatestReadings() {
            Promise.all([
                fetch('get_latest_reading.php?device_id=GS1'),
                fetch('get_latest_reading.php?device_id=GS2')
            ])
            .then(responses => Promise.all(responses.map(res => res.json())))
            .then(data => {
                const readingsText = data.map(reading => {
                    // Check if any of the statuses are '1' (indicating gas detected)
                    const gasDetected = (reading.smoke_status == '1' || reading.co_status == '1' || reading.lpg_status == '1');
                    const statusText = gasDetected 
                        ? `<span class="status-detected">Gas Detected</span>` 
                        : `<span class="status-not-detected">No Gas Detected</span>`;
                    return `Device ID: ${reading.device_id}, Gas Level: ${reading.gas_level} ppm, Status: ${statusText}, Timestamp: ${new Date(reading.timestamp).toLocaleString()}`;
                }).join('<br>');
                document.getElementById('latest-readings').innerHTML = readingsText;

                // Insert new rows at the bottom of the table
                data.forEach(reading => {
                    const newRow = tableBody.insertRow();
                    
                    // Correctly check each gas type status and display it
                    const smokeStatus = reading.smoke_status == '1' ? '<span class="status-detected">Gas Detected</span>' : 'No Gas Detected';
                    const coStatus = reading.co_status == '1' ? '<span class="status-detected">Gas Detected</span>' : 'No Gas Detected';
                    const lpgStatus = reading.lpg_status == '1' ? '<span class="status-detected">Gas Detected</span>' : 'No Gas Detected';

                    newRow.innerHTML = `
                        <td>${reading.device_id}</td>
                        <td>${reading.gas_level} ppm</td>
                        <td class="${reading.smoke_status == '1' ? 'status-detected' : 'status-not-detected'}">
                            ${smokeStatus}
                        </td>
                        <td class="${reading.co_status == '1' ? 'status-detected' : 'status-not-detected'}">
                            ${coStatus}
                        </td>
                        <td class="${reading.lpg_status == '1' ? 'status-detected' : 'status-not-detected'}">
                            ${lpgStatus}
                        </td>
                        <td>${new Date(reading.timestamp).toLocaleString()}</td>
                    `;

                    // Update the online/offline status for GS1 and GS2
                    if (reading.device_id === 'GS1') {
                        const gs1StatusElement = document.getElementById('gs1-status');
                        gs1StatusElement.className = isDeviceOnline(reading.timestamp) ? 'online' : 'offline';
                        gs1StatusElement.textContent = isDeviceOnline(reading.timestamp) ? 'Online ✔️' : 'Offline ❌';
                    } else if (reading.device_id === 'GS2') {
                        const gs2StatusElement = document.getElementById('gs2-status');
                        gs2StatusElement.className = isDeviceOnline(reading.timestamp) ? 'online' : 'offline';
                        gs2StatusElement.textContent = isDeviceOnline(reading.timestamp) ? 'Online ✔️' : 'Offline ❌';
                    }
                });

                // Scroll to the bottom of the table to show the latest reading
                const tableContainer = document.getElementById('tableContainer');
                tableContainer.scrollTop = tableContainer.scrollHeight;
            })
            .catch(error => console.error('Error fetching latest readings:', error));
        }

        function fetchGraphReadings() {
            fetch('get_readings.php') 
                .then(response => response.json())
                .then(data => {
                    // Get the latest readings regardless of the time
                    const last10Readings = data.slice(-10);
                    
                    // Update gas level chart
                    gasLevelChart.data.labels = last10Readings.map(entry => new Date(entry.timestamp).toLocaleString());
                    gasLevelChart.data.datasets[0].data = last10Readings.filter(entry => entry.device_id === 'GS1').map(entry => entry.gas_level);
                    gasLevelChart.data.datasets[1].data = last10Readings.filter(entry => entry.device_id === 'GS2').map(entry => entry.gas_level);
                    gasLevelChart.update();
                })
                .catch(error => console.error('Error fetching graph readings:', error));
        }

        // Fetch latest readings and update the table initially
        fetchLatestReadings();
        setInterval(fetchLatestReadings, 3000); // Update latest readings every 3 seconds

        // Fetch graph readings initially
        fetchGraphReadings();
        setInterval(fetchGraphReadings, 5000); // Update graph every 5 minutes
    </script>

</body>
</html>
