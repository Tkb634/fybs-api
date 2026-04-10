<?php
// Database connection settings
$host = "sql103.infinityfree.com";
$user = "if0_41617889";          // Default XAMPP username
$password = "T1n0t3nda123";          // Default XAMPP password (empty)
$database = "if0_41617889_fybs_app";  // Your database name

// Create MySQL connection
$conn = mysqli_connect($host, $user, $password, $database);

// Check if connection failed
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>
