<?php
$host = 'localhost'; // Your server IP or 'localhost'
$db = 'leaksense';   // Database name
$user = 'root';      // Your database username
$pass = '';          // Your database password

try {
    // Create a new PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set character set to utf8mb4 to support a wide range of characters
    $pdo->exec("set names utf8mb4");
} catch (PDOException $e) {
    // Display an error message
    echo "Connection failed: " . htmlspecialchars($e->getMessage());
}
?>
