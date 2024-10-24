<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to LeakSense</title>
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
        .container {
            text-align: center;
            background: white;
            padding: 40px 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
        }
        h1 {
            font-size: 32px;
            color: #4a90e2;
            margin-bottom: 20px;
        }
        p {
            font-size: 18px;
            color: #333;
            margin-bottom: 30px;
        }
        .login-button {
            background-color: #4a90e2;
            color: white;
            padding: 10px 20px;
            font-size: 18px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
        }
        .login-button:hover {
            background-color: #357ABD;
        }
        .footer {
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }
        /* Image Styling */
        .logo {
            max-width: 100px; /* Adjust size of the image */
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Add your image here -->
    <img src="assets/images/1.jpg" alt="LeakSense Logo" class="logo">
    
    <h1>Welcome to LeakSense</h1>
    <p>Your safety, our priority. Monitor gas levels with ease.</p>
    <a href="login.php" class="login-button">Login</a>
    <div class="footer">
        <p>&copy; <?php echo date("Y"); ?> Animus Co.</p>
    </div>
</div>

</body>
</html>
