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
        body { font-family: Arial, sans-serif; background-color: #1E1E2D; color: #fff; display: flex; }
        .dashboard-container { display: flex; height: 100vh; width: 100%; }
        .sidebar { background-color: #2B2D42; width: 220px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between; }
        .sidebar h2 { color: #8D99AE; font-size: 1.5em; margin-bottom: 20px; }
        .sidebar ul { list-style: none; padding-left: 0; }
        .sidebar li { margin-bottom: 15px; }
        .sidebar a { text-decoration: none; color: #D6D8E7; font-size: 1em; display: block; padding: 10px; border-radius: 5px; transition: background-color 0.2s; }
        .sidebar a:hover, .sidebar a.active { background-color: #F72585; color: #fff; }
        
        .main-dashboard { flex: 1; padding: 20px; overflow-y: auto; }
        .filter-section { background: #3A3A5A; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .filter-section h3 { color: #8D99AE; margin-bottom: 15px; }
        .filter-group { display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
        .filter-group label { color: #D6D8E7; }
        .filter-group select, .filter-group input {
            padding: 5px;
            background-color: #2B2D42;
            color: #D6D8E7;
            border: 1px solid #444;
            border-radius: 5px;
            outline: none;
        }
        .button-group { display: flex; gap: 10px; margin-top: 15px; }
        .button-group button, .button-group a {
            padding: 8px 12px;
            background-color: #F72585;
            border: none;
            border-radius: 5px;
            color: #fff;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
        }
        .button-group button:hover, .button-group a:hover { background-color: #FF4571; }

        .table-container { background: #3A3A5A; padding: 20px; border-radius: 10px; }
        table { width: 100%; color: #D6D8E7; margin-top: 10px; border-collapse: collapse; }
        table th, table td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        .status-pending { color: #FFA500; font-weight: bold; }
        .status-acknowledged { color: #36A2EB; font-weight: bold; }
        .status-false-alarm { color: #FF6384; font-weight: bold; }

        /* Stylisation de la section inférieure */
        .bottom-section {
            border-top: 1px solid #444;
            padding-top: 20px;
            color: #D6D8E7;
            text-align: left;
        }
        .bottom-section h3, .bottom-section h5 { margin-bottom: 10px; color: #D6D8E7; }
        .bottom-section a { color: #F72585; text-decoration: none; font-weight: bold; display: inline-block; margin-top: 10px; }
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
                        <li><a href="gs1_fr.php">ESP32-GasSensor1</a></li>
                        <li><a href="gs2_fr.php">ESP32-GasSensor2</a></li>
                        <li><a href="Reports_fr.php" class="active">Rapports</a></li>
                        <?php if ($_SESSION['userrole'] !== 'user'): ?>
                            <li><a href="manage_user_fr.php">Manage User</a></li>
                            <li><a href="Threshold_fr.php">Threshold Setup</a></li>
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
                    <a href="../api/export_csv.php?deviceID=<?php echo urlencode($deviceID); ?>&gasType=<?php echo urlencode($gasType); ?>&startDate=<?php echo urlencode($startDate); ?>&startTime=<?php echo urlencode($startTime); ?>&endDate=<?php echo urlencode($endDate); ?>&endTime=<?php echo urlencode($endTime); ?>&alertStatus=<?php echo urlencode($alertStatus); ?>&acknowledgedBy=<?php echo urlencode($acknowledgedBy); ?>" class="export-button">Exporter en CSV</a>
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
        // Fonction pour appliquer les filtres
        document.getElementById('applyFilters').addEventListener('click', function() {
            const deviceID = document.getElementById('deviceID').value;
            const gasType = document.getElementById('gasType').value;
            const startDate = document.getElementById('startDate').value;
            const startTime = document.getElementById('startTime').value;
            const endDate = document.getElementById('endDate').value;
            const endTime = document.getElementById('endTime').value;
            const alertStatus = document.getElementById('alertStatus').value;
            const acknowledgedBy = document.getElementById('acknowledgedBy').value;

            // Créer une chaîne de requête avec les filtres
            const queryString = `?deviceID=${deviceID}&gasType=${gasType}&startDate=${startDate}&startTime=${startTime}&endDate=${endDate}&endTime=${endTime}&alertStatus=${alertStatus}&acknowledgedBy=${acknowledgedBy}`;

            // Rediriger vers la même page avec les paramètres de requête
            window.location.href = window.location.pathname + queryString;
        });

        // Réinitialiser les filtres
        document.getElementById('resetFilters').addEventListener('click', function() {
            document.getElementById('deviceID').value = '';
            document.getElementById('gasType').value = '';
            document.getElementById('startDate').value = '';
            document.getElementById('startTime').value = '';
            document.getElementById('endDate').value = '';
            document.getElementById('endTime').value = '';
            document.getElementById('alertStatus').value = '';
            document.getElementById('acknowledgedBy').value = '';
            // Réinitialiser l'URL pour effacer les filtres
            window.history.pushState({}, document.title, window.location.pathname);
        });

        // Imprimer le rapport
        document.getElementById('printReport').addEventListener('click', function() {
            const printContent = document.querySelector('.table-container').innerHTML;
            const originalContent = document.body.innerHTML;

            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContent; // Restaurer le contenu original après impression
        });

        // Appliquer le filtre en appuyant sur la touche Entrée dans l'entrée Reconnu par
        document.getElementById('acknowledgedBy').addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault(); // Empêcher la soumission du formulaire par défaut
                document.getElementById('applyFilters').click(); // Déclencher l'application des filtres
            }
        });
    </script>
</body>
</html>
