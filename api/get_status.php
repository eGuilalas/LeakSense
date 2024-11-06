<?php
session_start();
include '../db_connection.php'; // Include your database connection

$response = [];

// Check Server Status
$response['server'] = 'Online'; // Replace this with your actual server status logic

// Check Sensor Statuses
$deviceIDs = ['GS1', 'GS2']; // Your device IDs

foreach ($deviceIDs as $deviceID) {
    // Query to get the status from the device table
    $query = "SELECT status FROM device WHERE deviceID = :deviceID";
    
    // Prepare and execute the statement
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':deviceID', $deviceID);
    
    try {
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            // Check the status field
            $response[$deviceID] = ($row['status'] == 1) ? 'Online' : 'Offline';
        } else {
            $response[$deviceID] = 'Offline'; // If device is not found, set as Offline
        }
    } catch (PDOException $e) {
        $response[$deviceID] = 'Error: ' . $e->getMessage(); // Handle query error
    }
}

echo json_encode($response);
?>
