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

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="report.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Write header row
fputcsv($output, array('ID', 'Device ID', 'Gas Level (ppm)', 'Gas Detected', 'Alert Status', 'Acknowledged By', 'Response Time', 'Comments', 'Timestamp'));

// Write data rows
foreach ($report_data as $row) {
    fputcsv($output, $row);
}

// Close output stream
fclose($output);
exit();
?>
