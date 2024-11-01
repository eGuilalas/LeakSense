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
            top: 0; /* Position the header at the top of the table container */
            z-index: 10; /* Ensure the header stays above other content */
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
    <h1>Live Gas Readings Graph</h1>
    <div class="container">
        <h2>Latest Gas Readings</h2>
        <p id="latest-readings">Fetching latest readings...</p>
        <canvas id="gasLevelChart" width="600" height="300"></canvas>
        <canvas id="smokeCoLpgChart" width="600" height="300"></canvas>
        
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

        // Chart for smoke, CO, and LPG
        const ctxSmokeCoLpg = document.getElementById('smokeCoLpgChart').getContext('2d');
        const smokeCoLpgChart = new Chart(ctxSmokeCoLpg, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Smoke Status',
                        data: [],
                        borderColor: 'rgba(255, 206, 86, 1)',
                        backgroundColor: 'rgba(255, 206, 86, 0.2)',
                        borderWidth: 1,
                    },
                    {
                        label: 'CO Status',
                        data: [],
                        borderColor: 'rgba(54, 162, 235, 1)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderWidth: 1,
                    },
                    {
                        label: 'LPG Status',
                        data: [],
                        borderColor: 'rgba(153, 102, 255, 1)',
                        backgroundColor: 'rgba(153, 102, 255, 0.2)',
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
                            text: 'Status (1 = Detected, 0 = Not Detected)',
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
                                return `${tooltipItem.dataset.label}: ${tooltipItem.raw}`;
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
                    const statusText = reading.status === '1' 
                        ? `<span class="status-detected">Gas Detected</span>` 
                        : `<span class="status-not-detected">No Gas Detected</span>`;
                    return `Device ID: ${reading.device_id}, Gas Level: ${reading.gas_level} ppm, Status: ${statusText}, Timestamp: ${new Date(reading.timestamp).toLocaleString()}`;
                }).join('<br>');
                document.getElementById('latest-readings').innerHTML = readingsText;

                // Clear the table before populating
                tableBody.innerHTML = '';

                data.forEach(reading => {
                    const newRow = tableBody.insertRow();
                    newRow.innerHTML = `
                        <td>${reading.device_id}</td>
                        <td>${reading.gas_level} ppm</td>
                        <td class="${reading.smoke_status === '1' ? 'status-detected' : 'status-not-detected'}">
                            ${reading.smoke_status === '1' ? 'Gas Detected' : 'No Gas Detected'}
                        </td>
                        <td class="${reading.co_status === '1' ? 'status-detected' : 'status-not-detected'}">
                            ${reading.co_status === '1' ? 'Gas Detected' : 'No Gas Detected'}
                        </td>
                        <td class="${reading.lpg_status === '1' ? 'status-detected' : 'status-not-detected'}">
                            ${reading.lpg_status === '1' ? 'Gas Detected' : 'No Gas Detected'}
                        </td>
                        <td>${new Date(reading.timestamp).toLocaleString()}</td>
                    `;
                });

                // Scroll to the bottom of the table
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
                    gasLevelChart.data.datasets[0].data = last10Readings.map(entry => entry.gas_level_gs1);
                    gasLevelChart.data.datasets[1].data = last10Readings.map(entry => entry.gas_level_gs2);
                    gasLevelChart.update();

                    // Update smoke, CO, and LPG chart
                    smokeCoLpgChart.data.labels = last10Readings.map(entry => new Date(entry.timestamp).toLocaleString());
                    smokeCoLpgChart.data.datasets[0].data = last10Readings.map(entry => entry.smoke_status);
                    smokeCoLpgChart.data.datasets[1].data = last10Readings.map(entry => entry.co_status);
                    smokeCoLpgChart.data.datasets[2].data = last10Readings.map(entry => entry.lpg_status);
                    smokeCoLpgChart.update();
                })
                .catch(error => console.error('Error fetching graph readings:', error));
        }

        // Fetch latest readings and graph data every 5 seconds
        setInterval(() => {
            fetchLatestReadings();
            fetchGraphReadings();
        }, 5000);
        
        // Initial fetch
        fetchLatestReadings();
        fetchGraphReadings();
    </script>
</body>
</html>
