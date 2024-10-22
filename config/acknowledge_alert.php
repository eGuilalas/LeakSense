<?php
// acknowledge_alert.php

include('db_connection.php');

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reading_id = $_POST['reading_id'];

    // Update the alert status to "Acknowledged"
    $query = "UPDATE gas_readings SET alert_status = 1 WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $reading_id);
    
    if ($stmt->execute()) {
        echo json_encode(["message" => "Alert acknowledged successfully.", "status" => 1]);
    } else {
        echo json_encode(["message" => "Failed to acknowledge the alert.", "status" => 0]);
    }

    $stmt->close();
    $conn->close();
}
?>
