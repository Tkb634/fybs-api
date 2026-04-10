<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Debug: Log received data
error_log("Received action: " . ($_POST['action'] ?? 'none'));
error_log("POST data: " . print_r($_POST, true));

// Check if it's a check-in submission
if (isset($_POST['program_id']) || isset($_POST['craving_intensity'])) {
    submitDailyCheckin();
} 
// Check if it's a program start
elseif (isset($_POST['program_type']) || isset($_POST['addiction_type'])) {
    startRecoveryProgram();
}
else {
    // Get action from POST or default to submit_checkin
    $action = $_POST['action'] ?? 'submit_checkin';
    
    switch ($action) {
        case 'submit_checkin':
            submitDailyCheckin();
            break;
        case 'start_program':
            startRecoveryProgram();
            break;
        case 'update_program':
            updateRecoveryProgram();
            break;
        case 'log_strategy':
            logStrategyUsage();
            break;
        case 'log_emergency_call':
            logEmergencyCall();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
    }
}

function submitDailyCheckin() {
    global $conn, $user_id;
    
    // Debug: Log received data
    error_log("submitDailyCheckin called");
    error_log("POST data: " . print_r($_POST, true));
    
    // Check if we have program_id directly or need to get it
    if (isset($_POST['program_id'])) {
        $program_id = $_POST['program_id'];
    } else {
        // Get user's active program
        $program_query = "SELECT id FROM addiction_breaker_programs WHERE user_id = ? LIMIT 1";
        $stmt = $conn->prepare($program_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'No active program found']);
            return;
        }
        
        $program = $result->fetch_assoc();
        $program_id = $program['id'];
    }
    
    // Get form data with defaults
    $craving_intensity = $_POST['craving_intensity'] ?? 'none';
    $mood_before = $_POST['mood_before'] ?? 'neutral';
    $substance_free = isset($_POST['substance_free']) ? (int)$_POST['substance_free'] : 1;
    $sleep_hours = !empty($_POST['sleep_hours']) ? floatval($_POST['sleep_hours']) : null;
    $challenges_faced = $_POST['challenges_faced'] ?? null;
    $victories_achieved = $_POST['victories_achieved'] ?? null;
    
    // Get program details
    $program_query = "SELECT * FROM addiction_breaker_programs WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($program_query);
    $stmt->bind_param("ii", $program_id, $user_id);
    $stmt->execute();
    $program_result = $stmt->get_result();
    
    if ($program_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Program not found']);
        return;
    }
    
    $program = $program_result->fetch_assoc();
    
    // Calculate day streak
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    // Check if checked in yesterday
    $yesterday_checkin = $conn->prepare("SELECT id FROM addiction_daily_checkins WHERE program_id = ? AND checkin_date = ?");
    $yesterday_checkin->bind_param("is", $program_id, $yesterday);
    $yesterday_checkin->execute();
    $had_yesterday_checkin = ($yesterday_checkin->get_result()->num_rows > 0);
    
    $new_streak = $program['current_streak'];
    $new_total_clean = $program['total_clean_days'];
    
    if ($substance_free == 1) {
        $new_total_clean++;
        
        if ($had_yesterday_checkin) {
            $new_streak++;
        } else {
            $new_streak = 1; // Reset streak if missed yesterday
        }
    } else {
        $new_streak = 0; // Reset streak on relapse
        
        // Update relapse count
        $relapse_count = $program['relapse_count'] + 1;
        $update_relapse = $conn->prepare("UPDATE addiction_breaker_programs SET relapse_count = ?, last_relapse_date = ? WHERE id = ?");
        $update_relapse->bind_param("isi", $relapse_count, $today, $program_id);
        $update_relapse->execute();
    }
    
    // Update longest streak if needed
    $longest_streak = $program['longest_streak'];
    if ($new_streak > $longest_streak) {
        $longest_streak = $new_streak;
    }
    
    // Calculate progress percentage (based on days clean and streak)
    $progress = min(100, ($new_total_clean / 90) * 100); // 90 days target
    
    // Update program stats
    $update_program = $conn->prepare("UPDATE addiction_breaker_programs SET current_streak = ?, longest_streak = ?, total_clean_days = ?, progress_percentage = ? WHERE id = ?");
    $update_program->bind_param("iiidi", $new_streak, $longest_streak, $new_total_clean, $progress, $program_id);
    $update_program->execute();
    
    // Check if check-in already exists for today
    $check_existing = $conn->prepare("SELECT id FROM addiction_daily_checkins WHERE program_id = ? AND checkin_date = ?");
    $check_existing->bind_param("is", $program_id, $today);
    $check_existing->execute();
    
    if ($check_existing->get_result()->num_rows > 0) {
        // Update existing check-in
        $checkin_query = "UPDATE addiction_daily_checkins SET 
            checkin_time = NOW(), 
            day_clean = ?,
            craving_intensity = ?,
            mood_before = ?,
            substance_free = ?,
            sleep_hours = ?,
            challenges_faced = ?,
            victories_achieved = ?,
            is_completed = 1,
            completed_at = NOW()
            WHERE program_id = ? AND checkin_date = ?";
        
        $stmt = $conn->prepare($checkin_query);
        $stmt->bind_param(
            "isssdsssis",
            $new_total_clean,
            $craving_intensity,
            $mood_before,
            $substance_free,
            $sleep_hours,
            $challenges_faced,
            $victories_achieved,
            $program_id,
            $today
        );
    } else {
        // Insert new check-in record
        $checkin_query = "INSERT INTO addiction_daily_checkins (
            program_id, checkin_date, checkin_time, day_clean, 
            craving_intensity, mood_before, substance_free,
            sleep_hours, challenges_faced, victories_achieved,
            is_completed, completed_at
        ) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
        
        $stmt = $conn->prepare($checkin_query);
        $stmt->bind_param(
            "isisssdss",
            $program_id, $today, $new_total_clean,
            $craving_intensity, $mood_before, $substance_free,
            $sleep_hours, $challenges_faced, $victories_achieved
        );
    }
    
    if ($stmt->execute()) {
        // Check for milestone achievements
        checkMilestones($program_id, $new_streak, $new_total_clean);
        
        echo json_encode(['success' => true, 'message' => 'Check-in submitted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit check-in: ' . $conn->error]);
    }
}

