<?php
session_start(); // Start the session
include '../db_connection.php'; // Include your database connection

// Check if user is logged in
if (!isset($_SESSION['userID'])) {
    $_SESSION['error'] = "You must log in to access this page.";
    header("Location: ../login.php");
    exit();
}

// Handle form submission for acknowledging or false alarm
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $readingID = $_POST['readingID'];
    $comment = $_POST['comment'];
    $status = ($action == 'acknowledge') ? 2 : 3;
    $userID = $_SESSION['userID'];

    $updateQuery = "
        UPDATE sensor_reading 
        SET status = :status, comment = :comment, actionby = :actionby, actionbytimestamp = NOW() 
        WHERE readingID = :readingID
    ";
    $updateStmt = $pdo->prepare($updateQuery);
    $updateStmt->bindParam(':status', $status);
    $updateStmt->bindParam(':comment', $comment);
    $updateStmt->bindParam(':actionby', $userID);
    $updateStmt->bindParam(':readingID', $readingID);
    
    if ($updateStmt->execute()) {
        echo "<script>alert('Action recorded successfully.');</script>";
    } else {
        echo "<script>alert('Failed to record action.');</script>";
    }
}

$query = "
    SELECT 
        sr.readingID, 
        sr.deviceID, 
        sr.ppm, 
        sr.smoke_status, 
        sr.co_status, 
        sr.lpg_status, 
        sr.timestamp, 
        sr.status, 
        u.username AS actioned_by,
        sr.comment, 
        sr.actionbytimestamp
    FROM 
        sensor_reading sr
    JOIN 
        device d ON sr.deviceID = d.deviceID
    LEFT JOIN 
        `user` u ON sr.actionby = u.userID  
    WHERE 
        sr.deviceID = 'GS2' AND sr.status IN (1, 2, 3)
    ORDER BY 
        sr.timestamp DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute();
