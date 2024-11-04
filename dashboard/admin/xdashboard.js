// Toggle the sidebar visibility when the hamburger menu is clicked
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('collapsed');
    document.body.classList.toggle('collapsed');
}

// Function to check if a device is online based on the timestamp of the last reading
// Considers the device online if the reading was within the last 5 minutes (300 seconds)
function isDeviceOnline(timestamp) {
    const currentTime = new Date().getTime();
    const readingTime = new Date(timestamp).getTime();
    const timeDiff = (currentTime - readingTime) / 1000; // Convert time difference to seconds
    console.log(`Checking device online status: Current Time: ${new Date(currentTime)}, Reading Time: ${new Date(readingTime)}, Time Difference: ${timeDiff} seconds`);
    return timeDiff <= 300; // Online if within 5 minutes (300 seconds)
}

// Function to determine gas detection status text and color
function getStatusTextAndColor(smokeStatus, coStatus, lpgStatus) {
    if (smokeStatus == '1' || coStatus == '1' || lpgStatus == '1') {
        return { text: 'Gas Detected!', color: 'red' }; // Indicates gas detection
    } else {
        return { text: 'No Gas Detected', color: 'green' }; // No gas detected
    }
}

// Configure the gas level chart for both devices
const ctxGasLevel = document.getElementById('gasLevelChart').getContext('2d');
const gasLevelChart = new Chart(ctxGasLevel, {
    type: 'line',
    data: {
        labels: [], // Time labels for the chart
        datasets: [
            { label: 'GS1 Gas Level (ppm)', data: [], borderColor: 'rgba(75, 192, 192, 1)', backgroundColor: 'rgba(75, 192, 192, 0.2)', borderWidth: 1 },
            { label: 'GS2 Gas Level (ppm)', data: [], borderColor: 'rgba(255, 99, 132, 1)', backgroundColor: 'rgba(255, 99, 132, 0.2)', borderWidth: 1 }
        ]
    },
    options: {
        scales: {
            y: { beginAtZero: true, title: { display: true, text: 'Gas Level (ppm)' } },
            x: { title: { display: true, text: 'Time' } }
        }
    }
});

// Fetch and display the counters dynamically based on response data
function fetchAndDisplayCounters() {
    fetch('../../config/get_alert_responses.php')
        .then(response => response.json())
        .then(data => {
            let pendingCount = 0;
            let acknowledgedCount = 0;
            let falseAlarmCount = 0;
            let duplicateCount = 0;
            const uniqueIds = new Set();

            data.forEach(reading => {
                const { response_type, reading_id } = reading;

                // Count types of responses
                if (response_type === 'acknowledged') acknowledgedCount++;
                else if (response_type === 'false_alarm') falseAlarmCount++;
                else pendingCount++;

                // Check for duplicates by reading_id
                if (uniqueIds.has(reading_id)) {
                    duplicateCount++;
                } else {
                    uniqueIds.add(reading_id);
                }
            });

            // Display the counts in the designated HTML elements
            document.getElementById('pending-count').innerText = pendingCount;
            document.getElementById('acknowledged-count').innerText = acknowledgedCount;
            document.getElementById('false-alarm-count').innerText = falseAlarmCount;
            document.getElementById('duplicate-count').innerText = duplicateCount;
        })
        .catch(error => console.error('Error fetching counters:', error));
}

// Fetch and display server status
function fetchServerStatus() {
    fetch('../../config/server_status.php')
        .then(response => response.json())
        .then(data => {
            const serverStatusElement = document.getElementById('server-status');
            serverStatusElement.textContent = data.status === "online" ? "Online ✔️" : "Offline ❌";
            serverStatusElement.className = data.status === "online" ? "online" : "offline";
        })
        .catch(error => console.error("Error fetching server status:", error));
}

// Fetch and display the latest readings for GS1 and GS2 and update the status for each device
function fetchLatestReadings() {
    Promise.all([
        fetch('../../config/get_latest_reading.php?device_id=GS1'),
        fetch('../../config/get_latest_reading.php?device_id=GS2')
    ])
    .then(responses => Promise.all(responses.map(res => res.json())))
    .then(data => {
        data.forEach(reading => {
            const deviceElement = reading.device_id === 'GS1' ? 'latest-readings-gs1' : 'latest-readings-gs2';
            const status = getStatusTextAndColor(reading.smoke_status, reading.co_status, reading.lpg_status);
            document.getElementById(deviceElement).innerHTML = `
                <span style="color: blue;">Device: ${reading.device_id}</span>, 
                <span style="color: blue;">Gas Level: ${reading.gas_level} ppm</span>, 
                <span style="color: ${status.color};">Status: ${status.text}</span>, 
                <span style="color: gray;">Time: ${new Date(reading.timestamp).toLocaleString()}</span>
            `;
            
            // Update online/offline status
            const deviceStatusElement = document.getElementById(`${reading.device_id.toLowerCase()}-status`);
            const isOnline = isDeviceOnline(reading.timestamp);
            deviceStatusElement.className = isOnline ? 'online' : 'offline';
            deviceStatusElement.textContent = isOnline ? 'Online ✔️' : 'Offline ❌';

            updateTable(reading); // Populate the readings table
        });
    })
    .catch(error => console.error('Error fetching latest readings:', error));
}

// Populate the readings table with the latest reading data, including color-coded gas detection statuses
function updateTable(reading) {
    const tableBody = document.getElementById('readingsTable').getElementsByTagName('tbody')[0];
    const newRow = tableBody.insertRow();

    // Apply color-coded status for each type of gas detection (smoke, CO, LPG)
    const smokeStatus = reading.smoke_status == '1' ? '<span style="color:red">Gas Detected!</span>' : '<span style="color:green">No Gas Detected</span>';
    const coStatus = reading.co_status == '1' ? '<span style="color:red">Gas Detected!</span>' : '<span style="color:green">No Gas Detected</span>';
    const lpgStatus = reading.lpg_status == '1' ? '<span style="color:red">Gas Detected!</span>' : '<span style="color:green">No Gas Detected</span>';

    newRow.innerHTML = `
        <td>${reading.device_id}</td>
        <td>${reading.gas_level} ppm</td>
        <td>${smokeStatus}</td>
        <td>${coStatus}</td>
        <td>${lpgStatus}</td>
        <td>${new Date(reading.timestamp).toLocaleString()}</td>
    `;
}

// Fetch and display readings for the chart with the latest data
function fetchGraphReadings() {
    fetch('../../config/get_readings.php')
        .then(response => response.json())
        .then(data => {
            const last10Readings = data.slice(-10).reverse();
            gasLevelChart.data.labels = last10Readings.map(entry => new Date(entry.timestamp).toLocaleString());
            gasLevelChart.data.datasets[0].data = last10Readings.filter(entry => entry.device_id === 'GS1').map(entry => entry.gas_level);
            gasLevelChart.data.datasets[1].data = last10Readings.filter(entry => entry.device_id === 'GS2').map(entry => entry.gas_level);
            gasLevelChart.update();
        })
        .catch(error => console.error('Error fetching graph readings:', error));
}

// Initial fetches and interval updates
fetchServerStatus();
setInterval(fetchServerStatus, 5000); // Update server status every 5 seconds
fetchAndDisplayCounters();
setInterval(fetchAndDisplayCounters, 5000); // Update counters every 5 seconds
fetchLatestReadings();
setInterval(fetchLatestReadings, 3000); // Update latest readings every 3 seconds
fetchGraphReadings();
setInterval(fetchGraphReadings, 5000); // Update chart every 5 seconds
