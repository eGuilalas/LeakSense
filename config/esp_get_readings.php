<?php
// esp_get_readings.php

include('db_connection.php');

// Query to get readings and join with alert responses to get actioned_by information
$query = "
    SELECT 
        g.id, g.device_id, g.gas_level, g.gas_type, g.smoke_status, g.co_status, g.lpg_status, 
        g.status, g.alert_status, g.timestamp,
        ar.user_id AS actioned_by, ar.response_type
    FROM gas_readings g
    LEFT JOIN gas_alert_responses ar ON g.id = ar.reading_id
    WHERE g.device_id = 'GS1' AND (g.smoke_status = 1 OR g.co_status = 1 OR g.lpg_status = 1)
";

$result = $conn->query($query);

$readings = [];
while ($row = $result->fetch_assoc()) {
    $readings[] = $row;
}

// Return the result as JSON
header('Content-Type: application/json');
echo json_encode($readings);
?>