$gas_readings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ESP32-GasSensor 2 Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; display: flex; transition: background-color 0.3s, color 0.3s; }
        
        /* Dark Mode */
        body.dark { background-color: #1E1E2D; color: #fff; }
        body.dark .sidebar { background-color: #2B2D42; color: #D6D8E7; }
        body.dark .sidebar h2, body.dark .sidebar a { color: #D6D8E7; }
        body.dark .sidebar a.active, body.dark .sidebar a:hover { background-color: #F72585; color: #fff; }
        body.dark .table-container, body.dark .header-box { background: #3A3A5A; }
        
        /* Light Mode */
        body.light { background-color: #f0f0f0; color: #333; }
        body.light .sidebar { background-color: #e6e6e6; color: #333; }
        body.light .sidebar h2, body.light .sidebar a { color: #333; }
        body.light .sidebar a.active, body.light .sidebar a:hover { background-color: #4CAF50; color: #fff; }
        body.light .table-container, body.light .header-box { background: #f9f9f9; }

        .dashboard-container { display: flex; height: 100vh; width: 100%; }
        .sidebar { width: 240px; padding: 25px; display: flex; flex-direction: column; justify-content: space-between; }
        .sidebar h2 { font-size: 1.8em; margin-bottom: 20px; }
        .sidebar ul { list-style: none; padding-left: 0; }
        .sidebar li { margin-bottom: 15px; }
        .sidebar a { text-decoration: none; font-size: 1em; display: block; padding: 10px; border-radius: 5px; transition: background-color 0.2s; }

        .toggle-container { display: flex; align-items: center; gap: 10px; }
        .toggle-container label { font-size: 0.9em; }

        .main-dashboard { flex: 1; padding: 25px; overflow-y: auto; }
        .dashboard-header { display: flex; gap: 20px; margin-bottom: 20px; }
        .header-box { padding: 15px; border-radius: 8px; text-align: center; transition: background-color 0.3s; flex: 1; }

        .table-container { padding: 20px; border-radius: 10px; transition: background-color 0.3s; }
        table { width: 100%; color: inherit; margin-top: 10px; border-collapse: collapse; }
        table th, table td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }

        .filter-section { margin: 20px 0; color: #8D99AE; }
        .filter-section select { padding: 5px; background-color: inherit; color: inherit; border: 1px solid #ddd; border-radius: 5px; }

        .action-button { background-color: #FF6384; color: white; border: none; padding: 8px 12px; border-radius: 5px; cursor: pointer; transition: background-color 0.3s; font-size: 14px; }
        .action-button.false-alarm { background-color: #FFCE56; }
        .action-button:hover { opacity: 0.8; }
        .popup { display: none; position: fixed; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 999; }
        .popup-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 60%; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; }
        .close:hover, .close:focus { color: black; text-decoration: none; cursor: pointer; }
    </style>
</head>
<body class="light">
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div>
                <h2>ESP32-GasSensor 2 Dashboard</h2>
                <nav>
                    <ul>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="gs1.php">ESP32-GasSensor1</a></li>
                        <li><a href="gs2.php" class="active">ESP32-GasSensor2</a></li>
                        <li><a href="Reports.php">Reports</a></li>
                        <?php if ($_SESSION['userrole'] !== 'user'): ?>
                            <li><a href="manage_user.php">Manage User</a></li>
                            <li><a href="Threshold.php">Threshold Setup</a></li>
                            <li><a href="email_alert_report.php">Email Alert Report</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>

            <div class="toggle-container">
                <label for="theme-toggle">Dark Mode</label>
                <input type="checkbox" id="theme-toggle">
            </div>

            <div class="bottom-section">
                <h3>Welcome!</h3>
                <h4><?php echo htmlspecialchars($_SESSION['username']); ?></h4>
                <h4>Role: <?php echo htmlspecialchars($_SESSION['userrole']); ?></h4>
            </div>
            <div class="bottom-section">
                <h3>Language</h3>
                <li><a href="gs2_fr.php">French</a></li>
            </div>
            <div class="bottom-section">
                <a href="../logout.php">Logout</a>
            </div>
        </aside>
        
        <!-- Main Dashboard -->
        <main class="main-dashboard">
            <div class="dashboard-header">
                <div class="header-box">
                    <h3>Pending</h3>
                    <p><?php echo count(array_filter($gas_readings, fn($reading) => $reading['status'] == 1)); ?></p>
                </div>
                <div class="header-box">
                    <h3>Acknowledge</h3>
                    <p><?php echo count(array_filter($gas_readings, fn($reading) => $reading['status'] == 2)); ?></p>
                </div>
                <div class="header-box">
                    <h3>False Alarm</h3>
                    <p><?php echo count(array_filter($gas_readings, fn($reading) => $reading['status'] == 3)); ?></p>
                </div>
            </div>

            <!-- Filter Section with Dropdown -->
            <div class="filter-section">
                <label for="statusFilter">Filter by Status:</label>
                <select id="statusFilter" onchange="filterStatus(this.value)">
                    <option value="">All</option>
                    <option value="Pending">Pending</option>
                    <option value="Acknowledged">Acknowledged</option>
                    <option value="False Alarm">False Alarm</option>
                </select>
            </div>

            <!-- Gas Readings Table -->
            <div class="table-container">
                <h3>Gas Readings</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Gas Level (ppm)</th>
                            <th>Gas Detected</th>
                            <th>Timestamp</th>
                            <th>Alert Status</th>
                            <th>Actioned By</th>
                            <th>Response Time</th>
                            <th>Comment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="gasReadingsTable">
                        <?php foreach ($gas_readings as $reading): ?>
                            <tr data-status="<?php echo strtolower($reading["status"]); ?>">
                                <td><?php echo $reading["ppm"]; ?></td>
                                <td>
                                    <?php 
                                    if ($reading["smoke_status"] == 1) {
                                        echo "Smoke Detected";
                                    } elseif ($reading["co_status"] == 1) {
                                        echo "CO Detected";
                                    } elseif ($reading["lpg_status"] == 1) {
                                        echo "LPG Detected";
                                    } else {
                                        echo "No Gas Detected";
                                    }
                                    ?>
                                </td>
                                <td><?php echo $reading["timestamp"]; ?></td>
                                <td class="<?php echo 'status-' . ($reading['status'] == 1 ? 'pending' : ($reading['status'] == 2 ? 'acknowledged' : 'false-alarm')); ?>">
                                    <?php 
                                    if ($reading["status"] == 1) {
                                        echo "Pending";
                                    } elseif ($reading["status"] == 2) {
                                        echo "Acknowledged";
                                    } elseif ($reading["status"] == 3) {
                                        echo "False Alarm";
                                    }
                                    ?>
                                </td>
                                <td><?php echo $reading["actioned_by"]; ?></td>
                                <td><?php echo $reading["actionbytimestamp"]; ?></td>
                                <td><?php echo $reading["comment"] ? $reading["comment"] : 'No comment'; ?></td>
                                <td>
                                    <button class="action-button acknowledge-button" onclick="openPopup('<?php echo $reading['readingID']; ?>', 'acknowledge')">Acknowledge</button> 
                                    <button class="action-button false-alarm-button" onclick="openPopup('<?php echo $reading['readingID']; ?>', 'false_alarm')">False Alarm</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <div id="popup" class="popup">
        <div class="popup-content">
            <span class="close" onclick="closePopup()">&times;</span>
            <h2 id="popupTitle">Enter Comment</h2>
            <form id="popupForm" method="POST">
                <input type="hidden" name="readingID" id="readingID">
                <input type="hidden" name="action" id="action">
                <input type="text" name="comment" placeholder="Enter comment" required>
                <button type="submit">Submit</button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('theme-toggle').addEventListener('change', function() {
            document.body.classList.toggle('dark', this.checked);
            document.body.classList.toggle('light', !this.checked);
        });

        function filterStatus(status) {
            const rows = document.querySelectorAll("#gasReadingsTable tr");
            rows.forEach(row => {
                const rowStatus = row.getAttribute("data-status");
                if (status === "" || (status === "Pending" && rowStatus == 1) || 
                    (status === "Acknowledged" && rowStatus == 2) || 
                    (status === "False Alarm" && rowStatus == 3)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }

        function openPopup(readingID, action) {
            document.getElementById('readingID').value = readingID;
            document.getElementById('action').value = action;
            document.getElementById('popup').style.display = 'block';
        }

        function closePopup() {
            document.getElementById('popup').style.display = 'none';
        }
    </script>
</body>
</html>
