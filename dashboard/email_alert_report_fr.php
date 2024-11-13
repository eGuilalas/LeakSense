<?php
session_start();
include '../db_connection.php'; // Include your database connection

// Check if user is logged in
if (!isset($_SESSION['userID'])) {
    // Redirect to login page
    $_SESSION['error'] = "Vous devez vous connecter pour accéder à cette page.";
    header("Location: ../login.php");
    exit();
}

// Fetch email alert reports from the database
$query = "
    SELECT 
        deviceID,
        readingID,
        email,
        gastype,
        gaslevel,
        timestamp,
        status
    FROM 
        alert
    ORDER BY 
        timestamp DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute();
$email_alert_reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport d'Alerte par E-mail - Tableau de bord Leaksense</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; display: flex; transition: background-color 0.3s, color 0.3s; }

        /* Dark Mode */
        body.dark { background-color: #1E1E2D; color: #fff; }
        body.dark .sidebar { background-color: #2B2D42; color: #D6D8E7; }
        body.dark .sidebar h2, body.dark .sidebar a { color: #D6D8E7; }
        body.dark .sidebar a.active, body.dark .sidebar a:hover { background-color: #F72585; color: #fff; }
        body.dark .table-container { background: #3A3A5A; }
        body.dark .bottom-section { color: #D6D8E7; }

        /* Light Mode */
        body.light { background-color: #f0f0f0; color: #333; }
        body.light .sidebar { background-color: #e6e6e6; color: #333; }
        body.light .sidebar h2, body.light .sidebar a { color: #333; }
        body.light .sidebar a.active, body.light .sidebar a:hover { background-color: #4CAF50; color: #fff; }
        body.light .table-container { background: #f9f9f9; }
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
        .table-container { padding: 20px; border-radius: 10px; }
        table { width: 100%; color: inherit; margin-top: 10px; border-collapse: collapse; }
        table th, table td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }

        /* Bottom section styling */
        .bottom-section { border-top: 1px solid #444; padding-top: 20px; color: inherit; text-align: left; }
        .bottom-section h3, .bottom-section h5 { margin-bottom: 10px; }
        .bottom-section a { text-decoration: none; font-weight: bold; display: inline-block; margin-top: 10px; }
    </style>
</head>
<body class="dark">
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div>
                <h2>Tableau de bord Leaksense</h2>
                <nav>
                    <ul>
                        <li><a href="dashboard_fr.php">Tableau de Bord</a></li>
                        <li><a href="gs1_fr.php">ESP32-CapteurGaz 1</a></li>
                        <li><a href="gs2_fr.php">ESP32-CapteurGaz 2</a></li>
                        <li><a href="Reports.php">Rapports</a></li>
                        <?php if ($_SESSION['userrole'] !== 'user'): ?>
                            <li><a href="manage_user_fr.php">Gérer Utilisateurs</a></li>
                            <li><a href="Threshold_fr.php">Paramètres de Seuil</a></li>
                            <li><a href="email_alert_report_fr.php" class="active">Rapport d'Alerte par E-mail</a></li>
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
                <li><a href="email_alert_report.php">Anglais</a></li>
            </div>
            <div class="bottom-section">
                <a href="../logout.php">Déconnexion</a>
            </div>
        </aside>
        
        <!-- Main Dashboard -->
        <main class="main-dashboard">
            <div class="table-container">
                <h3>Rapport d'Alerte par E-mail</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID Appareil</th>
                            <th>ID Lecture</th>
                            <th>Email</th>
                            <th>Type de Gaz</th>
                            <th>Niveau de Gaz (ppm)</th>
                            <th>Horodatage</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($email_alert_reports as $report): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($report['deviceID']); ?></td>
                                <td><?php echo htmlspecialchars($report['readingID']); ?></td>
                                <td><?php echo htmlspecialchars($report['email']); ?></td>
                                <td><?php echo htmlspecialchars($report['gastype']); ?></td>
                                <td><?php echo htmlspecialchars($report['gaslevel']); ?></td>
                                <td><?php echo htmlspecialchars($report['timestamp']); ?></td>
                                <td><?php echo htmlspecialchars($report['status'] == 1 ? 'Envoyé avec succès' : 'Échec'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
    document.getElementById('theme-toggle').addEventListener('change', function() {
        document.body.classList.toggle('light', this.checked);
        document.body.classList.toggle('dark', !this.checked);
    });
    </script>
</body>
</html>
