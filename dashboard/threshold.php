<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Threshold Setup - Leaksense Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background-color: #1E1E2D; color: #fff; display: flex; }
        .dashboard-container { display: flex; height: 100vh; width: 100%; }
        .sidebar { background-color: #2B2D42; width: 220px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between; }
        .sidebar h2 { color: #8D99AE; font-size: 1.5em; margin-bottom: 20px; }
        .sidebar ul { list-style: none; padding-left: 0; }
        .sidebar li { margin-bottom: 15px; }
        .sidebar a {
            text-decoration: none;
            color: #D6D8E7;
            font-size: 1em;
            display: block;
            padding: 10px;
            border-radius: 5px;
            transition: background-color 0.2s;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: #F72585;
            color: #fff;
        }
        
        .main-dashboard { flex: 1; padding: 20px; overflow-y: auto; }
        .content-container { background: #3A3A5A; padding: 20px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
        h3, label { color: #8D99AE; }
        .warning { background-color: #F8D7DA; color: #721C24; padding: 10px; margin-bottom: 20px; border-radius: 5px; }
        .threshold-group { margin-bottom: 30px; }
        .device-title { font-size: 1.2em; color: #D6D8E7; margin: 15px 0; }

        /* Center threshold controls */
        .threshold-controls { display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; }
        .threshold-control { display: flex; align-items: center; gap: 8px; background-color: #2B2D42; padding: 10px; border-radius: 8px; }
        .threshold-control label { font-size: 1em; color: #D6D8E7; }
        .threshold-control input[type="number"] {
            width: 40px;
            text-align: center;
            background-color: #444;
            color: #D6D8E7;
            border: none;
            border-radius: 5px;
            font-size: 1em;
            font-weight: bold;
        }
        .threshold-control button {
            background-color: #F72585;
            border: none;
            border-radius: 5px;
            color: #fff;
            padding: 5px 8px;
            cursor: pointer;
            font-size: 1em;
        }
        .threshold-control button:hover { background-color: #FF4571; }
        .save-btn { margin-top: 20px; padding: 10px 15px; background-color: #F72585; border: none; border-radius: 5px; color: #fff; cursor: pointer; font-size: 1em; }

        /* Red text styling for values above 9 */
        .high-threshold { color: red; font-weight: bold; }

        /* Bottom section styling */
        .bottom-section {
            border-top: 1px solid #444;
            padding-top: 20px;
            color: #D6D8E7;
            text-align: left;
        }
        .bottom-section h3, .bottom-section h5 { color: #8D99AE; margin-bottom: 10px; }
        .bottom-section a { color: #F72585; text-decoration: none; font-weight: bold; display: inline-block; margin-top: 10px; }
        .bottom-section a:hover { text-decoration: underline; }
    </style>
    <script>
        function updateThreshold(inputId, change) {
            const input = document.getElementById(inputId);
            let value = parseInt(input.value) + change;
            if (value < 0) value = 0;
            input.value = value;
            input.className = value > 9 ? 'high-threshold' : '';
        }
    </script>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div>
                <h2>Leaksense Dashboard</h2>
                <nav>
                    <ul>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="gs1.php">ESP32-GasSensor 1</a></li>
                        <li><a href="gs2.php">ESP32-GasSensor 2</a></li>
                        <li><a href="Reports.php">Reports</a></li>
                        <li><a href="manage_user.php">Manage User</a></li>
                        <li><a href="threshold.php" class="active">Threshold Setup</a></li>
                    </ul>
                </nav>
            </div>
            <div class="bottom-section">
                <h3>USERNAME</h3>
                <h3>Role</h3>
            </div>
            <div class="bottom-section">
                <h3>Language</h3>
                <h5>ENG - FR</h5>
            </div>
            <div class="bottom-section">
                <a href="login.php">Logout</a>
            </div>
        </aside>

        <!-- Main content -->
        <main class="main-dashboard">
            <div class="content-container">
                <h3>Threshold Settings</h3>
                <div class="warning">
                    <p>Warning: Ensure thresholds are set appropriately. Suggested thresholds are:</p>
                    <ul>
                        <li>Smoke Threshold: 2</li>
                        <li>CO Threshold: 3</li>
                        <li>LPG Threshold: 4</li>
                    </ul>
                </div>

                <!-- ESP32-GasSensor 1 Threshold Settings -->
                <div class="threshold-group">
                    <div class="device-title">ESP32-GasSensor 1</div>
                    <div class="threshold-controls">
                        <div class="threshold-control">
                            <label>Smoke Threshold:</label>
                            <button onclick="updateThreshold('sensor1_smoke', -1)">-</button>
                            <input type="number" id="sensor1_smoke" value="3" readonly>
                            <button onclick="updateThreshold('sensor1_smoke', 1)">+</button>
                        </div>
                        <div class="threshold-control">
                            <label>CO Threshold:</label>
                            <button onclick="updateThreshold('sensor1_co', -1)">-</button>
                            <input type="number" id="sensor1_co" value="3" readonly>
                            <button onclick="updateThreshold('sensor1_co', 1)">+</button>
                        </div>
                        <div class="threshold-control">
                            <label>LPG Threshold:</label>
                            <button onclick="updateThreshold('sensor1_lpg', -1)">-</button>
                            <input type="number" id="sensor1_lpg" value="4" readonly>
                            <button onclick="updateThreshold('sensor1_lpg', 1)">+</button>
                        </div>
                    </div>
                </div>

                <!-- ESP32-GasSensor 2 Threshold Settings -->
                <div class="threshold-group">
                    <div class="device-title">ESP32-GasSensor 2</div>
                    <div class="threshold-controls">
                        <div class="threshold-control">
                            <label>Smoke Threshold:</label>
                            <button onclick="updateThreshold('sensor2_smoke', -1)">-</button>
                            <input type="number" id="sensor2_smoke" value="2" readonly>
                            <button onclick="updateThreshold('sensor2_smoke', 1)">+</button>
                        </div>
                        <div class="threshold-control">
                            <label>CO Threshold:</label>
                            <button onclick="updateThreshold('sensor2_co', -1)">-</button>
                            <input type="number" id="sensor2_co" value="3" readonly>
                            <button onclick="updateThreshold('sensor2_co', 1)">+</button>
                        </div>
                        <div class="threshold-control">
                            <label>LPG Threshold:</label>
                            <button onclick="updateThreshold('sensor2_lpg', -1)">-</button>
                            <input type="number" id="sensor2_lpg" value="4" readonly>
                            <button onclick="updateThreshold('sensor2_lpg', 1)">+</button>
                        </div>
                    </div>
                </div>

                <button class="save-btn">Save Changes</button>
            </div>
        </main>
    </div>
</body>
</html>
