<?php
include '../../config/db_connection.php'; // Include your MySQLi database connection

session_start(); // Start the session

// Handle form submission for adding recipient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_recipient'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid email format';
    } else {
        // Insert new recipient into the database
        $stmt = $conn->prepare("INSERT INTO email_recipients (email) VALUES (?)");
        $stmt->bind_param('s', $email);

        if ($stmt->execute()) {
            $message = 'Recipient added successfully!';
        } else {
            $message = 'Error: ' . htmlspecialchars($conn->error);
        }
        $stmt->close();
    }
}

// Handle recipient deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_recipient'])) {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("DELETE FROM email_recipients WHERE id = ?");
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        $message = 'Recipient deleted successfully!';
    } else {
        $message = 'Error: ' . htmlspecialchars($conn->error);
    }
    $stmt->close();
}

// Handle logout request
if (isset($_POST['logout'])) {
    session_destroy(); // Destroy the session
    header("Location: ../login.php"); // Redirect to login page
    exit();
}

// Fetch all recipients from the database
$result = $conn->query("SELECT * FROM email_recipients");
$recipients = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recipients[] = $row;
    }
}

// Close the MySQLi connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LeakSense Admin Dashboard - Recipient Setup</title>
    <!-- Link to external CSS -->
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>

    <!-- Dashboard Layout -->
    <div class="dashboard">
        <div class="sidebar" id="sidebar">
            <!-- Hamburger Menu inside the sidebar -->
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
                <a href="#"><span class="icon">⚙️</span>Recipient Setup</a>
            </div>

            <div class="menu-section">
                <h2>Welcome</h2>
                <a href="#"><span class="icon">👤</span><span style="color: red;"><?php echo htmlspecialchars($_SESSION['username']); ?></span> - <?php echo htmlspecialchars($_SESSION['role']); ?></a>
            </div>

            <!-- Logout Section -->
            <div class="menu-section">
                <h2>Logout</h2>
                <a href="../logout.php"><span class="icon">🚪</span>Logout</a>
            </div>
        </div>

        <div class="main-content" id="main-content">
            <h1>Email Recipient Setup</h1>

            <div class="container">
                <h2>Manage Email Recipients</h2>

                <!-- Display message -->
                <?php if (isset($message)): ?>
                    <p><?php echo $message; ?></p>
                <?php endif; ?>

                <!-- Form to add new recipient -->
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

    <!-- Link to external JS -->
    <script src="dashboard.js"></script>

</body>
</html>
