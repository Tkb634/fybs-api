<?php
session_start();
include "config.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
if (isset($_SESSION['full_name']) && !empty($_SESSION['full_name'])) {
    $user_name = $_SESSION['full_name'];
} elseif (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];
    $name_part = explode('@', $email)[0];
    $user_name = ucwords(str_replace('.', ' ', $name_part));
} else {
    $user_name = "Believer";
}

$first_name = explode(' ', $user_name)[0];

// Get user's active gospel programs
$programs_query = "SELECT * FROM gospel_programs WHERE user_id = ? AND status = 'active'";
$stmt = $conn->prepare($programs_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$programs_result = $stmt->get_result();

$active_programs = [];
$total_streak = 0;
$total_sessions = 0;
$completed_sessions = 0;

while ($program = $programs_result->fetch_assoc()) {
    $active_programs[] = $program;
    $total_streak += $program['current_streak'];
    $total_sessions += $program['total_sessions'];
    $completed_sessions += $program['completed_sessions'];
}

$has_programs = count($active_programs) > 0;

// Get today's activity status
$today = date('Y-m-d');

// Check prayer sessions today
$prayer_today = $conn->prepare("SELECT COUNT(*) as count FROM prayer_sessions WHERE user_id = ? AND session_date = ?");
$prayer_today->bind_param("is", $user_id, $today);
$prayer_today->execute();
$prayer_today_result = $prayer_today->get_result();
$prayer_today_count = $prayer_today_result->fetch_assoc()['count'];

// Check Bible study today
$bible_today = $conn->prepare("SELECT COUNT(*) as count FROM bible_study_sessions WHERE user_id = ? AND session_date = ?");
$bible_today->bind_param("is", $user_id, $today);
$bible_today->execute();
$bible_today_result = $bible_today->get_result();
$bible_today_count = $bible_today_result->fetch_assoc()['count'];

// Check evangelism today
$evangelism_today = $conn->prepare("SELECT COUNT(*) as count FROM evangelism_activities WHERE user_id = ? AND activity_date = ?");
$evangelism_today->bind_param("is", $user_id, $today);
$evangelism_today->execute();
$evangelism_today_result = $evangelism_today->get_result();
$evangelism_today_count = $evangelism_today_result->fetch_assoc()['count'];

// Get recent gospel goals
$goals_query = "SELECT * FROM gospel_goals WHERE user_id = ? AND status = 'active' ORDER BY deadline ASC LIMIT 3";
$stmt = $conn->prepare($goals_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$goals_result = $stmt->get_result();
$active_goals = [];
while ($row = $goals_result->fetch_assoc()) {
    $active_goals[] = $row;
}

// Get featured resources
$resources_query = "SELECT * FROM gospel_resources WHERE is_featured = 1 ORDER BY created_at DESC LIMIT 6";
$resources_result = $conn->query($resources_query);
$featured_resources = [];
while ($row = $resources_result->fetch_assoc()) {
    $featured_resources[] = $row;
}

// Calculate overall progress
$overall_progress = $total_sessions > 0 ? min(100, ($completed_sessions / $total_sessions) * 100) : 0;

// Get motivational scripture
$scriptures = [
    ['text' => 'Go into all the world and preach the gospel to all creation.', 'ref' => 'Mark 16:15'],
    ['text' => 'Pray continually.', 'ref' => '1 Thessalonians 5:17'],
    ['text' => 'Your word is a lamp for my feet, a light on my path.', 'ref' => 'Psalm 119:105'],
    ['text' => 'Always be prepared to give an answer to everyone who asks you to give the reason for the hope that you have.', 'ref' => '1 Peter 3:15']
];
$daily_scripture = $scriptures[date('z') % count($scriptures)];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Gospel Movement - FYBS Youth App</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#059669">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="manifest.json">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #059669;
            --primary-light: #10b981;
            --primary-dark: #047857;
            --prayer-color: #3b82f6;
            --bible-color: #f59e0b;
            --evangelism-color: #ef4444;
            --discipleship-color: #8b5cf6;
            --dark-color: #1f2937;
            --light-color: #f9fafb;
            --gradient-primary: linear-gradient(135deg, #059669 0%, #10b981 100%);
            --gradient-prayer: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            --gradient-bible: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --gradient-evangelism: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            --gradient-discipleship: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: var(--dark-color);
            overflow-x: hidden;
            min-height: 100vh;
        }
        
        /* Header Styles */
        .main-header {
            background: white;
            padding: 20px 0;
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .app-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo-icon {
            width: 40px;
            height: 40px;
            background: var(--gradient-primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            box-shadow: var(--shadow-md);
        }
        
        .logo-text {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 24px;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 8px 16px;
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        .user-profile:hover {
            background: #f8fafc;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
            box-shadow: var(--shadow-md);
        }
        
        /* Main Container */
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px 100px;
        }
        
        /* Welcome Section */
        .welcome-section {
            margin-bottom: 30px;
        }
        
        .welcome-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--shadow-lg);
            border: 1px solid #e5e7eb;
            position: relative;
            overflow: hidden;
        }
        
        .welcome-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: var(--gradient-primary);
        }
        
        .greeting-title {
            font-family: 'Poppins', sans-serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 10px;
        }
        
        .greeting-subtitle {
            font-size: 16px;
            color: #6b7280;
            margin-bottom: 20px;
        }
        
        .scripture-card {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left: 4px solid #f59e0b;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
        }
        
        .scripture-text {
            font-style: italic;
            color: #92400e;
            margin-bottom: 5px;
        }
        
        .scripture-ref {
            text-align: right;
            font-weight: 500;
            color: #92400e;
            font-size: 14px;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--shadow-md);
            border: 1px solid #e5e7eb;
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            margin: 0 auto 15px;
        }
        
        .stat-card:nth-child(1) .stat-icon {
            background: var(--gradient-primary);
        }
        
        .stat-card:nth-child(2) .stat-icon {
            background: var(--gradient-prayer);
        }
        
        .stat-card:nth-child(3) .stat-icon {
            background: var(--gradient-bible);
        }
        
        .stat-card:nth-child(4) .stat-icon {
            background: var(--gradient-evangelism);
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #6b7280;
        }
        
        /* Daily Check-in */
        .daily-checkin {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--shadow-lg);
            border: 1px solid #e5e7eb;
            margin-bottom: 25px;
        }
        
        .checkin-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkin-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .checkin-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .checkin-item {
            background: #f9fafb;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .checkin-item:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }
        
        .checkin-item.completed {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border: 2px solid #10b981;
        }
        
        .checkin-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin: 0 auto 15px;
        }
        
        .checkin-item:nth-child(1) .checkin-icon {
            background: var(--gradient-prayer);
        }
        
        .checkin-item:nth-child(2) .checkin-icon {
            background: var(--gradient-bible);
        }
        
        .checkin-item:nth-child(3) .checkin-icon {
            background: var(--gradient-evangelism);
        }
        
        .checkin-name {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--dark-color);
        }
        
        .checkin-status {
            font-size: 14px;
            font-weight: 500;
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }
        
        /* Quick Actions */
        .actions-section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-family: 'Poppins', sans-serif;
            font-size: 22px;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .actions-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .action-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--shadow-md);
            border: 1px solid #e5e7eb;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            cursor: pointer;
            text-align: center;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            color: inherit;
        }
        
        .action-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin: 0 auto 15px;
            background: var(--gradient-primary);
        }
        
        .action-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--dark-color);
        }
        
        .action-desc {
            font-size: 14px;
            color: #6b7280;
        }
        
        /* Active Goals */
        .goals-section {
            margin-bottom: 25px;
        }
        
        .goals-list {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: var(--shadow-lg);
            border: 1px solid #e5e7eb;
        }
        
        .goal-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .goal-item:last-child {
            border-bottom: none;
        }
        
        .goal-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .goal-content {
            flex: 1;
        }
        
        .goal-title {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 4px;
        }
        
        .goal-progress {
            font-size: 13px;
            color: #6b7280;
        }
        
        .progress-bar {
            height: 6px;
            background: #e5e7eb;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 8px;
        }
        
        .progress-fill {
            height: 100%;
            background: var(--primary-color);
            border-radius: 3px;
        }
        
        /* Featured Resources */
        .resources-section {
            margin-bottom: 25px;
        }
        
        .resources-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .resources-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        .resource-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--shadow-md);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .resource-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }
        
        .resource-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            margin-bottom: 15px;
        }
        
        .resource-card:nth-child(1) .resource-icon {
            background: var(--gradient-prayer);
        }
        
        .resource-card:nth-child(2) .resource-icon {
            background: var(--gradient-bible);
        }
        
        .resource-card:nth-child(3) .resource-icon {
            background: var(--gradient-evangelism);
        }
        
        .resource-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--dark-color);
        }
        
        .resource-desc {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 10px;
        }
        
        .resource-meta {
            font-size: 12px;
            color: var(--primary-color);
            font-weight: 500;
        }
        
        /* Active Programs */
        .programs-section {
            margin-bottom: 25px;
        }
        
        .programs-list {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: var(--shadow-lg);
            border: 1px solid #e5e7eb;
        }
        
        .program-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .program-item:last-child {
            border-bottom: none;
        }
        
        .program-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .program-content {
            flex: 1;
        }
        
        .program-title {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 4px;
        }
        
        .program-info {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 8px;
        }
        
        /* Bottom Navigation */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
            padding: 12px 20px;
            z-index: 1000;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
        
        .nav-items {
            display: flex;
            justify-content: space-around;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #6b7280;
            transition: all 0.3s ease;
            padding: 8px 16px;
            border-radius: 12px;
        }
        
        .nav-item:hover, .nav-item.active {
            color: var(--primary-color);
            background: rgba(5, 150, 105, 0.1);
        }
        
        .nav-icon {
            font-size: 20px;
            margin-bottom: 4px;
        }
        
        .nav-label {
            font-size: 12px;
            font-weight: 500;
        }
        
        /* Buttons */
        .btn-primary {
            background: var(--gradient-primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }
        
        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .form-select, .form-input, .form-textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-select:focus, .form-input:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }
            
            .greeting-title {
                font-size: 24px;
            }
            
            .main-container {
                padding: 20px 15px 100px;
            }
            
            .welcome-card, .daily-checkin {
                padding: 20px;
            }
            
            .section-title {
                font-size: 20px;
            }
        }
        
        /* Dark Mode */
        @media (prefers-color-scheme: dark) {
            body {
                background: #111827;
                color: #f9fafb;
            }
            
            .main-header, .welcome-card, .stat-card,
            .daily-checkin, .action-card, .goals-list,
            .resources-grid, .programs-list {
                background: #1f2937;
                border-color: #374151;
            }
            
            .greeting-title, .checkin-title, .section-title,
            .stat-value, .action-title, .goal-title,
            .resource-title, .program-title {
                color: #f9fafb;
            }
            
            .greeting-subtitle, .stat-label, .action-desc,
            .goal-progress, .resource-desc, .program-info {
                color: #d1d5db;
            }
            
            .checkin-item {
                background: #374151;
            }
            
            .checkin-item.completed {
                background: linear-gradient(135deg, #065f46 0%, #047857 100%);
            }
            
            .scripture-card {
                background: linear-gradient(135deg, #92400e 0%, #78350f 100%);
            }
            
            .scripture-text, .scripture-ref {
                color: #fde68a;
            }
            
            .bottom-nav {
                background: #1f2937;
                background: rgba(31, 41, 55, 0.95);
            }
            
            .nav-item {
                color: #9ca3af;
            }
            
            .nav-item:hover, .nav-item.active {
                color: var(--primary-light);
                background: rgba(5, 150, 105, 0.1);
            }
        }
    </style>
</head>
<body>
    <!-- Main Header -->
    <header class="main-header">
        <div class="header-content">
            <div class="app-logo">
                <div class="logo-icon">
                    <i class="fas fa-cross"></i>
                </div>
                <div class="logo-text">Gospel Movement</div>
            </div>
            
            <div class="user-profile" onclick="window.location.href='profile.php'">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($first_name, 0, 1)); ?>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="main-container">
        <!-- Welcome Section -->
        <section class="welcome-section">
            <div class="welcome-card">
                <h1 class="greeting-title">Spread the Good News 🌟</h1>
                <p class="greeting-subtitle">
                    <?php if ($has_programs): ?>
                        You're actively involved in <?php echo count($active_programs); ?> gospel programs. Keep shining your light!
                    <?php else: ?>
                        Join the movement to share God's love through prayer, Bible study, and evangelism.
                    <?php endif; ?>
                </p>
                
                <div class="scripture-card">
                    <p class="scripture-text">"<?php echo $daily_scripture['text']; ?>"</p>
                    <div class="scripture-ref">— <?php echo $daily_scripture['ref']; ?></div>
                </div>
            </div>
        </section>
        
        <!-- Stats Grid -->
        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-fire"></i>
                </div>
                <div class="stat-value"><?php echo $total_streak; ?> 🔥</div>
                <div class="stat-label">Day Streak</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-hands-praying"></i>
                </div>
                <div class="stat-value"><?php echo $completed_sessions; ?></div>
                <div class="stat-label">Sessions Done</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-bible"></i>
                </div>
                <div class="stat-value"><?php echo $total_sessions; ?></div>
                <div class="stat-label">Total Sessions</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-value"><?php echo number_format($overall_progress, 0); ?>%</div>
                <div class="stat-label">Overall Progress</div>
            </div>
        </section>
        
        <!-- Daily Check-in -->
        <section class="daily-checkin">
            <h2 class="checkin-title">
                <i class="fas fa-calendar-check"></i>
                Today's Activities
            </h2>
            
            <div class="checkin-grid">
                <div class="checkin-item <?php echo $prayer_today_count > 0 ? 'completed' : ''; ?>" onclick="openPrayerModal()">
                    <div class="checkin-icon">
                        <i class="fas fa-hands-praying"></i>
                    </div>
                    <h3 class="checkin-name">Prayer</h3>
                    <div class="checkin-status <?php echo $prayer_today_count > 0 ? 'status-completed' : 'status-pending'; ?>">
                        <?php echo $prayer_today_count > 0 ? '✓ Prayed Today' : 'Pray Today'; ?>
                    </div>
                </div>
                
                <div class="checkin-item <?php echo $bible_today_count > 0 ? 'completed' : ''; ?>" onclick="openBibleModal()">
                    <div class="checkin-icon">
                        <i class="fas fa-book-bible"></i>
                    </div>
                    <h3 class="checkin-name">Bible Study</h3>
                    <div class="checkin-status <?php echo $bible_today_count > 0 ? 'status-completed' : 'status-pending'; ?>">
                        <?php echo $bible_today_count > 0 ? '✓ Studied Today' : 'Study Today'; ?>
                    </div>
                </div>
                
                <div class="checkin-item <?php echo $evangelism_today_count > 0 ? 'completed' : ''; ?>" onclick="openEvangelismModal()">
                    <div class="checkin-icon">
                        <i class="fas fa-microphone"></i>
                    </div>
                    <h3 class="checkin-name">Evangelism</h3>
                    <div class="checkin-status <?php echo $evangelism_today_count > 0 ? 'status-completed' : 'status-pending'; ?>">
                        <?php echo $evangelism_today_count > 0 ? '✓ Shared Today' : 'Share Today'; ?>
                    </div>
                </div>
            </div>
            
            <button class="btn-primary" onclick="openAllActivities()">
                <i class="fas fa-clipboard-list me-2"></i> Log All Activities
            </button>
        </section>
        
        <!-- Quick Actions -->
        <section class="actions-section">
            <h2 class="section-title">
                <i class="fas fa-bolt"></i>
                Quick Actions
            </h2>
            
            <div class="actions-grid">
                <div class="action-card" onclick="startPrayerProgram()">
                    <div class="action-icon">
                        <i class="fas fa-hands-praying"></i>
                    </div>
                    <h3 class="action-title">Prayer Program</h3>
                    <p class="action-desc">Start a 30-day prayer challenge</p>
                </div>
                
                <div class="action-card" onclick="startBibleProgram()">
                    <div class="action-icon">
                        <i class="fas fa-book-bible"></i>
                    </div>
                    <h3 class="action-title">Bible Study</h3>
                    <p class="action-desc">Begin reading plan</p>
                </div>
                
                <div class="action-card" onclick="startEvangelismProgram()">
                    <div class="action-icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <h3 class="action-title">Evangelism</h3>
                    <p class="action-desc">Share the gospel</p>
                </div>
                
                <div class="action-card" onclick="openResources()">
                    <div class="action-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h3 class="action-title">Resources</h3>
                    <p class="action-desc">Tools & guides</p>
                </div>
            </div>
        </section>
        
        <!-- Active Goals -->
        <?php if (!empty($active_goals)): ?>
        <section class="goals-section">
            <h2 class="section-title">
                <i class="fas fa-bullseye"></i>
                Active Goals
            </h2>
            
            <div class="goals-list">
                <?php foreach ($active_goals as $goal): 
                    $progress = $goal['target_value'] > 0 ? min(100, ($goal['current_value'] / $goal['target_value']) * 100) : 0;
                ?>
                <div class="goal-item">
                    <div class="goal-icon" style="background: <?php 
                        switch($goal['goal_type']) {
                            case 'prayer': echo 'var(--gradient-prayer)'; break;
                            case 'bible': echo 'var(--gradient-bible)'; break;
                            case 'evangelism': echo 'var(--gradient-evangelism)'; break;
                            default: echo 'var(--gradient-primary)';
                        }
                    ?>;">
                        <i class="fas <?php 
                            switch($goal['goal_type']) {
                                case 'prayer': echo 'fa-hands-praying'; break;
                                case 'bible': echo 'fa-book-bible'; break;
                                case 'evangelism': echo 'fa-bullhorn'; break;
                                default: echo 'fa-flag';
                            }
                        ?>"></i>
                    </div>
                    
                    <div class="goal-content">
                        <div class="goal-title"><?php echo htmlspecialchars($goal['title']); ?></div>
                        <div class="goal-progress">
                            <?php echo $goal['current_value']; ?> / <?php echo $goal['target_value']; ?> <?php echo $goal['unit']; ?>
                            <?php if ($goal['deadline']): ?>
                            • Due: <?php echo date('M j', strtotime($goal['deadline'])); ?>
                            <?php endif; ?>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Featured Resources -->
        <?php if (!empty($featured_resources)): ?>
        <section class="resources-section">
            <h2 class="section-title">
                <i class="fas fa-star"></i>
                Featured Resources
            </h2>
            
            <div class="resources-grid">
                <?php foreach ($featured_resources as $resource): ?>
                <div class="resource-card" onclick="openResource(<?php echo $resource['id']; ?>)">
                    <div class="resource-icon">
                        <i class="fas <?php 
                            switch($resource['resource_type']) {
                                case 'prayer_guide': echo 'fa-hands-praying'; break;
                                case 'bible_study': echo 'fa-book-bible'; break;
                                case 'evangelism_tool': echo 'fa-bullhorn'; break;
                                default: echo 'fa-book-open';
                            }
                        ?>"></i>
                    </div>
                    
                    <h3 class="resource-title"><?php echo htmlspecialchars($resource['title']); ?></h3>
                    <p class="resource-desc"><?php echo htmlspecialchars(substr($resource['description'], 0, 80)); ?>...</p>
                    <div class="resource-meta">
                        <i class="fas fa-clock me-1"></i>
                        <?php echo $resource['duration_minutes']; ?> min
                        •
                        <i class="fas fa-user me-1"></i>
                        <?php echo $resource['author'] ?: 'Various'; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Active Programs -->
        <?php if (!empty($active_programs)): ?>
        <section class="programs-section">
            <h2 class="section-title">
                <i class="fas fa-running"></i>
                Active Programs
            </h2>
            
            <div class="programs-list">
                <?php foreach ($active_programs as $program): 
                    $program_progress = $program['total_sessions'] > 0 ? min(100, ($program['completed_sessions'] / $program['total_sessions']) * 100) : 0;
                ?>
                <div class="program-item">
                    <div class="program-icon" style="background: <?php 
                        switch($program['program_type']) {
                            case 'prayer': echo 'var(--gradient-prayer)'; break;
                            case 'bible_study': echo 'var(--gradient-bible)'; break;
                            case 'evangelism': echo 'var(--gradient-evangelism)'; break;
                            default: echo 'var(--gradient-discipleship)';
                        }
                    ?>;">
                        <i class="fas <?php 
                            switch($program['program_type']) {
                                case 'prayer': echo 'fa-hands-praying'; break;
                                case 'bible_study': echo 'fa-book-bible'; break;
                                case 'evangelism': echo 'fa-bullhorn'; break;
                                default: echo 'fa-users';
                            }
                        ?>"></i>
                    </div>
                    
                    <div class="program-content">
                        <div class="program-title"><?php echo htmlspecialchars($program['title']); ?></div>
                        <div class="program-info">
                            <?php echo ucfirst($program['program_type']); ?> • 
                            <?php echo $program['current_streak']; ?> day streak • 
                            <?php echo number_format($program['progress_percentage'], 0); ?>% complete
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $program_progress; ?>%"></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </main>
    
    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <div class="nav-items">
            <a href="index.php" class="nav-item">
                <i class="fas fa-home nav-icon"></i>
                <span class="nav-label">Home</span>
            </a>
            
            <a href="addiction.php" class="nav-item">
                <i class="fas fa-heart-crack nav-icon"></i>
                <span class="nav-label">Recovery</span>
            </a>
            
            <a href="gospel.php" class="nav-item active">
                <i class="fas fa-cross nav-icon"></i>
                <span class="nav-label">Gospel</span>
            </a>
            
            <a href="spiritual-growth.php" class="nav-item">
                <i class="fas fa-pray nav-icon"></i>
                <span class="nav-label">Spiritual</span>
            </a>
            
            <a href="profile.php" class="nav-item">
                <i class="fas fa-user nav-icon"></i>
                <span class="nav-label">Profile</span>
            </a>
        </div>
    </nav>
    
    <!-- Prayer Modal -->
    <div class="modal-overlay" id="prayerModal">
        <div class="modal-content">
            <h2 style="font-size: 24px; font-weight: 600; margin-bottom: 20px; color: var(--dark-color);">
                <i class="fas fa-hands-praying me-2"></i>Log Prayer Session
            </h2>
            
            <form id="prayerForm">
                <div class="form-group">
                    <label class="form-label">Prayer Type</label>
                    <select class="form-select" name="prayer_type" required>
                        <option value="">Select type</option>
                        <option value="personal">Personal Prayer</option>
                        <option value="intercessory">Intercessory Prayer</option>
                        <option value="thanksgiving">Thanksgiving</option>
                        <option value="petition">Petition</option>
                        <option value="worship">Worship</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Topic/Subject</label>
                    <input type="text" class="form-input" name="topic" placeholder="e.g., Family, Healing, Nation">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Duration (minutes)</label>
                    <input type="number" class="form-input" name="duration_minutes" min="1" max="120" value="10">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Scripture Reference</label>
                    <input type="text" class="form-input" name="scripture_reference" placeholder="e.g., Philippians 4:6-7">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Prayer Points</label>
                    <textarea class="form-textarea" name="prayer_points" placeholder="List your prayer points..."></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea class="form-textarea" name="notes" placeholder="Any insights or experiences during prayer..."></textarea>
                </div>
                
                <button type="button" class="btn-primary" onclick="submitPrayer()">
                    <i class="fas fa-save me-2"></i> Save Prayer
                </button>
                <button type="button" class="btn-primary mt-2" onclick="closeModal('prayerModal')" style="background: #6b7280;">
                    Cancel
                </button>
            </form>
        </div>
    </div>
    
    <!-- Bible Study Modal -->
    <div class="modal-overlay" id="bibleModal">
        <div class="modal-content">
            <h2 style="font-size: 24px; font-weight: 600; margin-bottom: 20px; color: var(--dark-color);">
                <i class="fas fa-book-bible me-2"></i>Log Bible Study
            </h2>
            
            <form id="bibleForm">
                <div class="form-group">
                    <label class="form-label">Book</label>
                    <select class="form-select" name="book" required>
                        <option value="">Select book</option>
                        <option value="Genesis">Genesis</option>
                        <option value="Psalms">Psalms</option>
                        <option value="Matthew">Matthew</option>
                        <option value="John">John</option>
                        <option value="Romans">Romans</option>
                        <option value="Ephesians">Ephesians</option>
                        <option value="Philippians">Philippians</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Chapter</label>
                    <input type="number" class="form-input" name="chapter" min="1" max="150" placeholder="e.g., 1">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Verses (From-To)</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="number" class="form-input" name="verse_from" min="1" max="176" placeholder="From" style="flex: 1;">
                        <input type="number" class="form-input" name="verse_to" min="1" max="176" placeholder="To" style="flex: 1;">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Study Method</label>
                    <select class="form-select" name="study_method" required>
                        <option value="devotional">Devotional Reading</option>
                        <option value="inductive">Inductive Study</option>
                        <option value="topical">Topical Study</option>
                        <option value="chapter">Chapter Study</option>
                        <option value="verse_by_verse">Verse-by-Verse</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Duration (minutes)</label>
                    <input type="number" class="form-input" name="duration_minutes" min="1" max="120" value="15">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Key Verses</label>
                    <textarea class="form-textarea" name="key_verses" placeholder="Write down key verses that stood out..."></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Insights & Observations</label>
                    <textarea class="form-textarea" name="observations" placeholder="What did you learn or observe?"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Application</label>
                    <textarea class="form-textarea" name="applications" placeholder="How will you apply this to your life?"></textarea>
                </div>
                
                <button type="button" class="btn-primary" onclick="submitBibleStudy()">
                    <i class="fas fa-save me-2"></i> Save Study
                </button>
                <button type="button" class="btn-primary mt-2" onclick="closeModal('bibleModal')" style="background: #6b7280;">
                    Cancel
                </button>
            </form>
        </div>
    </div>
    
    <!-- Evangelism Modal -->
    <div class="modal-overlay" id="evangelismModal">
        <div class="modal-content">
            <h2 style="font-size: 24px; font-weight: 600; margin-bottom: 20px; color: var(--dark-color);">
                <i class="fas fa-bullhorn me-2"></i>Log Evangelism Activity
            </h2>
            
            <form id="evangelismForm">
                <div class="form-group">
                    <label class="form-label">Activity Type</label>
                    <select class="form-select" name="activity_type" required>
                        <option value="">Select type</option>
                        <option value="personal_witness">Personal Witness</option>
                        <option value="group_outreach">Group Outreach</option>
                        <option value="digital_evangelism">Digital Evangelism</option>
                        <option value="prayer_walk">Prayer Walk</option>
                        <option value="literature_distribution">Literature Distribution</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Title</label>
                    <input type="text" class="form-input" name="title" placeholder="e.g., Street Evangelism, Online Sharing" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Location</label>
                    <input type="text" class="form-input" name="location" placeholder="e.g., City Center, Facebook, Church">
                </div>
                
                <div class="form-group">
                    <label class="form-label">People Reached</label>
                    <input type="number" class="form-input" name="people_reached" min="0" value="0">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Decisions Made</label>
                    <input type="number" class="form-input" name="decisions_made" min="0" value="0">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Duration (minutes)</label>
                    <input type="number" class="form-input" name="duration_minutes" min="1" value="60">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Challenges Faced</label>
                    <textarea class="form-textarea" name="challenges_faced" placeholder="What challenges did you encounter?"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Victories & Testimonies</label>
                    <textarea class="form-textarea" name="victories_shared" placeholder="Share victories, conversations, or testimonies..."></textarea>
                </div>
                
                <button type="button" class="btn-primary" onclick="submitEvangelism()">
                    <i class="fas fa-save me-2"></i> Save Activity
                </button>
                <button type="button" class="btn-primary mt-2" onclick="closeModal('evangelismModal')" style="background: #6b7280;">
                    Cancel
                </button>
            </form>
        </div>
    </div>
    
    <!-- Program Start Modal -->
    <div class="modal-overlay" id="programModal">
        <div class="modal-content">
            <h2 style="font-size: 24px; font-weight: 600; margin-bottom: 20px; color: var(--dark-color);">
                Start Gospel Program
            </h2>
            
            <form id="programForm">
                <input type="hidden" id="program_type" name="program_type">
                
                <div class="form-group">
                    <label class="form-label">Program Title</label>
                    <input type="text" class="form-input" name="title" placeholder="e.g., 30-Day Prayer Challenge" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-textarea" name="description" placeholder="Brief description of your program..."></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Frequency</label>
                    <select class="form-select" name="frequency" required>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="biweekly">Bi-weekly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Duration (days)</label>
                    <input type="number" class="form-input" name="target_duration_days" min="1" max="365" value="30">
                </div>
                
                <div class="form-group">
                    <label class="form-check-label" style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" name="is_public" value="1">
                        Share program publicly
                    </label>
                </div>
                
                <button type="button" class="btn-primary mt-3" onclick="submitProgram()">
                    <i class="fas fa-play me-2"></i> Start Program
                </button>
                <button type="button" class="btn-primary mt-2" onclick="closeModal('programModal')" style="background: #6b7280;">
                    Cancel
                </button>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Modal functions
        function openPrayerModal() {
            document.getElementById('prayerModal').style.display = 'flex';
        }
        
        function openBibleModal() {
            document.getElementById('bibleModal').style.display = 'flex';
        }
        
        function openEvangelismModal() {
            document.getElementById('evangelismModal').style.display = 'flex';
        }
        
        function openProgramModal(type) {
            document.getElementById('program_type').value = type;
            document.getElementById('programModal').style.display = 'flex';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Close modal on overlay click
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.style.display = 'none';
                }
            });
        });
        
        // Navigation functions
        function openAllActivities() {
            // Open all modals in sequence or redirect to detailed page
            window.location.href = 'gospel-activities.php';
        }
        
        function openResources() {
            window.location.href = 'gospel-resources.php';
        }
        
        function startPrayerProgram() {
            openProgramModal('prayer');
        }
        
        function startBibleProgram() {
            openProgramModal('bible_study');
        }
        
        function startEvangelismProgram() {
            openProgramModal('evangelism');
        }
        
        function openResource(resourceId) {
            window.location.href = 'gospel-resource-detail.php?id=' + resourceId;
        }
        
        // Submit functions
        async function submitPrayer() {
            const form = document.getElementById('prayerForm');
            const formData = new FormData(form);
            formData.append('action', 'submit_prayer');
            
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Saving...';
            btn.disabled = true;
            
            try {
                const response = await fetch('gospel-actions.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification('Prayer session saved!', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification('Error: ' + data.message, 'error');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Network error. Please try again.', 'error');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
        
        async function submitBibleStudy() {
            const form = document.getElementById('bibleForm');
            const formData = new FormData(form);
            formData.append('action', 'submit_bible_study');
            
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Saving...';
            btn.disabled = true;
            
            try {
                const response = await fetch('gospel-actions.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification('Bible study saved!', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification('Error: ' + data.message, 'error');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Network error. Please try again.', 'error');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
        
        async function submitEvangelism() {
            const form = document.getElementById('evangelismForm');
            const formData = new FormData(form);
            formData.append('action', 'submit_evangelism');
            
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Saving...';
            btn.disabled = true;
            
            try {
                const response = await fetch('gospel-actions.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification('Evangelism activity saved!', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification('Error: ' + data.message, 'error');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Network error. Please try again.', 'error');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
        
        async function submitProgram() {
            const form = document.getElementById('programForm');
            const formData = new FormData(form);
            formData.append('action', 'start_program');
            
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Starting...';
            btn.disabled = true;
            
            try {
                const response = await fetch('gospel-actions.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification('Program started successfully!', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification('Error: ' + data.message, 'error');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Network error. Please try again.', 'error');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
        
        // Show notification
        function showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 100px;
                right: 20px;
                background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
                color: white;
                padding: 15px 20px;
                border-radius: 12px;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                z-index: 9999;
                animation: slideIn 0.3s ease;
                max-width: 300px;
            `;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
                ${message}
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                notification.style.animation = 'slideIn 0.3s ease reverse forwards';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        // PWA Service Worker
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js')
                .then(registration => {
                    console.log('SW registered:', registration);
                })
                .catch(error => {
                    console.log('SW registration failed:', error);
                });
        }
    </script>
</body>
</html>