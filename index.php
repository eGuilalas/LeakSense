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
            background-color: #f4f4f4; 
            color: #333; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh;
            overflow: hidden;
            position: relative;
        }

        /* Smoke Animation */
        .smoke {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }

        .smoke span {
            position: absolute;
            bottom: -150px;
            width: 200px;
            height: 200px;
            background: rgba(128, 128, 128, 0.1);
            border-radius: 50%;
            animation: smoke 10s linear infinite;
            filter: blur(15px);
        }

        .smoke span:nth-child(1) {
            left: 10%;
            animation-delay: 0s;
        }

        .smoke span:nth-child(2) {
            left: 30%;
            animation-delay: 2s;
        }

        .smoke span:nth-child(3) {
            left: 50%;
            animation-delay: 4s;
        }

        .smoke span:nth-child(4) {
            left: 70%;
            animation-delay: 6s;
        }

        .smoke span:nth-child(5) {
            left: 90%;
            animation-delay: 8s;
        }

        @keyframes smoke {
            0% {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
            100% {
                transform: translateY(-100%) scale(1.5);
                opacity: 0;
            }
        }

        /* Welcome Container */
        .welcome-container { 
            background: #ffffff; 
            padding: 40px; 
            border-radius: 10px; 
            text-align: center; 
            max-width: 600px; 
            width: 100%; 
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            z-index: 1;
        }

        .welcome-container img { 
            width: 80px; 
            margin-bottom: 20px; 
        }

        .welcome-container h1 { 
            color: #4CAF50; 
            margin-bottom: 15px; 
            font-size: 2em; 
        }

        .welcome-container p { 
            color: #555; 
            font-size: 1.2em; 
            margin-bottom: 15px;
        }

        .welcome-container a {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: #fff;
            border-radius: 5px;
            text-decoration: none;
            font-size: 1em;
            transition: background-color 0.2s;
        }

        .welcome-container a:hover {
            background-color: #388E3C;
        }
    </style>
</head>
<body>
    <!-- Smoke Animation Background -->
    <div class="smoke">
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
    </div>

    <div class="welcome-container">
        <!-- Add your image here -->
        <img src="assets/images/1.jpg" alt="Leaksense Logo">
        <h1>Welcome to Leaksense</h1>
        <p>Your reliable gas monitoring and alert system.</p>
        <p>Navigate through the application to access the dashboard, reports, and settings.</p>
        <a href="login.php">Log in</a>
        <p>© 2024 Animus Co.</p>
    </div>
</body>
</html>
