<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Gas Readings Graph</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f9;
            color: #333;
        }
        .navbar {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            background-color: #4a90e2;
            color: white;
        }
        .navbar button {
            background-color: white;
            color: #4a90e2;
            border: none;
            padding: 10px 15px;
            font-size: 14px;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
        }
        .navbar button:hover {
            background-color: #f4f4f9;
            color: #333;
        }
        h1 {
            text-align: center;
            color: #4a90e2;
            margin-bottom: 20px;
        }
        h2 {
            color: #333;
            margin-bottom: 10px;
        }
        .container {
            max-width: 1000px;
            margin: auto;
            padding: 20px;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        canvas {
            max-width: 100%;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .table-container {
            max-height: 300px;
            overflow-y: auto;
            margin: 20px 0;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            position: relative;
        }
        th, td {
            border: 1px solid #e0e0e0;
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #f0f0f0;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .status-detected {
            color: red;
            font-weight: bold;
        }
        .status-not-detected {
            color: green;
            font-weight: normal;
        }
    </style>
</head>
<body>
    <!-- Navbar with Logout and Settings buttons -->
    <div class="navbar">
        <button onclick="window.location.href='admin_email.php'">Settings</button>
        <button onclick="window.location.href='../logout.php'">Logout</button>
    </div>

    <h1>Live Gas Readings Graph</h1>
    <div class="container">
        <h2>Latest Gas Readings</h2>
        <p id="latest-readings">Fetching latest readings...</p>
        <canvas id="gasLevelChart" width="600" height="300"></canvas>
        
        <h2>Live Readings Table</h2>
        <div class="table-container" id="tableContainer">
            <table id="readingsTable">
                <thead>
                    <tr>
                        <th>Device ID</th>
                        <th>Gas Level (ppm)</th>
                        <th>Smoke Status</th>
                        <th>CO Status</th>
                        <th>LPG Status</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Rows will be populated dynamically -->
                </tbody>
            </table>
        </div>
    </div>

    <script>
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
                fetch('get_latest_reading.php?device_id=GS1'),
                fetch('get_latest_reading.php?device_id=GS2')
            ])
            .then(responses => Promise.all(responses.map(res => res.json())))
            .then(data => {
                const readingsText = data.map(reading => {
                    // Check if any of the statuses are '1' (indicating gas detected)
                    const gasDetected = (reading.smoke_status == '1' || reading.co_status == '1' || reading.lpg_status == '1');
                    const statusText = gasDetected 
                        ? `<span class="status-detected">Gas Detected</span>` 
                        : `<span class="status-not-detected">No Gas Detected</span>`;
                    return `Device ID: ${reading.device_id}, Gas Level: ${reading.gas_level} ppm, Status: ${statusText}, Timestamp: ${new Date(reading.timestamp).toLocaleString()}`;
                }).join('<br>');
                document.getElementById('latest-readings').innerHTML = readingsText;

                // Insert new rows at the bottom of the table
                data.forEach(reading => {
                    const newRow = tableBody.insertRow();
                    
                    // Correctly check each gas type status and display it
                    const smokeStatus = reading.smoke_status == '1' ? '<span class="status-detected">Gas Detected</span>' : 'No Gas Detected';
                    const coStatus = reading.co_status == '1' ? '<span class="status-detected">Gas Detected</span>' : 'No Gas Detected';
                    const lpgStatus = reading.lpg_status == '1' ? '<span class="status-detected">Gas Detected</span>' : 'No Gas Detected';

                    newRow.innerHTML = `
                        <td>${reading.device_id}</td>
                        <td>${reading.gas_level} ppm</td>
                        <td class="${reading.smoke_status == '1' ? 'status-detected' : 'status-not-detected'}">
                            ${smokeStatus}
                        </td>
                        <td class="${reading.co_status == '1' ? 'status-detected' : 'status-not-detected'}">
                            ${coStatus}
                        </td>
                        <td class="${reading.lpg_status == '1' ? 'status-detected' : 'status-not-detected'}">
                            ${lpgStatus}
                        </td>
                        <td>${new Date(reading.timestamp).toLocaleString()}</td>
                    `;
                });

                // Scroll to the bottom of the table to show the latest reading
                const tableContainer = document.getElementById('tableContainer');
                tableContainer.scrollTop = tableContainer.scrollHeight;
            })
            .catch(error => console.error('Error fetching latest readings:', error));
        }

        function fetchGraphReadings() {
            fetch('get_readings.php') 
                .then(response => response.json())
                .then(data => {
                    // Get the latest readings regardless of the time
                    const last10Readings = data.slice(-10);
                    
                    // Update gas level chart
                    gasLevelChart.data.labels = last10Readings.map(entry => new Date(entry.timestamp).toLocaleString());
                    gasLevelChart.data.datasets[0].data = last10Readings.filter(entry => entry.device_id === 'GS1').map(entry => entry.gas_level);
                    gasLevelChart.data.datasets[1].data = last10Readings.filter(entry => entry.device_id === 'GS2').map(entry => entry.gas_level);
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
    </script>
</body>
</html>
