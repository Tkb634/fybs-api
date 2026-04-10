<?php
header('Content-Type: application/json');
session_start();
include "config.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

// Handle different actions
switch ($action) {
    case 'get_stats':
        getLifeStats();
        break;
    case 'get_habits':
        getHabits();
        break;
    case 'add_habit':
        addHabit();
        break;
    case 'toggle_habit':
        toggleHabit();
        break;
    case 'get_time_entries':
        getTimeEntries();
        break;
    case 'save_time_entry':
        saveTimeEntry();
        break;
    case 'save_manual_time':
        saveManualTime();
        break;
    case 'delete_habit':
        deleteHabit();
        break;
    case 'get_business_tips':
        getBusinessTips();
        break;
    case 'get_health_tips':
        getHealthTips();
        break;
    case 'get_knowledge_articles':
        getKnowledgeArticles();
        break;
    case 'get_analytics':
        getAnalytics();
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}

function getLifeStats() {
    global $conn, $user_id;
    
    $stats = [];
    
    // Active habits
    $sql = "SELECT COUNT(*) as count FROM life_habits WHERE user_id = ? AND is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['active_habits'] = $result->fetch_assoc()['count'];
    
    // Current streak (max streak from active habits)
    $sql = "SELECT MAX(current_streak) as streak FROM life_habits WHERE user_id = ? AND is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['current_streak'] = $result->fetch_assoc()['streak'] ?? 0;
    
    // Total time tracked (in minutes)
    $sql = "SELECT SUM(duration_minutes) as total FROM life_time_tracking WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['total_minutes'] = $result->fetch_assoc()['total'] ?? 0;
    
    // Habits completed today
    $today = date('Y-m-d');
    $sql = "SELECT COUNT(DISTINCT hl.habit_id) as count 
            FROM habit_logs hl
            JOIN life_habits lh ON hl.habit_id = lh.id
            WHERE hl.user_id = ? AND hl.completed_date = ? AND lh.is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['completed_today'] = $result->fetch_assoc()['count'] ?? 0;
    
    echo json_encode(['success' => true, 'stats' => $stats]);
}

function getHabits() {
    global $conn, $user_id;
    
    $today = date('Y-m-d');
    
    $sql = "SELECT lh.*, 
            (SELECT COUNT(*) FROM habit_logs hl WHERE hl.habit_id = lh.id AND hl.completed_date = ?) as completed_today
            FROM life_habits lh
            WHERE lh.user_id = ? AND lh.is_active = 1
            ORDER BY lh.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $today, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $habits = [];
    while ($row = $result->fetch_assoc()) {
        $habits[] = $row;
    }
    
    echo json_encode(['success' => true, 'habits' => $habits]);
}

function addHabit() {
    global $conn, $user_id;
    
    $habit_name = mysqli_real_escape_string($conn, $_POST['habit_name']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $frequency = mysqli_real_escape_string($conn, $_POST['frequency']);
    $target = intval($_POST['target']);
    
    $sql = "INSERT INTO life_habits (user_id, habit_name, category, frequency, target_value) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssi", $user_id, $habit_name, $category, $frequency, $target);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Habit created successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to create habit']);
    }
}

function toggleHabit() {
    global $conn, $user_id;
    
    $habit_id = intval($_POST['habit_id']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    
    // Check if already logged for today
    $check_sql = "SELECT id FROM habit_logs WHERE habit_id = ? AND user_id = ? AND completed_date = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("iis", $habit_id, $user_id, $date);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Remove log
        $delete_sql = "DELETE FROM habit_logs WHERE habit_id = ? AND user_id = ? AND completed_date = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("iis", $habit_id, $user_id, $date);
        $delete_stmt->execute();
        
        // Update streak (simplified logic)
        $update_sql = "UPDATE life_habits SET current_streak = GREATEST(0, current_streak - 1) WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $habit_id);
        $update_stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Habit unmarked', 'action' => 'remove']);
    } else {
        // Add log
        $insert_sql = "INSERT INTO habit_logs (habit_id, user_id, completed_date) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iis", $habit_id, $user_id, $date);
        $insert_stmt->execute();
        
        // Update streak
        $update_sql = "UPDATE life_habits SET current_streak = current_streak + 1 WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $habit_id);
        $update_stmt->execute();
        
        // Update longest streak if needed
        $update_longest_sql = "UPDATE life_habits SET longest_streak = GREATEST(longest_streak, current_streak) WHERE id = ?";
        $update_longest_stmt = $conn->prepare($update_longest_sql);
        $update_longest_stmt->bind_param("i", $habit_id);
        $update_longest_stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Habit completed!', 'action' => 'add']);
    }
}

function getTimeEntries() {
    global $conn, $user_id;
    
    $limit = intval($_GET['limit'] ?? 10);
    
    $sql = "SELECT * FROM life_time_tracking 
            WHERE user_id = ? 
            ORDER BY start_time DESC 
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $entries = [];
    while ($row = $result->fetch_assoc()) {
        $entries[] = $row;
    }
    
    echo json_encode(['success' => true, 'entries' => $entries]);
}

function saveTimeEntry() {
    global $conn, $user_id;
    
    $activity = mysqli_real_escape_string($conn, $_POST['activity']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $start_time = mysqli_real_escape_string($conn, $_POST['start_time']);
    $end_time = mysqli_real_escape_string($conn, $_POST['end_time']);
    $duration = intval($_POST['duration']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
    
    $sql = "INSERT INTO life_time_tracking (user_id, activity_name, category, start_time, end_time, duration_minutes, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssis", $user_id, $activity, $category, $start_time, $end_time, $duration, $notes);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Time entry saved']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save time entry']);
    }
}

