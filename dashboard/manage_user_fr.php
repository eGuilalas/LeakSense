<?php
session_start();
include '../db_connection.php'; // Inclure la connexion à la base de données

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['userID'])) {
    $_SESSION['error'] = "Vous devez vous connecter pour accéder à cette page.";
    header("Location: ../login.php");
    exit();
}

// Initialiser les variables
$username = '';
$password = '';
$userrole = '';
$type = '';
$name = '';
$email = '';
$phone = '';
$address = '';
$action = 'Ajouter un Utilisateur';

// Gérer l'action d'édition
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['username'])) {
    $username = $_GET['username'];

    // Récupérer les détails de l'utilisateur de la base de données
    $stmt = $pdo->prepare("SELECT * FROM user WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $userrole = $user['userrole'];
        $type = $user['type'];
        $name = $user['name'];
        $email = $user['email'];
        $phone = $user['phone'];
        $address = $user['address'];
        $action = 'Modifier l\'Utilisateur';
    }
}

// Gérer la soumission du formulaire pour ajouter ou mettre à jour un utilisateur
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
    $userrole = $_POST['userrole'];
    $type = $_POST['type'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    // Validation
    if (empty($username) || empty($userrole) || empty($type) || empty($name) || empty($email) || empty($phone) || empty($address)) {
        $_SESSION['error'] = "Tous les champs sont obligatoires.";
    } else {
        // Vérifier si l'utilisateur existe déjà
        $stmt = $pdo->prepare("SELECT * FROM user WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $existingUser = $stmt->fetch();

        if ($existingUser) {
            // Mettre à jour l'utilisateur existant
            $updateQuery = "UPDATE user SET userrole = :userrole, type = :type, name = :name, email = :email, phone = :phone, address = :address";
            $params = [
                'userrole' => $userrole,
                'type' => $type,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'username' => $username
            ];
            if ($password) {
                $updateQuery .= ", password = :password";
                $params['password'] = $password;
            }
            $updateQuery .= " WHERE username = :username";
            $stmt = $pdo->prepare($updateQuery);
            $stmt->execute($params);
            $_SESSION['success'] = "Utilisateur mis à jour avec succès.";
        } else {
            // Ajouter un nouvel utilisateur
            $stmt = $pdo->prepare("INSERT INTO user (username, password, userrole, type, name, email, phone, address) VALUES (:username, :password, :userrole, :type, :name, :email, :phone, :address)");
            $stmt->execute([
                'username' => $username,
                'password' => $password,
                'userrole' => $userrole,
                'type' => $type,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'address' => $address
            ]);
            $_SESSION['success'] = "Utilisateur ajouté avec succès.";
        }
        header("Location: manage_user.php");
        exit();
    }
}

// Gérer la suppression de l'utilisateur
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['username'])) {
    $usernameToDelete = $_GET['username'];
    $stmt = $pdo->prepare("DELETE FROM user WHERE username = :username");
    $stmt->execute(['username' => $usernameToDelete]);
    $_SESSION['success'] = "Utilisateur supprimé avec succès.";
    header("Location: manage_user.php");
    exit();
}

// Gérer l'action de déverrouillage
if (isset($_GET['action']) && $_GET['action'] === 'unlock' && isset($_GET['username'])) {
    $usernameToUnlock = $_GET['username'];
    $stmt = $pdo->prepare("UPDATE user SET login_attempt = 0, lockout_until = NULL WHERE username = :username");
    $stmt->execute(['username' => $usernameToUnlock]);
    $_SESSION['success'] = "Utilisateur déverrouillé avec succès.";
    header("Location: manage_user.php");
    exit();
}

