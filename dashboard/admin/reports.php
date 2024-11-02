<?php
// reports.php

session_start();
if (!isset($_SESSION['loggedin']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')) {
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
    'alert_status' => ''
];

// Handle filter submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_filters'])) {
    $filters['device_id'] = $_POST['device_id'] ?? '';
    $filters['gas_type'] = $_POST['gas_type'] ?? '';
    $filters['start_date'] = $_POST['start_date'] ?? '';
    $filters['start_time'] = $_POST['start_time'] ?? '';
    $filters['end_date'] = $_POST['end_date'] ?? '';
    $filters['end_time'] = $_POST['end_time'] ?? '';
    $filters['alert_status'] = $_POST['alert_status'] ?? '';
}

// Construct SQL query with filters
$sql = "SELECT * FROM gas_readings WHERE 1=1";
$params = [];
$types = '';

// Apply filters based on user input
if (!empty($filters['device_id'])) {
    $sql .= " AND device_id = ?";
    $params[] = $filters['device_id'];
    $types .= 's';
}

if (!empty($filters['gas_type'])) {
    $sql .= " AND gas_type = ?";
    $params[] = $filters['gas_type'];
    $types .= 's';
}

if (!empty($filters['start_date']) && !empty($filters['start_time'])) {
    $sql .= " AND timestamp >= ?";
    $params[] = $filters['start_date'] . ' ' . $filters['start_time'];
    $types .= 's';
} elseif (!empty($filters['start_date'])) {
    $sql .= " AND timestamp >= ?";
    $params[] = $filters['start_date'] . ' 00:00:00';
    $types .= 's';
}

if (!empty($filters['end_date']) && !empty($filters['end_time'])) {
    $sql .= " AND timestamp <= ?";
    $params[] = $filters['end_date'] . ' ' . $filters['end_time'];
    $types .= 's';
} elseif (!empty($filters['end_date'])) {
    $sql .= " AND timestamp <= ?";
    $params[] = $filters['end_date'] . ' 23:59:59';
    $types .= 's';
}

if ($filters['alert_status'] !== '') {
    $sql .= " AND alert_status = ?";
    $params[] = $filters['alert_status'];
    $types .= 'i';
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
        .report-container { padding: 20px; }
        .filters, .report-table { margin: 20px 0; }
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
                <h2>Logout</h2>
                <a href="../../logout.php"><span class="icon">🚪</span>Logout</a>
            </div>
    </div>

    <div class="main-content">
        <h1>Gas Readings Report</h1>
        
        <div class="report-container">
            <form method="post" class="filters">
                <label>Device ID:
                    <input type="text" name="device_id" value="<?= htmlspecialchars($filters['device_id']) ?>">
                </label>
                <label>Gas Type:
                    <input type="text" name="gas_type" value="<?= htmlspecialchars($filters['gas_type']) ?>">
                </label>
                <label>Start Date:
                    <input type="date" name="start_date" value="<?= htmlspecialchars($filters['start_date']) ?>">
                </label>
                <label>Start Time:
                    <input type="time" name="start_time" value="<?= htmlspecialchars($filters['start_time']) ?>">
                </label>
                <label>End Date:
                    <input type="date" name="end_date" value="<?= htmlspecialchars($filters['end_date']) ?>">
                </label>
                <label>End Time:
                    <input type="time" name="end_time" value="<?= htmlspecialchars($filters['end_time']) ?>">
                </label>
                <label>Alert Status:
                    <select name="alert_status">
                        <option value="">Any</option>
                        <option value="1" <?= $filters['alert_status'] === '1' ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= $filters['alert_status'] === '0' ? 'selected' : '' ?>>Inactive</option>
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
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($readings as $reading): ?>
                            <tr>
                                <td><?= htmlspecialchars($reading['id']) ?></td>
                                <td><?= htmlspecialchars($reading['device_id']) ?></td>
                                <td><?= htmlspecialchars($reading['gas_level']) ?></td>
                                <td><?= htmlspecialchars($reading['gas_type']) ?></td>
                                <td><?= $reading['smoke_status'] ? 'Detected' : 'Not Detected' ?></td>
                                <td><?= $reading['co_status'] ? 'Detected' : 'Not Detected' ?></td>
                                <td><?= $reading['lpg_status'] ? 'Detected' : 'Not Detected' ?></td>
                                <td><?= $reading['alert_status'] ? 'Active' : 'Inactive' ?></td>
                                <td><?= htmlspecialchars($reading['timestamp']) ?></td>
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
