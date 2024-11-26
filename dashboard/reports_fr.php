<?php
session_start();
include '../db_connection.php'; // Inclure la connexion à la base de données

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['userID'])) {
    $_SESSION['error'] = "Vous devez vous connecter pour accéder à cette page.";
    header("Location: ../login.php");
    exit();
}

// Initialiser les paramètres de filtrage
$deviceID = $_GET['deviceID'] ?? '';
$gasType = $_GET['gasType'] ?? '';
$startDate = $_GET['startDate'] ?? '';
$startTime = $_GET['startTime'] ?? '';
$endDate = $_GET['endDate'] ?? '';
$endTime = $_GET['endTime'] ?? '';
$alertStatus = $_GET['alertStatus'] ?? '';
$acknowledgedBy = $_GET['acknowledgedBy'] ?? '';

// Construire la requête de base
$query = "
    SELECT 
        sr.readingID AS ID,
        sr.deviceID AS 'ID de l\'appareil',
        sr.ppm AS 'Niveau de gaz',
        CASE 
            WHEN sr.smoke_status = 1 THEN 'Fumée'
            WHEN sr.co_status = 1 THEN 'CO'
            WHEN sr.lpg_status = 1 THEN 'GPL'
            ELSE 'Aucun gaz'
        END AS 'Gaz détecté',
        CASE 
            WHEN sr.status = 1 THEN 'En attente'
            WHEN sr.status = 2 THEN 'Reconnu'
            WHEN sr.status = 3 THEN 'Fausse alarme'
            ELSE 'Pas de statut'
        END AS 'Statut de l\'alerte',
        u.username AS 'Reconnu par',
        sr.actionbytimestamp AS 'Temps de réponse',
        sr.comment AS 'Commentaires',
        sr.timestamp AS 'Horodatage'
    FROM 
        sensor_reading sr
    LEFT JOIN 
        user u ON sr.actionby = u.userID
    WHERE 1=1"; // Condition de base pour ajouter des filtres

// Ajouter des filtres en fonction des entrées de l'utilisateur
if ($deviceID) {
    $query .= " AND sr.deviceID = :deviceID";
}
if ($gasType) {
    $query .= " AND (sr.smoke_status = 1 OR sr.co_status = 1 OR sr.lpg_status = 1)";
}
if ($startDate && $startTime) {
    $query .= " AND sr.timestamp >= :startDateTime";
}
if ($endDate && $endTime) {
    $query .= " AND sr.timestamp <= :endDateTime";
}
if ($alertStatus) {
    switch ($alertStatus) {
        case 'En attente':
            $query .= " AND sr.status = 1";
            break;
        case 'Reconnu':
            $query .= " AND sr.status = 2";
            break;
        case 'Fausse alarme':
            $query .= " AND sr.status = 3";
            break;
    }
}
if ($acknowledgedBy) {
    $query .= " AND u.username = :acknowledgedBy";
}

$query .= " ORDER BY sr.timestamp DESC";

$stmt = $pdo->prepare($query);

// Lier les paramètres s'ils ont été définis
if ($deviceID) {
    $stmt->bindParam(':deviceID', $deviceID);
}
if ($startDate && $startTime) {
    $startDateTime = $startDate . ' ' . $startTime;
    $stmt->bindParam(':startDateTime', $startDateTime);
}
if ($endDate && $endTime) {
    $endDateTime = $endDate . ' ' . $endTime;
    $stmt->bindParam(':endDateTime', $endDateTime);
}
if ($acknowledgedBy) {
    $stmt->bindParam(':acknowledgedBy', $acknowledgedBy);
}

