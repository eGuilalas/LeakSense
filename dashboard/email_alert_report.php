<?php
session_start();
include '../db_connection.php'; // Include your database connection

// Check if user is logged in
if (!isset($_SESSION['userID'])) {
    // Redirect to login page
    $_SESSION['error'] = "You must log in to access this page.";
    header("Location: ../login.php");
    exit();
}

// Fetch email alert reports from the database
$query = "
    SELECT 
        deviceID,
        readingID,
        email,
        gastype,
        gaslevel,
        thresholdlevel,
        timestamp,
        status
    FROM 
        alert
    ORDER BY 
        timestamp DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute();
$email_alert_reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Alert Report - Leaksense Dashboard</title>
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
        .bottom-section { border-top: 1px solid #444; padding-top: 20px; color: #D6D8E7; text-align: left; }
        .bottom-section h3, .bottom-section h5 { color: #8D99AE; margin-bottom: 10px; }
        .bottom-section a { color: #F72585; text-decoration: none; font-weight: bold; }

        .main-dashboard { flex: 1; padding: 20px; overflow-y: auto; }
        .table-container { background: #3A3A5A; padding: 20px; border-radius: 10px; }
        table { width: 100%; color: #D6D8E7; margin-top: 10px; border-collapse: collapse; }
        table th, table td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
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
                        <li><a href="gs1.php">ESP32-GasSensor 1</a></li>
                        <li><a href="gs2.php">ESP32-GasSensor 2</a></li>
                        <li><a href="Reports.php">Reports</a></li>
                        <li><a href="manage_user.php">Manage User</a></li>
                        <li><a href="Threshold.php">Threshold Setup</a></li>
                        <li><a href="email_alert_report.php" class="active">Email Alert Report</a></li>
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
                <li><a href="email_alert_report_fr.php">French</a></li>
            </div>
            <div class="bottom-section">
                <a href="../logout.php">Logout</a>
            </div>
        </aside>
        
        <!-- Main Dashboard -->
        <main class="main-dashboard">
            <div class="table-container">
                <h3>Email Alert Report</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Device ID</th>
                            <th>Reading ID</th>
                            <th>Email</th>
                            <th>Gas Type</th>
                            <th>Gas Level (ppm)</th>
                            <th>Threshold Level</th>
                            <th>Timestamp</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($email_alert_reports as $report): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($report['deviceID']); ?></td>
                                <td><?php echo htmlspecialchars($report['readingID']); ?></td>
                                <td><?php echo htmlspecialchars($report['email']); ?></td>
                                <td><?php echo htmlspecialchars($report['gastype']); ?></td>
                                <td><?php echo htmlspecialchars($report['gaslevel']); ?></td>
                                <td><?php echo htmlspecialchars($report['thresholdlevel']); ?></td>
                                <td><?php echo htmlspecialchars($report['timestamp']); ?></td>
                                <td><?php echo htmlspecialchars($report['status'] == 1 ? 'Sent Successfully' : 'Failed'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
