<?php
// false_alarm.php

include('db_connection.php');

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reading_id = $_POST['reading_id'];

    // Update the alert status to "False Alarm"
    $query = "UPDATE gas_readings SET alert_status = 2 WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $reading_id);
    
    if ($stmt->execute()) {
        echo json_encode(["message" => "False alarm recorded successfully.", "status" => 1]);
    } else {
        echo json_encode(["message" => "Failed to record the false alarm.", "status" => 0]);
    }

    $stmt->close();
    $conn->close();
}
?>
