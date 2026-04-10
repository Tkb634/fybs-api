<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// Debug logging
error_log("Gospel action received: $action");

switch ($action) {
    case 'submit_prayer':
        submitPrayerSession();
        break;
    case 'submit_bible_study':
        submitBibleStudy();
        break;
    case 'submit_evangelism':
        submitEvangelismActivity();
        break;
    case 'start_program':
        startGospelProgram();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
}

function submitPrayerSession() {
    global $conn, $user_id;
    
    $prayer_type = $_POST['prayer_type'] ?? 'personal';
    $topic = $_POST['topic'] ?? null;
    $duration_minutes = intval($_POST['duration_minutes'] ?? 5);
    $scripture_reference = $_POST['scripture_reference'] ?? null;
    $prayer_points = $_POST['prayer_points'] ?? null;
    $notes = $_POST['notes'] ?? null;
    
    $session_date = date('Y-m-d');
    $start_time = date('H:i:s');
    
    // Get or create prayer program
    $program_id = getOrCreateProgram($user_id, 'prayer', 'Daily Prayer', 'Daily personal prayer time');
    
    $query = "INSERT INTO prayer_sessions (
        user_id, program_id, prayer_type, topic, 
        duration_minutes, scripture_reference, prayer_points,
        notes, session_date, start_time
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "iississsss",
        $user_id, $program_id, $prayer_type, $topic,
        $duration_minutes, $scripture_reference, $prayer_points,
        $notes, $session_date, $start_time
    );
    
    if ($stmt->execute()) {
        updateProgramStats($program_id);
        updateStreak($user_id, $program_id, 'prayer');
        
        echo json_encode(['success' => true, 'message' => 'Prayer session saved']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save prayer session: ' . $conn->error]);
    }
}

function submitBibleStudy() {
    global $conn, $user_id;
    
    $book = $_POST['book'] ?? '';
    $chapter = intval($_POST['chapter'] ?? 1);
    $verse_from = intval($_POST['verse_from'] ?? 1);
    $verse_to = intval($_POST['verse_to'] ?? 1);
    $study_method = $_POST['study_method'] ?? 'devotional';
    $duration_minutes = intval($_POST['duration_minutes'] ?? 15);
    $key_verses = $_POST['key_verses'] ?? null;
    $observations = $_POST['observations'] ?? null;
    $applications = $_POST['applications'] ?? null;
    
    $session_date = date('Y-m-d');
    
    // Get or create Bible study program
    $program_id = getOrCreateProgram($user_id, 'bible_study', 'Daily Bible Study', 'Regular Bible reading and study');
    
    $query = "INSERT INTO bible_study_sessions (
        user_id, program_id, book, chapter, verse_from, verse_to,
        study_method, duration_minutes, key_verses, observations,
        applications, session_date
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "iisiiisissss",
        $user_id, $program_id, $book, $chapter, $verse_from, $verse_to,
        $study_method, $duration_minutes, $key_verses, $observations,
        $applications, $session_date
    );
    
    if ($stmt->execute()) {
        updateProgramStats($program_id);
        updateStreak($user_id, $program_id, 'bible_study');
        
        echo json_encode(['success' => true, 'message' => 'Bible study saved']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save Bible study: ' . $conn->error]);
    }
}

function submitEvangelismActivity() {
    global $conn, $user_id;
    
    $activity_type = $_POST['activity_type'] ?? 'personal_witness';
    $title = $_POST['title'] ?? '';
    $location = $_POST['location'] ?? null;
    $people_reached = intval($_POST['people_reached'] ?? 0);
    $decisions_made = intval($_POST['decisions_made'] ?? 0);
    $duration_minutes = intval($_POST['duration_minutes'] ?? 60);
    $challenges_faced = $_POST['challenges_faced'] ?? null;
    $victories_shared = $_POST['victories_shared'] ?? null;
    
    $activity_date = date('Y-m-d');
    
    // Get or create evangelism program
    $program_id = getOrCreateProgram($user_id, 'evangelism', 'Evangelism Outreach', 'Sharing the gospel with others');
    
    $query = "INSERT INTO evangelism_activities (
        user_id, program_id, activity_type, title, location,
        people_reached, decisions_made, duration_minutes,
        challenges_faced, victories_shared, activity_date
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "iisssiiisss",
        $user_id, $program_id, $activity_type, $title, $location,
        $people_reached, $decisions_made, $duration_minutes,
        $challenges_faced, $victories_shared, $activity_date
    );
    
    if ($stmt->execute()) {
        updateProgramStats($program_id);
        updateStreak($user_id, $program_id, 'evangelism');
        
        // Create goal if this is first evangelism activity
        createEvangelismGoal($user_id, $program_id);
        
        echo json_encode(['success' => true, 'message' => 'Evangelism activity saved']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save evangelism activity: ' . $conn->error]);
    }
}