// NEW FUNCTION: Save manual time entry (without timer)
function saveManualTime() {
    global $conn, $user_id;
    
    $activity = mysqli_real_escape_string($conn, $_POST['activityName']);
    $category = mysqli_real_escape_string($conn, $_POST['activityCategory']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
    
    // Create a time entry for today
    $today = date('Y-m-d');
    $start_time = $today . ' 00:00:00';
    $end_time = $today . ' 23:59:59';
    $duration = 60; // Default 60 minutes
    
    $sql = "INSERT INTO life_time_tracking (user_id, activity_name, category, start_time, end_time, duration_minutes, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssis", $user_id, $activity, $category, $start_time, $end_time, $duration, $notes);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Time entry saved']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save time entry']);
    }
}

// NEW FUNCTION: Delete habit
function deleteHabit() {
    global $conn, $user_id;
    
    $habit_id = intval($_POST['habit_id']);
    
    // First, check if the habit belongs to the user
    $check_sql = "SELECT id FROM life_habits WHERE id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $habit_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Habit not found or unauthorized']);
        return;
    }
    
    // First delete related logs
    $delete_logs_sql = "DELETE FROM habit_logs WHERE habit_id = ? AND user_id = ?";
    $delete_logs_stmt = $conn->prepare($delete_logs_sql);
    $delete_logs_stmt->bind_param("ii", $habit_id, $user_id);
    $delete_logs_stmt->execute();
    
    // Then delete the habit
    $delete_habit_sql = "DELETE FROM life_habits WHERE id = ? AND user_id = ?";
    $delete_habit_stmt = $conn->prepare($delete_habit_sql);
    $delete_habit_stmt->bind_param("ii", $habit_id, $user_id);
    
    if ($delete_habit_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Habit deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete habit']);
    }
}

function getBusinessTips() {
    global $conn;
    
    $sql = "SELECT * FROM life_business_tips ORDER BY is_featured DESC, created_at DESC LIMIT 5";
    $result = $conn->query($sql);
    
    $tips = [];
    while ($row = $result->fetch_assoc()) {
        $tips[] = $row;
    }
    
    echo json_encode(['success' => true, 'tips' => $tips]);
}

function getHealthTips() {
    global $conn;
    
    $sql = "SELECT * FROM life_health_tips ORDER BY is_featured DESC, created_at DESC LIMIT 5";
    $result = $conn->query($sql);
    
    $tips = [];
    while ($row = $result->fetch_assoc()) {
        $tips[] = $row;
    }
    
    echo json_encode(['success' => true, 'tips' => $tips]);
}

function getKnowledgeArticles() {
    global $conn;
    
    $limit = intval($_GET['limit'] ?? 5);
    
    $sql = "SELECT * FROM life_knowledge_base ORDER BY views DESC, created_at DESC LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $articles = [];
    while ($row = $result->fetch_assoc()) {
        $articles[] = $row;
    }
    
    echo json_encode(['success' => true, 'articles' => $articles]);
}

function getAnalytics() {
    global $conn, $user_id;
    
    $analytics = [];
    
    // Habit completion data (last 7 days)
    $sql = "SELECT DATE(hl.completed_date) as date, COUNT(*) as count
            FROM habit_logs hl
            JOIN life_habits lh ON hl.habit_id = lh.id
            WHERE hl.user_id = ? AND hl.completed_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(hl.completed_date)
            ORDER BY date";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $analytics['habit_data'] = $result->fetch_all(MYSQLI_ASSOC);
    
    // Time distribution by category (last 30 days)
    $sql = "SELECT category, SUM(duration_minutes) as total
            FROM life_time_tracking
            WHERE user_id = ? AND start_time >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY category";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $analytics['time_data'] = $result->fetch_all(MYSQLI_ASSOC);
    
    // Monthly summary
    $sql = "SELECT 
            COUNT(DISTINCT hl.habit_id) as habits_completed,
            SUM(ltt.duration_minutes) as minutes_tracked,
            COUNT(DISTINCT DATE(ltt.start_time)) as active_days
            FROM habit_logs hl
            LEFT JOIN life_time_tracking ltt ON hl.user_id = ltt.user_id
            WHERE hl.user_id = ? AND MONTH(hl.completed_date) = MONTH(CURDATE())
            GROUP BY hl.user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $analytics['monthly_data'] = $result->fetch_assoc() ?: [];
    
    echo json_encode(['success' => true, 'data' => $analytics]);
}

// NEW FUNCTION: Get habit details (for future use)
function getHabitDetails() {
    global $conn, $user_id;
    
    $habit_id = intval($_GET['habit_id']);
    
    $sql = "SELECT lh.*, 
            (SELECT COUNT(*) FROM habit_logs WHERE habit_id = lh.id) as total_completions,
            (SELECT MAX(completed_date) FROM habit_logs WHERE habit_id = lh.id) as last_completed
            FROM life_habits lh
            WHERE lh.id = ? AND lh.user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $habit_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $habit = $result->fetch_assoc();
        
        // Get completion history
        $history_sql = "SELECT completed_date FROM habit_logs 
                       WHERE habit_id = ? ORDER BY completed_date DESC LIMIT 30";
        $history_stmt = $conn->prepare($history_sql);
        $history_stmt->bind_param("i", $habit_id);
        $history_stmt->execute();
        $history_result = $history_stmt->get_result();
        
        $history = [];
        while ($row = $history_result->fetch_assoc()) {
            $history[] = $row['completed_date'];
        }
        
        $habit['history'] = $history;
        
        echo json_encode(['success' => true, 'habit' => $habit]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Habit not found']);
    }
}

// Add this to the switch statement
// case 'get_habit_details':
//     getHabitDetails();
//     break;
?>