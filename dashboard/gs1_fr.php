<?php
session_start(); // Démarrer la session
include '../db_connection.php'; // Inclure la connexion à la base de données

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['userID'])) {
    // Rediriger vers la page de connexion
    $_SESSION['error'] = "Vous devez vous connecter pour accéder à cette page.";
    header("Location: ../login.php");
    exit();
}

// Gérer la soumission du formulaire pour l'accusé de réception ou la fausse alerte
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $readingID = $_POST['readingID'];
    $comment = $_POST['comment'];

    // Déterminer le statut en fonction de l'action
    $status = ($action == 'acknowledge') ? 2 : 3; // 2 pour Accusé de Réception, 3 pour Fausse Alarme

    // Récupérer l'ID de l'utilisateur connecté à partir de la session
    $userID = $_SESSION['userID']; // Supposant que l'ID de l'utilisateur est stocké dans la session à la connexion

    // Mettre à jour la base de données avec le nouveau statut, commentaire et action de l'utilisateur
    $updateQuery = "
        UPDATE sensor_reading 
        SET status = :status, comment = :comment, actionby = :actionby, actionbytimestamp = NOW() 
        WHERE readingID = :readingID
    ";

    $updateStmt = $pdo->prepare($updateQuery);
    $updateStmt->bindParam(':status', $status);
    $updateStmt->bindParam(':comment', $comment);
    $updateStmt->bindParam(':actionby', $userID);
    $updateStmt->bindParam(':readingID', $readingID);
    
    if ($updateStmt->execute()) {
        echo "<script>alert('Action enregistrée avec succès.');</script>";
    } else {
        echo "<script>alert('Échec de l'enregistrement de l'action.');</script>";
    }
}

// Récupérer les relevés de gaz de la base de données avec un statut d'alerte spécifié pour GS1
$query = "
    SELECT 
        sr.readingID, 
        sr.deviceID, 
        sr.ppm, 
        sr.smoke_status, 
        sr.co_status, 
        sr.lpg_status, 
        sr.timestamp, 
        sr.status, 
        u.username AS actioned_by,
        sr.comment, 
        sr.actionbytimestamp -- Sélectionner le champ actionbytimestamp réel
    FROM 
        sensor_reading sr
    JOIN 
        device d ON sr.deviceID = d.deviceID
    LEFT JOIN 
        `user` u ON sr.actionby = u.userID  
    WHERE 
        sr.deviceID = 'GS1' AND sr.status IN (1, 2, 3)
    ORDER BY 
        sr.timestamp DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute();
