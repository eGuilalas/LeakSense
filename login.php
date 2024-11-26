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
            background-color: #f9f9f9; 
            color: #333; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh;
        }
        .login-container { 
            background: #fff; 
            padding: 40px; 
            border-radius: 10px; 
            text-align: center; 
            max-width: 400px; 
            width: 100%; 
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); 
            border: 1px solid #ddd;
        }
        .login-container img { 
            width: 60px; 
            margin-bottom: 20px; 
        }
        .login-container h1 { 
            color: #4CAF50; 
            margin-bottom: 15px; 
            font-size: 2em; 
        }
        .login-container p { 
            color: #555; 
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
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #fff;
            color: #333;
            font-size: 1em;
        }
        .login-container button {
            width: 100%;
            padding: 10px;
            margin-top: 20px;
            background-color: #4CAF50;
            border: none;
            border-radius: 5px;
            color: #fff;
            font-size: 1em;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .login-container button:hover {
            background-color: #45A049;
        }
        .login-container a {
            color: #4CAF50;
            text-decoration: none;
            margin-top: 15px;
            display: inline-block;
        }
        .login-container a:hover {
            text-decoration: underline;
        }
        .error-message {
            color: #D8000C;
            background-color: #FFD2D2;
            border: 1px solid #D8000C;
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
            font-size: 0.9em;
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
