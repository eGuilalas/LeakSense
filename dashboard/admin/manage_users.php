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
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $address = $conn->real_escape_string($_POST['address']);
    $employee_id = $conn->real_escape_string($_POST['employee_id']);
    $position = $conn->real_escape_string($_POST['position']);
    $expiration_date = $_POST['expiration_date'];

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (username, password, role, name, email, phone, address, employee_id, position, expiration_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('ssssssssss', $username, $hashedPassword, $role, $name, $email, $phone, $address, $employee_id, $position, $expiration_date);

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
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $address = $conn->real_escape_string($_POST['address']);
    $employee_id = $conn->real_escape_string($_POST['employee_id']);
    $position = $conn->real_escape_string($_POST['position']);
    $expiration_date = $_POST['expiration_date'];
    $password = $_POST['password'];

    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username = ?, role = ?, password = ?, name = ?, email = ?, phone = ?, address = ?, employee_id = ?, position = ?, expiration_date = ? WHERE id = ?");
        $stmt->bind_param('ssssssssssi', $username, $role, $hashedPassword, $name, $email, $phone, $address, $employee_id, $position, $expiration_date, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username = ?, role = ?, name = ?, email = ?, phone = ?, address = ?, employee_id = ?, position = ?, expiration_date = ? WHERE id = ?");
        $stmt->bind_param('sssssssssi', $username, $role, $name, $email, $phone, $address, $employee_id, $position, $expiration_date, $id);
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

// Handle unlocking a user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlock_user'])) {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("UPDATE users SET failed_attempts = 0, lockout_until = NULL WHERE id = ?");
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        $message = 'User unlocked successfully!';
    } else {
        $message = 'Error: ' . htmlspecialchars($conn->error);
    }
    $stmt->close();
}

// Fetch all users from the database
$result = $conn->query("SELECT id, username, role, name, email, phone, address, employee_id, position, expiration_date FROM users");
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
    <style>
        .form-section, table {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background-color: #f9f9f9;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .form-section input, .form-section select, .form-section button {
            margin: 8px 4px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            width: calc(100% / 3 - 20px);
        }
        .form-section button.clear-btn {
            background-color: #f44336;
            color: white;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th, table td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        table th {
            background-color: #4CAF50;
            color: white;
        }
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .edit-btn {
            background-color: #4CAF50;
            color: white;
            width: 100%;
        }
        .delete-btn {
            background-color: #f44336;
            color: white;
            width: 100%;
        }
        .unlock-btn {
            background-color: #ff9800;
            color: white;
            width: 100%;
        }
        button {
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
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

        <div class="main-content">
            <h1>Manage Users</h1>

            <?php if ($message): ?>
                <p><?php echo htmlspecialchars($message); ?></p>
            <?php endif; ?>

            <div class="form-section">
                <h2>Add New User</h2>
                <form method="post" action="">
                    <input type="text" name="username" placeholder="Username" required>
                    <input type="text" name="password" placeholder="Password" required>
                    <input type="text" name="name" placeholder="Name" required>
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="text" name="phone" placeholder="Phone" required>
                    <input type="text" name="address" placeholder="Address" required>
                    <input type="text" name="employee_id" placeholder="Employee ID" required>
                    <input type="text" name="position" placeholder="Position" required>
                    <input type="date" name="expiration_date" placeholder="Expiration Date">
                    <select name="role" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                        <option value="super_user">Super User</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                    <button type="submit" name="add_user">Add User</button>
                    <button type="button" class="clear-btn" onclick="clearForm(this.form)">Clear</button>
                </form>
            </div>

            <div class="form-section">
                <h2>Edit User</h2>
                <form method="post" action="" id="editForm">
                    <input type="hidden" name="id" id="edit-id">
                    <input type="text" name="username" id="edit-username" placeholder="Username" required>
                    <input type="text" name="password" id="edit-password" placeholder="New Password">
                    <input type="text" name="name" id="edit-name" placeholder="Name" required>
                    <input type="email" name="email" id="edit-email" placeholder="Email" required>
                    <input type="text" name="phone" id="edit-phone" placeholder="Phone" required>
                    <input type="text" name="address" id="edit-address" placeholder="Address" required>
                    <input type="text" name="employee_id" id="edit-employee-id" placeholder="Employee ID" required>
                    <input type="text" name="position" id="edit-position" placeholder="Position" required>
                    <input type="date" name="expiration_date" id="edit-expiration-date" placeholder="Expiration Date">
                    <select name="role" id="edit-role" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                        <option value="super_user">Super User</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                    <button type="submit" name="update_user">Update User</button>
                    <button type="button" class="clear-btn" onclick="clearForm(this.form)">Clear</button>
                </form>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Employee ID</th>
                        <th>Position</th>
                        <th>Expiration Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($user['role'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($user['name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($user['phone'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($user['address'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($user['employee_id'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($user['position'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($user['expiration_date'] ?? ''); ?></td>
                            <td class="action-buttons">
                                <button class="edit-btn" onclick="populateEditForm(
                                    <?php echo $user['id']; ?>,
                                    '<?php echo htmlspecialchars($user['username'] ?? ''); ?>',
                                    '<?php echo htmlspecialchars($user['role'] ?? ''); ?>',
                                    '<?php echo htmlspecialchars($user['name'] ?? ''); ?>',
                                    '<?php echo htmlspecialchars($user['email'] ?? ''); ?>',
                                    '<?php echo htmlspecialchars($user['phone'] ?? ''); ?>',
                                    '<?php echo htmlspecialchars($user['address'] ?? ''); ?>',
                                    '<?php echo htmlspecialchars($user['employee_id'] ?? ''); ?>',
                                    '<?php echo htmlspecialchars($user['position'] ?? ''); ?>',
                                    '<?php echo htmlspecialchars($user['expiration_date'] ?? ''); ?>'
                                )">Edit</button>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id'] ?? ''); ?>">
                                    <button type="submit" name="delete_user" class="delete-btn" onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id'] ?? ''); ?>">
                                    <button type="submit" name="unlock_user" class="unlock-btn" onclick="return confirm('Unlock this user?')">Unlock</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function populateEditForm(id, username, role, name, email, phone, address, employee_id, position, expiration_date) {
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-username').value = username;
            document.getElementById('edit-role').value = role;
            document.getElementById('edit-name').value = name;
            document.getElementById('edit-email').value = email;
            document.getElementById('edit-phone').value = phone;
            document.getElementById('edit-address').value = address;
            document.getElementById('edit-employee-id').value = employee_id;
            document.getElementById('edit-position').value = position;
            document.getElementById('edit-expiration-date').value = expiration_date;
            document.getElementById('edit-password').value = ''; // Clear password field
            document.getElementById('editForm').scrollIntoView({ behavior: 'smooth' });
        }

        function clearForm(form) {
            form.reset();
        }
    </script>
</body>
</html>
