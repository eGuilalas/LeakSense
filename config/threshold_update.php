<?php
include 'db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $device_ids = $_POST['device_id'];
    $gas_thresholds = $_POST['gas_threshold'];
    $smoke_thresholds = $_POST['smoke_threshold'];
    $co_thresholds = $_POST['co_threshold'];
    $lpg_thresholds = $_POST['lpg_threshold'];

    foreach ($device_ids as $index => $device_id) {
        $gas_threshold = $gas_thresholds[$index];
        $smoke_threshold = $smoke_thresholds[$index];
        $co_threshold = $co_thresholds[$index];
        $lpg_threshold = $lpg_thresholds[$index];

        $stmt = $conn->prepare("UPDATE thresholds SET gas_threshold = ?, smoke_threshold = ?, co_threshold = ?, lpg_threshold = ? WHERE device_id = ?");
        $stmt->bind_param("dddds", $gas_threshold, $smoke_threshold, $co_threshold, $lpg_threshold, $device_id);
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode(["message" => "Thresholds updated successfully."]);
} else {
    echo json_encode(["message" => "Invalid request method."]);
}

$conn->close();
?>
