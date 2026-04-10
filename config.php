<?php
// Database connection settings
$host = "localhost";
$user = "root";          // Default XAMPP username
$password = "";          // Default XAMPP password (empty)
$database = "fybs_app";  // Your database name

// Create MySQL connection
$conn = mysqli_connect($host, $user, $password, $database);

// Check if connection failed
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>
