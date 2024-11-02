<?php
// get_alert_responses.php

include('db_connection.php');

// Query to get readings and alert responses with user info for "Actioned By"
$query = "
    SELECT 
        gas_readings.id AS reading_id,
        gas_readings.device_id,
        gas_readings.gas_level,
        gas_readings.gas_type,
        gas_readings.smoke_status,
        gas_readings.co_status,
        gas_readings.lpg_status,
        gas_readings.timestamp,
        gas_alert_responses.response_type,
        gas_alert_responses.comments AS comment,
        gas_alert_responses.response_time,
        users.username AS actioned_by
    FROM 
        gas_readings
    LEFT JOIN 
        gas_alert_responses ON gas_readings.id = gas_alert_responses.reading_id
    LEFT JOIN 
        users ON gas_alert_responses.user_id = users.id
    WHERE 
        gas_readings.device_id = 'GS2' AND 
        (gas_readings.smoke_status = 1 OR gas_readings.co_status = 1 OR gas_readings.lpg_status = 1)
";

$result = $conn->query($query);

$readings = [];
while ($row = $result->fetch_assoc()) {
    $readings[] = $row;
}

header('Content-Type: application/json');
echo json_encode($readings);
?>
