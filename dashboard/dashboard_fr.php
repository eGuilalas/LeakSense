<?php
session_start();

if (!isset($_SESSION['userID'])) {
    $_SESSION['error'] = "You must log in to access this page.";
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Leaksense</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; display: flex; transition: background-color 0.3s, color 0.3s; }

        /* Dark Mode */
        body.dark { background-color: #1E1E2D; color: #fff; }
        body.dark .sidebar { background-color: #2B2D42; color: #D6D8E7; }
        body.dark .sidebar h2, body.dark .sidebar a { color: #D6D8E7; }
        body.dark .sidebar a.active, body.dark .sidebar a:hover { background-color: #F72585; color: #fff; }
        body.dark .header-box, body.dark .chart, body.dark .table-container { background: #3A3A5A; }
        body.dark .status-detected { color: red; }
        body.dark .status-not-detected { color: green; }
        body.dark .bottom-section { color: #D6D8E7; }

        /* Light Mode */
        body.light { background-color: #f0f0f0; color: #333; }
        body.light .sidebar { background-color: #e6e6e6; color: #333; }
        body.light .sidebar h2, body.light .sidebar a { color: #333; }
        body.light .sidebar a.active, body.light .sidebar a:hover { background-color: #4CAF50; color: #fff; }
        body.light .header-box, body.light .chart, body.light .table-container { background: #f9f9f9; }
        body.light .status-detected { color: red; }
        body.light .status-not-detected { color: green; }
        body.light .bottom-section { color: #333; }
        
        /* General Styles */
        .dashboard-container { display: flex; height: 100vh; width: 100%; }
        .sidebar { width: 220px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between; }
        .sidebar h2 { font-size: 1.5em; margin-bottom: 20px; }
        .sidebar ul { list-style: none; padding-left: 0; }
        .sidebar li { margin-bottom: 15px; }
        .sidebar a { text-decoration: none; font-size: 1em; display: block; padding: 10px; border-radius: 5px; transition: background-color 0.2s; }
        
        .toggle-container { display: flex; align-items: center; gap: 10px; }
        .toggle-container label { font-size: 0.9em; }

        .main-dashboard { flex: 1; padding: 20px; overflow-y: auto; }
        .dashboard-header { display: flex; gap: 20px; margin-bottom: 20px; }
        .header-box { padding: 20px; border-radius: 10px; flex: 1; }
        .header-box h3 { color: inherit; }
        .charts-container { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .chart, .table-container { padding: 20px; border-radius: 10px; }
        table { width: 100%; color: inherit; margin-top: 10px; border-collapse: collapse; }
        table th, table td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }

        /* Bottom section styling */
        .bottom-section { border-top: 1px solid #444; padding-top: 20px; color: inherit; text-align: left; }
        .bottom-section h1, .bottom-section h3 { margin-bottom: 10px; }
        .bottom-section a { text-decoration: none; font-weight: bold; display: inline-block; margin-top: 10px; }

        .online { color: green; }
        .offline { color: red; }
        .on { color: green; font-weight: bold; }
        .standby { color: orange; font-weight: bold; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="dark">
    <div class="dashboard-container">
        <aside class="sidebar">
            <div>
                <h2>Tableau de bord Leaksense</h2>
                <nav>
                    <ul>
                        <li><a href="#" class="active">Tableau de bord</a></li>
                        <li><a href="gs1_fr.php">ESP32-GasSensor 1</a></li>
                        <li><a href="gs2_fr.php">ESP32-GasSensor 2</a></li>
                        <li><a href="Reports_fr.php">Rapports</a></li>
                        <?php if ($_SESSION['userrole'] !== 'user'): ?>
                            <li><a href="manage_user_fr.php">Gérer l'utilisateur</a></li>
                            <li><a href="Threshold_fr.php">Configuration du seuil</a></li>
                            <li><a href="email_alert_report_fr.php">Rapport d'alerte par e-mail</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <!-- Toggle Switch -->
            <div class="toggle-container">
                <label for="theme-toggle">Mode Clair</label>
                <input type="checkbox" id="theme-toggle">
            </div>
            
            <div class="bottom-section">
                <h3>Bienvenue!</h3>
                <h4><?php echo htmlspecialchars($_SESSION['username']); ?></h4>
                <h4>Rôle: <?php echo htmlspecialchars($_SESSION['userrole']); ?></h4>
            </div>
            <div class="bottom-section">
                <h3>Langue</h3>
                <li><a href="dashboard.php">Anglais</a></li>
            </div>
            <div class="bottom-section">
                <a href="../logout.php">Se déconnecter</a>
            </div>
        </aside>

        <main class="main-dashboard">
            <div class="dashboard-header">
                <div class="header-box">
                    <h3>État du serveur</h3>
                    <p id="serverStatus" class="online">Online</p>
                </div>
                <div class="header-box">
                    <h3>État de l'ESP32-GasSensor 1</h3>
                    <p id="sensor1Status" class="online">...</p>
                </div>
                <div class="header-box">
                    <h3>État de l'ESP32-GasSensor 2</h3>
                    <p id="sensor2Status" class="online">...</p>
                </div>
                <div class="header-box">
                    <h3>État de l'ESP32-GasSensor-Fan-1</h3>
                    <p id="fan1Status" class="standby">...</p>
                </div>
                <div class="header-box">
                    <h3>État de l'ESP32-GasSensor-Fan-2</h3>
                    <p id="fan2Status" class="standby">...</p>
                </div>
            </div>
            <div class="dashboard-header">
                <div class="header-box">
                    <h3>En attente</h3>
                    <p id="pendingCount" style="color: #36A2EB;">0</p>
                </div>
                <div class="header-box">
                    <h3>Accusé</h3>
                    <p id="acknowledgeCount" style="color: #FF6384;">0</p>
                </div>
                <div class="header-box">
                    <h3>Faux alarme</h3>
                    <p id="falseAlarmCount" style="color: #FFCE56;">0</p>
                </div>
            </div>

            <div class="charts-container">
                <div class="chart">
                    <canvas id="livegasChart"></canvas>
                </div>
                <div class="chart">
                    <canvas id="statusChart"></canvas>
                </div>
                <div class="table-container">
                    <h3>Tableau de gaz en direct</h3>
                    <table id="gasTable">
                        <thead>
                            <tr>
                                <th>ID Appareil</th>
                                <th>Niveau de gaz (ppm)</th>
                                <th>État</th>
                                <th>Horodatage</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
    document.getElementById('theme-toggle').addEventListener('change', function() {
        document.body.classList.toggle('light', this.checked);
        document.body.classList.toggle('dark', !this.checked);
    });

    const liveGasChartCtx = document.getElementById('livegasChart').getContext('2d');
    const statusChartCtx = document.getElementById('statusChart').getContext('2d');

    let liveGasChart = new Chart(liveGasChartCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                { label: 'GS1', data: [], borderColor: '#FF6384', fill: false },
                { label: 'GS2', data: [], borderColor: '#36A2EB', fill: false }
            ]
        },
        options: { responsive: true, scales: { x: { title: { display: true, text: 'Heure' } }, y: { title: { display: true, text: 'Niveau de gaz (ppm)' } } } }
    });

    let statusChart = new Chart(statusChartCtx, {
        type: 'doughnut',
        data: {
            labels: ['En attente', 'Accusé', 'Faux alarme'],
            datasets: [{
                data: [0, 0, 0],
                backgroundColor: ['#36A2EB', '#FF6384', '#FFCE56']
            }]
        },
        options: { responsive: true }
    });

    function fetchLiveChartData() {
        fetch('../api/get_live_chart_data.php')
            .then(response => response.json())
            .then(data => {
                if (data['ESP32-GasSensor1'] && data['ESP32-GasSensor1'].length > 0) {
                    liveGasChart.data.labels = data['ESP32-GasSensor1'].map(d => d.time);
                    liveGasChart.data.datasets[0].data = data['ESP32-GasSensor1'].map(d => d.ppm);
                }
                if (data['ESP32-GasSensor2'] && data['ESP32-GasSensor2'].length > 0) {
                    liveGasChart.data.datasets[1].data = data['ESP32-GasSensor2'].map(d => d.ppm);
                } else {
                    liveGasChart.data.datasets[1].data = [];
                }
                liveGasChart.update();
            })
            .catch(error => console.error("Erreur lors de la récupération des données du graphique en direct :", error));
    }

    function fetchStatusData() {
        fetch('../api/get_status_data.php')
            .then(response => response.json())
            .then(data => {
                document.getElementById('pendingCount').innerText = data.pending;
                document.getElementById('acknowledgeCount').innerText = data.acknowledge;
                document.getElementById('falseAlarmCount').innerText = data.false_alarm;
                statusChart.data.datasets[0].data = [data.pending, data.acknowledge, data.false_alarm];
                statusChart.update();
            })
            .catch(error => console.error("Erreur lors de la récupération des données d'état :", error));
    }

    function fetchDeviceStatus() {
        fetch('../api/get_status.php')
            .then(response => response.json())
            .then(data => {
                const sensor1Online = data.GS1 === 'Online';
                const sensor2Online = data.GS2 === 'Online';

                document.getElementById('sensor1Status').innerText = data.GS1;
                document.getElementById('sensor2Status').innerText = data.GS2;

                const fan1StatusElem = document.getElementById('fan1Status');
                const fan2StatusElem = document.getElementById('fan2Status');

                if (!sensor1Online) {
                    fan1StatusElem.innerText = "Hors ligne";
                    fan1StatusElem.className = "offline";
                }

                if (!sensor2Online) {
                    fan2StatusElem.innerText = "Hors ligne";
                    fan2StatusElem.className = "offline";
                }

                if (sensor1Online || sensor2Online) {
                    fetchLiveTableData(sensor1Online, sensor2Online);
                }

                document.getElementById('serverStatus').className = data.server === 'Online' ? 'online' : 'offline';
                document.getElementById('sensor1Status').className = sensor1Online ? 'online' : 'offline';
                document.getElementById('sensor2Status').className = sensor2Online ? 'online' : 'offline';
            })
            .catch(error => console.error("Erreur lors de la récupération de l'état de l'appareil :", error));
    }

    function fetchLiveTableData(sensor1Online, sensor2Online) {
        fetch('../api/get_live_table_data.php')
            .then(response => response.json())
            .then(data => {
                const tableBody = document.querySelector("#gasTable tbody");
                tableBody.innerHTML = "";

                const latestEntries = data.slice(-7);
                let isGasDetected = false;

                latestEntries.forEach(row => {
                    const statusClass = row.status === "Gas Detected" ? 'status-detected' : 'status-not-detected';
                    tableBody.innerHTML += `
                        <tr>
                            <td>${row.deviceID}</td>
                            <td>${row.ppm}</td>
                            <td class="${statusClass}">${row.status}</td>
                            <td>${row.timestamp}</td>
                        </tr>`;
                    if (row.status === "Gas Detected") {
                        isGasDetected = true;
                    }
                });

                const fan1StatusElem = document.getElementById('fan1Status');
                const fan2StatusElem = document.getElementById('fan2Status');

                if (sensor1Online) {
                    fan1StatusElem.innerText = isGasDetected ? "Activé" : "En veille";
                    fan1StatusElem.className = isGasDetected ? "on" : "standby";
                }

                if (sensor2Online) {
                    fan2StatusElem.innerText = isGasDetected ? "Activé" : "En veille";
                    fan2StatusElem.className = isGasDetected ? "on" : "standby";
                }
            })
            .catch(error => console.error("Erreur lors de la récupération des données du tableau en direct :", error));
    }

    setInterval(fetchLiveChartData, 3000);
    setInterval(fetchStatusData, 3000);
    setInterval(fetchDeviceStatus, 3000);
    setInterval(fetchLiveTableData, 1000);
    </script>
</body>
</html>