$gas_readings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord ESP32-CapteurGaz 1</title>
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
        .bottom-section { border-top: 1px solid #444; padding-top: 20px; color: #D6D8E7; text-align: left; }
        .bottom-section h3, .bottom-section h5 { color: #8D99AE; margin-bottom: 10px; }
        .bottom-section a { color: #F72585; text-decoration: none; font-weight: bold; }

        .main-dashboard { flex: 1; padding: 20px; overflow-y: auto; }
        .dashboard-header { display: flex; gap: 20px; margin-bottom: 20px; }
        .header-box { background: #3A3A5A; padding: 20px; border-radius: 10px; flex: 1; }
        .header-box h3 { color: #8D99AE; }
        
        .filter-section { margin: 20px 0; color: #8D99AE; }
        .filter-section select { padding: 5px; background-color: #3A3A5A; color: #D6D8E7; border: none; border-radius: 5px; }

        .table-container { background: #3A3A5A; padding: 20px; border-radius: 10px; }
        table { width: 100%; color: #D6D8E7; margin-top: 10px; border-collapse: collapse; }
        table th, table td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        .status-pending { color: #36A2EB; font-weight: bold; }
        .status-acknowledged { color: #FF6384; font-weight: bold; }
        .status-false-alarm { color: #FFCE56; font-weight: bold; }
        .action-button {
            background-color: #FF6384; /* Couleur du bouton Accusé de Réception */
            color: white; /* Couleur du texte */
            border: none; /* Pas de bordure */
            padding: 10px 15px; /* Padding pour la taille */
            border-radius: 5px; /* Coins arrondis */
            cursor: pointer; /* Curseur pointeur au survol */
            transition: background-color 0.3s; /* Transition lisse pour la couleur */
            font-size: 14px; /* Taille de police */
        }
        .action-button.false-alarm {
            background-color: #FFCE56; /* Couleur du bouton Fausse Alarme */
        }
        .action-button:hover {
            opacity: 0.8; /* Légèrement estompé au survol */
        }
        .popup {
            display: none; /* Caché par défaut */
            position: fixed; /* Resté en place */
            left: 0;
            top: 0;
            width: 100%; /* Largeur complète */
            height: 100%; /* Hauteur complète */
            background-color: rgba(0, 0, 0, 0.5); /* Fond noir avec opacité */
            z-index: 999; /* En haut */
        }
        .popup-content {
            background-color: #fefefe;
            margin: 15% auto; /* 15% du haut et centré */
            padding: 20px;
            border: 1px solid #888;
            width: 80%; /* Peut varier selon la taille de l'écran */
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Barre latérale -->
        <aside class="sidebar">
            <div>
                <h2>Tableau de Bord ESP32-CapteurGaz 1</h2>
                <nav>
                    <ul>
                        <li><a href="dashboard_fr.php">Tableau de Bord</a></li>
                        <li><a href="#" class="active">ESP32-CapteurGaz1</a></li>
                        <li><a href="gs2_fr.php">ESP32-CapteurGaz2</a></li>
                        <li><a href="Reports_fr.php">Rapports</a></li>
                        <?php if ($_SESSION['userrole'] !== 'user'): ?>
                            <li><a href="manage_user_fr.php">Manage User</a></li>
                            <li><a href="Threshold_fr.php">Threshold Setup</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <div class="bottom-section">
                <h3>Bienvenue !</h3>
                <h4><?php echo htmlspecialchars($_SESSION['username']); ?></h4>
                <h4>Rôle : <?php echo htmlspecialchars($_SESSION['userrole']); ?></h4>
            </div>
            <div class="bottom-section">
                <h3>Langue</h3>
                <li><a href="gs1.php">English</a></li>
            </div>
            <div class="bottom-section">
                <a href="../logout.php">Déconnexion</a>
            </div>
        </aside>
        
        <!-- Tableau de bord principal -->
        <main class="main-dashboard">
            <div class="dashboard-header">
                <div class="header-box">
                    <h3>En Attente</h3>
                    <p><?php echo count(array_filter($gas_readings, fn($reading) => $reading['status'] == 1)); ?></p>
                </div>
                <div class="header-box">
                    <h3>Accusé de Réception</h3>
                    <p><?php echo count(array_filter($gas_readings, fn($reading) => $reading['status'] == 2)); ?></p>
                </div>
                <div class="header-box">
                    <h3>Fausse Alarme</h3>
                    <p><?php echo count(array_filter($gas_readings, fn($reading) => $reading['status'] == 3)); ?></p>
                </div>
            </div>

            <!-- Section des filtres avec une liste déroulante -->
            <div class="filter-section">
                <label for="statusFilter">Filtrer par statut :</label>
                <select id="statusFilter" onchange="filterStatus(this.value)">
                    <option value="">Tous</option>
                    <option value="Pending">En Attente</option>
                    <option value="Acknowledged">Accusé de Réception</option>
                    <option value="False Alarm">Fausse Alarme</option>
                </select>
            </div>

            <!-- Tableau des relevés de gaz -->
            <div class="table-container">
                <h3>Relevés de Gaz</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Niveau de Gaz (ppm)</th>
                            <th>Gaz Détecté</th>
                            <th>Horodatage</th>
                            <th>Statut de l'Alerte</th>
                            <th>Actionné par</th>
                            <th>Temps de Réponse</th>
                            <th>Commentaire</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="gasReadingsTable">
                        <?php foreach ($gas_readings as $reading): ?>
                            <tr data-status="<?php echo strtolower($reading["status"]); ?>">
                                <td><?php echo $reading["ppm"]; ?></td>
                                <td>
                                    <?php 
                                    if ($reading["smoke_status"] == 1) {
                                        echo "Fumée Détectée";
                                    } elseif ($reading["co_status"] == 1) {
                                        echo "CO Détecté";
                                    } elseif ($reading["lpg_status"] == 1) {
                                        echo "LPG Détecté";
                                    } else {
                                        echo "Aucun Gaz Détecté";
                                    }
                                    ?>
                                </td>
                                <td><?php echo $reading["timestamp"]; ?></td>
                                <td class="<?php echo 'status-' . ($reading['status'] == 1 ? 'pending' : ($reading['status'] == 2 ? 'acknowledged' : 'false-alarm')); ?>">
                                    <?php 
                                    if ($reading["status"] == 1) {
                                        echo "En Attente";
                                    } elseif ($reading["status"] == 2) {
                                        echo "Accusé de Réception";
                                    } elseif ($reading["status"] == 3) {
                                        echo "Fausse Alarme";
                                    }
                                    ?>
                                </td>
                                <td><?php echo $reading["actioned_by"]; ?></td>
                                <td><?php echo $reading["actionbytimestamp"]; ?></td> <!-- Afficher le temps de réponse -->
                                <td><?php echo $reading["comment"] ? $reading["comment"] : 'Aucun commentaire'; ?></td> <!-- Afficher le commentaire -->
                                <td>
                                    <button class="action-button acknowledge-button" onclick="openPopup('<?php echo $reading['readingID']; ?>', 'acknowledge')">Accuser Réception</button> 
                                    <button class="action-button false-alarm-button" onclick="openPopup('<?php echo $reading['readingID']; ?>', 'false_alarm')">Fausse Alarme</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <div id="popup" class="popup">
        <div class="popup-content">
            <span class="close" onclick="closePopup()">&times;</span>
            <h2 id="popupTitle">Entrer un Commentaire</h2>
            <form id="popupForm" method="POST">
                <input type="hidden" name="readingID" id="readingID">
                <input type="hidden" name="action" id="action">
                <input type="text" name="comment" placeholder="Entrez un commentaire" required>
                <button type="submit">Soumettre</button>
            </form>
        </div>
    </div>

    <script>
        function filterStatus(status) {
            const rows = document.querySelectorAll("#gasReadingsTable tr");
            rows.forEach(row => {
                const rowStatus = row.getAttribute("data-status");
                if (status === "" || (status === "Pending" && rowStatus == 1) || 
                    (status === "Acknowledged" && rowStatus == 2) || 
                    (status === "False Alarm" && rowStatus == 3)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }

        function openPopup(readingID, action) {
            document.getElementById('readingID').value = readingID;
            document.getElementById('action').value = action;
            document.getElementById('popup').style.display = 'block';
        }

        function closePopup() {
            document.getElementById('popup').style.display = 'none';
        }
    </script>
</body>
</html>