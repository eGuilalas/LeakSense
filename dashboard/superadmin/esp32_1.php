<?php
// esp32_1.php

session_start();
if (!isset($_SESSION['loggedin'])) {
    header('Location: ../../login.php');
    exit();
}

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ESP32 - 1 Readings</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .filter-controls {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        .status-counters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .status-counter {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 8px;
            color: white;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
            transition: background-color 0.3s ease;
        }
        .status-counter.pending { background-color: #e74c3c; }
        .status-counter.pending:hover { background-color: #c0392b; }
        .status-counter.acknowledged { background-color: #3498db; }
        .status-counter.acknowledged:hover { background-color: #2980b9; }
        .status-counter.false-alarm { background-color: #e67e22; }
        .status-counter.false-alarm:hover { background-color: #d35400; }
        .status-counter.duplicate { background-color: #9b59b6; }
        .status-counter.duplicate:hover { background-color: #8e44ad; }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <button class="hamburger" id="hamburger" onclick="toggleSidebar()">&#9776;</button>
            <h2>Monitoring</h2>
            <a href="super_admin_dashboard.php"><span class="icon">📊</span>Dashboard</a>
            <a href="#"><span class="icon">💽</span>ESP32 - 1</a>
            <a href="../../logout.php"><span class="icon">🚪</span>Logout</a>
        </div>

        <div class="main-content">
            <h1>Gas Readings - ESP32 GS1</h1>

            <!-- Filter and Counters -->
            <div class="status-counters">
                <div class="status-counter pending" onclick="filterByStatus('Pending')">Pending: <span id="pending-count">0</span></div>
                <div class="status-counter acknowledged" onclick="filterByStatus('Acknowledged')">Acknowledged: <span id="acknowledged-count">0</span></div>
                <div class="status-counter false-alarm" onclick="filterByStatus('False Alarm')">False Alarm: <span id="false-alarm-count">0</span></div>
                <div class="status-counter duplicate" onclick="filterByStatus('Duplicate ID')">Duplicate ID: <span id="duplicate-count">0</span></div>
            </div>

            <div class="filter-controls">
                <label for="filter-status">Filter by Status:</label>
                <select id="filter-status">
                    <option value="">All</option>
                    <option value="Pending">Pending</option>
                    <option value="Acknowledged">Acknowledged</option>
                    <option value="False Alarm">False Alarm</option>
                    <option value="Duplicate ID">Duplicate ID</option>
                </select>
                <button onclick="clearFilter()">Clear Filter</button>
            </div>

            <div class="table-container">
                <table id="readingsTableGS1">
                    <thead>
                        <tr>
                            <th>Gas Level (ppm)</th>
                            <th>Smoke Status</th>
                            <th>CO Status</th>
                            <th>LPG Status</th>
                            <th>Timestamp</th>
                            <th>Alert Status</th>
                            <th>Actioned By</th>
                            <th>Comment</th>
                            <th>Response Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Rows will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const USER_ID = <?php echo json_encode($user_id); ?>;

        function fetchReadings() {
            fetch('../../config/get_alert_responses.php')
                .then(response => response.json())
                .then(data => {
                    renderTable(data);
                    updateCounters(data);
                })
                .catch(error => console.error('Error fetching readings:', error));
        }

        function renderTable(data) {
            const tableBody = document.getElementById('readingsTableGS1').getElementsByTagName('tbody')[0];
            const filterStatus = document.getElementById('filter-status').value;
            tableBody.innerHTML = '';

            const idCounts = data.reduce((acc, reading) => {
                acc[reading.reading_id] = (acc[reading.reading_id] || 0) + 1;
                return acc;
            }, {});
            const duplicateIds = new Set(Object.keys(idCounts).filter(id => idCounts[id] > 1));

            data.forEach(reading => {
                let alertStatusText = 'Pending';
                let alertStatusColor = 'red';

                if (reading.response_type === 'acknowledged') {
                    alertStatusText = 'Acknowledged';
                    alertStatusColor = 'blue';
                } else if (reading.response_type === 'false_alarm') {
                    alertStatusText = 'False Alarm';
                    alertStatusColor = 'orange';
                }

                const isDuplicate = duplicateIds.has(String(reading.reading_id));
                if (isDuplicate && alertStatusText !== 'Pending') alertStatusText = 'Duplicate ID';

                if (filterStatus && filterStatus !== alertStatusText) return;

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${reading.gas_level} ppm</td>
                    <td>${reading.smoke_status === "1" ? '<span style="color:red">Gas Detected!</span>' : 'No Gas Detected'}</td>
                    <td>${reading.co_status === "1" ? '<span style="color:red">Gas Detected!</span>' : 'No Gas Detected'}</td>
                    <td>${reading.lpg_status === "1" ? '<span style="color:red">Gas Detected!</span>' : 'No Gas Detected'}</td>
                    <td>${new Date(reading.timestamp).toLocaleString()}</td>
                    <td style="color: ${alertStatusColor}">${alertStatusText}</td>
                    <td>${reading.actioned_by || 'N/A'}</td>
                    <td>${reading.comment || 'N/A'}</td>
                    <td>${reading.response_time ? new Date(reading.response_time).toLocaleString() : 'N/A'}</td>
                    <td>
                        <button class="acknowledge-btn" data-reading-id="${reading.reading_id}">Acknowledge</button>
                        <button class="false-alarm-btn" data-reading-id="${reading.reading_id}">False Alarm</button>
                    </td>
                `;

                tableBody.appendChild(row);
            });

            attachButtonListeners();
        }

        function updateCounters(data) {
            let pending = 0, acknowledged = 0, falseAlarm = 0, duplicates = 0;
            const duplicateIds = new Set(data.map(reading => reading.reading_id));

            data.forEach(reading => {
                if (duplicateIds.has(reading.reading_id)) duplicates++;
                if (reading.response_type === 'acknowledged') acknowledged++;
                else if (reading.response_type === 'false_alarm') falseAlarm++;
                else pending++;
            });

            document.getElementById('pending-count').innerText = pending;
            document.getElementById('acknowledged-count').innerText = acknowledged;
            document.getElementById('false-alarm-count').innerText = falseAlarm;
            document.getElementById('duplicate-count').innerText = duplicates;
        }

        function clearFilter() {
            document.getElementById('filter-status').value = '';
            fetchReadings();
        }

        function attachButtonListeners() {
            document.querySelectorAll('.acknowledge-btn').forEach(button => {
                button.removeEventListener('click', handleAcknowledge);
                button.addEventListener('click', handleAcknowledge);
            });

            document.querySelectorAll('.false-alarm-btn').forEach(button => {
                button.removeEventListener('click', handleFalseAlarm);
                button.addEventListener('click', handleFalseAlarm);
            });
        }

        function handleAcknowledge() {
            const readingId = this.getAttribute('data-reading-id');
            const comment = prompt("Enter a comment for this acknowledgment:");
            if (comment === null) return;

            fetch('../../config/acknowledge_alert.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `reading_id=${readingId}&user_id=${USER_ID}&comment=${encodeURIComponent(comment)}`
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                fetchReadings();
            })
            .catch(error => console.error('Error acknowledging alert:', error));
        }

        function handleFalseAlarm() {
            const readingId = this.getAttribute('data-reading-id');
            const comment = prompt("Enter a comment for marking this as a false alarm:");
            if (comment === null) return;

            fetch('../../config/false_alarm.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `reading_id=${readingId}&user_id=${USER_ID}&comment=${encodeURIComponent(comment)}`
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                fetchReadings();
            })
            .catch(error => console.error('Error marking false alarm:', error));
        }

        document.getElementById('filter-status').addEventListener('change', fetchReadings);
        fetchReadings();
        setInterval(fetchReadings, 5000); // Refresh every 5 seconds
    </script>
</body>
</html>
