<?php
session_start();
include "../config.php";

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

$sql = "SELECT COUNT(*) as habits_completed FROM habit_logs hl 
        JOIN life_habits lh ON hl.habit_id = lh.id 
        WHERE hl.user_id = $user_id AND hl.completed_date = '$today'";

$result = mysqli_query($conn, $sql);
if($result) {
    $row = mysqli_fetch_assoc($result);
    echo json_encode(['success' => true, 'habits_completed' => $row['habits_completed']]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}