function startRecoveryProgram() {
    global $conn, $user_id;
    
    // Debug: Log received data
    error_log("startRecoveryProgram called");
    error_log("POST data: " . print_r($_POST, true));
    
    $program_type = $_POST['program_type'] ?? 'substance';
    $addiction_type = $_POST['addiction_type'] ?? '';
    $severity_level = $_POST['severity_level'] ?? 'moderate';
    $emergency_contact_name = $_POST['emergency_contact_name'] ?? null;
    $emergency_contact_phone = $_POST['emergency_contact_phone'] ?? null;
    $daily_checkin_time = $_POST['daily_checkin_time'] ?? '19:00';
    $trigger_alerts = isset($_POST['trigger_alerts_enabled']) ? 1 : 0;
    
    if (empty($addiction_type)) {
        echo json_encode(['success' => false, 'message' => 'Addiction type is required']);
        return;
    }
    
    $start_date = date('Y-m-d');
    $target_end_date = date('Y-m-d', strtotime('+90 days')); // 90-day program
    
    // Determine initial stage based on severity
    $current_stage = 'assessment';
    
    // Check if user already has a program
    $check_query = "SELECT id FROM addiction_breaker_programs WHERE user_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        // Update existing program
        $query = "UPDATE addiction_breaker_programs SET 
            program_type = ?, 
            addiction_type = ?, 
            severity_level = ?,
            start_date = ?,
            target_end_date = ?,
            current_stage = ?,
            emergency_contact_name = ?,
            emergency_contact_phone = ?,
            daily_checkin_time = ?,
            trigger_alerts_enabled = ?,
            updated_at = NOW()
            WHERE user_id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "ssssssssssi",
            $program_type, $addiction_type, $severity_level,
            $start_date, $target_end_date, $current_stage,
            $emergency_contact_name, $emergency_contact_phone,
            $daily_checkin_time, $trigger_alerts,
            $user_id
        );
        
        if ($stmt->execute()) {
            $program_id = $conn->insert_id;
            echo json_encode(['success' => true, 'message' => 'Program updated', 'program_id' => $program_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update program: ' . $conn->error]);
        }
    } else {
        // Create new program
        $query = "INSERT INTO addiction_breaker_programs (
            user_id, program_type, addiction_type, severity_level,
            start_date, target_end_date, current_stage,
            emergency_contact_name, emergency_contact_phone,
            daily_checkin_time, trigger_alerts_enabled,
            created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "isssssssssi",
            $user_id, $program_type, $addiction_type, $severity_level,
            $start_date, $target_end_date, $current_stage,
            $emergency_contact_name, $emergency_contact_phone,
            $daily_checkin_time, $trigger_alerts
        );
        
        if ($stmt->execute()) {
            $program_id = $conn->insert_id;
            
            // Create initial milestones
            createInitialMilestones($program_id);
            
            echo json_encode(['success' => true, 'message' => 'Program started', 'program_id' => $program_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to start program: ' . $conn->error]);
        }
    }
}

function createInitialMilestones($program_id) {
    global $conn;
    
    $milestones = [
        ['days_clean', 'First 24 Hours', 1],
        ['days_clean', 'One Week Clean', 7],
        ['days_clean', 'Two Weeks Strong', 14],
        ['days_clean', '30-Day Milestone', 30],
        ['days_clean', '60 Days Victory', 60],
        ['days_clean', '90-Day Complete', 90]
    ];
    
    foreach ($milestones as $milestone) {
        $query = "INSERT INTO addiction_progress_milestones (
            program_id, milestone_type, milestone_name, target_value,
            created_at
        ) VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issi", $program_id, $milestone[0], $milestone[1], $milestone[2]);
        $stmt->execute();
    }
}

