<?php
session_start();
include('db_connection.php');

if (!isset($_POST['reading_id'], $_POST['user_id'], $_POST['comment'])) {
    echo json_encode(['message' => 'Invalid request']);
    exit();
}

$reading_id = $_POST['reading_id'];
$user_id = $_POST['user_id'];
$comment = $_POST['comment'];

$query = "INSERT INTO gas_alert_responses (reading_id, user_id, response_type, comments) 
          VALUES (?, ?, 'acknowledged', ?) 
          ON DUPLICATE KEY UPDATE response_type='acknowledged', user_id=?, comments=?";
$stmt = $conn->prepare($query);
$stmt->bind_param("iisis", $reading_id, $user_id, $comment, $user_id, $comment);

if ($stmt->execute()) {
    echo json_encode(['message' => 'Alert acknowledged successfully']);
} else {
    echo json_encode(['message' => 'Failed to acknowledge alert']);
}
?>
