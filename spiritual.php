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

// Get current hour for greeting and devotional type
$current_hour = date('H');
if ($current_hour < 12) {
    $greeting = "Good Morning";
    $greeting_icon = "☀️";
    $devotional_type = "Morning Devotional";
    $devotional_icon = "🌅";
    $db_devotional_type = "morning";
} elseif ($current_hour < 17) {
    $greeting = "Good Afternoon";
    $greeting_icon = "🌤️";
    $devotional_type = "Afternoon Reflection";
    $devotional_icon = "☀️";
    $db_devotional_type = "afternoon";
} else {
    $greeting = "Good Evening";
    $greeting_icon = "🌙";
    $devotional_type = "Evening Devotional";
    $devotional_icon = "🌌";
    $db_devotional_type = "evening";
}

// Get day and date
$day_of_week = date('l');
$date = date('F j, Y');
$today = date('Y-m-d');

// Get user's spiritual stats
$stats_query = "SELECT * FROM spiritual_user_stats WHERE user_id = ?";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats_result = $stmt->get_result();

if ($stats_result->num_rows > 0) {
    $stats = $stats_result->fetch_assoc();
    $streak_days = $stats['streak_days'];
    $devotionals_completed = $stats['total_devotionals'];
    $scriptures_read = $stats['scriptures_read'];
    $sermons_watched = $stats['total_sermons_watched'];
} else {
    $streak_days = 0;
    $devotionals_completed = 0;
    $scriptures_read = 0;
    $sermons_watched = 0;
}

// Calculate prayer time
$prayer_minutes = $stats['total_prayer_time'] ?? 0;
if ($prayer_minutes >= 60) {
    $hours = floor($prayer_minutes / 60);
    $minutes = $prayer_minutes % 60;
    $prayer_time = $hours . "h " . $minutes . "m";
} else {
    $prayer_time = $prayer_minutes . "min";
}

// Get today's devotional
$devotional_query = "SELECT * FROM spiritual_devotionals 
                    WHERE devotional_type = ? 
                    AND scheduled_date = ? 
                    AND is_published = 1 
                    LIMIT 1";
$stmt = $conn->prepare($devotional_query);
$stmt->bind_param("ss", $db_devotional_type, $today);
$stmt->execute();
$devotional_result = $stmt->get_result();

if ($devotional_result->num_rows > 0) {
    $daily_devotional = $devotional_result->fetch_assoc();
} else {
    $fallback_query = "SELECT * FROM spiritual_devotionals 
                      WHERE scheduled_date = ? 
                      AND is_published = 1 
                      LIMIT 1";
    $stmt = $conn->prepare($fallback_query);
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $fallback_result = $stmt->get_result();
    
    if ($fallback_result->num_rows > 0) {
        $daily_devotional = $fallback_result->fetch_assoc();
    } else {
        $recent_query = "SELECT * FROM spiritual_devotionals 
                        WHERE is_published = 1 
                        ORDER BY scheduled_date DESC 
                        LIMIT 1";
        $recent_result = $conn->query($recent_query);
        if ($recent_result->num_rows > 0) {
            $daily_devotional = $recent_result->fetch_assoc();
        }
    }
}

// Get featured sermons
$sermons_query = "SELECT s.*, p.name as preacher_name 
                 FROM spiritual_sermons s
                 LEFT JOIN spiritual_preachers p ON s.preacher_id = p.id
                 WHERE s.is_featured = 1 
                 ORDER BY s.published_date DESC 
                 LIMIT 4";
$sermons_result = $conn->query($sermons_query);
$featured_sermons = [];
while ($row = $sermons_result->fetch_assoc()) {
    $featured_sermons[] = $row;
}

// Get reading plans
$plans_query = "SELECT * FROM spiritual_reading_plans 
               WHERE is_featured = 1 
               ORDER BY participants_count DESC 
               LIMIT 3";
$plans_result = $conn->query($plans_query);
$reading_plans = [];
while ($row = $plans_result->fetch_assoc()) {
    $reading_plans[] = $row;
}

