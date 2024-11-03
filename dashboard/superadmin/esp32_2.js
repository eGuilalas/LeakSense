// esp32_2.js

// Fetch Gas Detections for GS2 and Populate the Table with Gas Detected Only
function fetchReadings() {
    fetch('../../config/esp_get_readings.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to fetch readings');
            }
            return response.json();
        })
        .then(data => {
            console.log(data);  // Log the fetched data for debugging

            const tableBodyGS2 = document.getElementById('readingsTableGS2').getElementsByTagName('tbody')[0];
            tableBodyGS2.innerHTML = '';  // Clear the table

            // Loop through each reading and create rows only for GS2 where gas is detected
            data.forEach(reading => {
                if (reading.device_id === 'GS2' && (reading.smoke_status == '1' || reading.co_status == '1' || reading.lpg_status == '1')) {
                    const newRow = document.createElement('tr');

                    const smokeStatus = reading.smoke_status == '1' ? '<span style="color:red">Gas Detected!</span>' : '<span style="color:green">No Gas Detected</span>';
                    const coStatus = reading.co_status == '1' ? '<span style="color:red">Gas Detected!</span>' : '<span style="color:green">No Gas Detected</span>';
                    const lpgStatus = reading.lpg_status == '1' ? '<span style="color:red">Gas Detected!</span>' : '<span style="color:green">No Gas Detected</span>';
                    
                    // Correctly interpret the alert_status from the database
                    let alertStatus;
                    if (reading.alert_status === '1') {
                        alertStatus = '<span style="color:blue">Acknowledged</span>';
                    } else if (reading.alert_status === '2') {
                        alertStatus = '<span style="color:orange">False Alarm</span>';
                    } else {
                        alertStatus = '<span style="color:red">Pending</span>';
                    }

                    newRow.innerHTML = `
                        <td>${reading.gas_level} ppm</td>
                        <td>${smokeStatus}</td>
                        <td>${coStatus}</td>
                        <td>${lpgStatus}</td>
                        <td>${new Date(reading.timestamp).toLocaleString()}</td>
                        <td>${alertStatus}</td>
                        <td>
                            <button class="acknowledge-btn" data-reading-id="${reading.id}">Acknowledge</button>
                            <button class="false-alarm-btn" data-reading-id="${reading.id}">False Alarm</button>
                        </td>
                    `;
                    tableBodyGS2.appendChild(newRow);
                }
            });

            // Attach event listeners to the buttons only once
            attachButtonListeners();
        })
        .catch(error => {
            console.error('Error fetching readings:', error);
        });
}

// Attach Acknowledge and False Alarm button event listeners
function attachButtonListeners() {
    document.querySelectorAll('.acknowledge-btn').forEach(button => {
        button.removeEventListener('click', handleAcknowledge); // Remove any existing listeners
        button.addEventListener('click', handleAcknowledge);    // Attach the new listener
    });

    document.querySelectorAll('.false-alarm-btn').forEach(button => {
        button.removeEventListener('click', handleFalseAlarm);  // Remove any existing listeners
        button.addEventListener('click', handleFalseAlarm);     // Attach the new listener
    });
}

// Handle Acknowledge button click
function handleAcknowledge() {
    const readingId = this.getAttribute('data-reading-id');
    fetch('../../config/acknowledge_alert.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `reading_id=${readingId}`,
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        fetchReadings(); // Refresh the table to show updated status
    })
    .catch(error => {
        console.error('Error acknowledging alert:', error);
    });
}

// Handle False Alarm button click
function handleFalseAlarm() {
    const readingId = this.getAttribute('data-reading-id');
    fetch('../../config/false_alarm.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `reading_id=${readingId}`,
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        fetchReadings(); // Refresh the table to show updated status
    })
    .catch(error => {
        console.error('Error reporting false alarm:', error);
    });
}

// Fetch readings when the page loads and every 5 seconds
fetchReadings();
setInterval(fetchReadings, 5000);
