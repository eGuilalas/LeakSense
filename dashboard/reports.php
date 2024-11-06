<?php
session_start();
include '../db_connection.php'; // Include your database connection

// Check if user is logged in
if (!isset($_SESSION['userID'])) {
    $_SESSION['error'] = "You must log in to access this page.";
    header("Location: ../login.php");
    exit();
}

// Initialize filter parameters
$deviceID = $_GET['deviceID'] ?? '';
$gasType = $_GET['gasType'] ?? '';
$startDate = $_GET['startDate'] ?? '';
$startTime = $_GET['startTime'] ?? '';
$endDate = $_GET['endDate'] ?? '';
$endTime = $_GET['endTime'] ?? '';
$alertStatus = $_GET['alertStatus'] ?? '';
$acknowledgedBy = $_GET['acknowledgedBy'] ?? '';

// Construct the base query
$query = "
    SELECT 
        sr.readingID AS ID,
        sr.deviceID AS 'Device ID',
        sr.ppm AS 'Gas Level',
        CASE 
            WHEN sr.smoke_status = 1 THEN 'Smoke'
            WHEN sr.co_status = 1 THEN 'CO'
            WHEN sr.lpg_status = 1 THEN 'LPG'
            ELSE 'No Gas'
        END AS 'Gas Detected',
        CASE 
            WHEN sr.status = 1 THEN 'Pending'
            WHEN sr.status = 2 THEN 'Acknowledged'
            WHEN sr.status = 3 THEN 'False Alarm'
            ELSE 'No Status'
        END AS 'Alert Status',
        u.username AS 'Acknowledged By',
        sr.actionbytimestamp AS 'Response Time',
        sr.comment AS 'Comments',
        sr.timestamp AS 'Timestamp'
    FROM 
        sensor_reading sr
    LEFT JOIN 
        user u ON sr.actionby = u.userID
    WHERE 1=1"; // Base condition to append filters

// Append filters based on user input
if ($deviceID) {
    $query .= " AND sr.deviceID = :deviceID";
}
if ($gasType) {
    $query .= " AND (sr.smoke_status = 1 OR sr.co_status = 1 OR sr.lpg_status = 1)";
}
if ($startDate && $startTime) {
    $query .= " AND sr.timestamp >= :startDateTime";
}
if ($endDate && $endTime) {
    $query .= " AND sr.timestamp <= :endDateTime";
}
if ($alertStatus) {
    switch ($alertStatus) {
        case 'Pending':
            $query .= " AND sr.status = 1";
            break;
        case 'Acknowledged':
            $query .= " AND sr.status = 2";
            break;
        case 'False Alarm':
            $query .= " AND sr.status = 3";
            break;
    }
}
if ($acknowledgedBy) {
    $query .= " AND u.username = :acknowledgedBy";
}

$query .= " ORDER BY sr.timestamp DESC";

$stmt = $pdo->prepare($query);

// Bind parameters if they were set
if ($deviceID) {
    $stmt->bindParam(':deviceID', $deviceID);
}
if ($startDate && $startTime) {
    $startDateTime = $startDate . ' ' . $startTime;
    $stmt->bindParam(':startDateTime', $startDateTime);
}
if ($endDate && $endTime) {
    $endDateTime = $endDate . ' ' . $endTime;
    $stmt->bindParam(':endDateTime', $endDateTime);
}
if ($acknowledgedBy) {
    $stmt->bindParam(':acknowledgedBy', $acknowledgedBy);
}