// Get recommended preachers
$preachers_query = "SELECT * FROM spiritual_preachers 
                   WHERE is_recommended = 1 
                   AND is_active = 1 
                   ORDER BY followers_count DESC 
                   LIMIT 4";
$preachers_result = $conn->query($preachers_query);
$preachers = [];
while ($row = $preachers_result->fetch_assoc()) {
    $preachers[] = $row;
}

// Get user following
$following_ids = [];
$following_query = "SELECT preacher_id FROM spiritual_following WHERE user_id = ?";
$stmt = $conn->prepare($following_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$following_result = $stmt->get_result();
while ($row = $following_result->fetch_assoc()) {
    $following_ids[] = $row['preacher_id'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
    <title>Spiritual Growth - CYIC Youth App</title>
    
    <meta name="theme-color" content="#7c3aed">
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
            --primary: #7c3aed;
            --primary-dark: #6d28d9;
            --primary-light: #8b5cf6;
            --secondary: #10b981;
            --accent: #f59e0b;
            --dark: #0f172a;
            --gray-dark: #334155;
            --gray: #64748b;
            --gray-light: #f1f5f9;
            --white: #ffffff;
            --radius: 20px;
            --radius-sm: 14px;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #f8fafc;
            padding-bottom: 70px;
        }

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

        .date-info {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 12px;
            color: var(--gray);
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .devotional-badge {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 12px;
            border-radius: 12px;
            font-size: 13px;
            color: #92400e;
            margin-top: 12px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--radius-sm);
            padding: 16px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            transition: transform 0.1s;
            cursor: pointer;
        }

        .stat-card:active {
            transform: scale(0.97);
        }

        .stat-value {
            font-size: 28px;
            font-weight: 800;
            color: var(--primary);
        }

        .stat-label {
            font-size: 12px;
            color: var(--gray);
            margin-top: 4px;
        }

        /* Section Headers */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin: 20px 0 14px;
        }

        .section-title {
            font-weight: 700;
            font-size: 18px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title i {
            color: var(--primary);
        }

        /* Quick Actions */
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }

        .action-card {
            background: var(--white);
            border-radius: var(--radius-sm);
            padding: 16px;
            text-align: center;
            text-decoration: none;
            color: inherit;
            box-shadow: var(--shadow-sm);
            transition: transform 0.1s;
            display: block;
        }

        .action-card:active {
            transform: scale(0.97);
        }

        .action-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            color: white;
            font-size: 20px;
        }

        .action-title {
            font-weight: 600;
            font-size: 14px;
            color: var(--dark);
            margin-bottom: 4px;
        }

        .action-desc {
            font-size: 11px;
            color: var(--gray);
        }

        /* Devotional Card */
        .devotional-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
        }

        .devotional-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
        }

        .devotional-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 8px;
        }

        .devotional-title {
            font-weight: 700;
            font-size: 16px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .devotional-time {
            font-size: 11px;
            background: var(--gray-light);
            padding: 4px 12px;
            border-radius: 20px;
            color: var(--gray);
        }

        .devotional-verse {
            background: #fef3c7;
            border-left: 3px solid #f59e0b;
            padding: 12px;
            border-radius: 12px;
            font-size: 13px;
            color: #92400e;
            margin-bottom: 16px;
        }

        .devotional-content {
            font-size: 14px;
            color: var(--gray-dark);
            line-height: 1.5;
            margin-bottom: 16px;
        }

        .devotional-prayer {
            background: #dbeafe;
            border-left: 3px solid #3b82f6;
            padding: 12px;
            border-radius: 12px;
            font-size: 13px;
            color: #1e40af;
            margin-bottom: 16px;
        }

        .devotional-actions {
            display: flex;
            gap: 12px;
        }

        .btn-read {
            flex: 1;
            padding: 10px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border: none;
            border-radius: 40px;
            color: white;
            font-weight: 600;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            cursor: pointer;
        }

        .btn-share {
            flex: 1;
            padding: 10px;
            background: var(--gray-light);
            border: none;
            border-radius: 40px;
            color: var(--gray-dark);
            font-weight: 600;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            cursor: pointer;
        }

        /* Sermons Grid */
        .sermons-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }

        .sermon-card {
            background: var(--white);
            border-radius: var(--radius-sm);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: transform 0.1s;
            cursor: pointer;
        }

        .sermon-card:active {
            transform: scale(0.98);
        }

        .sermon-thumb {
            height: 100px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
        }

        .sermon-info {
            padding: 12px;
        }

        .sermon-title {
            font-weight: 600;
            font-size: 13px;
            color: var(--dark);
            margin-bottom: 4px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .sermon-preacher {
            font-size: 11px;
            color: var(--gray);
            margin-bottom: 6px;
        }

        .sermon-meta {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: var(--gray);
        }

        /* Reading Plans */
        .plans-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 24px;
        }

        .plan-card {
            background: var(--white);
            border-radius: var(--radius-sm);
            padding: 16px;
            display: flex;
            gap: 14px;
            align-items: center;
            box-shadow: var(--shadow-sm);
            cursor: pointer;
            transition: transform 0.1s;
        }

        .plan-card:active {
            transform: scale(0.98);
        }

        .plan-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            flex-shrink: 0;
        }

        .plan-content {
            flex: 1;
        }

        .plan-title {
            font-weight: 600;
            font-size: 14px;
            color: var(--dark);
            margin-bottom: 2px;
        }

        .plan-duration {
            font-size: 11px;
            color: var(--gray);
            margin-bottom: 4px;
        }

        .plan-participants {
            font-size: 10px;
            color: var(--primary);
        }

        /* Preachers Grid */
        .preachers-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }

        .preacher-card {
            background: var(--white);
            border-radius: var(--radius-sm);
            padding: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: var(--shadow-sm);
        }

        .preacher-avatar {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 18px;
            flex-shrink: 0;
        }

        .preacher-info {
            flex: 1;
        }

        .preacher-name {
            font-weight: 600;
            font-size: 13px;
            color: var(--dark);
            margin-bottom: 2px;
        }

        .preacher-topic {
            font-size: 10px;
            color: var(--gray);
            margin-bottom: 6px;
        }

        .follow-btn {
            padding: 4px 12px;
            background: var(--gray-light);
            border: none;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
            color: var(--primary);
            cursor: pointer;
        }

        .follow-btn.following {
            background: var(--secondary);
            color: white;
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

        .nav-link-item i {
            font-size: 20px;
        }

        .nav-link-item.active {
            color: var(--primary);
            font-weight: 600;
        }

        /* Toast */
        .toast-message {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--dark);
            color: white;
            padding: 10px 20px;
            border-radius: 40px;
            font-size: 13px;
            z-index: 1100;
            max-width: 90%;
            text-align: center;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from { transform: translateX(-50%) translateY(-20px); opacity: 0; }
            to { transform: translateX(-50%) translateY(0); opacity: 1; }
        }

        /* Dark Mode */
        @media (prefers-color-scheme: dark) {
            body {
                background: #0f172a;
            }
            .app-header, .welcome-card, .stat-card, .action-card, 
            .devotional-card, .sermon-card, .plan-card, .preacher-card {
                background: #1e293b;
            }
            .greeting-title, .section-title, .action-title, .devotional-title,
            .sermon-title, .plan-title, .preacher-name, .stat-value {
                color: #f1f5f9;
            }
            .greeting-sub, .action-desc, .sermon-preacher, .sermon-meta,
            .plan-duration, .preacher-topic, .stat-label, .devotional-content {
                color: #94a3b8;
            }
            .devotional-verse {
                background: #92400e;
                color: #fde68a;
            }
            .devotional-prayer {
                background: #1e3a8a;
                color: #dbeafe;
            }
            .devotional-time, .btn-share {
                background: #334155;
                color: #cbd5e1;
            }
            .follow-btn {
                background: #334155;
                color: var(--primary-light);
            }
            .bottom-nav {
                background: rgba(30, 41, 59, 0.96);
                border-top-color: #334155;
            }
            .nav-link-item {
                color: #94a3b8;
            }
            .nav-link-item.active {
                color: var(--primary-light);
            }
        }

        @media (display-mode: standalone) {
            .app-header {
                padding-top: max(12px, env(safe-area-inset-top));
            }
            .bottom-nav {
                padding-bottom: max(20px, env(safe-area-inset-bottom));
            }
        }
    </style>
