<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$servername = "localhost";  // Database host, typically 'localhost'
$username = "root";         // Your database username
$password = "";             // Your database password
$dbname = "PROJECT";        // Your database name

try {
    // Create a PDO instance to connect to the database
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Connection successful (No output)
}
catch(PDOException $e) {
    // Log the connection error and display a user-friendly message
    error_log("Database Connection Error: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}
?>