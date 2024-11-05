<?php
include_once '../db_connection.php'; // Make sure the correct path to db_connection.php is used

// Retrieve active recipients
$sql = "SELECT email FROM user WHERE status = 0 AND userrole IN ('admin', 'super_user', 'user', 'super_admin')";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $recipients = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $recipients[] = $row['email'];
    }

    // Return recipients in JSON format
    echo json_encode(['recipients' => $recipients]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Failed to fetch recipients: ' . $e->getMessage()]);
}
?>
