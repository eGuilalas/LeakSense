<?php
// manage_users.php

session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../login.php');
    exit();
}

include '../../config/db_connection.php';

$message = '';

// Handle form submission for adding a new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    $role = $conn->real_escape_string($_POST['role']);

    // Hash the password before saving it to the database
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user into the database
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $username, $hashedPassword, $role);

    if ($stmt->execute()) {
        $message = 'User added successfully!';
    } else {
        $message = 'Error: ' . htmlspecialchars($conn->error);
    }
    $stmt->close();
}

// Handle user update, including password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $id = intval($_POST['id']);
    $username = $conn->real_escape_string($_POST['username']);
    $role = $conn->real_escape_string($_POST['role']);
    $password = $_POST['password'];

    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username = ?, role = ?, password = ? WHERE id = ?");
        $stmt->bind_param('sssi', $username, $role, $hashedPassword, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
        $stmt->bind_param('ssi', $username, $role, $id);
    }

    if ($stmt->execute()) {
        $message = 'User updated successfully!';
    } else {
        $message = 'Error: ' . htmlspecialchars($conn->error);
    }
    $stmt->close();
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        $message = 'User deleted successfully!';
    } else {
        $message = 'Error: ' . htmlspecialchars($conn->error);
    }
    $stmt->close();
}

// Fetch all users from the database
$result = $conn->query("SELECT id, username, role FROM users");
$users = $result->fetch_all(MYSQLI_ASSOC);

$username = $_SESSION['username'];
$role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <div class="dashboard">
        <div class="sidebar" id="sidebar">
            <button class="hamburger" id="hamburger" onclick="toggleSidebar()">&#9776;</button>
            <h2>Monitoring</h2>
            <a href="admin_dashboard.php"><span class="icon">📊</span>Dashboard</a>
            <a href="esp32_1.php"><span class="icon">💽</span>ESP32 - 1</a>
            <a href="esp32_2.php"><span class="icon">💽</span>ESP32 - 2</a>
            <a href="reports.php"><span class="icon">📅</span>Reports</a>

            <div class="menu-section">
                <h2>Settings</h2>
                <a href="manage_users.php"><span class="icon">👥</span>Manage Users</a>
                <a href="threshold_management.php"><span class="icon">⚙️</span>Threshold</a>
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
            <h1>Manage Users</h1>

            <div class="container">
                <?php if ($message): ?>
                    <p><?php echo htmlspecialchars($message); ?></p>
                <?php endif; ?>

                <h2>Add New User</h2>
                <form method="post" action="">
                    <input type="text" name="username" placeholder="Username" required>
                    <input type="text" name="password" placeholder="Password (unmasked)" required>
                    <select name="role" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                        <option value="super_user">Super User</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                    <input type="submit" name="add_user" value="Add User">
                </form>

                <h2>Edit User</h2>
                <form method="post" action="" id="editForm">
                    <input type="hidden" name="id" id="edit-id">
                    <input type="text" name="username" id="edit-username" placeholder="Username" required>
                    <input type="text" name="password" id="edit-password" placeholder="New Password (leave blank to keep current)">
                    <select name="role" id="edit-role" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                        <option value="super_user">Super User</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                    <input type="submit" name="update_user" value="Update User">
                </form>

                <h2>Current Users</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['role']); ?></td>
                                <td>
                                    <button onclick="populateEditForm(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>', '<?php echo htmlspecialchars($user['role']); ?>')">Edit</button>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                        <input type="submit" name="delete_user" value="Delete" onclick="return confirm('Are you sure?')">
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="dashboard.js"></script>
    <script>
        function populateEditForm(id, username, role) {
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-username').value = username;
            document.getElementById('edit-role').value = role;
            document.getElementById('edit-password').value = ''; // Clear password field
            document.getElementById('editForm').scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>
