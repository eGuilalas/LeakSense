<?php
// Ensure the session is started and user is authenticated
include '../../config/auth.php'; // Authenticate user
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Threshold Management</title>
    <link rel="stylesheet" href="../admin/dashboard.css">
    <style>
        /* Container styling for threshold management */
        .thresholds-container {
            width: 90%;
            max-width: 800px;
            margin: 30px auto;
            padding: 25px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .thresholds-container h1 {
            color: #4a90e2;
            font-size: 1.8em;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .device-section {
            margin-top: 30px;
            padding: 20px;
            background-color: #f1f8ff;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        .device-header {
            font-size: 1.2em;
            color: #4a90e2;
            background-color: #e3f2fd;
            padding: 10px;
            border-radius: 6px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 15px;
        }

        .threshold-item {
            background-color: #f9f9f9;
            padding: 15px;
            margin: 10px 0;
            border: 1px solid #e0e4e7;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: box-shadow 0.3s ease;
        }

        .threshold-item:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .threshold-item label {
            font-size: 0.95em;
            color: #555;
            font-weight: bold;
            flex: 1;
            text-align: left;
            margin-right: 15px;
        }

        .threshold-item input[type="number"] {
            width: 120px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.95em;
            transition: border-color 0.3s ease;
        }

        .threshold-item input[type="number"]:focus {
            border-color: #4a90e2;
            box-shadow: 0 0 4px rgba(74, 144, 226, 0.4);
        }

        button[type="submit"] {
            width: 50%;
            max-width: 200px;
            padding: 12px;
            font-size: 1em;
            color: #fff;
            background-color: #4a90e2;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            margin-top: 25px;
        }

        button[type="submit"]:hover {
            background-color: #357ABD;
            transform: translateY(-2px);
        }

        #response-message {
            margin-top: 20px;
            font-weight: bold;
            color: green;
            font-size: 1em;
        }
    </style>
</head>
<body>

    <div class="dashboard">
        <div class="sidebar" id="sidebar">
            <button class="hamburger" id="hamburger" onclick="toggleSidebar()">&#9776;</button>
            <h2>Monitoring</h2>
            <a href="../admin/admin_dashboard.php"><span class="icon">📊</span>Dashboard</a>
            <a href="../admin/esp32_1.php"><span class="icon">💽</span>ESP32 - 1</a>
            <a href="../admin/esp32_2.php"><span class="icon">💽</span>ESP32 - 2</a>
            <a href="../admin/reports.php"><span class="icon">📅</span>Reports</a>
            <div class="menu-section">
                <h2>Settings</h2>
                <a href="manage_users.php"><span class="icon">👥</span>Manage Users</a>
                <a href="threshold_management.php" class="active"><span class="icon">⚙️</span>Threshold</a>
                <a href="recipient.php"><span class="icon">⚙️</span>Recipient Setup</a>
            </div>
            <div class="menu-section">
                <h2>Welcome</h2>
                <a href="#"><span class="icon">👤</span><span style="color: red;"><?php echo htmlspecialchars($username); ?></span> - <?php echo htmlspecialchars($role); ?></a>
            </div>
            <div class="menu-section">
                <h2>Logout</h2>
                <a href="../../logout.php"><span class="icon">🚪</span>Logout</a>
            </div>
        </div>

        <div class="main-content" id="main-content">
            <h1>Threshold Management</h1>
            <div class="container thresholds-container">
                <form id="threshold-form">
                    <div id="threshold-list">
                        <!-- Threshold settings will be loaded here dynamically -->
                    </div>
                    <button type="submit">Save Changes</button>
                </form>
                <p id="response-message"></p>
            </div>
        </div>
    </div>

    <script>
        // Load Threshold Data and Populate the Form
        async function loadThresholds() {
            const thresholdList = document.getElementById('threshold-list');
            thresholdList.innerHTML = ''; // Clear previous entries

            try {
                const response = await fetch('../../config/threshold_fetch.php');
                if (!response.ok) throw new Error("Failed to fetch threshold data.");

                const data = await response.json();

                // Separate devices into sections
                const devices = { GS1: [], GS2: [] };
                data.forEach(threshold => {
                    if (threshold.device_id === 'GS1') {
                        devices.GS1.push(threshold);
                    } else if (threshold.device_id === 'GS2') {
                        devices.GS2.push(threshold);
                    }
                });

                // Render each device's thresholds
                Object.keys(devices).forEach(deviceId => {
                    const deviceSection = document.createElement('div');
                    deviceSection.className = 'device-section';
                    deviceSection.innerHTML = `<div class="device-header">Device ID: ${deviceId}</div>`;

                    devices[deviceId].forEach(threshold => {
                        const thresholdItem = document.createElement('div');
                        thresholdItem.className = 'threshold-item';
                        thresholdItem.innerHTML = `
                            <label>Smoke Threshold:
                                <input type="number" name="smoke_threshold[]" step="0.01" value="${threshold.smoke_threshold}">
                            </label>
                            <label>CO Threshold:
                                <input type="number" name="co_threshold[]" step="0.01" value="${threshold.co_threshold}">
                            </label>
                            <label>LPG Threshold:
                                <input type="number" name="lpg_threshold[]" step="0.01" value="${threshold.lpg_threshold}">
                            </label>
                            <input type="hidden" name="device_id[]" value="${threshold.device_id}">
                        `;
                        deviceSection.appendChild(thresholdItem);
                    });

                    thresholdList.appendChild(deviceSection);
                });
            } catch (error) {
                console.error("Error loading thresholds:", error);
                document.getElementById('response-message').innerText = "Error loading thresholds.";
            }
        }

        // Submit the Form to Update Thresholds
        async function updateThresholds(event) {
            event.preventDefault();
            const formData = new FormData(document.getElementById('threshold-form'));

            try {
                const response = await fetch('../../config/threshold_update.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) throw new Error("Failed to update thresholds.");

                const result = await response.json();
                document.getElementById('response-message').innerText = result.message;

                // Reload thresholds to show the updated values
                loadThresholds();
            } catch (error) {
                console.error("Error updating thresholds:", error);
                document.getElementById('response-message').innerText = "Failed to update thresholds.";
            }
        }

        // Event listener for form submission
        document.getElementById('threshold-form').addEventListener('submit', updateThresholds);

        // Load thresholds on page load
        window.addEventListener('load', loadThresholds);
    </script>
</body>
</html>