$stmt->execute();
$report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        .sidebar a { text-decoration: none; color: #D6D8E7; font-size: 1em; display: block; padding: 10px; border-radius: 5px; transition: background-color 0.2s; }
        .sidebar a:hover, .sidebar a.active { background-color: #F72585; color: #fff; }
        
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
        .button-group button, .button-group a {
            padding: 8px 12px;
            background-color: #F72585;
            border: none;
            border-radius: 5px;
            color: #fff;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
        }
        .button-group button:hover, .button-group a:hover { background-color: #FF4571; }

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
                <h3><?php echo htmlspecialchars($_SESSION['username']); ?></h3>
                <h4>Role: <?php echo htmlspecialchars($_SESSION['userrole']); ?></h4>
            </div>
            <div class="bottom-section">
                <h3>Language</h3>
                <h5>ENG - FR</h5>
            </div>
            <div class="bottom-section">
                <a href="../logout.php">Logout</a>
            </div>
        </aside>

        <main class="main-dashboard">
            <!-- Filter Section -->
            <div class="filter-section">
                <h3>Filter Reports</h3>
                <div class="filter-group">
                    <label>Device ID:</label>
                    <select id="deviceID">
                        <option value="">All Devices</option>
                        <option value="GS1">ESP32-GasSensor1</option>
                        <option value="GS2">ESP32-GasSensor2</option>
                    </select>

                    <label>Gas Type:</label>
                    <select id="gasType">
                        <option value="">All Types</option>
                        <option value="Smoke">Smoke</option>
                        <option value="CO">CO</option>
                        <option value="LPG">LPG</option>
                    </select>

                    <label>Start Date:</label>
                    <input type="date" id="startDate">

                    <label>Start Time:</label>
                    <input type="time" id="startTime">

                    <label>End Date:</label>
                    <input type="date" id="endDate">

                    <label>End Time:</label>
                    <input type="time" id="endTime">

                    <label>Alert Status:</label>
                    <select id="alertStatus">
                        <option value="">Any</option>
                        <option value="Pending">Pending</option>
                        <option value="Acknowledged">Acknowledged</option>
                        <option value="False Alarm">False Alarm</option>
                    </select>

                    <label>Acknowledged By:</label>
                    <input type="text" id="acknowledgedBy" placeholder="Enter username">
                </div>
                <div class="button-group">
                    <button id="applyFilters">Apply Filters</button>
                    <button id="resetFilters">Reset Filters</button>
                    <button id="printReport">Print Report</button>
                    <a href="../api/export_csv.php?deviceID=<?php echo urlencode($deviceID); ?>&gasType=<?php echo urlencode($gasType); ?>&startDate=<?php echo urlencode($startDate); ?>&startTime=<?php echo urlencode($startTime); ?>&endDate=<?php echo urlencode($endDate); ?>&endTime=<?php echo urlencode($endTime); ?>&alertStatus=<?php echo urlencode($alertStatus); ?>&acknowledgedBy=<?php echo urlencode($acknowledgedBy); ?>" class="export-button">Export to CSV</a>
                </div>
            </div>

            <!-- Report Table Section -->
            <div class="table-container">
                <h3>Report Table</h3>
                <table>
                    <thead>
                        <tr>
                            <!-- <th>ID</th> -->
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
                        <?php foreach ($report_data as $row): ?>
                            <tr>
                                <!-- <td><?php echo $row["ID"]; ?></td> -->
                                <td><?php echo $row["Device ID"]; ?></td>
                                <td><?php echo $row["Gas Level"]; ?></td>
                                <td><?php echo $row["Gas Detected"]; ?></td>
                                <td class="<?php echo strtolower(str_replace(' ', '-', 'status-' . $row["Alert Status"])); ?>">
                                    <?php echo $row["Alert Status"]; ?>
                                </td>
                                <td><?php echo $row["Acknowledged By"]; ?></td>
                                <td><?php echo $row["Response Time"] ? date("Y-m-d H:i:s", strtotime($row["Response Time"])) : 'N/A'; ?></td>
                                <td><?php echo $row["Comments"]; ?></td>
                                <td><?php echo $row["Timestamp"]; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <script>
        // Function to apply filters
        document.getElementById('applyFilters').addEventListener('click', function() {
            const deviceID = document.getElementById('deviceID').value;
            const gasType = document.getElementById('gasType').value;
            const startDate = document.getElementById('startDate').value;
            const startTime = document.getElementById('startTime').value;
            const endDate = document.getElementById('endDate').value;
            const endTime = document.getElementById('endTime').value;
            const alertStatus = document.getElementById('alertStatus').value;
            const acknowledgedBy = document.getElementById('acknowledgedBy').value;

            // Create a query string with the filters
            const queryString = `?deviceID=${deviceID}&gasType=${gasType}&startDate=${startDate}&startTime=${startTime}&endDate=${endDate}&endTime=${endTime}&alertStatus=${alertStatus}&acknowledgedBy=${acknowledgedBy}`;

            // Redirect to the same page with query parameters
            window.location.href = window.location.pathname + queryString;
        });

        // Reset filters
        document.getElementById('resetFilters').addEventListener('click', function() {
            document.getElementById('deviceID').value = '';
            document.getElementById('gasType').value = '';
            document.getElementById('startDate').value = '';
            document.getElementById('startTime').value = '';
            document.getElementById('endDate').value = '';
            document.getElementById('endTime').value = '';
            document.getElementById('alertStatus').value = '';
            document.getElementById('acknowledgedBy').value = '';
            // Reset URL to clear filters
            window.history.pushState({}, document.title, window.location.pathname);
        });

        // Print report
        document.getElementById('printReport').addEventListener('click', function() {
            const printContent = document.querySelector('.table-container').innerHTML;
            const originalContent = document.body.innerHTML;

            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContent; // Restore original content after print
        });

        // Apply filter on pressing enter key in Acknowledged By input
        document.getElementById('acknowledgedBy').addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault(); // Prevent default form submission
                document.getElementById('applyFilters').click(); // Trigger apply filters
            }
        });
    </script>
</body>
</html>
