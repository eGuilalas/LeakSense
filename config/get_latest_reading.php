<?php
// get_latest_reading.php

header('Content-Type: application/json');

include('db_connection.php'); // Ensure the path is correct

$device_id = isset($_GET['device_id']) ? $_GET['device_id'] : '';

if ($device_id) {
    $query = "SELECT * FROM gas_readings WHERE device_id = ? ORDER BY timestamp DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("s", $device_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            echo json_encode($row);
        } else {
            echo json_encode([]);
        }

        $stmt->close();
    } else {
        echo json_encode(['error' => 'Failed to prepare statement.']);
    }
} else {
    echo json_encode(['error' => 'No device_id provided.']);
}

$conn->close();
?>
