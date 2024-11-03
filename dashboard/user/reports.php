<?php
// reports.php

session_start();
if (!isset($_SESSION['loggedin']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'user')) {
    header('Location: ../../login.php');
    exit();
}

include '../../config/db_connection.php';

$filters = [
    'device_id' => '',
    'gas_type' => '',
    'start_date' => '',
    'start_time' => '',
    'end_date' => '',
    'end_time' => '',
    'alert_status' => '',
    'acknowledged_by' => ''
];

// Get unique Device IDs for dropdown
$device_ids = [];
$device_result = $conn->query("SELECT DISTINCT device_id FROM gas_readings");
while ($row = $device_result->fetch_assoc()) {
    $device_ids[] = $row['device_id'];
}

// Get unique usernames for Acknowledged By dropdown
$usernames = [];
$user_result = $conn->query("SELECT DISTINCT u.username FROM gas_alert_responses gar JOIN users u ON gar.user_id = u.id");
while ($row = $user_result->fetch_assoc()) {
    $usernames[] = $row['username'];
}

// Handle filter submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_filters'])) {
    $filters['device_id'] = $_POST['device_id'] ?? '';
    $filters['gas_type'] = $_POST['gas_type'] ?? '';
    $filters['start_date'] = $_POST['start_date'] ?? '';
    $filters['start_time'] = $_POST['start_time'] ?? '';
    $filters['end_date'] = $_POST['end_date'] ?? '';
    $filters['end_time'] = $_POST['end_time'] ?? '';
    $filters['alert_status'] = $_POST['alert_status'] ?? '';
    $filters['acknowledged_by'] = $_POST['acknowledged_by'] ?? '';
}

// Construct SQL query with filters
$sql = "SELECT gr.*, COALESCE(gar.response_type, 'pending') AS alert_status, gar.response_time, gar.comments, u.username AS acknowledged_by_name 
        FROM gas_readings gr 
        LEFT JOIN gas_alert_responses gar ON gr.id = gar.reading_id 
        LEFT JOIN users u ON gar.user_id = u.id 
        WHERE 1=1";
$params = [];
$types = '';

// Apply filters based on user input
if (!empty($filters['device_id'])) {
    $sql .= " AND gr.device_id = ?";
    $params[] = $filters['device_id'];
    $types .= 's';
}

if (!empty($filters['gas_type'])) {
    $sql .= " AND gr.gas_type = ?";
    $params[] = $filters['gas_type'];
    $types .= 's';
}

if (!empty($filters['start_date']) && !empty($filters['start_time'])) {
    $sql .= " AND gr.timestamp >= ?";
    $params[] = $filters['start_date'] . ' ' . $filters['start_time'];
    $types .= 's';
} elseif (!empty($filters['start_date'])) {
    $sql .= " AND gr.timestamp >= ?";
    $params[] = $filters['start_date'] . ' 00:00:00';
    $types .= 's';
}

if (!empty($filters['end_date']) && !empty($filters['end_time'])) {
    $sql .= " AND gr.timestamp <= ?";
    $params[] = $filters['end_date'] . ' ' . $filters['end_time'];
    $types .= 's';
} elseif (!empty($filters['end_date'])) {
    $sql .= " AND gr.timestamp <= ?";
    $params[] = $filters['end_date'] . ' 23:59:59';
    $types .= 's';
}

if ($filters['alert_status'] !== '') {
    if ($filters['alert_status'] === 'pending') {
        $sql .= " AND gar.response_type IS NULL";
    } else {
        $sql .= " AND gar.response_type = ?";
        $params[] = $filters['alert_status'];
        $types .= 's';
    }
}

if (!empty($filters['acknowledged_by'])) {
    $sql .= " AND u.username = ?";
    $params[] = $filters['acknowledged_by'];
    $types .= 's';
}

// Prepare and execute the query
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$readings = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LeakSense Reports</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            color: #333;
        }
        .main-content {
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #4a90e2;
            font-size: 1.8em;
        }
        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        .filters label {
            font-weight: bold;
        }
        .filters select, .filters input {
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ccc;
            width: 180px;
        }
        .filters button {
            background-color: #4a90e2;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        .filters button:hover {
            background-color: #357ABD;
        }
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .report-table th, .report-table td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        .report-table th {
            background-color: #4a90e2;
            color: white;
        }
        .report-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>

