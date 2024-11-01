<?php
require 'db_connection.php'; // Include your database connection script

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get data from POST request
$device_id = $_POST['device_id'] ?? '';
$gas_level = $_POST['gas_level'] ?? 0.0;
$gas_type = $_POST['gas_type'] ?? '';
$smoke_status = $_POST['smoke_status'] ?? 0; // Ensure these values are integers (0 or 1)
$co_status = $_POST['co_status'] ?? 0; 
$lpg_status = $_POST['lpg_status'] ?? 0; 

// Determine overall status based on smoke, CO, and LPG status
$status = ($smoke_status || $co_status || $lpg_status) ? 1 : 0;

// Prepare and bind the SQL statement
$stmt = $conn->prepare("INSERT INTO gas_readings (device_id, gas_type, gas_level, smoke_status, co_status, lpg_status, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssddiii", $device_id, $gas_type, $gas_level, $smoke_status, $co_status, $lpg_status, $status);

// Execute the statement
if ($stmt->execute()) {
    echo "New record created successfully";
} else {
    echo "Error: " . $stmt->error;
}

// Close connections
$stmt->close();
$conn->close();
?>
