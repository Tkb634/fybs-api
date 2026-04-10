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
    $user_name = "User";
}

$user_role = isset($_SESSION['user_role']) ? ucfirst($_SESSION['user_role']) : 'Member';
$first_name = explode(' ', $user_name)[0];

// Get current hour for greeting
$current_hour = date('H');
if ($current_hour < 12) {
    $greeting = "Good Morning";
    $greeting_icon = "☀️";
} elseif ($current_hour < 17) {
    $greeting = "Good Afternoon";
    $greeting_icon = "🌤️";
} else {
    $greeting = "Good Evening";
    $greeting_icon = "🌙";
}

// Get day and date
$day_of_week = date('l');
$date = date('F j, Y');

// Get FYBS stats from database
$total_classes = 0;
$completed_classes = 0;
$time_spent = 0;
$streak_days = 0;

// Fetch total classes
$total_sql = "SELECT COUNT(*) as total FROM fybs_classes";
$total_result = $conn->query($total_sql);
if ($total_result->num_rows > 0) {
    $total_row = $total_result->fetch_assoc();
    $total_classes = $total_row['total'];
}

// Fetch completed classes
$completed_sql = "SELECT COUNT(*) as completed FROM fybs_progress WHERE user_id = ? AND completed = 1";
$stmt = $conn->prepare($completed_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$completed_result = $stmt->get_result();
if ($completed_result->num_rows > 0) {
    $completed_row = $completed_result->fetch_assoc();
    $completed_classes = $completed_row['completed'];
}

// Fetch total time spent
$time_sql = "SELECT SUM(time_spent) as total_time FROM fybs_progress WHERE user_id = ?";
$stmt = $conn->prepare($time_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$time_result = $stmt->get_result();
if ($time_result->num_rows > 0) {
    $time_row = $time_result->fetch_assoc();
    $time_spent = $time_row['total_time'] ? round($time_row['total_time'] / 60, 1) : 0;
}

// Get streak from user stats
$streak_sql = "SELECT streak_days FROM spiritual_user_stats WHERE user_id = ?";
$stmt = $conn->prepare($streak_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$streak_result = $stmt->get_result();
if ($streak_result->num_rows > 0) {
    $streak_row = $streak_result->fetch_assoc();
    $streak_days = $streak_row['streak_days'];
}

// Get daily Bible verse for FYBS
$verses = [
    "Your word is a lamp to my feet and a light to my path. - Psalm 119:105",
    "All Scripture is God-breathed and is useful for teaching, rebuking, correcting and training in righteousness. - 2 Timothy 3:16",
    "Do not merely listen to the word, and so deceive yourselves. Do what it says. - James 1:22",
    "The grass withers and the flowers fall, but the word of our God endures forever. - Isaiah 40:8",
    "I have hidden your word in my heart that I might not sin against you. - Psalm 119:11"
];
$daily_verse = $verses[date('z') % count($verses)];

// Fetch all classes
$classes = [];
$class_sql = "SELECT * FROM fybs_classes ORDER BY order_index";
$class_result = $conn->query($class_sql);
while ($class_row = $class_result->fetch_assoc()) {
    $classes[] = $class_row;
}

// Fetch completed class IDs
$completed_class_ids = [];
$completed_ids_sql = "SELECT class_id FROM fybs_progress WHERE user_id = ? AND completed = 1";
$stmt = $conn->prepare($completed_ids_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$completed_ids_result = $stmt->get_result();
while ($row = $completed_ids_result->fetch_assoc()) {
    $completed_class_ids[] = $row['class_id'];
}

// Handle class completion
if (isset($_POST['complete_class'])) {
    $class_id = $_POST['class_id'];
    $time_spent_minutes = $_POST['time_spent'] ?? 30;
    
    // Check if already completed
    $check_sql = "SELECT * FROM fybs_progress WHERE user_id = ? AND class_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ii", $user_id, $class_id);
    $stmt->execute();
    $check_result = $stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update existing record
        $update_sql = "UPDATE fybs_progress SET completed = 1, time_spent = time_spent + ?, completed_at = NOW() WHERE user_id = ? AND class_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("iii", $time_spent_minutes, $user_id, $class_id);
        $stmt->execute();
    } else {
        // Insert new record
        $insert_sql = "INSERT INTO fybs_progress (user_id, class_id, completed, time_spent, completed_at) VALUES (?, ?, 1, ?, NOW())";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("iii", $user_id, $class_id, $time_spent_minutes);
        $stmt->execute();
    }
    
    header("Location: fybs.php?completed=" . $class_id);
    exit();
}

// Handle certificate generation
if (isset($_GET['certificate'])) {
    $class_id = $_GET['certificate'];
    
    // Get class details
    $class_sql = "SELECT title, level FROM fybs_classes WHERE id = ?";
    $stmt = $conn->prepare($class_sql);
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $class = $result->fetch_assoc();
    
    // Create certificate HTML
    $certificate = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .certificate-container { max-width: 100%; width: 100%; }
            .certificate { 
                background: white; 
                padding: 40px 24px; 
                border-radius: 24px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.2);
                position: relative;
                text-align: center;
            }
            .seal {
                width: 80px;
                height: 80px;
                background: radial-gradient(circle, #fef3c7 40%, #f59e0b 100%);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 20px;
                border: 3px solid #d97706;
            }
            .seal i { font-size: 40px; color: #92400e; }
            h1 { 
                font-size: 28px; 
                color: #1d4ed8; 
                margin-bottom: 8px;
            }
            .subtitle { 
                font-size: 14px; 
                color: #6b7280; 
                margin-bottom: 24px;
                letter-spacing: 2px;
            }
            .awarded-to { font-size: 14px; color: #6b7280; margin: 20px 0 8px; }
            .name { 
                font-size: 28px; 
                font-weight: 700;
                color: #1f2937; 
                margin: 16px 0 24px;
                padding: 0 16px;
                word-break: break-word;
            }
            .course { font-size: 20px; color: #1d4ed8; font-weight: 600; margin: 16px 0 8px; }
            .level { font-size: 16px; color: #10b981; margin-bottom: 24px; }
            .date { font-size: 14px; color: #6b7280; margin: 24px 0; padding-top: 16px; border-top: 1px solid #e5e7eb; }
            .signature-line { border-top: 2px solid #1f2937; width: 200px; margin: 20px auto 8px; }
            .director { font-weight: 600; font-size: 14px; }
            .footer-note { font-size: 10px; color: #9ca3af; margin-top: 20px; }
            @media print {
                body { background: white; padding: 0; }
                .certificate { box-shadow: none; padding: 20px; }
                .certificate-container { padding: 0; }
            }
        </style>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </head>
    <body>
        <div class="certificate-container">
            <div class="certificate">
                <div class="seal"><i class="fas fa-award"></i></div>
                <h1>Certificate of Completion</h1>
                <div class="subtitle">Foundational Youth Bible School</div>
                <div class="awarded-to">This certificate is presented to</div>
                <div class="name">' . htmlspecialchars($user_name) . '</div>
                <div class="course">' . htmlspecialchars($class['title']) . '</div>
                <div class="level">' . ucfirst($class['level']) . ' Level</div>
                <div class="date"><i class="fas fa-calendar-check me-2"></i>' . date('F j, Y') . '</div>
                <div class="signature">
                    <div class="signature-line"></div>
                    <div class="director">Rev. Dr. Samuel Johnson</div>
                    <div class="small text-muted">FYBS Academic Director</div>
                </div>
                <div class="footer-note">Certificate ID: FYBS-' . str_pad($user_id, 4, '0', STR_PAD_LEFT) . '-' . str_pad($class_id, 3, '0', STR_PAD_LEFT) . '</div>
            </div>
        </div>
        <script>window.onload = function() { setTimeout(function() { window.print(); }, 500); };</script>
    </body>
    </html>';
    
    echo $certificate;
    exit();
}

// Calculate category counts
$beginner_count = array_reduce($classes, function($carry, $class) {
    return $carry + ($class['level'] == 'beginner' ? 1 : 0);
}, 0);

$intermediate_count = array_reduce($classes, function($carry, $class) {
    return $carry + ($class['level'] == 'intermediate' ? 1 : 0);
}, 0);

$advanced_count = array_reduce($classes, function($carry, $class) {
    return $carry + ($class['level'] == 'advanced' ? 1 : 0);
}, 0);

$progress_percent = $total_classes > 0 ? round(($completed_classes / $total_classes) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
    <title>FYBS - Bible School - FYBS Youth App</title>
    
    <meta name="theme-color" content="#1d4ed8">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="manifest.json">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        :root {
            --primary: #1d4ed8;
            --primary-dark: #1e40af;
            --primary-light: #3b82f6;
            --secondary: #10b981;
            --accent: #f59e0b;
            --dark: #0f172a;
            --gray-dark: #334155;
            --gray: #64748b;
            --gray-light: #f1f5f9;
            --white: #ffffff;
            --danger: #ef4444;
            --success: #22c55e;
            --radius: 20px;
            --radius-sm: 14px;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #f8fafc;
            padding-bottom: 70px;
        }

        /* Mobile Container */
        .app-container {
            max-width: 500px;
            margin: 0 auto;
            padding: 0 16px;
        }

        /* Header */
        .app-header {
            background: var(--white);
            padding: 12px 0;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.96);
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .header-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 500px;
            margin: 0 auto;
            padding: 0 16px;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }

        .logo-text {
            font-weight: 700;
            font-size: 18px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .user-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--gray-light);
            padding: 6px 12px 6px 8px;
            border-radius: 40px;
            cursor: pointer;
        }

        .user-avatar-sm {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--secondary), #059669);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }

        .user-name-sm {
            font-size: 14px;
            font-weight: 600;
            color: var(--dark);
        }

        /* Welcome Card */
        .welcome-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 20px;
            margin: 20px 0;
            box-shadow: var(--shadow-sm);
        }

        .greeting-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }

        .greeting-icon {
            font-size: 28px;
        }

        .greeting-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
        }

        .greeting-sub {
            font-size: 13px;
            color: var(--gray);
            margin-bottom: 12px;
        }

        .daily-verse {
            background: #eff6ff;
            border-left: 4px solid var(--primary);
            padding: 12px;
            border-radius: 12px;
            font-size: 13px;
            color: #1e40af;
            margin-top: 12px;
        }

        /* Progress Card */
        .progress-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-sm);
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 12px;
        }

        .progress-title {
            font-weight: 600;
            color: var(--dark);
        }

        .progress-percent {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
        }

        .progress-bar-container {
            height: 8px;
            background: var(--gray-light);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 12px;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            border-radius: 10px;
            width: <?php echo $progress_percent; ?>%;
            transition: width 0.3s;
        }

        .progress-stats {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: var(--gray);
        }

        /* Study Timer */
        .study-timer {
            background: var(--gray-light);
            border-radius: 16px;
            padding: 16px;
            margin-top: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .timer-display {
            font-size: 32px;
            font-weight: 700;
            font-family: monospace;
            color: var(--primary);
        }

        .timer-buttons {
            display: flex;
            gap: 8px;
        }

        .timer-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 40px;
            font-weight: 500;
            font-size: 13px;
            cursor: pointer;
            transition: transform 0.1s;
        }

        .timer-btn:active { transform: scale(0.96); }
        .timer-btn.start { background: var(--secondary); color: white; }
        .timer-btn.pause { background: var(--accent); color: white; }
        .timer-btn.reset { background: #e5e7eb; color: var(--gray); }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--radius-sm);
            padding: 16px 12px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            cursor: pointer;
            transition: transform 0.1s;
        }

        .stat-card:active { transform: scale(0.97); }
        .stat-value { font-size: 28px; font-weight: 800; color: var(--primary); }
        .stat-label { font-size: 12px; color: var(--gray); margin-top: 4px; }

        /* Category Tabs */
        .category-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            overflow-x: auto;
            padding-bottom: 4px;
            scrollbar-width: none;
        }

        .category-tabs::-webkit-scrollbar { display: none; }
        .category-tab {
            flex: 1;
            text-align: center;
            padding: 12px 8px;
            background: var(--white);
            border-radius: 40px;
            font-weight: 600;
            font-size: 13px;
            color: var(--gray);
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
            box-shadow: var(--shadow-sm);
        }
        .category-tab.active { background: var(--primary); color: white; }
        .category-tab i { margin-right: 6px; font-size: 14px; }

        /* Class Cards */
        .class-card {
            background: var(--white);
            border-radius: var(--radius);
            margin-bottom: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: transform 0.1s;
        }
        .class-card:active { transform: scale(0.99); }
        .class-card.completed { border-left: 4px solid var(--secondary); }
        
        .class-header {
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .class-title { font-weight: 700; font-size: 16px; color: var(--dark); margin-bottom: 4px; }
        .class-desc { font-size: 13px; color: var(--gray); line-height: 1.4; }
        .level-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .level-badge.beginner { background: #dbeafe; color: #1d4ed8; }
        .level-badge.intermediate { background: #fef3c7; color: #d97706; }
        .level-badge.advanced { background: #fee2e2; color: #dc2626; }
        
        .class-footer {
            padding: 12px 16px;
            border-top: 1px solid #eef2ff;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .class-meta { display: flex; gap: 12px; font-size: 12px; color: var(--gray); }
        .btn-start, .btn-certificate {
            padding: 8px 20px;
            border: none;
            border-radius: 40px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: transform 0.1s;
        }
        .btn-start { background: linear-gradient(135deg, var(--primary), var(--primary-light)); color: white; }
        .btn-certificate { background: var(--accent); color: white; }
        .btn-start:active, .btn-certificate:active { transform: scale(0.96); }
        .completed-badge { display: flex; align-items: center; gap: 6px; color: var(--secondary); font-size: 12px; }

        /* Modal */
        .modal-content {
            border-radius: 24px;
            border: none;
            max-height: 85vh;
        }
        .modal-body { padding: 20px; overflow-y: auto; }
        
        /* Video/Audio Players */
        .video-wrapper { margin-bottom: 20px; }
        video, audio { width: 100%; border-radius: 16px; background: #000; }
        .media-controls { margin-top: 12px; display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
        .media-btn {
            padding: 8px 16px;
            background: var(--gray-light);
            border: none;
            border-radius: 40px;
            font-size: 13px;
            cursor: pointer;
        }
        .quiz-option {
            background: var(--gray-light);
            padding: 14px;
            border-radius: 12px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .quiz-option.selected { background: var(--primary); color: white; }
        .notes-textarea {
            width: 100%;
            min-height: 100px;
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            font-family: inherit;
            resize: vertical;
        }
        
        /* Bottom Navigation */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255,255,255,0.96);
            backdrop-filter: blur(12px);
            border-top: 1px solid rgba(0,0,0,0.05);
            padding: 8px 20px 20px;
            max-width: 500px;
            margin: 0 auto;
            z-index: 100;
        }
        .nav-links {
            display: flex;
            justify-content: space-around;
            align-items: center;
        }
        .nav-link-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            text-decoration: none;
            color: var(--gray);
            font-size: 11px;
            padding: 6px 12px;
            border-radius: 40px;
        }
        .nav-link-item i { font-size: 20px; }
        .nav-link-item.active { color: var(--primary); font-weight: 600; }

        /* Empty State */
        .empty-state { text-align: center; padding: 48px 20px; color: var(--gray); }
        
        @media (prefers-color-scheme: dark) {
            body { background: #0f172a; }
            .app-header, .welcome-card, .progress-card, .stat-card, .class-card, .category-tab, .modal-content {
                background: #1e293b;
            }
            .greeting-title, .class-title, .progress-title { color: #f1f5f9; }
            .greeting-sub, .class-desc, .stat-label, .progress-stats { color: #94a3b8; }
            .daily-verse { background: #1e3a8a; color: #dbeafe; }
            .study-timer { background: #334155; }
            .timer-btn.reset { background: #475569; color: #cbd5e1; }
            .media-btn { background: #334155; color: #e2e8f0; }
            .quiz-option { background: #334155; color: #e2e8f0; }
        }
    </style>
</head>
<body>
<div class="app-container">
    <!-- Header -->
    <div class="app-header">
        <div class="header-inner">
            <div class="logo-area">
                <div class="logo-icon"><i class="fas fa-book-bible"></i></div>
                <span class="logo-text">FYBS</span>
            </div>
            <div class="user-badge" onclick="window.location.href='profile.php'">
                <div class="user-avatar-sm"><?php echo strtoupper(substr($first_name, 0, 1)); ?></div>
                <span class="user-name-sm"><?php echo htmlspecialchars($first_name); ?></span>
                <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div id="mainContent">
        <!-- Welcome Card -->
        <div class="welcome-card">
            <div class="greeting-row">
                <span class="greeting-icon"><?php echo $greeting_icon; ?></span>
                <h2 class="greeting-title"><?php echo $greeting; ?>, <?php echo htmlspecialchars($first_name); ?>!</h2>
            </div>
            <p class="greeting-sub">Deepen your faith through structured Bible learning</p>
            <div class="daily-verse">
                <i class="fas fa-quote-left me-2"></i> <?php echo $daily_verse; ?>
            </div>
        </div>

        <!-- Progress Card -->
        <div class="progress-card">
            <div class="progress-header">
                <span class="progress-title">Your Journey</span>
                <span class="progress-percent"><?php echo $progress_percent; ?>%</span>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar-fill"></div>
            </div>
            <div class="progress-stats">
                <span><i class="fas fa-check-circle text-success me-1"></i> <?php echo $completed_classes; ?>/<?php echo $total_classes; ?> completed</span>
                <span><i class="fas fa-clock me-1"></i> <?php echo $time_spent; ?> hrs</span>
            </div>
            
            <!-- Study Timer -->
            <div class="study-timer">
                <div class="timer-display" id="timerDisplay">25:00</div>
                <div class="timer-buttons">
                    <button class="timer-btn start" onclick="startTimer()"><i class="fas fa-play"></i></button>
                    <button class="timer-btn pause" onclick="pauseTimer()"><i class="fas fa-pause"></i></button>
                    <button class="timer-btn reset" onclick="resetTimer()"><i class="fas fa-redo"></i></button>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card" onclick="window.location.href='leaderboard.php'">
                <div class="stat-value"><?php echo $streak_days; ?></div>
                <div class="stat-label">Day Streak</div>
            </div>
            <div class="stat-card" onclick="window.location.href='leaderboard.php'">
                <div class="stat-value"><?php echo $completed_classes; ?></div>
                <div class="stat-label">Certificates</div>
            </div>
            <div class="stat-card" onclick="window.location.href='bible_quiz.php'">
                <div class="stat-value"><i class="fas fa-star"></i></div>
                <div class="stat-label">Test Skills</div>
            </div>
        </div>

        <!-- Category Tabs -->
        <div class="category-tabs" id="categoryTabs">
            <div class="category-tab active" data-cat="beginner"><i class="fas fa-seedling"></i> Beginner</div>
            <div class="category-tab" data-cat="intermediate"><i class="fas fa-leaf"></i> Intermediate</div>
            <div class="category-tab" data-cat="advanced"><i class="fas fa-tree"></i> Advanced</div>
            <div class="category-tab" data-cat="completed"><i class="fas fa-trophy"></i> Completed</div>
        </div>

        <!-- Classes Container -->
        <div id="classesContainer">
            <?php foreach(['beginner', 'intermediate', 'advanced'] as $level): ?>
                <div class="class-category" id="category-<?php echo $level; ?>" style="display: <?php echo $level === 'beginner' ? 'block' : 'none'; ?>">
                    <?php 
                    $has_classes = false;
                    foreach($classes as $class): 
                        if($class['level'] != $level) continue;
                        $has_classes = true;
                        $is_completed = in_array($class['id'], $completed_class_ids);
                    ?>
                    <div class="class-card <?php echo $is_completed ? 'completed' : ''; ?>" data-class-id="<?php echo $class['id']; ?>">
                        <div class="class-header">
                            <div style="flex: 1;">
                                <div class="class-title"><?php echo htmlspecialchars($class['title']); ?></div>
                                <div class="class-desc"><?php echo htmlspecialchars($class['description']); ?></div>
                            </div>
                            <span class="level-badge <?php echo $class['level']; ?>"><?php echo ucfirst($class['level']); ?></span>
                        </div>
                        <div class="class-footer">
                            <div class="class-meta">
                                <span><i class="fas fa-clock"></i> <?php echo $class['duration_minutes']; ?> min</span>
                                <span><i class="fas fa-<?php echo $class['content_type'] === 'video' ? 'play-circle' : ($class['content_type'] === 'audio' ? 'headphones' : 'book'); ?>"></i> <?php echo ucfirst($class['content_type']); ?></span>
                            </div>
                            <?php if($is_completed): ?>
                                <div class="completed-badge">
                                    <i class="fas fa-check-circle"></i> Completed
                                </div>
                            <?php else: ?>
                                <button class="btn-start" onclick="startClass(<?php echo $class['id']; ?>, '<?php echo addslashes($class['title']); ?>')">
                                    <i class="fas fa-play"></i> Start
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if(!$has_classes): ?>
                        <div class="empty-state"><i class="fas fa-folder-open fa-2x mb-3"></i><p>No <?php echo $level; ?> classes yet</p></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <!-- Completed Category -->
            <div class="class-category" id="category-completed" style="display: none;">
                <?php 
                $completed_found = false;
                foreach($classes as $class): 
                    if(!in_array($class['id'], $completed_class_ids)) continue;
                    $completed_found = true;
                ?>
                <div class="class-card completed">
                    <div class="class-header">
                        <div style="flex: 1;">
                            <div class="class-title"><?php echo htmlspecialchars($class['title']); ?></div>
                            <div class="class-desc"><?php echo htmlspecialchars($class['description']); ?></div>
                        </div>
                        <span class="level-badge <?php echo $class['level']; ?>"><?php echo ucfirst($class['level']); ?></span>
                    </div>
                    <div class="class-footer">
                        <div class="completed-badge">
                            <i class="fas fa-check-circle"></i> Completed
                        </div>
                        <button class="btn-certificate" onclick="window.open('fybs.php?certificate=<?php echo $class['id']; ?>', '_blank')">
                            <i class="fas fa-award"></i> Certificate
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if(!$completed_found): ?>
                    <div class="empty-state"><i class="fas fa-trophy fa-2x mb-3"></i><p>Complete classes to earn certificates!</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <div class="bottom-nav">
        <div class="nav-links">
            <a href="index.php" class="nav-link-item"><i class="fas fa-home"></i><span>Home</span></a>
            <a href="fybs.php" class="nav-link-item active"><i class="fas fa-book-bible"></i><span>FYBS</span></a>
            <a href="bible_quiz.php" class="nav-link-item"><i class="fas fa-question-circle"></i><span>Quiz</span></a>
            <a href="leaderboard.php" class="nav-link-item"><i class="fas fa-trophy"></i><span>Rank</span></a>
            <a href="profile.php" class="nav-link-item"><i class="fas fa-user"></i><span>Profile</span></a>
        </div>
    </div>
</div>

<!-- Class Modal -->
<div class="modal fade" id="classModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="modalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalBody"></div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary" id="completeClassBtn" onclick="markComplete()">Mark Complete</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Timer Variables
    let timerSeconds = 25 * 60;
    let timerInterval = null;
    let isTimerRunning = false;
    let currentClassId = null;
    let currentClassName = null;
    let currentQuizAnswers = {};

    // Timer Functions
    function updateTimerDisplay() {
        const mins = Math.floor(timerSeconds / 60);
        const secs = timerSeconds % 60;
        document.getElementById('timerDisplay').textContent = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }

    function startTimer() {
        if (isTimerRunning) return;
        isTimerRunning = true;
        timerInterval = setInterval(() => {
            if (timerSeconds > 0) {
                timerSeconds--;
                updateTimerDisplay();
                if (timerSeconds === 0) {
                    pauseTimer();
                    showToast("Study session complete! Great job! 🎉", "success");
                }
            }
        }, 1000);
    }

    function pauseTimer() {
        clearInterval(timerInterval);
        isTimerRunning = false;
    }

    function resetTimer() {
        pauseTimer();
        timerSeconds = 25 * 60;
        updateTimerDisplay();
    }

    // Category Tabs
    document.querySelectorAll('.category-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.category-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            const cat = this.dataset.cat;
            document.querySelectorAll('.class-category').forEach(c => c.style.display = 'none');
            document.getElementById(`category-${cat}`).style.display = 'block';
        });
    });

    // Class Content
    function startClass(classId, className) {
        currentClassId = classId;
        currentClassName = className;
        document.getElementById('modalTitle').innerHTML = `<i class="fas fa-book-open me-2"></i>${className}`;
        
        const contents = getClassContent(classId);
        document.getElementById('modalBody').innerHTML = contents;
        
        // Check if already completed
        const isCompleted = <?php echo json_encode($completed_class_ids); ?>.includes(classId);
        const completeBtn = document.getElementById('completeClassBtn');
        if (isCompleted) {
            completeBtn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Already Completed';
            completeBtn.disabled = true;
            completeBtn.classList.remove('btn-primary');
            completeBtn.classList.add('btn-success');
        } else {
            completeBtn.innerHTML = '<i class="fas fa-check me-2"></i>Mark Complete';
            completeBtn.disabled = false;
            completeBtn.classList.remove('btn-success');
            completeBtn.classList.add('btn-primary');
        }
        
        // Load saved notes
        const savedNotes = localStorage.getItem(`classNotes_${classId}`) || '';
        const notesArea = document.getElementById('studyNotes');
        if (notesArea) notesArea.value = savedNotes;
        
        // Initialize media listeners
        setTimeout(initMedia, 100);
        
        new bootstrap.Modal(document.getElementById('classModal')).show();
    }

    function initMedia() {
        document.querySelectorAll('video').forEach(v => {
            v.addEventListener('timeupdate', function() {
                if (this.duration && this.currentTime / this.duration > 0.9) {
                    const checkbox = document.getElementById(`watched_${this.id}`);
                    if (checkbox && !checkbox.checked) checkbox.checked = true;
                }
            });
        });
        document.querySelectorAll('audio').forEach(a => {
            a.addEventListener('timeupdate', function() {
                if (this.duration && this.currentTime / this.duration > 0.9) {
                    const checkbox = document.getElementById(`listened_${this.id}`);
                    if (checkbox && !checkbox.checked) checkbox.checked = true;
                }
            });
        });
    }

    function playPauseVideo(videoId) {
        const video = document.getElementById(videoId);
        if (video.paused) video.play();
        else video.pause();
    }

    function playPauseAudio(audioId) {
        const audio = document.getElementById(audioId);
        if (audio.paused) audio.play();
        else audio.pause();
    }

    function saveNotes() {
        const notes = document.getElementById('studyNotes').value;
        localStorage.setItem(`classNotes_${currentClassId}`, notes);
        showToast("Notes saved!", "success");
    }

    function selectQuizOption(qIndex, optIndex) {
        currentQuizAnswers[qIndex] = optIndex;
        document.querySelectorAll(`[data-q="${qIndex}"]`).forEach(el => el.classList.remove('selected'));
        document.querySelector(`[data-q="${qIndex}"][data-opt="${optIndex}"]`).classList.add('selected');
    }

    function submitQuiz() {
        const quizData = window.currentQuizData;
        if (!quizData) return;
        
        let correct = 0;
        quizData.questions.forEach((q, i) => {
            if (currentQuizAnswers[i] === q.answer) correct++;
        });
        const percent = Math.round((correct / quizData.questions.length) * 100);
        let msg = percent >= 80 ? "Excellent! 🎉" : (percent >= 60 ? "Good job! 👍" : "Keep studying! 📚");
        showToast(`${msg} ${correct}/${quizData.questions.length} correct (${percent}%)`, percent >= 60 ? "success" : "warning");
    }

    function markComplete() {
        if (!currentClassId) return;
        if (confirm(`Mark "${currentClassName}" as completed?`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'fybs.php';
            form.innerHTML = `
                <input type="hidden" name="class_id" value="${currentClassId}">
                <input type="hidden" name="time_spent" value="30">
                <input type="hidden" name="complete_class" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function getClassContent(classId) {
        const contents = {
            1: `
                <div class="video-wrapper">
                    <video id="video1" controls class="w-100 rounded-3" poster="https://images.unsplash.com/photo-1457369804613-52c61a468e7d?w=400">
                        <source src="https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4" type="video/mp4">
                    </video>
                    <div class="media-controls mt-2">
                        <button class="media-btn" onclick="playPauseVideo('video1')"><i class="fas fa-play"></i> Play/Pause</button>
                        <label class="ms-auto"><input type="checkbox" id="watched_video1"> I watched this</label>
                    </div>
                </div>
                <h6 class="mt-3"><i class="fas fa-star text-warning"></i> Key Points</h6>
                <ul><li>SOAP Method: Scripture, Observation, Application, Prayer</li><li>Start with 15 minutes daily</li><li>Use a study journal</li></ul>
                <div class="mt-3"><label class="fw-bold">Study Notes</label><textarea id="studyNotes" class="notes-textarea mt-1" placeholder="Write your notes..."></textarea><button class="btn btn-sm btn-primary mt-2" onclick="saveNotes()"><i class="fas fa-save"></i> Save Notes</button></div>
            `,
            2: `
                <div class="video-wrapper">
                    <audio id="audio2" controls class="w-100">
                        <source src="https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3" type="audio/mp3">
                    </audio>
                    <div class="media-controls mt-2">
                        <button class="media-btn" onclick="playPauseAudio('audio2')"><i class="fas fa-play"></i> Play/Pause</button>
                        <label class="ms-auto"><input type="checkbox" id="listened_audio2"> I listened</label>
                    </div>
                </div>
                <h6><i class="fas fa-timeline"></i> Salvation Timeline</h6>
                <ul><li>Creation → Fall → Promise → Redemption → Restoration</li><li>Key verse: John 3:16</li></ul>
                <div class="mt-3"><label class="fw-bold">Study Notes</label><textarea id="studyNotes" class="notes-textarea mt-1" placeholder="Write your notes..."></textarea><button class="btn btn-sm btn-primary mt-2" onclick="saveNotes()"><i class="fas fa-save"></i> Save Notes</button></div>
            `,
            3: `
                <div class="video-wrapper">
                    <video id="video3" controls class="w-100 rounded-3">
                        <source src="https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4" type="video/mp4">
                    </video>
                    <div class="media-controls mt-2">
                        <button class="media-btn" onclick="playPauseVideo('video3')"><i class="fas fa-play"></i> Play/Pause</button>
                        <label><input type="checkbox" id="watched_video3"> I watched</label>
                    </div>
                </div>
                <h6>Jesus' Identity</h6>
                <ul><li>Fully God, Fully Man</li><li>John 1:1-14, Philippians 2:5-11</li></ul>
                <div class="mt-3"><textarea id="studyNotes" class="notes-textarea" placeholder="Your notes..."></textarea><button class="btn btn-sm btn-primary mt-2" onclick="saveNotes()">Save Notes</button></div>
            `
        };
        
        // Default content for other classes
        if (!contents[classId]) {
            return `
                <div class="text-center py-4"><i class="fas fa-book-open fa-3x text-primary mb-3"></i><h5>${currentClassName}</h5><p>Study the material and take notes below.</p></div>
                <div class="mt-3"><textarea id="studyNotes" class="notes-textarea" placeholder="Write your study notes here..."></textarea><button class="btn btn-primary mt-2 w-100" onclick="saveNotes()"><i class="fas fa-save"></i> Save Notes</button></div>
                <div class="mt-4"><h6><i class="fas fa-question-circle"></i> Quick Quiz</h6><div class="quiz-option" onclick="showToast('Correct! The Bible is God\'s Word.', 'success')">What is the central message of the Bible?</div><div class="quiz-option" onclick="showToast('Try again!', 'warning')">Who is the main character?</div></div>
            `;
        }
        return contents[classId];
    }

    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = 'position-fixed top-0 start-50 translate-middle-x p-3';
        toast.style.zIndex = '9999';
        toast.style.maxWidth = '90%';
        toast.innerHTML = `<div class="toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'primary'} border-0 show" role="alert"><div class="d-flex"><div class="toast-body">${message}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>`;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }
    
    // Check for completion message
    <?php if(isset($_GET['completed'])): ?>
        showToast("Class completed! 🎉 You earned a certificate!", "success");
    <?php endif; ?>
</script>
</body>
</html>