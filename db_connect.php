<?php
// Database configuration
$host = "localhost";       
$dbName = "society_management";  
$username = "root";        
$password = "";            

try {
    // Create PDO connection
    $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8", $username, $password);
    // Set PDO error mode
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

?>
