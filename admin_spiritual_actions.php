<?php
require_once "config.php";
session_start();

// Only allow admins
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$conn->autocommit(false);

try {
    switch ($action) {
        case 'add':
        case 'edit':
            handleDevotional();
            break;
            
        case 'get_devotional':
            getDevotional();
            break;
            
        case 'get_sermon':
            getSermon();
            break;
            
        case 'add_preacher':
            addPreacher();
            break;
            
        case 'add_plan':
            addPlan();
            break;
            
        case 'delete':
            deleteContent();
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit();
}

function handleDevotional() {
    global $conn;
    
    $id = $_POST['id'] ?? null;
    $title = trim($_POST['title']);
    $devotional_type = $_POST['devotional_type'];
    $scheduled_date = $_POST['scheduled_date'];
    $verse = trim($_POST['verse']);
    $content = trim($_POST['content']);
    $reflection = trim($_POST['reflection']);
    $prayer = trim($_POST['prayer']);
    $author = trim($_POST['author'] ?? '');
    $duration_minutes = intval($_POST['duration_minutes'] ?? 5);
    
    if ($id) {
        // Update existing devotional
        $stmt = $conn->prepare("UPDATE spiritual_devotionals SET 
            title = ?, devotional_type = ?, scheduled_date = ?, verse = ?, 
            content = ?, reflection = ?, prayer = ?, author = ?, 
            duration_minutes = ?, updated_at = NOW() 
            WHERE id = ?");
        $stmt->bind_param("ssssssssii", $title, $devotional_type, $scheduled_date, 
            $verse, $content, $reflection, $prayer, $author, $duration_minutes, $id);
    } else {
        // Insert new devotional
        $stmt = $conn->prepare("INSERT INTO spiritual_devotionals 
            (title, devotional_type, scheduled_date, verse, content, 
            reflection, prayer, author, duration_minutes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssi", $title, $devotional_type, $scheduled_date, 
            $verse, $content, $reflection, $prayer, $author, $duration_minutes);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Database error: ' . $stmt->error);
    }
}

function getDevotional() {
    global $conn;
    
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM spiritual_devotionals WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $devotional = $result->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $devotional]);
    } else {
        throw new Exception('Devotional not found');
    }
}

function getSermon() {
    global $conn;
    
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM spiritual_sermons WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $sermon = $result->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $sermon]);
    } else {
        throw new Exception('Sermon not found');
    }
}

function addPreacher() {
    global $conn;
    
    $name = trim($_POST['name']);
    $title = trim($_POST['title'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $specialization = trim($_POST['specialization']);
    $website = trim($_POST['website'] ?? '');
    $social_media = trim($_POST['social_media'] ?? '');
    $is_recommended = isset($_POST['is_recommended']) ? 1 : 0;
    
    $stmt = $conn->prepare("INSERT INTO spiritual_preachers 
        (name, title, bio, specialization, website, social_media, is_recommended) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssi", $name, $title, $bio, $specialization, 
        $website, $social_media, $is_recommended);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Database error: ' . $stmt->error);
    }
}

function addPlan() {
    global $conn;
    
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $duration_days = intval($_POST['duration_days']);
    $bible_books = trim($_POST['bible_books']);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    $stmt = $conn->prepare("INSERT INTO spiritual_reading_plans 
        (title, description, duration_days, bible_books, is_featured) 
        VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisi", $title, $description, $duration_days, 
        $bible_books, $is_featured);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Database error: ' . $stmt->error);
    }
}

function deleteContent() {
    global $conn;
    
    $type = $_POST['type'];
    $id = intval($_POST['id']);
    
    $tables = [
        'devotional' => 'spiritual_devotionals',
        'sermon' => 'spiritual_sermons',
        'preacher' => 'spiritual_preachers',
        'plan' => 'spiritual_reading_plans'
    ];
    
    if (!isset($tables[$type])) {
        throw new Exception('Invalid content type');
    }
    
    $table = $tables[$type];
    $stmt = $conn->prepare("DELETE FROM $table WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Database error: ' . $stmt->error);
    }
}
?>