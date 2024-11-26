<?php
session_start();
include '../db_connection.php'; // Inclure votre connexion à la base de données

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['userID'])) {
    $_SESSION['error'] = "Vous devez vous connecter pour accéder à cette page.";
    header("Location: ../login.php");
    exit();
}

// Traitement de la soumission du formulaire pour mettre à jour les seuils
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gs1_smoke = (float)$_POST['sensor1_smoke'];
    $gs1_co = (float)$_POST['sensor1_co'];
    $gs1_lpg = (float)$_POST['sensor1_lpg'];

    $gs2_smoke = (float)$_POST['sensor2_smoke'];
    $gs2_co = (float)$_POST['sensor2_co'];
    $gs2_lpg = (float)$_POST['sensor2_lpg'];

    // Mettre à jour les seuils pour GS1
    $stmt = $pdo->prepare("UPDATE thresholds SET smoke_threshold = :smoke, co_threshold = :co, lpg_threshold = :lpg WHERE deviceID = 'GS1'");
    $stmt->execute(['smoke' => $gs1_smoke, 'co' => $gs1_co, 'lpg' => $gs1_lpg]);

    // Mettre à jour les seuils pour GS2
    $stmt = $pdo->prepare("UPDATE thresholds SET smoke_threshold = :smoke, co_threshold = :co, lpg_threshold = :lpg WHERE deviceID = 'GS2'");
    $stmt->execute(['smoke' => $gs2_smoke, 'co' => $gs2_co, 'lpg' => $gs2_lpg]);

    $_SESSION['success'] = "Les seuils ont été mis à jour avec succès.";
    header("Location: threshold_fr.php");
    exit();
}

// Récupérer les seuils actuels dans la base de données
$stmt = $pdo->query("SELECT * FROM thresholds");
$thresholds = $stmt->fetchAll(PDO::FETCH_ASSOC);

