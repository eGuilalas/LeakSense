<?php
include '../db_connection.php'; // Ensure `pdo` is available

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect POST data
    $device_id = $_POST['device_id'];
    $gas_level = $_POST['gas_level'];
    $gas_type = $_POST['gas_type'];
    $smoke_status = $_POST['smoke_status'];
    $co_status = $_POST['co_status'];
    $lpg_status = $_POST['lpg_status'];
    
    // Determine the status based on smoke, CO, and LPG status
    $status = ($smoke_status == 1 || $co_status == 1 || $lpg_status == 1) ? 1 : 0;

    // Check if deviceID exists in the device table
    $deviceCheckStmt = $pdo->prepare("SELECT COUNT(*) FROM device WHERE deviceID = :device_id");
    $deviceCheckStmt->bindParam(':device_id', $device_id);
    $deviceCheckStmt->execute();
    $deviceExists = $deviceCheckStmt->fetchColumn();

    if ($deviceExists) {
        // Insert the reading into the sensor_reading table with calculated status
        $stmt = $pdo->prepare("INSERT INTO sensor_reading (deviceID, ppm, smoke_status, co_status, lpg_status, status, timestamp) 
                               VALUES (:device_id, :gas_level, :smoke_status, :co_status, :lpg_status, :status, NOW())");
        $stmt->bindParam(':device_id', $device_id);
        $stmt->bindParam(':gas_level', $gas_level);
        $stmt->bindParam(':smoke_status', $smoke_status);
        $stmt->bindParam(':co_status', $co_status);
        $stmt->bindParam(':lpg_status', $lpg_status);
        $stmt->bindParam(':status', $status);

        if ($stmt->execute()) {
            echo "Reading saved successfully";
        } else {
            echo "Error saving reading";
        }
    } else {
        echo "Invalid deviceID";
    }
} else {
    echo "Invalid request";
}
?>
