<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'save_habit':
        $habit_name = mysqli_real_escape_string($conn, $_POST['habit_name']);
        $category = mysqli_real_escape_string($conn, $_POST['category']);
        $frequency = mysqli_real_escape_string($conn, $_POST['frequency']);
        $target_value = intval($_POST['target_value']);
        
        $sql = "INSERT INTO life_habits (user_id, habit_name, category, frequency, target_value, is_active) 
                VALUES ($user_id, '$habit_name', '$category', '$frequency', $target_value, 1)";
        
        if (mysqli_query($conn, $sql)) {
            echo json_encode(['success' => true, 'message' => 'Habit saved successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($conn)]);
        }
        break;
        
    case 'complete_habit':
        $habit_id = intval($_POST['habit_id']);
        $today = date('Y-m-d');
        
        // Check if already completed today
        $check_sql = "SELECT id FROM habit_logs WHERE habit_id = $habit_id AND user_id = $user_id AND completed_date = '$today'";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            echo json_encode(['success' => false, 'message' => 'Habit already completed today']);
            exit();
        }
        
        // Add to habit logs
        $log_sql = "INSERT INTO habit_logs (habit_id, user_id, completed_date) VALUES ($habit_id, $user_id, '$today')";
        mysqli_query($conn, $log_sql);
        
        // Update streak
        $streak_sql = "UPDATE life_habits 
                      SET current_streak = current_streak + 1,
                          longest_streak = GREATEST(longest_streak, current_streak + 1)
                      WHERE id = $habit_id AND user_id = $user_id";
        mysqli_query($conn, $streak_sql);
        
        echo json_encode(['success' => true, 'message' => 'Habit completed']);
        break;
        
    case 'save_time_entry':
        $activity_name = mysqli_real_escape_string($conn, $_POST['activity_name']);
        $category = mysqli_real_escape_string($conn, $_POST['category']);
        $duration_minutes = intval($_POST['duration_minutes']);
        $start_time = $_POST['start_time'] ?? date('Y-m-d H:i:s');
        $end_time = $_POST['end_time'] ?? date('Y-m-d H:i:s');
        
        $sql = "INSERT INTO life_time_tracking (user_id, activity_name, category, start_time, end_time, duration_minutes) 
                VALUES ($user_id, '$activity_name', '$category', '$start_time', '$end_time', $duration_minutes)";
        
        if (mysqli_query($conn, $sql)) {
            echo json_encode(['success' => true, 'message' => 'Time entry saved']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($conn)]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>