$gs1 = array_values(array_filter($thresholds, fn($t) => $t['deviceID'] === 'GS1'))[0] ?? ['smoke_threshold' => 0, 'co_threshold' => 0, 'lpg_threshold' => 0];
$gs2 = array_values(array_filter($thresholds, fn($t) => $t['deviceID'] === 'GS2'))[0] ?? ['smoke_threshold' => 0, 'co_threshold' => 0, 'lpg_threshold' => 0];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mise en place des seuils - Tableau de bord Leaksense</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background-color: #f9f9f9; color: #333; display: flex; }
        .dashboard-container { display: flex; height: 100vh; width: 100%; }
        .sidebar { background-color: #e6e6e6; width: 220px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between; }
        .sidebar h2 { color: #555; font-size: 1.5em; margin-bottom: 20px; }
        .sidebar ul { list-style: none; padding-left: 0; }
        .sidebar li { margin-bottom: 15px; }
        .sidebar a {
            text-decoration: none;
            color: #333;
            font-size: 1em;
            display: block;
            padding: 10px;
            border-radius: 5px;
            transition: background-color 0.2s;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: #4CAF50;
            color: #fff;
        }
        
        .main-dashboard { flex: 1; padding: 20px; overflow-y: auto; }
        .content-container { background: #fff; padding: 20px; border-radius: 10px; margin-bottom: 20px; text-align: center; border: 1px solid #ddd; }
        h3, label { color: #555; }
        .warning { background-color: #FFF4E5; color: #C27C2C; padding: 10px; margin-bottom: 20px; border-radius: 5px; border: 1px solid #F5CBA7; }
        .threshold-group { margin-bottom: 30px; }
        .device-title { font-size: 1.2em; color: #333; margin: 15px 0; }

        /* Center threshold controls */
        .threshold-controls { display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; }
        .threshold-control { display: flex; align-items: center; gap: 8px; background-color: #f1f1f1; padding: 10px; border-radius: 8px; }
        .threshold-control label { font-size: 1em; color: #333; }
        .threshold-control input[type="number"] {
            width: 60px;
            text-align: center;
            background-color: #fff;
            color: #333;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            font-weight: bold;
        }
        .threshold-control button {
            background-color: #4CAF50;
            border: none;
            border-radius: 5px;
            color: #fff;
            padding: 5px 8px;
            cursor: pointer;
            font-size: 1em;
        }
        .threshold-control button:hover { background-color: #45A049; }
        .save-btn { margin-top: 20px; padding: 10px 15px; background-color: #4CAF50; border: none; border-radius: 5px; color: #fff; cursor: pointer; font-size: 1em; }

        /* Red text styling for values above 9 */
        .high-threshold { color: red; font-weight: bold; }

        /* Bottom section styling */
        .bottom-section {
            border-top: 1px solid #ccc;
            padding-top: 20px;
            color: #555;
            text-align: left;
        }
        .bottom-section h3, .bottom-section h5 { color: #555; margin-bottom: 10px; }
        .bottom-section a { color: #4CAF50; text-decoration: none; font-weight: bold; display: inline-block; margin-top: 10px; }
        .bottom-section a:hover { text-decoration: underline; }
    </style>
    <script>
        function updateThreshold(inputId, change) {
            const input = document.getElementById(inputId);
            let value = parseFloat(input.value) + change;
            if (value < 0) value = 0;
            input.value = value.toFixed(1);
            input.className = value > 9 ? 'high-threshold' : '';
        }
    </script>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div>
                <h2>Tableau de bord Leaksense</h2>
                <nav>
                    <ul>
                        <li><a href="dashboard_fr.php">Tableau de bord</a></li>
                        <li><a href="gs1_fr.php">ESP32-GasSensor 1</a></li>
                        <li><a href="gs2_fr.php">ESP32-GasSensor 2</a></li>
                        <li><a href="Reports_fr.php">Rapports</a></li>
                        <li><a href="manage_user_fr.php">Gérer les utilisateurs</a></li>
                        <li><a href="threshold_fr.php" class="active">Configuration des seuils</a></li>
                        <li><a href="email_alert_report_fr.php">Email Alert Report</a></li>
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
                <li><a href="threshold.php">English</a></li>
            </div>
            <div class="bottom-section">
                <a href="../logout.php">Déconnexion</a>
            </div>
        </aside>

        <!-- Main content -->
        <main class="main-dashboard">
            <div class="content-container">
                <h3>Paramètres des seuils</h3>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="warning" style="background-color: #D4EDDA; color: #155724;">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="warning">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                <div class="warning">
                    <p>Avertissement : Assurez-vous que les seuils sont correctement définis. Les seuils suggérés sont :</p>
                    <ul>
                        <li>Seuil de fumée : 2.0</li>
                        <li>Seuil de CO : 3.0</li>
                        <li>Seuil de GPL : 4.0</li>
                    </ul>
                </div>

                <form method="post" action="">
                    <!-- Seuils de l'ESP32-GasSensor 1 -->
                    <div class="threshold-group">
                        <div class="device-title">ESP32-GasSensor 1</div>
                        <div class="threshold-controls">
                            <div class="threshold-control">
                                <label>Seuil de fumée :</label>
                                <button type="button" onclick="updateThreshold('sensor1_smoke', -0.1)">-</button>
                                <input type="number" step="0.1" id="sensor1_smoke" name="sensor1_smoke" value="<?php echo htmlspecialchars($gs1['smoke_threshold']); ?>">
                                <button type="button" onclick="updateThreshold('sensor1_smoke', 0.1)">+</button>
                            </div>
                            <div class="threshold-control">
                                <label>Seuil de CO :</label>
                                <button type="button" onclick="updateThreshold('sensor1_co', -0.1)">-</button>
                                <input type="number" step="0.1" id="sensor1_co" name="sensor1_co" value="<?php echo htmlspecialchars($gs1['co_threshold']); ?>">
                                <button type="button" onclick="updateThreshold('sensor1_co', 0.1)">+</button>
                            </div>
                            <div class="threshold-control">
                                <label>Seuil de GPL :</label>
                                <button type="button" onclick="updateThreshold('sensor1_lpg', -0.1)">-</button>
                                <input type="number" step="0.1" id="sensor1_lpg" name="sensor1_lpg" value="<?php echo htmlspecialchars($gs1['lpg_threshold']); ?>">
                                <button type="button" onclick="updateThreshold('sensor1_lpg', 0.1)">+</button>
                            </div>
                        </div>
                    </div>

                    <!-- Seuils de l'ESP32-GasSensor 2 -->
                    <div class="threshold-group">
                        <div class="device-title">ESP32-GasSensor 2</div>
                        <div class="threshold-controls">
                            <div class="threshold-control">
                                <label>Seuil de fumée :</label>
                                <button type="button" onclick="updateThreshold('sensor2_smoke', -0.1)">-</button>
                                <input type="number" step="0.1" id="sensor2_smoke" name="sensor2_smoke" value="<?php echo htmlspecialchars($gs2['smoke_threshold']); ?>">
                                <button type="button" onclick="updateThreshold('sensor2_smoke', 0.1)">+</button>
                            </div>
                            <div class="threshold-control">
                                <label>Seuil de CO :</label>
                                <button type="button" onclick="updateThreshold('sensor2_co', -0.1)">-</button>
                                <input type="number" step="0.1" id="sensor2_co" name="sensor2_co" value="<?php echo htmlspecialchars($gs2['co_threshold']); ?>">
                                <button type="button" onclick="updateThreshold('sensor2_co', 0.1)">+</button>
                            </div>
                            <div class="threshold-control">
                                <label>Seuil de GPL :</label>
                                <button type="button" onclick="updateThreshold('sensor2_lpg', -0.1)">-</button>
                                <input type="number" step="0.1" id="sensor2_lpg" name="sensor2_lpg" value="<?php echo htmlspecialchars($gs2['lpg_threshold']); ?>">
                                <button type="button" onclick="updateThreshold('sensor2_lpg', 0.1)">+</button>
                            </div>
                        </div>
                    </div>

                    <button class="save-btn" type="submit">Sauvegarder les modifications</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
