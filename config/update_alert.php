<?php
// update_alert.php

session_start();
include('../../config/db_connection.php');

$readingId = $_POST['reading_id'];
$action = $_POST['action'];
$userId = $_POST['user_id'];

if (!$userId) {
    echo json_encode(['message' => 'User not logged in']);
    exit();
}

$responseType = ($action === 'acknowledged') ? 'acknowledged' : 'false_alarm';
$alertStatus = ($action === 'acknowledged') ? 1 : 2;

$stmt = $conn->prepare("UPDATE gas_readings SET alert_status = ? WHERE id = ?");
$stmt->bind_param("ii", $alertStatus, $readingId);
$stmt->execute();

$stmtResponse = $conn->prepare("INSERT INTO gas_alert_responses (reading_id, user_id, response_type) VALUES (?, ?, ?)");
$stmtResponse->bind_param("iis", $readingId, $userId, $responseType);
$stmtResponse->execute();

echo json_encode(['message' => 'Action updated successfully']);
?>
