<?php
include '../../config/db_connection.php';

session_start();

// Handle form submission for adding recipient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_recipient'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid email format';
    } else {
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
    session_destroy();
    header("Location: ../login.php");
    exit();
}

// Fetch all recipients from the database
$result = $conn->query("SELECT * FROM email_recipients");
$recipients = $result->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LeakSense Admin Dashboard - Recipient Setup</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        /* Main content styling */
        .main-content h1 {
            color: #4a90e2;
            font-size: 1.8em;
            font-weight: bold;
            margin-bottom: 15px;
            text-align: center;
        }

        .container {
            max-width: 800px;
            margin: auto;
            padding: 25px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .container h2 {
            color: #333;
            font-weight: bold;
            margin-bottom: 20px;
        }

        /* Form styling */
        .form-container {
            margin-bottom: 20px;
        }

        .form-container input[type="email"] {
            width: 70%;
            padding: 10px;
            font-size: 1em;
            border: 1px solid #ddd;
            border-radius: 5px;
            transition: border-color 0.3s ease;
            margin-right: 10px;
        }

        .form-container input[type="email"]:focus {
            border-color: #4a90e2;
            box-shadow: 0 0 4px rgba(74, 144, 226, 0.4);
        }

        .form-container .btn {
            background-color: #4a90e2;
            color: #fff;
            padding: 10px 15px;
            font-size: 1em;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .form-container .btn:hover {
            background-color: #357ABD;
            transform: translateY(-2px);
        }

        /* Message styling */
        .message {
            font-weight: bold;
            color: green;
            margin: 10px 0;
            text-align: center;
        }

        /* Recipient list styling */
        .recipient-list {
            list-style: none;
            padding: 0;
            margin-top: 20px;
            max-width: 600px;
            margin: auto;
        }

        .recipient-list li {
            background-color: #f9f9f9;
            padding: 12px;
            margin: 8px 0;
            border: 1px solid #e0e4e7;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            transition: box-shadow 0.3s ease;
        }

        .recipient-list li:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .recipient-list .delete-btn {
            background-color: #f44336;
            color: #fff;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .recipient-list .delete-btn:hover {
            background-color: #e53935;
        }

        /* Logout button styling */
        .logout-container {
            margin-top: 30px;
        }

        .logout-container .btn {
            background-color: #4a90e2;
            color: #fff;
            padding: 10px 15px;
            font-size: 1em;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .logout-container .btn:hover {
            background-color: #357ABD;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

    <div class="dashboard">
        <div class="sidebar" id="sidebar">
            <!-- Sidebar Content Unchanged -->
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
                <a href="recipient.php" class="active"><span class="icon">⚙️</span>Recipient Setup</a>
            </div>
            <div class="menu-section">
                <h2>Welcome</h2>
                <a href="#"><span class="icon">👤</span><span style="color: red;"><?php echo htmlspecialchars($_SESSION['username']); ?></span> - <?php echo htmlspecialchars($_SESSION['role']); ?></a>
            </div>
            <div class="menu-section">
                <h2>Logout</h2>
                <a href="../logout.php"><span class="icon">🚪</span>Logout</a>
            </div>
        </div>

        <div class="main-content" id="main-content">
            <h1>Email Recipient Setup</h1>

            <div class="container">
                <h2>Manage Email Recipients</h2>

                <?php if (isset($message)): ?>
                    <p class="message"><?php echo $message; ?></p>
                <?php endif; ?>

                <div class="form-container">
                    <form method="post" action="">
                        <input type="email" name="email" placeholder="Enter email" required>
                        <input type="submit" name="add_recipient" class="btn" value="Add Recipient">
                    </form>
                </div>

                <h3>Current Recipients</h3>
                <ul class="recipient-list">
                    <?php foreach ($recipients as $recipient): ?>
                        <li>
                            <?php echo htmlspecialchars($recipient['email']); ?> 
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($recipient['id']); ?>">
                                <input type="submit" name="delete_recipient" class="delete-btn" value="Delete">
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <script src="dashboard.js"></script>

</body>
</html