</head>
<body>
<div class="app-container">
    <!-- Header -->
    <div class="app-header">
        <div class="header-inner">
            <div class="logo-area">
                <div class="logo-icon"><i class="fas fa-cross"></i></div>
                <span class="logo-text">Spiritual</span>
            </div>
            <div class="user-badge" onclick="window.location.href='profile.php'">
                <div class="user-avatar-sm"><?php echo strtoupper(substr($first_name, 0, 1)); ?></div>
                <span class="user-name-sm"><?php echo htmlspecialchars($first_name); ?></span>
                <i class="fas fa-chevron-right" style="font-size: 10px; color: #94a3b8;"></i>
            </div>
        </div>
    </div>

    <!-- Welcome Card -->
    <div class="welcome-card">
        <div class="greeting-row">
            <span class="greeting-icon"><?php echo $greeting_icon; ?></span>
            <h2 class="greeting-title"><?php echo $greeting; ?>, <?php echo htmlspecialchars($first_name); ?>!</h2>
        </div>
        <p class="greeting-sub">Nurture your spiritual life through devotionals and teachings.</p>
        <div class="date-info">
            <span><i class="far fa-calendar-alt"></i> <?php echo $day_of_week; ?>, <?php echo $date; ?></span>
            <span><i class="fas fa-clock"></i> <?php echo date('g:i A'); ?></span>
        </div>
        <div class="devotional-badge">
            <i class="fas <?php echo $current_hour < 12 ? 'fa-sun' : ($current_hour < 17 ? 'fa-cloud-sun' : 'fa-moon'); ?> me-2"></i>
            <?php echo $devotional_type; ?> time
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card" onclick="window.location.href='streak.php'">
            <div class="stat-value"><?php echo $streak_days; ?> 🔥</div>
            <div class="stat-label">Day Streak</div>
        </div>
        <div class="stat-card" onclick="window.location.href='devotionals.php'">
            <div class="stat-value"><?php echo $devotionals_completed; ?></div>
            <div class="stat-label">Devotionals</div>
        </div>
        <div class="stat-card" onclick="window.location.href='prayer.php'">
            <div class="stat-value"><?php echo $prayer_time; ?></div>
            <div class="stat-label">Prayer Time</div>
        </div>
        <div class="stat-card" onclick="window.location.href='sermons.php'">
            <div class="stat-value"><?php echo $sermons_watched; ?></div>
            <div class="stat-label">Sermons</div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="section-header">
        <div class="section-title"><i class="fas fa-bolt"></i> Spiritual Practices</div>
    </div>
    <div class="actions-grid">
        <div class="action-card" onclick="window.location.href='devotional.php'">
            <div class="action-icon"><i class="fas <?php echo $current_hour < 12 ? 'fa-sun' : ($current_hour < 17 ? 'fa-cloud-sun' : 'fa-moon'); ?>"></i></div>
            <div class="action-title">Daily Devotional</div>
            <div class="action-desc"><?php echo $devotional_type; ?></div>
        </div>
        <div class="action-card" onclick="window.location.href='prayer.php'">
            <div class="action-icon"><i class="fas fa-hands-praying"></i></div>
            <div class="action-title">Prayer Time</div>
            <div class="action-desc">Guided prayer</div>
        </div>
        <div class="action-card" onclick="window.location.href='sermons.php'">
            <div class="action-icon"><i class="fas fa-video"></i></div>
            <div class="action-title">Sermons</div>
            <div class="action-desc">Watch teachings</div>
        </div>
        <div class="action-card" onclick="window.location.href='mentoring.php'">
            <div class="action-icon"><i class="fas fa-users"></i></div>
            <div class="action-title">Mentoring</div>
            <div class="action-desc">Connect with mentors</div>
        </div>
    </div>

    <!-- Daily Devotional -->
    <div class="section-header">
        <div class="section-title"><i class="fas <?php echo $devotional_icon; ?>"></i> <?php echo $devotional_type; ?></div>
    </div>
    <div class="devotional-card">
        <?php if (isset($daily_devotional)): ?>
        <div class="devotional-header">
            <div class="devotional-title">
                <i class="fas fa-book-open"></i> Today's Word
            </div>
            <div class="devotional-time">
                <i class="far fa-clock"></i> Today
            </div>
        </div>
        <div class="devotional-verse">
            <strong><?php echo htmlspecialchars($daily_devotional['verse']); ?></strong><br>
            "<?php echo htmlspecialchars($daily_devotional['content']); ?>"
        </div>
        <?php if (!empty($daily_devotional['reflection'])): ?>
        <div class="devotional-content">
            <strong><i class="fas fa-lightbulb"></i> Reflection:</strong><br>
            <?php echo htmlspecialchars($daily_devotional['reflection']); ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($daily_devotional['prayer'])): ?>
        <div class="devotional-prayer">
            <strong><i class="fas fa-pray"></i> Prayer:</strong><br>
            "<?php echo htmlspecialchars($daily_devotional['prayer']); ?>"
        </div>
        <?php endif; ?>
        <div class="devotional-actions">
            <button class="btn-read" onclick="markRead(<?php echo $daily_devotional['id']; ?>, this)">
                <i class="fas fa-check-circle"></i> Mark Read
            </button>
            <button class="btn-share" onclick="shareDevotional()">
                <i class="fas fa-share-alt"></i> Share
            </button>
        </div>
        <?php else: ?>
        <div class="text-center py-3">
            <i class="fas fa-book-open fa-2x mb-2" style="color: #94a3b8;"></i>
            <p class="text-muted">No devotional available</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Featured Sermons -->
    <?php if (!empty($featured_sermons)): ?>
    <div class="section-header">
        <div class="section-title"><i class="fas fa-video"></i> Featured Sermons</div>
        <a href="sermons.php" class="see-all" style="font-size: 12px; color: var(--primary);">See all →</a>
    </div>
    <div class="sermons-grid">
        <?php foreach (array_slice($featured_sermons, 0, 4) as $sermon): ?>
        <div class="sermon-card" onclick="playSermon(<?php echo $sermon['id']; ?>)">
            <div class="sermon-thumb">
                <i class="fas fa-play-circle"></i>
            </div>
            <div class="sermon-info">
                <div class="sermon-title"><?php echo htmlspecialchars($sermon['title']); ?></div>
                <div class="sermon-preacher"><i class="fas fa-user"></i> <?php echo htmlspecialchars($sermon['preacher_name'] ?: 'Preacher'); ?></div>
                <div class="sermon-meta">
                    <span><i class="far fa-clock"></i> <?php echo $sermon['duration_minutes']; ?> min</span>
                    <span><i class="far fa-heart"></i> <?php echo $sermon['likes']; ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Reading Plans -->
    <?php if (!empty($reading_plans)): ?>
    <div class="section-header">
        <div class="section-title"><i class="fas fa-book-open"></i> Bible Reading Plans</div>
        <a href="reading-plans.php" class="see-all" style="font-size: 12px; color: var(--primary);">View all →</a>
    </div>
    <div class="plans-list">
        <?php foreach ($reading_plans as $plan): ?>
        <div class="plan-card" onclick="startPlan(<?php echo $plan['id']; ?>)">
            <div class="plan-icon"><i class="fas fa-bible"></i></div>
            <div class="plan-content">
                <div class="plan-title"><?php echo htmlspecialchars($plan['title']); ?></div>
                <div class="plan-duration"><i class="far fa-calendar"></i> <?php echo $plan['duration_days']; ?> days</div>
                <div class="plan-participants"><i class="fas fa-users"></i> <?php echo $plan['participants_count']; ?> participating</div>
            </div>
            <i class="fas fa-chevron-right" style="color: #94a3b8;"></i>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Recommended Mentors -->
    <?php if (!empty($preachers)): ?>
    <div class="section-header">
        <div class="section-title"><i class="fas fa-user-graduate"></i> Recommended Mentors</div>
    </div>
    <div class="preachers-grid">
        <?php foreach ($preachers as $preacher): 
            $isFollowing = in_array($preacher['id'], $following_ids);
        ?>
        <div class="preacher-card">
            <div class="preacher-avatar"><?php echo strtoupper(substr($preacher['name'], 0, 1)); ?></div>
            <div class="preacher-info">
                <div class="preacher-name"><?php echo htmlspecialchars($preacher['name']); ?></div>
                <div class="preacher-topic"><?php echo htmlspecialchars($preacher['specialization']); ?></div>
                <button class="follow-btn <?php echo $isFollowing ? 'following' : ''; ?>" 
                        onclick="toggleFollow(<?php echo $preacher['id']; ?>, '<?php echo htmlspecialchars($preacher['name']); ?>', this)">
                    <?php echo $isFollowing ? '✓ Following' : '+ Connect'; ?>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Bottom Navigation -->
