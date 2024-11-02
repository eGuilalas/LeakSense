<?php
header('Content-Type: application/json');

// Include database connection
require '../db_connection.php';

// Check if $pdo is initialized
if (isset($pdo)) {
    try {
        // Attempt a simple query to test the connection
        $stmt = $pdo->query("SELECT 1");
        
        // If the query is successful, the server is online
        echo json_encode(["status" => "online"]);
    } catch (PDOException $e) {
        // If there’s an error, the server is offline
        echo json_encode(["status" => "offline"]);
    }
} else {
    // $pdo is not defined, indicating a connection issue
    echo json_encode(["status" => "offline"]);
}
?>
