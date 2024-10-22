// dashboard.js

// Sidebar Toggle Functionality
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('collapsed');
    document.body.classList.toggle('collapsed');
}

// Function to check if a device is online (i.e., last reading is within the last minute)
function isDeviceOnline(timestamp) {
    const currentTime = new Date().getTime();
    const readingTime = new Date(timestamp).getTime();
    const timeDiff = (currentTime - readingTime) / 1000; // in seconds
    return timeDiff <= 60; // Consider online if the last reading was within the last 60 seconds
}

// Function to set the text color for the gas detection status
function getStatusTextAndColor(smokeStatus, coStatus, lpgStatus) {
    if (smokeStatus == '1' || coStatus == '1' || lpgStatus == '1') {
        return { text: 'Gas Detected!', color: 'red' }; // Gas detected
    } else {
        return { text: 'No Gas Detected', color: 'green' }; // No gas detected
    }
}

// Chart for gas levels
const ctxGasLevel = document.getElementById('gasLevelChart').getContext('2d');
const gasLevelChart = new Chart(ctxGasLevel, {
    type: 'line',
    data: {
        labels: [],
        datasets: [
            {
                label: 'GS1 Gas Level (ppm)',
                data: [],
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderWidth: 1,
            },
            {
                label: 'GS2 Gas Level (ppm)',
                data: [],
                borderColor: 'rgba(255, 99, 132, 1)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                borderWidth: 1,
            }
        ]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Gas Level (ppm)',
                },
            },
            x: {
                title: {
                    display: true,
                    text: 'Time',
                },
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(tooltipItem) {
                        return `${tooltipItem.dataset.label}: ${tooltipItem.raw} ppm`;
                    }
                }
            }
        }
    }
});

const tableBody = document.getElementById('readingsTable').getElementsByTagName('tbody')[0];

function fetchLatestReadings() {
    Promise.all([
        fetch('../../config/get_latest_reading.php?device_id=GS1'),
        fetch('../../config/get_latest_reading.php?device_id=GS2')
    ])
    .then(responses => Promise.all(responses.map(res => res.json())))
    .then(data => {
        console.log("Latest readings data:", data); // Debugging output

        // Clear the latest readings text
        document.getElementById('latest-readings-gs1').innerHTML = '';
        document.getElementById('latest-readings-gs2').innerHTML = '';

        data.forEach(reading => {
            // Log each device reading for debugging
            console.log(`Reading for device ${reading.device_id}:`, reading);

            if (reading.error) {
                console.error(`Error for device ${reading.device_id}: ${reading.error}`);
                return;
            }

            // Check if reading is for GS1 or GS2 and update status
            if (reading.device_id === 'GS1') {
                updateStatus(reading, 'gs1');
            } else if (reading.device_id === 'GS2') {
                updateStatus(reading, 'gs2');
            }

            // Display latest reading details for GS1 and GS2
            const status = getStatusTextAndColor(reading.smoke_status, reading.co_status, reading.lpg_status);
            const latestReadingHTML = `
                <span style="color: blue;">Device: ${reading.device_id}</span>, 
                <span style="color: blue;">Gas Level: ${reading.gas_level} ppm</span>, 
                <span style="color: ${status.color};">Status: ${status.text}</span>, 
                <span style="color: gray;">Time: ${new Date(reading.timestamp).toLocaleString()}</span>
            `;

            if (reading.device_id === 'GS1') {
                document.getElementById('latest-readings-gs1').innerHTML = latestReadingHTML;
            } else if (reading.device_id === 'GS2') {
                document.getElementById('latest-readings-gs2').innerHTML = latestReadingHTML;
            }

            // Insert data into the table
            const newRow = tableBody.insertRow();
            const smokeStatus = reading.smoke_status == '1' ? `<span style="color:red">Gas Detected!</span>` : `<span style="color:green">No Gas Detected</span>`;
            const coStatus = reading.co_status == '1' ? `<span style="color:red">Gas Detected!</span>` : `<span style="color:green">No Gas Detected</span>`;
            const lpgStatus = reading.lpg_status == '1' ? `<span style="color:red">Gas Detected!</span>` : `<span style="color:green">No Gas Detected</span>`;

            newRow.innerHTML = `
                <td>${reading.device_id}</td>
                <td>${reading.gas_level} ppm</td>
                <td>${smokeStatus}</td>
                <td>${coStatus}</td>
                <td>${lpgStatus}</td>
                <td>${new Date(reading.timestamp).toLocaleString()}</td>
            `;

            // Scroll to the bottom of the table to show the latest reading
            const tableContainer = document.getElementById('tableContainer');
            tableContainer.scrollTop = tableContainer.scrollHeight;
        });
    })
    .catch(error => console.error('Error fetching latest readings:', error));
}

function updateStatus(reading, device) {
    const statusElement = document.getElementById(`${device}-status`);
    const isOnline = isDeviceOnline(reading.timestamp);

    statusElement.className = isOnline ? 'online' : 'offline';
    statusElement.textContent = isOnline ? 'Online ✔️' : 'Offline ❌';
}

function fetchGraphReadings() {
    fetch('../../config/get_readings.php')
        .then(response => response.json())
        .then(data => {
            console.log("Graph readings data:", data); // Debugging output

            // Get the latest 10 readings for the chart
            const last10Readings = data.slice(-10).reverse(); // Reverse to have oldest first

            // Update gas level chart
            gasLevelChart.data.labels = last10Readings.map(entry => new Date(entry.timestamp).toLocaleString());
            gasLevelChart.data.datasets[0].data = last10Readings
                .filter(entry => entry.device_id === 'GS1')
                .map(entry => entry.gas_level);
            gasLevelChart.data.datasets[1].data = last10Readings
                .filter(entry => entry.device_id === 'GS2')
                .map(entry => entry.gas_level);
            gasLevelChart.update();
        })
        .catch(error => console.error('Error fetching graph readings:', error));
}

// Fetch latest readings and update the table initially
fetchLatestReadings();
setInterval(fetchLatestReadings, 3000); // Update latest readings every 3 seconds

// Fetch graph readings initially
fetchGraphReadings();
setInterval(fetchGraphReadings, 5000); // Update graph every 5 minutes