function checkMilestones($program_id, $current_streak, $total_clean_days) {
    global $conn;
    
    // Check for day-based milestones
    $day_milestones = [1, 3, 7, 14, 30, 60, 90];
    
    foreach ($day_milestones as $days) {
        if ($total_clean_days >= $days) {
            // Check if milestone exists and not achieved
            $check_query = "SELECT id FROM addiction_progress_milestones 
                          WHERE program_id = ? AND milestone_type = 'days_clean' 
                          AND target_value = ? AND is_achieved = 0";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("ii", $program_id, $days);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Mark as achieved
                $row = $result->fetch_assoc();
                $update_query = "UPDATE addiction_progress_milestones 
                               SET is_achieved = 1, achieved_date = CURDATE(),
                               current_value = ?, updated_at = NOW()
                               WHERE id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("ii", $total_clean_days, $row['id']);
                $stmt->execute();
            }
        }
    }
}

function logStrategyUsage() {
    global $conn, $user_id;
    
    $strategy_id = $_POST['strategy_id'] ?? 0;
    
    // Simple log - you can expand this to store in a separate table
    error_log("Strategy used by user $user_id: strategy_id=$strategy_id");
    
    echo json_encode(['success' => true, 'message' => 'Strategy usage logged']);
}

function logEmergencyCall() {
    global $conn, $user_id;
    
    // Simple log - you can expand this to store in a separate table
    error_log("Emergency call made by user $user_id");
    
    echo json_encode(['success' => true, 'message' => 'Emergency call logged']);
}

function updateRecoveryProgram() {
    // Implement program updates if needed
    echo json_encode(['success' => false, 'message' => 'Not implemented yet']);
}
?>