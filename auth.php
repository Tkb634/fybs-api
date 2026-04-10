<?php
// Start session
session_start();

// If user is not logged in, redirect to login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Helper function to check role
function requireRole($role) {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== $role) {
        // Stop access if role does not match
        http_response_code(403);
        echo "Access denied.";
        exit();
    }
}
