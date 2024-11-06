<?php
include '../db_connection.php';

$response = [];

try {
    $sql = "SELECT deviceID, ppm, smoke_status, co_status, lpg_status, timestamp FROM sensor_reading ORDER BY timestamp DESC LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Determine overall gas detection status
        $isGasDetected = $row["smoke_status"] == 1 || $row["co_status"] == 1 || $row["lpg_status"] == 1;
        $statusText = $isGasDetected ? "Gas Detected" : "No Gas Detected";

        $response[] = [
            "deviceID" => $row["deviceID"],
            "ppm" => floatval($row["ppm"]),
            "status" => $statusText,
            "timestamp" => $row["timestamp"]
        ];
    }
} catch (PDOException $e) {
    // Log error and return response
    error_log("Error in get_live_table_data: " . $e->getMessage(), 3, "../error_log.txt");
}

header('Content-Type: application/json');
echo json_encode($response);
?>
