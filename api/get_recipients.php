<?php
header('Content-Type: application/json'); // Set response format to JSON

// Include database connection
require 'db_connection.php';

$recipients = [];

try {
    // Ensure $pdo is defined
    if (!isset($pdo)) {
        throw new Exception("Database connection is not established.");
    }

    // SQL query to get email recipients
    $sql = "SELECT email FROM email_recipients";
    
    // Prepare and execute the query
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    // Fetch and store each email in the recipients array
    while ($row = $stmt->fetch()) {
        $recipients[] = $row['email'];
    }

    // Check if there are recipients
    if (count($recipients) === 0) {
        echo json_encode(["message" => "No recipients found in the database."]);
    } else {
        // Output recipients as JSON
        echo json_encode(["recipients" => $recipients]);
    }

} catch (Exception $e) {
    // Handle errors by returning an error message in JSON
    echo json_encode(["error" => $e->getMessage()]);
}
