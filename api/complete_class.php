<?php
session_start();
include "../config.php";

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$class_id = intval($data['class_id'] ?? 0);
$time_spent = intval($data['time_spent'] ?? 45);

// Check if already completed
$check_sql = "SELECT * FROM fybs_progress WHERE user_id = $user_id AND class_id = $class_id";
$check_result = mysqli_query($conn, $check_sql);

if(mysqli_num_rows($check_result) > 0) {
    // Update existing record
    $update_sql = "UPDATE fybs_progress SET 
                   completed = 1, 
                   time_spent = time_spent + $time_spent,
                   completed_at = NOW()
                   WHERE user_id = $user_id AND class_id = $class_id";
    mysqli_query($conn, $update_sql);
} else {
    // Insert new record
    $insert_sql = "INSERT INTO fybs_progress (user_id, class_id, completed, time_spent, completed_at) 
                   VALUES ($user_id, $class_id, 1, $time_spent, NOW())";
    mysqli_query($conn, $insert_sql);
}

// Get updated stats
$stats_sql = "SELECT 
    (SELECT COUNT(*) FROM fybs_progress WHERE user_id = $user_id AND completed = 1) as completed_classes,
    (SELECT COUNT(*) FROM fybs_classes WHERE is_active = 1) as total_classes,
    (SELECT SUM(time_spent) FROM fybs_progress WHERE user_id = $user_id) as total_time";

$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

// Get class title
$class_sql = "SELECT title FROM fybs_classes WHERE id = $class_id";
$class_result = mysqli_query($conn, $class_sql);
$class_title = mysqli_fetch_assoc($class_result)['title'] ?? 'Unknown Class';

// Calculate progress
$progress_percentage = $stats['total_classes'] > 0 ? 
    round(($stats['completed_classes'] / $stats['total_classes']) * 100) : 0;

// Calculate XP and level (1 XP per class)
$user_xp = $stats['completed_classes'];
$user_level = floor($user_xp / 5) + 1;
$xp_percentage = ($user_xp % 5) * 20;

echo json_encode([
    'success' => true,
    'message' => 'Class marked as complete',
    'stats' => [
        'completed_classes' => $stats['completed_classes'],
        'total_classes' => $stats['total_classes'],
        'progress_percentage' => $progress_percentage,
        'user_level' => $user_level,
        'user_xp' => $user_xp,
        'xp_percentage' => $xp_percentage
    ],
    'class_title' => $class_title
]);