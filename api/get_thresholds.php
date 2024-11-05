<?php
include '../db_connection.php'; // Ensure `pdo` is available

// Check if device_id parameter is provided
if (isset($_GET['device_id'])) {
    $deviceID = $_GET['device_id'];

    // Prepare and execute query to fetch thresholds
    $sql = "SELECT smoke_threshold, co_threshold, lpg_threshold FROM thresholds WHERE deviceID = :device_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':device_id', $deviceID, PDO::PARAM_STR);

    if ($stmt->execute()) {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            // Build the response
            $thresholds = [
                'device_id' => $deviceID,
                'smoke_threshold' => (float) $result['smoke_threshold'],
                'co_threshold' => (float) $result['co_threshold'],
                'lpg_threshold' => (float) $result['lpg_threshold']
            ];

            echo json_encode(['thresholds' => [$thresholds]]);
        } else {
            // No thresholds found for this device ID
            echo json_encode(['error' => 'No thresholds found for this device ID']);
        }
    } else {
        // Error executing query
        echo json_encode(['error' => 'Failed to fetch thresholds']);
    }
} else {
    // Device ID not provided in GET request
    echo json_encode(['error' => 'Device ID not provided']);
}
?>
