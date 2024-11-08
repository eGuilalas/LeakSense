<?php
session_start(); // Start the session
include '../db_connection.php'; // Include your database connection

// Check if user is logged in
if (!isset($_SESSION['userID'])) {
    // Redirect to login page
    $_SESSION['error'] = "You must log in to access this page.";
    header("Location: ../login.php");
    exit();
}

// Handle form submission for acknowledging or false alarm
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $readingID = $_POST['readingID'];
    $comment = $_POST['comment'];

    // Determine the status based on the action
    $status = ($action == 'acknowledge') ? 2 : 3; // 2 for Acknowledged, 3 for False Alarm

    // Fetch the logged-in user's ID from the session
    $userID = $_SESSION['userID']; // Assuming userID is stored in the session on login

    // Update the database with the new status, comment, and user action
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

// Fetch gas readings from the database with specified alert status for GS1
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
        sr.actionbytimestamp -- Select the actual actionbytimestamp
    FROM 
        sensor_reading sr
    JOIN 
        device d ON sr.deviceID = d.deviceID
    LEFT JOIN 
        `user` u ON sr.actionby = u.userID  
    WHERE 
        sr.deviceID = 'GS1' AND sr.status IN (1, 2, 3)
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
    <title>ESP32-GasSensor 1 Dashboard</title>
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
        .dashboard-header { display: flex; gap: 20px; margin-bottom: 20px; }
        .header-box { background: #3A3A5A; padding: 20px; border-radius: 10px; flex: 1; }
        .header-box h3 { color: #8D99AE; }
        
        .filter-section { margin: 20px 0; color: #8D99AE; }
        .filter-section select { padding: 5px; background-color: #3A3A5A; color: #D6D8E7; border: none; border-radius: 5px; }

        .table-container { background: #3A3A5A; padding: 20px; border-radius: 10px; }
        table { width: 100%; color: #D6D8E7; margin-top: 10px; border-collapse: collapse; }
        table th, table td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        .status-pending { color: #36A2EB; font-weight: bold; }
        .status-acknowledged { color: #FF6384; font-weight: bold; }
        .status-false-alarm { color: #FFCE56; font-weight: bold; }
        .action-button {
            background-color: #FF6384; /* Acknowledge button color */
            color: white; /* Text color */
            border: none; /* No border */
            padding: 10px 15px; /* Padding for size */
            border-radius: 5px; /* Rounded corners */
            cursor: pointer; /* Pointer cursor on hover */
            transition: background-color 0.3s; /* Smooth background transition */
            font-size: 14px; /* Font size */
        }
        .action-button.false-alarm {
            background-color: #FFCE56; /* False Alarm button color */
        }
        .action-button:hover {
            opacity: 0.8; /* Slightly dim the button on hover */
        }
        .popup {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            background-color: rgba(0, 0, 0, 0.5); /* Black background with opacity */
            z-index: 999; /* Sit on top */
        }
        .popup-content {
            background-color: #fefefe;
            margin: 15% auto; /* 15% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            width: 80%; /* Could be more or less, depending on screen size */
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div>
                <h2>ESP32-GasSensor 1 Dashboard</h2>
                <nav>
                    <ul>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="gs1.php" class="active">ESP32-GasSensor1</a></li>
                        <li><a href="gs2.php">ESP32-GasSensor2</a></li>
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
                <li><a href="gs1_fr.php">French</a></li>
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
                                <td><?php echo $reading["actionbytimestamp"]; ?></td> <!-- Show actionbytimestamp -->
                                <td><?php echo $reading["comment"] ? $reading["comment"] : 'No comment'; ?></td> <!-- Display comment -->
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
