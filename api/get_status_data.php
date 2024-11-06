<?php
include '../db_connection.php';

$response = [
    "pending" => 0,
    "acknowledge" => 0,
    "false_alarm" => 0
];

try {
    $sql = "SELECT status, COUNT(*) as count FROM sensor_reading WHERE status IN (1, 2, 3) GROUP BY status";
    $stmt = $pdo->query($sql);

    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            switch ($row["status"]) {
                case 1:
                    $response["pending"] = intval($row["count"]);
                    break;
                case 2:
                    $response["acknowledge"] = intval($row["count"]);
                    break;
                case 3:
                    $response["false_alarm"] = intval($row["count"]);
                    break;
            }
        }
    }
} catch (PDOException $e) {
    // Log error and prepare error response
    error_log("Error in get_status_data: " . $e->getMessage(), 3, "../error_log.txt");
}

header('Content-Type: application/json');
echo json_encode($response);
?>
