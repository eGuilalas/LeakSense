// esp32_1.js

// Fetch readings and display them in the table with filtering by alert status
function fetchReadings() {
    const alertStatusFilter = document.getElementById('alertStatusFilter').value;

    fetch('../../config/get_alert_responses.php')
        .then(response => response.json())
        .then(data => {
            console.log("Fetched data:", data); // Log data for debugging

            const tableBodyGS1 = document.getElementById('readingsTableGS1').getElementsByTagName('tbody')[0];
            tableBodyGS1.innerHTML = ''; // Clear existing table rows

            // Render each reading, applying the alert status filter
            data.forEach(reading => {
                if (!alertStatusFilter || reading.response_type === alertStatusFilter) {
                    const row = document.createElement('tr');

                    row.innerHTML = `
                        <td>${reading.gas_level} ppm</td>
                        <td>${reading.smoke_status === "1" ? "Gas Detected!" : "No Gas Detected"}</td>
                        <td>${reading.co_status === "1" ? "Gas Detected!" : "No Gas Detected"}</td>
                        <td>${reading.lpg_status === "1" ? "Gas Detected!" : "No Gas Detected"}</td>
                        <td>${new Date(reading.timestamp).toLocaleString()}</td>
                        <td>${reading.response_type || 'Pending'}</td>
                        <td>
                            <button class="acknowledge-btn" data-reading-id="${reading.reading_id}">Acknowledge</button>
                            <button class="false-alarm-btn" data-reading-id="${reading.reading_id}">False Alarm</button>
                        </td>
                        <td>${reading.actioned_by || 'N/A'}</td>
                    `;

                    tableBodyGS1.appendChild(row); // Append the row to the table
                }
            });

            // Attach button listeners after rendering
            attachButtonListeners();
        })
        .catch(error => {
            console.error("Error fetching data:", error);
        });
}

// Attach event listeners for the action buttons
function attachButtonListeners() {
    document.querySelectorAll('.acknowledge-btn').forEach(button => {
        button.removeEventListener('click', handleAcknowledge); // Avoid duplicate listeners
        button.addEventListener('click', handleAcknowledge);
    });

    document.querySelectorAll('.false-alarm-btn').forEach(button => {
        button.removeEventListener('click', handleFalseAlarm);
        button.addEventListener('click', handleFalseAlarm);
    });
}

// Handle Acknowledge action
function handleAcknowledge() {
    const readingId = this.getAttribute('data-reading-id');
    fetch('../../config/acknowledge_alert.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `reading_id=${readingId}`
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        fetchReadings(); // Refresh table data
    })
    .catch(error => {
        console.error("Error acknowledging alert:", error);
    });
}

// Handle False Alarm action
function handleFalseAlarm() {
    const readingId = this.getAttribute('data-reading-id');
    fetch('../../config/false_alarm.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `reading_id=${readingId}`
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        fetchReadings(); // Refresh table data
    })
    .catch(error => {
        console.error("Error reporting false alarm:", error);
    });
}

// Fetch readings when the page loads and refresh every 5 seconds
fetchReadings();
setInterval(fetchReadings, 5000);
