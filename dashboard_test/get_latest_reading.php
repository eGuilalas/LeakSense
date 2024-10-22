<?php
// Database connection
require '../db_connection.php';

$device_id = $_GET['device_id'] ?? '';

if ($device_id) {
    // Fetch latest reading for the specified device
    $sql = "SELECT device_id, gas_level, smoke_status, co_status, lpg_status, timestamp FROM gas_readings WHERE device_id = :device_id ORDER BY timestamp DESC LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['device_id' => $device_id]);
    $latest_reading = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if any reading was found
    if ($latest_reading) {
        // Debugging: output the values directly
        error_log(print_r($latest_reading, true)); // Log the latest reading to see the exact values

        // Return the data as JSON
        header('Content-Type: application/json');
        echo json_encode($latest_reading);
    } else {
        // No readings found for the device
        header('Content-Type: application/json');
        echo json_encode(['error' => 'No readings found for the specified device.']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Device ID is required']);
}
?>
