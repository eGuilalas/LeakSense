<?php
// Database connection
require 'db_connection.php';

// Fetch the last 10 readings ordered by timestamp
$sql = "SELECT * FROM gas_readings ORDER BY timestamp DESC LIMIT 10";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$readings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Return the data as JSON
header('Content-Type: application/json');
echo json_encode($readings);
?>
