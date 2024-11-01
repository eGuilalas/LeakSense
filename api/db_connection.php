<?php
header('Content-Type: application/json'); // Set response format to JSON

// Database connection parameters
$servername = "localhost"; // Database server address
$username = "root"; // MySQL username
$password = ""; // MySQL password
$dbname = "leaksense"; // Database name

// DSN and options for PDO connection
$dsn = "mysql:host=$servername;dbname=$dbname;charset=utf8";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];

try {
    // Create a new PDO instance
    $pdo = new PDO($dsn, $username, $password, $options);

    // Query to retrieve email recipients
    $recipients = [];
    $sqlRecipients = "SELECT email FROM email_recipients";
    $stmtRecipients = $pdo->query($sqlRecipients);
    while ($row = $stmtRecipients->fetch()) {
        $recipients[] = $row['email'];
    }

    // Query to retrieve threshold values
    $thresholds = [];
    $sqlThresholds = "SELECT device_id, smoke_threshold, co_threshold, lpg_threshold FROM thresholds";
    $stmtThresholds = $pdo->query($sqlThresholds);
    while ($row = $stmtThresholds->fetch()) {
        $thresholds[] = [
            "device_id" => $row['device_id'],
            "smoke_threshold" => $row['smoke_threshold'],
            "co_threshold" => $row['co_threshold'],
            "lpg_threshold" => $row['lpg_threshold']
        ];
    }

    // Output the combined data in JSON format
    echo json_encode(["recipients" => $recipients, "thresholds" => $thresholds]);

} catch (PDOException $e) {
    // Error handling for connection and query issues
    echo json_encode(["error" => "Connection or query failed: " . $e->getMessage()]);
} finally {
    // Close the PDO connection
    $pdo = null;
}
?>
