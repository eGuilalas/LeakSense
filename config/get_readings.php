<?php
// get_readings.php

header('Content-Type: application/json');

include('db_connection.php'); // Ensure the path is correct

// Query to get all readings (modify as necessary)
$query = "SELECT * FROM gas_readings ORDER BY timestamp DESC LIMIT 50";
$result = $conn->query($query);

$readings = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $readings[] = $row;
    }
} else {
    echo json_encode(['error' => 'Failed to execute query.']);
    exit();
}

echo json_encode($readings);

$conn->close();
?>
