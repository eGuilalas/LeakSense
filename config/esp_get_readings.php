<?php
// esp_get_readings.php

include('db_connection.php');

// Query to get all readings from the gas_readings table
$query = "SELECT * FROM gas_readings";
$result = $conn->query($query);

$readings = [];
while ($row = $result->fetch_assoc()) {
    $readings[] = $row;
}

// Return the result as JSON
header('Content-Type: application/json');
echo json_encode($readings);
?>
