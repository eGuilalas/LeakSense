<?php
// Include database connection
include 'db_connection.php';

header('Content-Type: application/json');

// Check if the device_id is provided
if (isset($_GET['device_id'])) {
    $device_id = $_GET['device_id'];

    // Prepare and execute the SQL statement to fetch thresholds
    $sql = "SELECT gas_threshold, smoke_threshold, co_threshold, lpg_threshold FROM thresholds WHERE device_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $device_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $thresholds = $result->fetch_assoc();
        echo json_encode([
            "status" => "success",
            "thresholds" => $thresholds
        ]);
    } else {
        // No thresholds found for the device_id
        echo json_encode([
            "status" => "error",
            "message" => "No thresholds found for this device."
        ]);
    }

    $stmt->close();
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Device ID not provided."
    ]);
}

$conn->close();
?>
