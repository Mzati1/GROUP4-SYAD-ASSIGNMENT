<?php

// Database connection details
$host = 'localhost';
$dbname = 'soRealDatabase';
$username = 'admin';
$password = '1111';

// Create a Data Source Name (DSN)
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";

try {
    // Create a PDO instance
    $pdo = new PDO($dsn, $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // If the connection fails, display an error message and exit
    die("Database connection failed: " . $e->getMessage());
}
