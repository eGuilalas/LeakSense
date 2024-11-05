<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Leaksense</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background-color: #1E1E2D; 
            color: #fff; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh;
        }
        .login-container { 
            background: #3A3A5A; 
            padding: 40px; 
            border-radius: 10px; 
            text-align: center; 
            max-width: 400px; 
            width: 100%; 
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }
        .login-container img { 
            width: 60px; 
            margin-bottom: 20px; 
        }
        .login-container h1 { 
            color: #F72585; 
            margin-bottom: 15px; 
            font-size: 2em; 
        }
        .login-container p { 
            color: #D6D8E7; 
            margin-bottom: 20px;
        }
        .login-container form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .login-container input[type="text"],
        .login-container input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: none;
            border-radius: 5px;
            background-color: #444;
            color: #fff;
            font-size: 1em;
        }
        .login-container button {
            width: 100%;
            padding: 10px;
            margin-top: 20px;
            background-color: #F72585;
            border: none;
            border-radius: 5px;
            color: #fff;
            font-size: 1em;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .login-container button:hover {
            background-color: #FF4571;
        }
        .login-container a {
            color: #F72585;
            text-decoration: none;
            margin-top: 15px;
            display: inline-block;
        }
        .login-container a:hover {
            text-decoration: underline;
        }
        .error-message {
            color: #ff4c4c;
            background-color: #3a3a5a;
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Add your image here -->
        <!-- <img src="assets/images/logo.png" alt="Leaksense Logo"> -->
        <h1>Login</h1>
        <p>Enter your credentials to access the dashboard</p>

        <!-- Error message section -->
        <?php
        session_start();
        if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?php
                echo $_SESSION['error'];
                unset($_SESSION['error']); // Clear error after displaying
                ?>
            </div>
        <?php endif; ?>

        <form action="auth.php" method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Log In</button>
        </form>
        <p>© 2024 Animus Co.</p>
    </div>
</body>
</html>
