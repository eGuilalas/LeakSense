<?php
// Database connection
require '../db_connection.php';

// Fetch the last 10 readings ordered by timestamp
$sql = "SELECT device_id, gas_level, smoke_status, co_status, lpg_status, timestamp FROM gas_readings ORDER BY timestamp DESC LIMIT 10";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$readings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if readings were found
if ($readings) {
    // Return the data as JSON
    header('Content-Type: application/json');
    echo json_encode($readings);
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No readings found.']);
}
?>