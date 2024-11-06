<?php
// Include the database connection file
include '../db_connection.php';

// Initialize the response array for each sensor
$response = [
    "ESP32-GasSensor1" => [],
    "ESP32-GasSensor2" => []
];

try {
    // Prepare and execute the SQL query to retrieve latest sensor readings
    $stmt = $pdo->prepare("SELECT deviceID, ppm, timestamp 
                           FROM sensor_reading 
                           WHERE deviceID IN ('GS1', 'GS2') 
                           ORDER BY timestamp DESC 
                           LIMIT 10");
    $stmt->execute();

    // Fetch the data and format it into the response structure
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $deviceData = [
            "time" => $row["timestamp"],
            "ppm" => floatval($row["ppm"]),
        ];

        // Append data to the appropriate device in the response array
        if ($row["deviceID"] == "GS1") {
            $response["ESP32-GasSensor1"][] = $deviceData;
        } elseif ($row["deviceID"] == "GS2") {
            $response["ESP32-GasSensor2"][] = $deviceData;
        }
    }
} catch (PDOException $e) {
    // Log error and prepare error response
    error_log("Error in get_live_chart_data: " . $e->getMessage(), 3, "../error_log.txt");
    $response = ["error" => "Database query failed."];
}

// Set header and output JSON response
header('Content-Type: application/json');
echo json_encode($response);

// Close the PDO connection
$pdo = null;
?>
