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
    case 'get_prayers':
        getPrayerRequests();
        break;
    case 'add_prayer':
        addPrayerRequest();
        break;
    case 'pray_for':
        handlePrayerInteraction('prayed');
        break;
    case 'encourage':
        handlePrayerInteraction('encouraged');
        break;
    case 'get_testimonies':
        getTestimonies();
        break;
    case 'add_testimony':
        addTestimony();
        break;
    case 'like_testimony':
        likeTestimony();
        break;
    case 'get_messages':
        getMessages();
        break;
    case 'send_message':
        sendMessage();
        break;
    case 'get_stats':
        getStats();
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}

function getPrayerRequests() {
    global $conn, $user_id;
    
    $sql = "SELECT pr.*, u.full_name,
            (SELECT COUNT(*) FROM prayer_interactions pi 
             WHERE pi.prayer_id = pr.id AND pi.interaction_type = 'prayed') as pray_count,
            (SELECT COUNT(*) FROM prayer_interactions pi 
             WHERE pi.prayer_id = pr.id AND pi.interaction_type = 'encouraged') as encourage_count,
            (SELECT COUNT(*) FROM prayer_interactions pi 
             WHERE pi.prayer_id = pr.id AND pi.user_id = ? AND pi.interaction_type = 'prayed') as user_prayed,
            (SELECT COUNT(*) FROM prayer_interactions pi 
             WHERE pi.prayer_id = pr.id AND pi.user_id = ? AND pi.interaction_type = 'encouraged') as user_encouraged
            FROM prayer_requests pr
            LEFT JOIN users u ON pr.user_id = u.id
            ORDER BY pr.created_at DESC
            LIMIT 20";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $prayers = [];
    while ($row = $result->fetch_assoc()) {
        // Handle anonymous posts
        if ($row['is_anonymous'] == 1) {
            $row['full_name'] = 'Anonymous';
        }
        $prayers[] = $row;
    }
    
    echo json_encode(['success' => true, 'prayers' => $prayers]);
}

function addPrayerRequest() {
    global $conn, $user_id;
    
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $anonymous = isset($_POST['anonymous']) ? 1 : 0;
    
    $sql = "INSERT INTO prayer_requests (user_id, title, content, is_anonymous) 
            VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issi", $user_id, $title, $content, $anonymous);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Prayer request added']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to add prayer request']);
    }
}

function handlePrayerInteraction($type) {
    global $conn, $user_id;
    
    $prayer_id = intval($_POST['prayer_id']);
    
    // Check if already interacted
    $check_sql = "SELECT id FROM prayer_interactions 
                  WHERE prayer_id = ? AND user_id = ? AND interaction_type = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("iis", $prayer_id, $user_id, $type);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'Already ' . $type]);
        return;
    }
    
    // Add interaction
    $sql = "INSERT INTO prayer_interactions (prayer_id, user_id, interaction_type) 
            VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $prayer_id, $user_id, $type);
    
    if ($stmt->execute()) {
        // Update count in prayer_requests table
        $update_field = $type == 'prayed' ? 'prayer_count' : 'encourage_count';
        $update_sql = "UPDATE prayer_requests SET $update_field = $update_field + 1 WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $prayer_id);
        $update_stmt->execute();
        
        echo json_encode(['success' => true, 'message' => ucfirst($type) . ' successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to record interaction']);
    }
}

function getTestimonies() {
    global $conn;
    
    $sql = "SELECT t.*, u.full_name,
            (SELECT COUNT(*) FROM testimony_likes tl WHERE tl.testimony_id = t.id) as like_count,
            (SELECT COUNT(*) FROM testimony_likes tl WHERE tl.testimony_id = t.id AND tl.user_id = ?) as user_liked
            FROM testimonies t
            LEFT JOIN users u ON t.user_id = u.id
            ORDER BY t.created_at DESC
            LIMIT 20";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $testimonies = [];
    while ($row = $result->fetch_assoc()) {
        $testimonies[] = $row;
    }
    
    echo json_encode(['success' => true, 'testimonies' => $testimonies]);
}

function addTestimony() {
    global $conn, $user_id;
    
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    
    $sql = "INSERT INTO testimonies (user_id, title, content, category) 
            VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $user_id, $title, $content, $category);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Testimony shared']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to share testimony']);
    }
}

function likeTestimony() {
    global $conn, $user_id;
    
    $testimony_id = intval($_POST['testimony_id']);
    
    // Check if already liked
    $check_sql = "SELECT id FROM testimony_likes WHERE testimony_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $testimony_id, $user_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'Already liked']);
        return;
    }
    
    // Add like
    $sql = "INSERT INTO testimony_likes (testimony_id, user_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $testimony_id, $user_id);
    
    if ($stmt->execute()) {
        // Update like count
        $update_sql = "UPDATE testimonies SET like_count = like_count + 1 WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $testimony_id);
        $update_stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Testimony liked']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to like testimony']);
    }
}

function getMessages() {
    global $conn;
    
    $sql = "SELECT gm.*, u.full_name 
            FROM gyc_messages gm
            LEFT JOIN users u ON gm.user_id = u.id
            ORDER BY gm.created_at DESC
            LIMIT 50";
    
    $result = $conn->query($sql);
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    
    echo json_encode(['success' => true, 'messages' => $messages]);
}

function sendMessage() {
    global $conn, $user_id;
    
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    $room = mysqli_real_escape_string($conn, $_POST['room'] ?? 'general');
    
    $sql = "INSERT INTO gyc_messages (user_id, message, room) VALUES (?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $user_id, $message, $room);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Message sent']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to send message']);
    }
}

function getStats() {
    global $conn;
    
    $stats = [];
    
    // Total prayers
    $result = $conn->query("SELECT COUNT(*) as total FROM prayer_requests");
    $stats['total_prayers'] = $result->fetch_assoc()['total'];
    
    // Answered prayers
    $result = $conn->query("SELECT COUNT(*) as answered FROM prayer_requests WHERE status = 'answered'");
    $stats['answered_prayers'] = $result->fetch_assoc()['answered'];
    
    // Total testimonies
    $result = $conn->query("SELECT COUNT(*) as total FROM testimonies");
    $stats['total_testimonies'] = $result->fetch_assoc()['total'];
    
    // Online users (last 5 minutes)
    $result = $conn->query("SELECT COUNT(DISTINCT user_id) as online FROM gyc_messages 
                           WHERE created_at >= NOW() - INTERVAL 5 MINUTE");
    $stats['online_users'] = $result->fetch_assoc()['online'];
    
    echo json_encode(['success' => true, 'stats' => $stats]);
}
?>