<div class="dashboard">
    <div class="sidebar" id="sidebar">
        <button class="hamburger" id="hamburger" onclick="toggleSidebar()">&#9776;</button>
        <h2>Monitoring</h2>
        <a href="user_dashboard.php"><span class="icon">📊</span>Dashboard</a>
        <a href="esp32_1.php"><span class="icon">💽</span>ESP32 - 1</a>
        <a href="esp32_2.php"><span class="icon">💽</span>ESP32 - 2</a>
        <a href="reports.php"><span class="icon">📅</span>Reports</a>

        <div class="menu-section">
            <h2>Logout</h2>
            <a href="../../logout.php"><span class="icon">🚪</span>Logout</a>
        </div>
    </div>

    <div class="main-content">
        <h1>Gas Readings Report</h1>
        
        <div class="report-container">
            <form method="post" class="filters">
                <label>Device ID:
                    <select name="device_id">
                        <option value="">All Devices</option>
                        <?php foreach ($device_ids as $device_id): ?>
                            <option value="<?= htmlspecialchars($device_id) ?>" <?= $filters['device_id'] === $device_id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($device_id) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Gas Type:
                    <select name="gas_type">
                        <option value="">All Types</option>
                        <option value="Smoke" <?= $filters['gas_type'] === 'Smoke' ? 'selected' : '' ?>>Smoke</option>
                        <option value="CO" <?= $filters['gas_type'] === 'CO' ? 'selected' : '' ?>>CO</option>
                        <option value="LPG" <?= $filters['gas_type'] === 'LPG' ? 'selected' : '' ?>>LPG</option>
                    </select>
                </label>
                <label>Start Date:
                    <input type="date" name="start_date" value="<?= htmlspecialchars($filters['start_date'] ?? '') ?>">
                </label>
                <label>Start Time:
                    <input type="time" name="start_time" value="<?= htmlspecialchars($filters['start_time'] ?? '') ?>">
                </label>
                <label>End Date:
                    <input type="date" name="end_date" value="<?= htmlspecialchars($filters['end_date'] ?? '') ?>">
                </label>
                <label>End Time:
                    <input type="time" name="end_time" value="<?= htmlspecialchars($filters['end_time'] ?? '') ?>">
                </label>
                <label>Alert Status:
                    <select name="alert_status">
                        <option value="">Any</option>
                        <option value="pending" <?= $filters['alert_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="acknowledged" <?= $filters['alert_status'] === 'acknowledged' ? 'selected' : '' ?>>Acknowledged</option>
                        <option value="false_alarm" <?= $filters['alert_status'] === 'false_alarm' ? 'selected' : '' ?>>False Alarm</option>
                    </select>
                </label>
                <label>Acknowledged By:
                    <select name="acknowledged_by">
                        <option value="">Any User</option>
                        <?php foreach ($usernames as $username): ?>
                            <option value="<?= htmlspecialchars($username) ?>" <?= $filters['acknowledged_by'] === $username ? 'selected' : '' ?>>
                                <?= htmlspecialchars($username) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit" name="apply_filters">Apply Filters</button>
                <button type="submit" name="reset_filters">Reset Filters</button>
                <button type="button" onclick="printReport()">Print Report</button>
            </form>

            <div id="printable-report">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Device ID</th>
                            <th>Gas Level (ppm)</th>
                            <th>Gas Type</th>
                            <th>Smoke Status</th>
                            <th>CO Status</th>
                            <th>LPG Status</th>
                            <th>Alert Status</th>
                            <th>Acknowledged By</th>
                            <th>Response Time</th>
                            <th>Comments</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($readings as $reading): ?>
                            <tr>
                                <td><?= htmlspecialchars($reading['id'] ?? '') ?></td>
                                <td><?= htmlspecialchars($reading['device_id'] ?? '') ?></td>
                                <td><?= htmlspecialchars($reading['gas_level'] ?? '') ?></td>
                                <td><?= htmlspecialchars($reading['gas_type'] ?? '') ?></td>
                                <td><?= $reading['smoke_status'] ? 'Detected' : 'Not Detected' ?></td>
                                <td><?= $reading['co_status'] ? 'Detected' : 'Not Detected' ?></td>
                                <td><?= $reading['lpg_status'] ? 'Detected' : 'Not Detected' ?></td>
                                <td><?= htmlspecialchars($reading['alert_status'] ?? 'Pending') ?></td>
                                <td><?= htmlspecialchars($reading['acknowledged_by_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($reading['response_time'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($reading['comments'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($reading['timestamp'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function printReport() {
        const printableContent = document.getElementById('printable-report').innerHTML;
        const originalContent = document.body.innerHTML;
        
        document.body.innerHTML = `<html><head><title>Print Report</title></head><body>${printableContent}</body></html>`;
        window.print();
        document.body.innerHTML = originalContent;
        location.reload(); // Refresh the page after printing to restore the original content
    }
</script>
<script src="dashboard.js"></script>
</body>
</html>
