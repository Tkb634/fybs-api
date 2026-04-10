<?php
// Get credentials from environment variables (set on Render)
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME') ?: 'fybs_app';

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die(json_encode(["status" => "error", "message" => "Database connection failed"]));
}
?>