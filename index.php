<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaksense - Home</title>
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
        .welcome-container { 
            background: #3A3A5A; 
            padding: 40px; 
            border-radius: 10px; 
            text-align: center; 
            max-width: 600px; 
            width: 100%; 
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }
        .welcome-container h1 { 
            color: #F72585; 
            margin-bottom: 15px; 
            font-size: 2em; 
        }
        .welcome-container p { 
            color: #D6D8E7; 
            font-size: 1.2em; 
            margin-bottom: 15px;
        }
        .welcome-container a {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #F72585;
            color: #fff;
            border-radius: 5px;
            text-decoration: none;
            font-size: 1em;
            transition: background-color 0.2s;
        }
        .welcome-container a:hover {
            background-color: #FF4571;
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <h1>Welcome to Leaksense</h1>
        <p>Your reliable gas monitoring and alert system.</p>
        <p>Navigate through the application to access the dashboard, reports, and settings.</p>
        <a href="Login.php">Log in</a>
        <p></p>
        <p>© 2024 Animus Co.</p>
    </div>
</body>
</html>
