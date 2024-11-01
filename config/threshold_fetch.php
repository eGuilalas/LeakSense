<?php
include 'db_connection.php';

header('Content-Type: application/json');

$sql = "SELECT * FROM thresholds";
$result = $conn->query($sql);

$thresholds = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $thresholds[] = $row;
    }
}

echo json_encode($thresholds);
$conn->close();
?>
