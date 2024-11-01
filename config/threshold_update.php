<?php
include 'db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $device_ids = $_POST['device_id'];
    $smoke_thresholds = $_POST['smoke_threshold'];
    $co_thresholds = $_POST['co_threshold'];
    $lpg_thresholds = $_POST['lpg_threshold'];

    if (is_array($device_ids) && is_array($smoke_thresholds) && is_array($co_thresholds) && is_array($lpg_thresholds)) {
        foreach ($device_ids as $index => $device_id) {
            $smoke_threshold = (float)$smoke_thresholds[$index];
            $co_threshold = (float)$co_thresholds[$index];
            $lpg_threshold = (float)$lpg_thresholds[$index];

            // Prepare and execute the update statement
            $stmt = $conn->prepare("UPDATE thresholds SET smoke_threshold = ?, co_threshold = ?, lpg_threshold = ? WHERE device_id = ?");
            $stmt->bind_param("ddds", $smoke_threshold, $co_threshold, $lpg_threshold, $device_id);
            $stmt->execute();
            $stmt->close();
        }

        echo json_encode(["message" => "Thresholds updated successfully."]);
    } else {
        echo json_encode(["message" => "Invalid input data."]);
    }
} else {
    echo json_encode(["message" => "Invalid request method."]);
}

$conn->close();
?>