// Récupérer tous les utilisateurs pour le tableau
$stmt = $pdo->query("SELECT * FROM user");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer les Utilisateurs - Tableau de Bord Leaksense</title>
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
        .form-container, .table-container { background: #3A3A5A; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        h3 { color: #8D99AE; margin-bottom: 15px; }
        label { color: #D6D8E7; display: block; margin-top: 10px; }
        input, select {
            width: 100%;
            padding: 8px;
            background-color: #2B2D42;
            color: #D6D8E7;
            border: 1px solid #444;
            border-radius: 5px;
            margin-top: 5px;
        }
        .button-group { margin-top: 15px; }
        button {
            padding: 10px 15px;
            background-color: #F72585;
            border: none;
            border-radius: 5px;
            color: #fff;
            cursor: pointer;
            margin-right: 10px;
        }
        button:hover { background-color: #FF4571; }
        table { width: 100%; color: #D6D8E7; margin-top: 10px; border-collapse: collapse; }
        table th, table td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        .action-buttons a { color: #F72585; text-decoration: none; margin-right: 10px; font-weight: bold; }
        
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
    <script>
        function filterUsers() {
            const filter = document.getElementById("searchInput").value.toLowerCase();
            const rows = document.querySelectorAll("#userTable tbody tr");
            rows.forEach(row => {
                const username = row.querySelector("td:nth-child(1)").innerText.toLowerCase();
                const name = row.querySelector("td:nth-child(4)").innerText.toLowerCase();
                row.style.display = (username.includes(filter) || name.includes(filter)) ? "" : "none";
            });
        }
    </script>
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
                        <li><a href="Reports_fr.php">Rapports</a></li>
                        <li><a href="#" class="active">Gérer les Utilisateurs</a></li>
                        <li><a href="Threshold_fr.php">Configurer les Seuils</a></li>
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
                <li><a href="manage_user.php">English</a></li>
            </div>
            <div class="bottom-section">
                <a href="../logout.php">Déconnexion</a>
            </div>
        </aside>

        <main class="main-dashboard">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="success-message" style="color: #36C36C; margin-bottom: 20px;">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-message" style="color: #FF4571; margin-bottom: 20px;">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            <!-- Formulaire d'ajout/modification d'utilisateur -->
            <div class="form-container">
                <h3><?php echo $action; ?></h3>
                <form action="" method="post">
                    <label>Nom d'utilisateur</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" required <?php echo ($action === 'Modifier l\'Utilisateur') ? 'readonly' : ''; ?>>

                    <label>Mot de passe</label>
                    <input type="password" name="password" <?php echo ($action === 'Modifier l\'Utilisateur') ? '' : 'required'; ?>>

                    <label>Rôle de l'utilisateur</label>
                    <select name="userrole" required>
                        <option value="admin" <?php echo ($userrole === 'admin') ? 'selected' : ''; ?>>Admin</option>
                        <option value="user" <?php echo ($userrole === 'user') ? 'selected' : ''; ?>>Utilisateur</option>
                        <option value="super_user" <?php echo ($userrole === 'super_user') ? 'selected' : ''; ?>>Super Utilisateur</option>
                        <option value="super_admin" <?php echo ($userrole === 'super_admin') ? 'selected' : ''; ?>>Super Admin</option>
                    </select>

                    <label>Type</label>
                    <select name="type" required>
                        <option value="corporate" <?php echo ($type === 'corporate') ? 'selected' : ''; ?>>Entreprise</option>
                        <option value="homeowner" <?php echo ($type === 'homeowner') ? 'selected' : ''; ?>>Propriétaire</option>
                    </select>

                    <label>Nom</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required>

                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>

                    <label>Téléphone</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>

                    <label>Adresse</label>
                    <input type="text" name="address" value="<?php echo htmlspecialchars($address); ?>" required>

                    <div class="button-group">
                        <button type="submit">Enregistrer l'Utilisateur</button>
                        <button type="reset">Effacer le Formulaire</button>
                    </div>
                </form>
            </div>

            <!-- Tableau des utilisateurs avec filtre de recherche -->
            <div class="table-container">
                <h3>Tous les Utilisateurs</h3>
                <div class="search-container">
                    <label for="searchInput">Rechercher par Nom d'Utilisateur ou Nom :</label>
                    <input type="text" id="searchInput" onkeyup="filterUsers()" placeholder="Entrez le nom d'utilisateur ou le nom à rechercher">
                </div>
                <table id="userTable">
                    <thead>
                        <tr>
                            <th>Nom d'Utilisateur</th>
                            <th>Rôle</th>
                            <th>Type</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Adresse</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user["username"]); ?></td>
                                <td><?php echo htmlspecialchars($user["userrole"]); ?></td>
                                <td><?php echo htmlspecialchars($user["type"]); ?></td>
                                <td><?php echo htmlspecialchars($user["name"]); ?></td>
                                <td><?php echo htmlspecialchars($user["email"]); ?></td>
                                <td><?php echo htmlspecialchars($user["phone"]); ?></td>
                                <td><?php echo htmlspecialchars($user["address"]); ?></td>
                                <td class="action-buttons">
                                    <a href="?action=edit&username=<?php echo urlencode($user["username"]); ?>">Modifier</a>
                                    <a href="?action=delete&username=<?php echo urlencode($user["username"]); ?>" onclick="return confirm('Êtes-vous sûr ?')">Supprimer</a>
                                    <a href="?action=unlock&username=<?php echo urlencode($user["username"]); ?>">Déverrouiller</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
