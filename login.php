<?php
session_start();
require 'db_connection.php'; // Include the database connection file

$error_message = ''; // Initialize an empty error message
$lockout_duration = 15; // Lockout duration in minutes
$max_attempts = 5; // Maximum allowed attempts

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve username and password from POST request
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare and execute SQL query to fetch user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Check if account is expired
        $currentDate = new DateTime();
        if ($user['expiration_date'] && new DateTime($user['expiration_date']) < $currentDate) {
            $error_message = 'Your account has expired. Please contact support.';
        }
        // Check if user is locked out
        elseif ($user['lockout_until'] && strtotime($user['lockout_until']) > time()) {
            $error_message = 'Account is locked. Please try again later.';
        } else {
            // If account is not locked or expired, check the password
            if (password_verify($password, $user['password'])) {
                // Reset failed attempts on successful login
                $stmt = $pdo->prepare("UPDATE users SET failed_attempts = 0, lockout_until = NULL WHERE id = :id");
                $stmt->execute(['id' => $user['id']]);

                // Set session variables
                $_SESSION['loggedin'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['expiration_date'] = $user['expiration_date']; // Add expiration_date to session

                // Redirect based on user role
                switch ($user['role']) {
                    case 'admin':
                        header('Location: dashboard/admin/admin_dashboard.php');
                        break;
                    case 'user':
                        header('Location: dashboard/user/user_dashboard.php');
                        break;
                    case 'super_user':
                        header('Location: dashboard/superuser/super_user_dashboard.php');
                        break;
                    case 'super_admin':
                        header('Location: dashboard/superadmin/super_admin_dashboard.php');
                        break;
                    default:
                        $error_message = 'Role not recognized.';
                        break;
                }
                exit;
            } else {
                // If password is incorrect, increment failed attempts
                $failed_attempts = $user['failed_attempts'] + 1;

                if ($failed_attempts >= $max_attempts) {
                    // Lock the account
                    $lockout_until = date("Y-m-d H:i:s", strtotime("+$lockout_duration minutes"));
                    $stmt = $pdo->prepare("UPDATE users SET failed_attempts = :failed_attempts, lockout_until = :lockout_until WHERE id = :id");
                    $stmt->execute([
                        'failed_attempts' => $failed_attempts,
                        'lockout_until' => $lockout_until,
                        'id' => $user['id']
                    ]);
                    $error_message = 'Account is locked due to multiple failed login attempts. Please try again later.';
                } else {
                    // Update failed attempts without locking the account
                    $stmt = $pdo->prepare("UPDATE users SET failed_attempts = :failed_attempts WHERE id = :id");
                    $stmt->execute([
                        'failed_attempts' => $failed_attempts,
                        'id' => $user['id']
                    ]);
                    $error_message = 'Invalid credentials. Please try again.';
                }
            }
        }
    } else {
        $error_message = 'Invalid credentials. Please try again.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LeakSense</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-container {
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        h2 {
            margin-bottom: 20px;
            color: #4a90e2;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }
        .login-button {
            background-color: #4a90e2;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        .login-button:hover {
            background-color: #357ABD;
        }
        .error-message {
            color: red;
            margin-bottom: 15px;
        }
        .footer {
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>

<div class="login-container">
    <!-- Add your image here -->
    <img src="assets/images/1.jpg" alt="LeakSense Logo" class="logo">
    <h2>Login to LeakSense</h2>

    <?php if (!empty($error_message)): ?>
        <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="login-button">Login</button>
    </form>

    <div class="footer">
        <p>&copy; <?php echo date("Y"); ?> Animus Co.</p>
    </div>
</div>

</body>
</html>
