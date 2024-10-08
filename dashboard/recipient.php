<?php
include '../db_connection.php'; // Include your database connection

session_start(); // Start the session

// Handle form submission for adding recipient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_recipient'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid email format';
    } else {
        // Insert new recipient into the database
        try {
            $stmt = $pdo->prepare("INSERT INTO email_recipients (email) VALUES (:email)");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $message = 'Recipient added successfully!';
        } catch (PDOException $e) {
            $message = 'Error: ' . htmlspecialchars($e->getMessage());
        }
    }
}

// Handle recipient deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_recipient'])) {
    $id = intval($_POST['id']);
    try {
        $stmt = $pdo->prepare("DELETE FROM email_recipients WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $message = 'Recipient deleted successfully!';
    } catch (PDOException $e) {
        $message = 'Error: ' . htmlspecialchars($e->getMessage());
    }
}

// Handle logout request
if (isset($_POST['logout'])) {
    session_destroy(); // Destroy the session
    header("Location: ../login.php"); // Redirect to login page
    exit();
}

// Fetch all recipients from the database
$stmt = $pdo->query("SELECT * FROM email_recipients");
$recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Close the PDO connection (optional, PDO connections are closed when the script ends)
$pdo = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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

        input[type="email"] {
            padding: 10px;
            font-size: 16px;
            border-radius: 5px;
            border: 1px solid #ccc;
            margin-right: 10px;
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

        ul {
            list-style-type: none;
            padding: 0;
        }

        ul li {
            margin-bottom: 10px;
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
            <h1>Admin Dashboard - Email Recipients</h1>

            <div class="container">
                <h2>Email Recipients</h2>

                <?php if (isset($message)): ?>
                    <p><?php echo $message; ?></p>
                <?php endif; ?>

                <form method="post" action="">
                    <input type="email" name="email" placeholder="Enter email" required>
                    <input type="submit" name="add_recipient" class="btn" value="Add Recipient">
                </form>

                <h3>Current Recipients</h3>
                <ul>
                    <?php foreach ($recipients as $recipient): ?>
                        <li>
                            <?php echo htmlspecialchars($recipient['email']); ?> 
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($recipient['id']); ?>">
                                <input type="submit" name="delete_recipient" class="btn" value="Delete">
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <!-- Logout Button -->
                <form method="post" action="">
                    <input type="submit" name="logout" class="btn" value="Logout">
                </form>
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