<div class="bottom-nav">
    <div class="nav-links">
        <a href="index.php" class="nav-link-item"><i class="fas fa-home"></i><span>Home</span></a>
        <a href="fybs.php" class="nav-link-item"><i class="fas fa-book-bible"></i><span>FYBS</span></a>
        <a href="gyc.php" class="nav-link-item"><i class="fas fa-comments"></i><span>GYC</span></a>
        <a href="life.php" class="nav-link-item"><i class="fas fa-bolt"></i><span>LIFE</span></a>
        <a href="spiritual.php" class="nav-link-item active"><i class="fas fa-cross"></i><span>Spiritual</span></a>
    </div>
</div>

<script>
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = 'toast-message';
        toast.style.background = type === 'success' ? '#10b981' : (type === 'error' ? '#ef4444' : '#1f2937');
        toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>${message}`;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }

    async function markRead(devotionalId, btn) {
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing';
        btn.disabled = true;
        
        try {
            const res = await fetch('spiritual-actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=mark_devotional_read&devotional_id=' + devotionalId
            });
            const data = await res.json();
            
            if (data.success) {
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Read';
                btn.style.background = '#10b981';
                showToast('Devotional marked as read!', 'success');
                
                // Update stats
                const stats = document.querySelectorAll('.stat-value');
                if (stats[0]) stats[0].innerHTML = (parseInt(stats[0].innerHTML) + 1) + ' 🔥';
                if (stats[1]) stats[1].innerHTML = parseInt(stats[1].innerHTML) + 1;
            } else {
                btn.innerHTML = original;
                btn.disabled = false;
                showToast(data.message || 'Error', 'error');
            }
        } catch (e) {
            btn.innerHTML = original;
            btn.disabled = false;
            showToast('Network error', 'error');
        }
    }

    function shareDevotional() {
        if (navigator.share) {
            navigator.share({ title: 'Daily Devotional', url: window.location.href });
        } else {
            navigator.clipboard.writeText(window.location.href);
            showToast('Link copied!', 'success');
        }
    }

    async function toggleFollow(preacherId, name, btn) {
        const isFollowing = btn.classList.contains('following');
        const action = isFollowing ? 'unfollow_preacher' : 'follow_preacher';
        
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;
        
        try {
            const res = await fetch('spiritual-actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=${action}&preacher_id=${preacherId}`
            });
            const data = await res.json();
            
            if (data.success) {
                if (isFollowing) {
                    btn.innerHTML = '+ Connect';
                    btn.classList.remove('following');
                    showToast(`Unfollowed ${name}`, 'info');
                } else {
                    btn.innerHTML = '✓ Following';
                    btn.classList.add('following');
                    showToast(`Connected with ${name}!`, 'success');
                }
                btn.disabled = false;
            } else {
                btn.innerHTML = isFollowing ? '✓ Following' : '+ Connect';
                btn.disabled = false;
                showToast(data.message || 'Error', 'error');
            }
        } catch (e) {
            btn.innerHTML = isFollowing ? '✓ Following' : '+ Connect';
            btn.disabled = false;
            showToast('Network error', 'error');
        }
    }

    function playSermon(id) {
        window.location.href = `sermon-player.php?id=${id}`;
    }

    function startPlan(id) {
        window.location.href = `reading-plan.php?id=${id}`;
    }

    // Dark mode detection
    if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
        document.body.classList.add('dark-mode');
    }
</script>
</body>
</html>