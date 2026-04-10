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
    case 'mark_devotional_read':
        $devotional_id = intval($_POST['devotional_id']);
        
        // Check if already logged today
        $check_query = "SELECT * FROM spiritual_devotional_logs 
                       WHERE user_id = ? AND devotional_id = ? AND completed_date = CURDATE()";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("ii", $user_id, $devotional_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            // Log devotional completion
            $log_query = "INSERT INTO spiritual_devotional_logs (user_id, devotional_id, completed_date, is_completed) 
                         VALUES (?, ?, CURDATE(), 1)";
            $stmt = $conn->prepare($log_query);
            $stmt->bind_param("ii", $user_id, $devotional_id);
            $stmt->execute();
            
            // Update user stats
            $update_query = "INSERT INTO spiritual_user_stats (user_id, streak_days, total_devotionals, scriptures_read, last_active_date)
                            VALUES (?, 1, 1, 1, CURDATE())
                            ON DUPLICATE KEY UPDATE 
                            streak_days = IF(DATEDIFF(CURDATE(), last_active_date) = 1, streak_days + 1, 1),
                            total_devotionals = total_devotionals + 1,
                            scriptures_read = scriptures_read + 1,
                            last_active_date = CURDATE()";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            // Update devotional views
            $update_views = "UPDATE spiritual_devotionals SET views = views + 1 WHERE id = ?";
            $stmt = $conn->prepare($update_views);
            $stmt->bind_param("i", $devotional_id);
            $stmt->execute();
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Already marked as read today']);
        }
        break;
        
    case 'follow_preacher':
        $preacher_id = intval($_POST['preacher_id']);
        
        // Check if already following
        $check_query = "SELECT * FROM spiritual_following WHERE user_id = ? AND preacher_id = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("ii", $user_id, $preacher_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            // Follow preacher
            $follow_query = "INSERT INTO spiritual_following (user_id, preacher_id) VALUES (?, ?)";
            $stmt = $conn->prepare($follow_query);
            $stmt->bind_param("ii", $user_id, $preacher_id);
            $stmt->execute();
            
            // Update preacher's followers count
            $update_query = "UPDATE spiritual_preachers SET followers_count = followers_count + 1 WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("i", $preacher_id);
            $stmt->execute();
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Already following']);
        }
        break;
        
    case 'unfollow_preacher':
        $preacher_id = intval($_POST['preacher_id']);
        
        // Unfollow preacher
        $unfollow_query = "DELETE FROM spiritual_following WHERE user_id = ? AND preacher_id = ?";
        $stmt = $conn->prepare($unfollow_query);
        $stmt->bind_param("ii", $user_id, $preacher_id);
        $stmt->execute();
        
        // Update preacher's followers count
        $update_query = "UPDATE spiritual_preachers SET followers_count = GREATEST(followers_count - 1, 0) WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $preacher_id);
        $stmt->execute();
        
        echo json_encode(['success' => true]);
        break;
        
    case 'start_reading_plan':
        $plan_id = intval($_POST['plan_id']);
        
        // Check if already started
        $check_query = "SELECT * FROM spiritual_plan_progress WHERE user_id = ? AND plan_id = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("ii", $user_id, $plan_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            // Start reading plan
            $start_query = "INSERT INTO spiritual_plan_progress (user_id, plan_id, start_date, last_activity) 
                           VALUES (?, ?, CURDATE(), NOW())";
            $stmt = $conn->prepare($start_query);
            $stmt->bind_param("ii", $user_id, $plan_id);
            $stmt->execute();
            
            // Update plan participants count
            $update_query = "UPDATE spiritual_reading_plans SET participants_count = participants_count + 1 WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("i", $plan_id);
            $stmt->execute();
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Plan already started']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>