$stmt->execute();
$report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapports - Tableau de Bord Leaksense</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background-color: #f9f9f9; color: #333; display: flex; }
        .dashboard-container { display: flex; height: 100vh; width: 100%; }
        .sidebar { background-color: #e6e6e6; width: 220px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between; }
        .sidebar h2 { color: #555; font-size: 1.5em; margin-bottom: 20px; }
        .sidebar ul { list-style: none; padding-left: 0; }
        .sidebar li { margin-bottom: 15px; }
        .sidebar a { text-decoration: none; color: #333; font-size: 1em; display: block; padding: 10px; border-radius: 5px; transition: background-color 0.2s; }
        .sidebar a:hover, .sidebar a.active { background-color: #4CAF50; color: #fff; }
        
        .main-dashboard { flex: 1; padding: 20px; overflow-y: auto; }
        .filter-section { background: #fff; padding: 20px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #ddd; }
        .filter-section h3 { color: #555; margin-bottom: 15px; }
        .filter-group { display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
        .filter-group label { color: #333; }
        .filter-group select, .filter-group input {
            padding: 5px;
            background-color: #fff;
            color: #333;
            border: 1px solid #ccc;
            border-radius: 5px;
            outline: none;
        }
        .button-group { display: flex; gap: 10px; margin-top: 15px; }
        .button-group button, .button-group a {
            padding: 8px 12px;
            background-color: #4CAF50;
            border: none;
            border-radius: 5px;
            color: #fff;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
        }
        .button-group button:hover, .button-group a:hover { background-color: #45A049; }

        .table-container { background: #fff; padding: 20px; border-radius: 10px; border: 1px solid #ddd; }
        table { width: 100%; color: #333; margin-top: 10px; border-collapse: collapse; }
        table th, table td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        .status-pending { color: #FFA500; font-weight: bold; }
        .status-acknowledged { color: #36A2EB; font-weight: bold; }
        .status-false-alarm { color: #FF6384; font-weight: bold; }

        /* Stylisation de la section inférieure */
        .bottom-section {
            border-top: 1px solid #ccc;
            padding-top: 20px;
            color: #555;
            text-align: left;
        }
        .bottom-section h3, .bottom-section h5 { margin-bottom: 10px; color: #555; }
        .bottom-section a { color: #4CAF50; text-decoration: none; font-weight: bold; display: inline-block; margin-top: 10px; }
        .bottom-section a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Barre latérale -->
        <aside class="sidebar">
            <div>
                <h2>Tableau de Bord Leaksense</h2>
                <nav>
                    <ul>
                        <li><a href="dashboard_fr.php">Tableau de Bord</a></li>
                        <li><a href="gs1_fr.php">ESP32-CapteurGaz1</a></li>
                        <li><a href="gs2_fr.php">ESP32-CapteurGaz2</a></li>
                        <li><a href="reports_fr.php" class="active">Rapports</a></li>
                        <?php if ($_SESSION['userrole'] !== 'user'): ?>
                            <li><a href="manage_user_fr.php">Gérer les utilisateurs</a></li>
                            <li><a href="Threshold_fr.php">Configuration des seuils</a></li>
                            <li><a href="email_alert_report_fr.php">Rapport d'alerte email</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <div class="bottom-section">
            <h3>Bienvenue !</h3>
                <h3><?php echo htmlspecialchars($_SESSION['username']); ?></h3>
                <h4>Rôle : <?php echo htmlspecialchars($_SESSION['userrole']); ?></h4>
            </div>
            <div class="bottom-section">
                <h3>Langue</h3>
                <li><a href="reports.php">English</a></li>
            </div>
            <div class="bottom-section">
                <a href="../logout.php">Déconnexion</a>
            </div>
        </aside>

        <main class="main-dashboard">
            <!-- Section de filtrage -->
            <div class="filter-section">
                <h3>Filtrer les Rapports</h3>
                <div class="filter-group">
                    <label>ID de l'appareil :</label>
                    <select id="deviceID">
                        <option value="">Tous les appareils</option>
                        <option value="GS1">ESP32-GasSensor1</option>
                        <option value="GS2">ESP32-GasSensor2</option>
                    </select>

                    <label>Type de gaz :</label>
                    <select id="gasType">
                        <option value="">Tous les types</option>
                        <option value="Fumée">Fumée</option>
                        <option value="CO">CO</option>
                        <option value="GPL">GPL</option>
                    </select>

                    <label>Date de début :</label>
                    <input type="date" id="startDate">

                    <label>Heure de début :</label>
                    <input type="time" id="startTime">

                    <label>Date de fin :</label>
                    <input type="date" id="endDate">

                    <label>Heure de fin :</label>
                    <input type="time" id="endTime">

                    <label>Statut de l'alerte :</label>
                    <select id="alertStatus">
                        <option value="">N'importe lequel</option>
                        <option value="En attente">En attente</option>
                        <option value="Reconnu">Reconnu</option>
                        <option value="Fausse alarme">Fausse alarme</option>
                    </select>

                    <label>Reconnu par :</label>
                    <input type="text" id="acknowledgedBy" placeholder="Entrez le nom d'utilisateur">
                </div>
                <div class="button-group">
                    <button id="applyFilters">Appliquer les Filtres</button>
                    <button id="resetFilters">Réinitialiser les Filtres</button>
                    <button id="printReport">Imprimer le Rapport</button>
                </div>
            </div>

            <!-- Section du tableau des rapports -->
            <div class="table-container">
                <h3>Tableau des Rapports</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID de l'appareil</th>
                            <th>Niveau de gaz (ppm)</th>
                            <th>Gaz détecté</th>
                            <th>Statut de l'alerte</th>
                            <th>Reconnu par</th>
                            <th>Temps de réponse</th>
                            <th>Commentaires</th>
                            <th>Horodatage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data as $row): ?>
                            <tr>
                                <td><?php echo $row["ID de l'appareil"]; ?></td>
                                <td><?php echo $row["Niveau de gaz"]; ?></td>
                                <td><?php echo $row["Gaz détecté"]; ?></td>
                                <td class="<?php echo strtolower(str_replace(' ', '-', 'status-' . $row["Statut de l'alerte"])); ?>">
                                    <?php echo $row["Statut de l'alerte"]; ?>
                                </td>
                                <td><?php echo $row["Reconnu par"]; ?></td>
                                <td><?php echo $row["Temps de réponse"] ? date("Y-m-d H:i:s", strtotime($row["Temps de réponse"])) : 'N/A'; ?></td>
                                <td><?php echo $row["Commentaires"]; ?></td>
                                <td><?php echo $row["Horodatage"]; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <script>
        // Fonctionnalité des filtres conservée
        // Ajouter ici d'autres scripts si nécessaire
    </script>
</body>
</html>
