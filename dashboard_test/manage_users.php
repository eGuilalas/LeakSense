<?php
include '../db_connection.php'; // Include your database connection

session_start(); // Start the session

// Handle form submission for adding a new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
    $role = filter_var($_POST['role'], FILTER_SANITIZE_STRING);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Hash the password

    // Insert new user into the database
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, role, password) VALUES (:username, :role, :password)");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':password', $password);
        $stmt->execute();
        $message = 'User added successfully!';
    } catch (PDOException $e) {
        $message = 'Error: ' . htmlspecialchars($e->getMessage());
    }
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = intval($_POST['user_id']);
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $message = 'User deleted successfully!';
    } catch (PDOException $e) {
        $message = 'Error: ' . htmlspecialchars($e->getMessage());
    }
}

// Handle user edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $user_id = intval($_POST['user_id']);
    $username = filter_var($_POST['edit_username'], FILTER_SANITIZE_STRING);
    $role = filter_var($_POST['edit_role'], FILTER_SANITIZE_STRING);

    if (!empty($_POST['edit_password'])) {
        $password = password_hash($_POST['edit_password'], PASSWORD_BCRYPT); // Hash the new password
        try {
            $stmt = $pdo->prepare("UPDATE users SET username = :username, role = :role, password = :password WHERE id = :user_id");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':role', $role);
            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $message = 'User updated successfully with new password!';
        } catch (PDOException $e) {
            $message = 'Error: ' . htmlspecialchars($e->getMessage());
        }
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET username = :username, role = :role WHERE id = :user_id");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':role', $role);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $message = 'User updated successfully!';
        } catch (PDOException $e) {
            $message = 'Error: ' . htmlspecialchars($e->getMessage());
        }
    }
}

// Fetch all users from the database
$stmt = $pdo->query("SELECT * FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle logout request
if (isset($_POST['logout'])) {
    session_destroy(); // Destroy the session
    header("Location: ../login.php"); // Redirect to login page
    exit();
}

// Close the PDO connection (optional, PDO connections are closed when the script ends)
$pdo = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User - Admin Dashboard</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
        }

        /* Dashboard Layout */
        .dashboard {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* Sidebar styling */
        .sidebar {
            width: 250px;
            background-color: #1e1e2f;
            color: white;
            display: flex;
            flex-direction: column;
            padding-top: 20px;
            transition: width 0.3s;
        }

        .sidebar.collapsed {
            width: 60px;
        }

        /* Hamburger Menu inside sidebar */
        .hamburger {
            font-size: 24px;
            background-color: transparent;
            border: none;
            color: white;
            cursor: pointer;
            margin-left: 20px;
            margin-bottom: 20px;
            text-align: left;
        }

        .sidebar.collapsed .hamburger {
            margin-left: 10px;
        }

        .sidebar h2 {
            color: #b2b3bf;
            font-size: 16px;
            text-transform: uppercase;
            margin-left: 20px;
            transition: opacity 0.3s;
        }

        .sidebar.collapsed h2 {
            opacity: 0;
        }

        .sidebar a {
            padding: 15px 20px;
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #b2b3bf;
            font-size: 16px;
            transition: background 0.3s, padding-left 0.3s;
        }

        .sidebar a .icon {
            margin-right: 10px;
            font-size: 18px;
        }

        .sidebar.collapsed a {
            padding-left: 10px;
            font-size: 0;
        }

        .sidebar.collapsed a .icon {
            margin-right: 0;
            font-size: 24px;
        }

        .sidebar a:hover {
            background-color: #35354e;
        }

        .menu-section {
            margin-top: 20px;
        }

        /* Main content styling */
        .main-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            transition: margin-left 0.3s;
        }

        .sidebar.collapsed ~ .main-content {
            margin-left: 60px;
        }

        h1 {
            text-align: center;
            color: #4a90e2;
        }

        h2, h3 {
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

        input[type="text"], input[type="password"], select {
            padding: 10px;
            font-size: 16px;
            border-radius: 5px;
            border: 1px solid #ccc;
            margin-right: 10px;
            margin-bottom: 10px;
            width: 100%;
            max-width: 400px;
        }

        .btn {
            background-color: #4a90e2;
            color: white;
            padding: 10px 20px;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn:hover {
            background-color: #357ABD;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f4f4f9;
        }

        form {
            display: inline;
        }
    </style>
</head>
<body>

    <!-- Dashboard Layout -->
    <div class="dashboard">
        <div class="sidebar" id="sidebar">
            <!-- Hamburger Menu inside the sidebar -->
            <button class="hamburger" id="hamburger" onclick="toggleSidebar()">&#9776;</button>

            <h2>Monitoring</h2>
            <a href="#"><span class="icon">📊</span>Dashboard</a>
            <a href="#"><span class="icon">📅</span>Reports</a>

            <div class="menu-section">
                <h2>Admin</h2>
                <a href="#"><span class="icon">👤</span><span style="color: red;">Admin</span></a>
            </div>

            <!-- Logout Section -->
            <div class="menu-section">
                <h2>Logout</h2>
                <a href="../logout.php"><span class="icon">🚪</span>Logout</a>
            </div>
        </div>

        <div class="main-content" id="main-content">
            <h1>Add New User</h1>

            <div class="container">
                <h2>Add User Form</h2>

                <?php if (isset($message)): ?>
                    <p><?php echo $message; ?></p>
                <?php endif; ?>

                <form method="post" action="">
                    <input type="text" name="username" placeholder="Enter Username" required><br>
                    <input type="password" name="password" placeholder="Enter Password" required><br>
                    
                    <label for="role">Select Role:</label>
                    <select name="role" id="role" required>
                        <option value="user">User</option>
                        <option value="super_user">Super Admin</option>
                        <option value="admin">Admin</option>
                        <option value="super_admin">Super Admin</option>
                    </select><br>

                    <input type="submit" name="add_user" class="btn" value="Add User">
                </form>

                <h2>All Users</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Edit</th>
                            <th>Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                            <td>
                                <form method="post" action="">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="text" name="edit_username" placeholder="Username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                    <select name="edit_role" required>
                                        <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                        <option value="super_user" <?php echo $user['role'] === 'super_user' ? 'selected' : ''; ?>>Super User</option>
                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        <option value="super_admin" <?php echo $user['role'] === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                                    </select>
                                    <input type="password" name="edit_password" placeholder="New Password (optional)">
                                    <input type="submit" name="edit_user" class="btn" value="Edit">
                                </form>
                            </td>
                            <td>
                                <form method="post" action="">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="submit" name="delete_user" class="btn" value="Delete">
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Sidebar Toggle Functionality
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
            document.body.classList.toggle('collapsed');
        }
    </script>

</body>
</html>
