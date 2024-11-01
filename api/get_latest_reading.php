<?php
// Database connection
require 'db_connection.php';

$device_id = $_GET['device_id'] ?? '';

if ($device_id) {
    // Fetch latest reading for the specified device
    $sql = "SELECT * FROM gas_readings WHERE device_id = :device_id ORDER BY timestamp DESC LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['device_id' => $device_id]);
    $latest_reading = $stmt->fetch(PDO::FETCH_ASSOC);

    // Return the data as JSON
    header('Content-Type: application/json');
    echo json_encode($latest_reading);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Device ID is required']);
}
?>
