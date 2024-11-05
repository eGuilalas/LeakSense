<?php
session_start();
include 'db_connection.php'; // Ensure `pdo` is available

// Constants for max login attempts and lockout time
define('MAX_ATTEMPTS', 3);
define('LOCKOUT_TIME', 15); // in minutes

// Check if the form data is posted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Check if fields are not empty
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Please enter both username and password.";
        header("Location: login.php");
        exit();
    }

    // Prepare SQL query to find the user
    $stmt = $pdo->prepare("SELECT userID, username, password, userrole, login_attempt, lockout_until FROM user WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    // Check if user exists
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if the account is currently locked
        if ($user['lockout_until'] && strtotime($user['lockout_until']) > time()) {
            $_SESSION['error'] = "Account locked. Please try again later.";
            header("Location: login.php");
            exit();
        }

        // Verify password
        if (password_verify($password, $user['password'])) {
            // Reset login attempts and lockout time on successful login
            $resetStmt = $pdo->prepare("UPDATE user SET login_attempt = 0, lockout_until = NULL WHERE userID = :userID");
            $resetStmt->bindParam(':userID', $user['userID']);
            $resetStmt->execute();

            // Store user information in the session
            $_SESSION['user_id'] = $user['userID'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['userrole'] = $user['userrole'];

            // Redirect to the dashboard
            header("Location: http://localhost/leaksense/dashboard/dashboard.php");
            exit();
        } else {
            // Password verification failed, increment login attempt
            $loginAttempts = $user['login_attempt'] + 1;
            $lockoutUntil = null;

            // Lock the account if max attempts reached
            if ($loginAttempts >= MAX_ATTEMPTS) {
                $lockoutUntil = date("Y-m-d H:i:s", strtotime("+" . LOCKOUT_TIME . " minutes"));
                $_SESSION['error'] = "Account locked due to too many failed attempts. Please try again in " . LOCKOUT_TIME . " minutes.";
            } else {
                $_SESSION['error'] = "Invalid username or password.";
            }

            // Update login attempt and lockout_until in the database
            $updateStmt = $pdo->prepare("UPDATE user SET login_attempt = :login_attempt, lockout_until = :lockout_until WHERE userID = :userID");
            $updateStmt->bindParam(':login_attempt', $loginAttempts);
            $updateStmt->bindParam(':lockout_until', $lockoutUntil);
            $updateStmt->bindParam(':userID', $user['userID']);
            $updateStmt->execute();

            header("Location: login.php");
            exit();
        }
    } else {
        // Username not found
        $_SESSION['error'] = "Invalid username or password.";
        header("Location: login.php");
        exit();
    }
} else {
    // If no form data, redirect to login
    $_SESSION['error'] = "Please log in.";
    header("Location: login.php");
    exit();
}