function startGospelProgram() {
    global $conn, $user_id;
    
    $program_type = $_POST['program_type'] ?? 'prayer';
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? null;
    $frequency = $_POST['frequency'] ?? 'daily';
    $target_duration_days = intval($_POST['target_duration_days'] ?? 30);
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    
    $start_date = date('Y-m-d');
    $target_end_date = date('Y-m-d', strtotime("+$target_duration_days days"));
    
    $query = "INSERT INTO gospel_programs (
        user_id, program_type, title, description,
        frequency, target_duration_days, start_date,
        target_end_date, is_public, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "issssissi",
        $user_id, $program_type, $title, $description,
        $frequency, $target_duration_days, $start_date,
        $target_end_date, $is_public
    );
    
    if ($stmt->execute()) {
        $program_id = $conn->insert_id;
        
        // Create initial goals for the program
        createProgramGoals($user_id, $program_id, $program_type, $target_duration_days);
        
        echo json_encode(['success' => true, 'message' => 'Program started', 'program_id' => $program_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to start program: ' . $conn->error]);
    }
}

function getOrCreateProgram($user_id, $program_type, $default_title, $default_description) {
    global $conn;
    
    // Check if user has active program of this type
    $query = "SELECT id FROM gospel_programs 
              WHERE user_id = ? AND program_type = ? AND status = 'active' 
              LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $user_id, $program_type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $program = $result->fetch_assoc();
        return $program['id'];
    }
    
    // Create new program
    $start_date = date('Y-m-d');
    $target_end_date = date('Y-m-d', strtotime('+30 days'));
    
    $query = "INSERT INTO gospel_programs (
        user_id, program_type, title, description,
        frequency, target_duration_days, start_date,
        target_end_date, status
    ) VALUES (?, ?, ?, ?, 'daily', 30, ?, ?, 'active')";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "isssss",
        $user_id, $program_type, $default_title, $default_description,
        $start_date, $target_end_date
    );
    
    if ($stmt->execute()) {
        return $conn->insert_id;
    }
    
    return 0;
}

function updateProgramStats($program_id) {
    global $conn;
    
    // Update total sessions
    $update = "UPDATE gospel_programs 
              SET total_sessions = total_sessions + 1,
                  completed_sessions = completed_sessions + 1,
                  updated_at = NOW()
              WHERE id = ?";
    $stmt = $conn->prepare($update);
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
}

function updateStreak($user_id, $program_id, $program_type) {
    global $conn;
    
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    // Check for activity yesterday
    $check_query = "";
    switch ($program_type) {
        case 'prayer':
            $check_query = "SELECT id FROM prayer_sessions WHERE user_id = ? AND session_date = ?";
            break;
        case 'bible_study':
            $check_query = "SELECT id FROM bible_study_sessions WHERE user_id = ? AND session_date = ?";
            break;
        case 'evangelism':
            $check_query = "SELECT id FROM evangelism_activities WHERE user_id = ? AND activity_date = ?";
            break;
    }
    
    $had_yesterday = false;
    if ($check_query) {
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("is", $user_id, $yesterday);
        $stmt->execute();
        $had_yesterday = ($stmt->get_result()->num_rows > 0);
    }
    
    // Get current streak
    $program_query = "SELECT current_streak, longest_streak FROM gospel_programs WHERE id = ?";
    $stmt = $conn->prepare($program_query);
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $program = $stmt->get_result()->fetch_assoc();
    
    $new_streak = $program['current_streak'];
    $longest_streak = $program['longest_streak'];
    
    if ($had_yesterday) {
        $new_streak++;
    } else {
        $new_streak = 1; // Start new streak
    }
    
    if ($new_streak > $longest_streak) {
        $longest_streak = $new_streak;
    }
    
    // Update streak
    $update = "UPDATE gospel_programs 
              SET current_streak = ?, longest_streak = ?,
                  progress_percentage = LEAST(100, (completed_sessions / target_duration_days) * 100),
                  updated_at = NOW()
              WHERE id = ?";
    $stmt = $conn->prepare($update);
    $stmt->bind_param("iii", $new_streak, $longest_streak, $program_id);
    $stmt->execute();
}

function createProgramGoals($user_id, $program_id, $program_type, $duration_days) {
    global $conn;
    
    $goals = [];
    
    switch ($program_type) {
        case 'prayer':
            $goals = [
                ['Pray for 5 minutes daily', 'prayer', 5, 'minutes'],
                ['Complete 7 consecutive days', 'prayer', 7, 'days'],
                ['Reach 30 days of prayer', 'prayer', 30, 'days']
            ];
            break;
        case 'bible_study':
            $goals = [
                ['Read one chapter daily', 'bible', 1, 'chapters/day'],
                ['Study 5 books of the Bible', 'bible', 5, 'books'],
                ['Complete reading plan', 'bible', $duration_days, 'days']
            ];
            break;
        case 'evangelism':
            $goals = [
                ['Share with 1 person this week', 'evangelism', 1, 'people'],
                ['Lead 3 people to Christ', 'evangelism', 3, 'decisions'],
                ['Reach 10 people with the gospel', 'evangelism', 10, 'people']
            ];
            break;
    }
    
    foreach ($goals as $goal) {
        $query = "INSERT INTO gospel_goals (
            user_id, program_id, goal_type, title, 
            target_value, unit, status
        ) VALUES (?, ?, ?, ?, ?, ?, 'active')";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "iissis",
            $user_id, $program_id, $goal[1], $goal[0],
            $goal[2], $goal[3]
        );
        $stmt->execute();
    }
}

function createEvangelismGoal($user_id, $program_id) {
    global $conn;
    
    // Check if evangelism goal exists
    $check = "SELECT id FROM gospel_goals 
              WHERE user_id = ? AND program_id = ? 
              AND goal_type = 'evangelism' 
              LIMIT 1";
    $stmt = $conn->prepare($check);
    $stmt->bind_param("ii", $user_id, $program_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        // Create initial evangelism goal
        $query = "INSERT INTO gospel_goals (
            user_id, program_id, goal_type, title,
            target_value, unit, status
        ) VALUES (?, ?, 'evangelism', 'Share the gospel with 5 people', 5, 'people', 'active')";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $user_id, $program_id);
        $stmt->execute();
    }
}
?>