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

// Check if user has an active program
$program_query = "SELECT * FROM addiction_breaker_programs WHERE user_id = ? ORDER BY id DESC LIMIT 1";
$stmt = $conn->prepare($program_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$program_result = $stmt->get_result();

$has_program = false;
$current_program = null;
$current_streak = 0;
$progress = 0;
$program_id = 0;

if ($program_result->num_rows > 0) {
    $has_program = true;
    $current_program = $program_result->fetch_assoc();
    $current_streak = $current_program['current_streak'] ?? 0;
    $progress = floatval($current_program['progress_percentage'] ?? 0);
    $program_id = $current_program['id'];
}

// Get today's check-in status
$today = date('Y-m-d');
$checkin_query = "SELECT * FROM addiction_daily_checkins WHERE program_id = ? AND checkin_date = ?";
$stmt = $conn->prepare($checkin_query);
$stmt->bind_param("is", $program_id, $today);
$stmt->execute();
$checkin_result = $stmt->get_result();

$checked_in_today = ($checkin_result->num_rows > 0);
$todays_checkin = $checked_in_today ? $checkin_result->fetch_assoc() : null;

// Get recent milestones
$milestones_query = "SELECT * FROM addiction_progress_milestones WHERE program_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($milestones_query);
$stmt->bind_param("i", $program_id);
$stmt->execute();
$milestones_result = $stmt->get_result();
$recent_milestones = [];
while ($row = $milestones_result->fetch_assoc()) {
    $recent_milestones[] = $row;
}

// Get coping strategies
$strategies_query = "SELECT * FROM addiction_coping_strategies WHERE is_active = 1 AND (program_type = ? OR program_type = 'all') ORDER BY sort_order LIMIT 6";
$stmt = $conn->prepare($strategies_query);
$program_type = $has_program ? ($current_program['program_type'] ?? 'all') : 'all';
$stmt->bind_param("s", $program_type);
$stmt->execute();
$strategies_result = $stmt->get_result();
$coping_strategies = [];
while ($row = $strategies_result->fetch_assoc()) {
    $coping_strategies[] = $row;
}

// Get motivational quotes
$quotes_query = "SELECT * FROM addiction_motivational_quotes WHERE is_active = 1 AND (program_type = ? OR program_type = 'all') ORDER BY RAND() LIMIT 3";
$stmt = $conn->prepare($quotes_query);
$stmt->bind_param("s", $program_type);
$stmt->execute();
$quotes_result = $stmt->get_result();
$motivational_quotes = [];
while ($row = $quotes_result->fetch_assoc()) {
    $motivational_quotes[] = $row;
}

// Get educational content
$education_query = "SELECT * FROM addiction_educational_content WHERE (program_type = ? OR program_type = 'all') ORDER BY order_index LIMIT 4";
$stmt = $conn->prepare($education_query);
$stmt->bind_param("s", $program_type);
$stmt->execute();
$education_result = $stmt->get_result();
$educational_content = [];
while ($row = $education_result->fetch_assoc()) {
    $educational_content[] = $row;
}

// Calculate streak statistics
$streak_data = [
    'current' => $current_streak,
    'longest' => $has_program ? ($current_program['longest_streak'] ?? 0) : 0,
    'total_clean' => $has_program ? ($current_program['total_clean_days'] ?? 0) : 0,
    'relapses' => $has_program ? ($current_program['relapse_count'] ?? 0) : 0,
    'current_stage' => $has_program ? ($current_program['current_stage'] ?? 1) : 1
];

// Get recovery tips (table doesn't exist yet)
$daily_tip = null;
// Uncomment when table is created:
// $tips_query = "SELECT * FROM addiction_recovery_tips WHERE is_active = 1 ORDER BY RAND() LIMIT 1";
// $tips_result = $conn->query($tips_query);
// $daily_tip = $tips_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
    <title>Addiction Breaker - Recovery Support</title>
    
    <meta name="theme-color" content="#dc2626">
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
            --primary: #dc2626;
            --primary-dark: #b91c1c;
            --primary-light: #ef4444;
            --secondary: #10b981;
            --accent: #f59e0b;
            --recovery: #3b82f6;
            --healing: #8b5cf6;
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
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
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
            align-items: center;
            margin-bottom: 16px;
        }

        .progress-title {
            font-weight: 700;
            font-size: 18px;
            color: var(--dark);
        }

        .streak-badge {
            background: linear-gradient(135deg, var(--accent), #d97706);
            padding: 6px 14px;
            border-radius: 40px;
            color: white;
            font-weight: 600;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .progress-bar-container {
            margin-bottom: 16px;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: var(--gray);
            margin-bottom: 8px;
        }

        .progress-track {
            height: 10px;
            background: var(--gray-light);
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--secondary), #059669);
            border-radius: 10px;
            width: <?php echo $progress; ?>%;
            transition: width 0.3s;
        }

        /* Stats Grid - 2x2 */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: var(--gray-light);
            border-radius: var(--radius-sm);
            padding: 16px;
            text-align: center;
            transition: transform 0.1s;
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

        /* Check-in Card */
        .checkin-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-sm);
        }

        .checkin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .checkin-title {
            font-weight: 700;
            font-size: 18px;
            color: var(--dark);
        }

        .checkin-status {
            padding: 6px 12px;
            border-radius: 40px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-done {
            background: #d1fae5;
            color: #065f46;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        /* Section Header */
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
            font-size: 16px;
        }

        .see-all {
            font-size: 12px;
            color: var(--primary);
            text-decoration: none;
        }

        /* Actions Grid */
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        .action-card {
            background: var(--white);
            border-radius: var(--radius-sm);
            padding: 16px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            cursor: pointer;
            transition: transform 0.1s;
        }

        .action-card:active {
            transform: scale(0.97);
        }

        .action-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 16px;
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

        /* Strategies Grid */
        .strategies-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        .strategy-card {
            background: var(--white);
            border-radius: var(--radius-sm);
            padding: 16px;
            box-shadow: var(--shadow-sm);
            cursor: pointer;
            transition: transform 0.1s;
        }

        .strategy-card:active {
            transform: scale(0.97);
        }

        .strategy-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--recovery), #1d4ed8);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            color: white;
            font-size: 18px;
        }

        .strategy-title {
            font-weight: 600;
            font-size: 14px;
            color: var(--dark);
            margin-bottom: 4px;
        }

        .strategy-desc {
            font-size: 11px;
            color: var(--gray);
            margin-bottom: 8px;
            line-height: 1.3;
        }

        .strategy-time {
            font-size: 10px;
            color: var(--primary);
            font-weight: 500;
        }

        /* Education Grid */
        .education-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        .education-card {
            background: var(--white);
            border-radius: var(--radius-sm);
            padding: 16px;
            box-shadow: var(--shadow-sm);
            cursor: pointer;
            transition: transform 0.1s;
        }

        .education-card:active {
            transform: scale(0.97);
        }

        .education-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--healing), #7c3aed);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            color: white;
            font-size: 18px;
        }

        .education-title {
            font-weight: 600;
            font-size: 14px;
            color: var(--dark);
            margin-bottom: 4px;
        }

        .education-desc {
            font-size: 11px;
            color: var(--gray);
            line-height: 1.3;
        }

        /* Milestones List */
        .milestones-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-sm);
        }

        .milestone-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #eef2ff;
        }

        .milestone-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .milestone-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--secondary), #059669);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }

        .milestone-info {
            flex: 1;
        }

        .milestone-name {
            font-weight: 600;
            font-size: 14px;
            color: var(--dark);
        }

        .milestone-date {
            font-size: 11px;
            color: var(--gray);
        }

        /* Quote Card */
        .quote-card {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: var(--radius);
            padding: 24px;
            margin-bottom: 20px;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .quote-card::before {
            content: '"';
            position: absolute;
            bottom: -20px;
            right: 10px;
            font-size: 100px;
            opacity: 0.15;
            font-family: Georgia, serif;
        }

        .quote-text {
            font-size: 16px;
            font-style: italic;
            line-height: 1.5;
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
        }

        .quote-author {
            font-size: 12px;
            opacity: 0.9;
            text-align: right;
        }

        /* Daily Tip */
        .tip-card {
            background: #fef3c7;
            border-radius: var(--radius-sm);
            padding: 16px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .tip-icon {
            width: 40px;
            height: 40px;
            background: var(--accent);
            border-radius: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }

        .tip-content {
            flex: 1;
        }

        .tip-title {
            font-weight: 600;
            font-size: 13px;
            color: #92400e;
            margin-bottom: 4px;
        }

        .tip-text {
            font-size: 12px;
            color: #78350f;
        }

        /* Buttons */
        .btn-large {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 60px;
            font-weight: 700;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: transform 0.1s;
            cursor: pointer;
        }

        .btn-large:active {
            transform: scale(0.97);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--recovery), #1d4ed8);
            color: white;
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
            align-items: center;
            justify-content: center;
            z-index: 1000;
            backdrop-filter: blur(4px);
        }

        .modal-container {
            background: var(--white);
            border-radius: 28px;
            width: 90%;
            max-width: 400px;
            max-height: 85vh;
            overflow-y: auto;
            padding: 24px;
        }

        .modal-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .form-input, .form-select {
            width: 100%;
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            font-size: 14px;
            font-family: inherit;
            background: var(--white);
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
        }

        textarea.form-input {
            resize: vertical;
            min-height: 80px;
        }

        .modal-buttons {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .modal-btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 40px;
            font-weight: 600;
            cursor: pointer;
        }

        .modal-btn-primary {
            background: var(--primary);
            color: white;
        }

        .modal-btn-secondary {
            background: var(--gray-light);
            color: var(--gray);
        }

        /* Program Cards */
        .program-card {
            background: var(--white);
            border-radius: var(--radius-sm);
            padding: 16px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid transparent;
        }

        .program-card.selected {
            border-color: var(--primary);
            background: rgba(220, 38, 38, 0.05);
        }

        .program-icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .program-info {
            flex: 1;
        }

        .program-name {
            font-weight: 700;
            font-size: 16px;
            color: var(--dark);
        }

        .program-desc {
            font-size: 12px;
            color: var(--gray);
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

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray);
        }

        /* Dark Mode */
        @media (prefers-color-scheme: dark) {
            body {
                background: #0f172a;
            }
            .app-header, .welcome-card, .progress-card, .checkin-card,
            .action-card, .strategy-card, .education-card, .milestones-card,
            .modal-container, .program-card {
                background: #1e293b;
            }
            .greeting-title, .progress-title, .checkin-title, .section-title,
            .action-title, .strategy-title, .education-title, .milestone-name,
            .modal-title, .program-name {
                color: #f1f5f9;
            }
            .greeting-sub, .stat-label, .action-desc, .strategy-desc,
            .education-desc, .milestone-date, .program-desc {
                color: #94a3b8;
            }
            .stat-card {
                background: #334155;
            }
            .stat-value {
                color: var(--primary-light);
            }
            .form-input, .form-select {
                background: #334155;
                border-color: #475569;
                color: #f1f5f9;
            }
            .bottom-nav {
                background: rgba(30, 41, 59, 0.96);
            }
            .nav-link-item {
                color: #94a3b8;
            }
            .nav-link-item.active {
                color: var(--primary-light);
            }
            .milestone-item {
                border-bottom-color: #334155;
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
                <div class="logo-icon"><i class="fas fa-heart"></i></div>
                <span class="logo-text">Addiction Breaker</span>
            </div>
            <div class="user-badge" onclick="window.location.href='profile.php'">
                <div class="user-avatar-sm"><?php echo strtoupper(substr($first_name, 0, 1)); ?></div>
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
        <p class="greeting-sub">
            <?php if ($has_program): ?>
                Day <strong><?php echo $current_streak; ?></strong> of freedom. You're stronger than you know.
            <?php else: ?>
                Ready to break free? Start your recovery journey today.
            <?php endif; ?>
        </p>
    </div>

    <?php if ($has_program): ?>
        <!-- Progress Card -->
        <div class="progress-card">
            <div class="progress-header">
                <h3 class="progress-title">Recovery Journey</h3>
                <div class="streak-badge"><i class="fas fa-fire"></i> <?php echo $current_streak; ?> day streak</div>
            </div>
            <div class="progress-bar-container">
                <div class="progress-label">
                    <span>Program Progress</span>
                    <span><?php echo round($progress); ?>%</span>
                </div>
                <div class="progress-track">
                    <div class="progress-fill"></div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $streak_data['longest']; ?></div>
                    <div class="stat-label">Best Streak</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $streak_data['total_clean']; ?></div>
                    <div class="stat-label">Clean Days</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $streak_data['relapses']; ?></div>
                    <div class="stat-label">Setbacks</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">Stage <?php echo $streak_data['current_stage']; ?></div>
                    <div class="stat-label">Current Stage</div>
                </div>
            </div>
        </div>

        <!-- Daily Check-in -->
        <div class="checkin-card">
            <div class="checkin-header">
                <h3 class="checkin-title"><i class="fas fa-calendar-check me-2"></i>Daily Check-in</h3>
                <div class="checkin-status <?php echo $checked_in_today ? 'status-done' : 'status-pending'; ?>">
                    <?php echo $checked_in_today ? '✓ Completed' : 'Pending'; ?>
                </div>
            </div>
            <?php if (!$checked_in_today): ?>
                <button class="btn-large btn-primary" onclick="openCheckinModal()">
                    <i class="fas fa-clipboard-list"></i> Start Daily Check-in
                </button>
            <?php else: ?>
                <div class="text-center py-2">
                    <i class="fas fa-check-circle" style="font-size: 48px; color: var(--secondary);"></i>
                    <p class="text-muted mt-2">Great job staying accountable!</p>
                    <button class="btn-large btn-secondary mt-2" onclick="openCheckinModal()">
                        <i class="fas fa-eye"></i> View Today's Check-in
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="section-header">
            <h3 class="section-title"><i class="fas fa-bolt"></i> Quick Actions</h3>
            <a href="addiction-tools.php" class="see-all">See All <i class="fas fa-arrow-right"></i></a>
        </div>

        <div class="actions-grid">
            <div class="action-card" onclick="window.location.href='coping-strategies.php'">
                <div class="action-icon"><i class="fas fa-brain"></i></div>
                <div class="action-title">Coping Tools</div>
                <div class="action-desc">Manage cravings</div>
            </div>
            <div class="action-card" onclick="window.location.href='emergency-contact.php'">
                <div class="action-icon"><i class="fas fa-phone-alt"></i></div>
                <div class="action-title">Emergency Help</div>
                <div class="action-desc">Get immediate support</div>
            </div>
            <div class="action-card" onclick="window.location.href='addiction-education.php'">
                <div class="action-icon"><i class="fas fa-graduation-cap"></i></div>
                <div class="action-title">Learn More</div>
                <div class="action-desc">Understanding addiction</div>
            </div>
            <div class="action-card" onclick="window.location.href='addiction-milestones.php'">
                <div class="action-icon"><i class="fas fa-trophy"></i></div>
                <div class="action-title">Milestones</div>
                <div class="action-desc">Celebrate wins</div>
            </div>
        </div>

        <!-- Coping Strategies -->
        <div class="section-header">
            <h3 class="section-title"><i class="fas fa-life-ring"></i> Coping Strategies</h3>
            <a href="coping-strategies.php" class="see-all">See All</a>
        </div>

        <div class="strategies-grid">
            <?php foreach (array_slice($coping_strategies, 0, 4) as $strategy): ?>
            <div class="strategy-card" onclick="window.location.href='strategy-detail.php?id=<?php echo $strategy['id']; ?>'">
                <div class="strategy-icon"><i class="fas <?php echo $strategy['icon'] ?? 'fa-tools'; ?>"></i></div>
                <div class="strategy-title"><?php echo htmlspecialchars($strategy['strategy_name']); ?></div>
                <div class="strategy-desc"><?php echo htmlspecialchars(substr($strategy['description'], 0, 50)); ?>...</div>
                <div class="strategy-time"><i class="fas fa-clock"></i> <?php echo $strategy['duration_minutes']; ?> min</div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Educational Content -->
        <div class="section-header">
            <h3 class="section-title"><i class="fas fa-book-open"></i> Learn & Grow</h3>
            <a href="addiction-education.php" class="see-all">See All</a>
        </div>

        <div class="education-grid">
            <?php foreach (array_slice($educational_content, 0, 4) as $content): ?>
            <div class="education-card" onclick="window.location.href='education-detail.php?id=<?php echo $content['id']; ?>'">
                <div class="education-icon"><i class="fas <?php 
                    echo $content['content_type'] == 'video' ? 'fa-video' : ($content['content_type'] == 'audio' ? 'fa-headphones' : 'fa-file-alt'); 
                ?>"></i></div>
                <div class="education-title"><?php echo htmlspecialchars($content['title']); ?></div>
                <div class="education-desc"><?php echo htmlspecialchars(substr($content['description'], 0, 50)); ?>...</div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Recent Milestones -->
        <?php if (!empty($recent_milestones)): ?>
        <div class="milestones-card">
            <div class="section-header" style="margin-top: 0; margin-bottom: 12px;">
                <h3 class="section-title"><i class="fas fa-flag-checkered"></i> Recent Wins</h3>
            </div>
            <?php foreach (array_slice($recent_milestones, 0, 3) as $milestone): ?>
            <div class="milestone-item">
                <div class="milestone-icon"><i class="fas fa-trophy"></i></div>
                <div class="milestone-info">
                    <div class="milestone-name"><?php echo htmlspecialchars($milestone['milestone_name']); ?></div>
                    <div class="milestone-date"><?php echo date('M j, Y', strtotime($milestone['created_at'])); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Daily Tip -->
        <?php if ($daily_tip): ?>
        <div class="tip-card">
            <div class="tip-icon"><i class="fas fa-lightbulb"></i></div>
            <div class="tip-content">
                <div class="tip-title">Daily Recovery Tip</div>
                <div class="tip-text"><?php echo htmlspecialchars($daily_tip['tip_text']); ?></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Motivational Quote -->
        <?php if (!empty($motivational_quotes)): ?>
        <div class="quote-card">
            <div class="quote-text"><?php echo htmlspecialchars($motivational_quotes[0]['quote_text']); ?></div>
            <div class="quote-author">— <?php echo htmlspecialchars($motivational_quotes[0]['author'] ?? 'Anonymous'); ?></div>
        </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- Start Journey - Program Selection -->
        <div class="progress-card">
            <div class="text-center mb-4">
                <i class="fas fa-heart" style="font-size: 48px; color: var(--primary);"></i>
                <h3 class="mt-3" style="font-size: 20px; font-weight: 700;">Start Your Recovery Journey</h3>
                <p class="text-muted mt-2">Choose a program that fits your needs. We're here to support you.</p>
            </div>

            <div id="programList">
                <div class="program-card" onclick="selectProgram('substance')" data-type="substance">
                    <div class="program-icon" style="background: linear-gradient(135deg, #dc2626, #ef4444);"><i class="fas fa-syringe"></i></div>
                    <div class="program-info">
                        <div class="program-name">Substance Recovery</div>
                        <div class="program-desc">Overcome alcohol, drug, or substance dependencies</div>
                    </div>
                </div>
                <div class="program-card" onclick="selectProgram('focus')" data-type="focus">
                    <div class="program-icon" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);"><i class="fas fa-mobile-alt"></i></div>
                    <div class="program-info">
                        <div class="program-name">Digital Wellness</div>
                        <div class="program-desc">Break phone, social media, or gaming addiction</div>
                    </div>
                </div>
                <div class="program-card" onclick="selectProgram('healing')" data-type="healing">
                    <div class="program-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);"><i class="fas fa-heart"></i></div>
                    <div class="program-info">
                        <div class="program-name">Emotional Healing</div>
                        <div class="program-desc">Heal from emotional wounds and trauma</div>
                    </div>
                </div>
                <div class="program-card" onclick="selectProgram('comprehensive')" data-type="comprehensive">
                    <div class="program-icon" style="background: linear-gradient(135deg, #10b981, #059669);"><i class="fas fa-hands-helping"></i></div>
                    <div class="program-info">
                        <div class="program-name">Comprehensive Care</div>
                        <div class="program-desc">Full support for multiple challenges</div>
                    </div>
                </div>
            </div>

            <button class="btn-large btn-primary mt-4" onclick="openProgramModal()" id="startProgramBtn" disabled>
                <i class="fas fa-play"></i> Start Selected Program
            </button>
        </div>

        <!-- Quote for motivation -->
        <div class="quote-card">
            <div class="quote-text">"The first step towards getting somewhere is to decide that you are not going to stay where you are."</div>
            <div class="quote-author">— J.P. Morgan</div>
        </div>
        <?php endif; ?>
</div>

<!-- Bottom Navigation -->
<div class="bottom-nav">
    <div class="nav-links">
        <a href="index.php" class="nav-link-item"><i class="fas fa-home"></i><span>Home</span></a>
        <a href="addiction.php" class="nav-link-item active"><i class="fas fa-heart"></i><span>Recovery</span></a>
        <a href="bible_quiz.php" class="nav-link-item"><i class="fas fa-question-circle"></i><span>Quiz</span></a>
        <a href="leaderboard.php" class="nav-link-item"><i class="fas fa-trophy"></i><span>Rank</span></a>
        <a href="profile.php" class="nav-link-item"><i class="fas fa-user"></i><span>Profile</span></a>
    </div>
</div>

<!-- Check-in Modal -->
<div class="modal-overlay" id="checkinModal">
    <div class="modal-container">
        <h3 class="modal-title"><i class="fas fa-clipboard-list me-2"></i>Daily Check-in</h3>
        <form id="checkinForm">
            <input type="hidden" name="program_id" value="<?php echo $program_id; ?>">
            <div class="form-group">
                <label class="form-label">How are you feeling today?</label>
                <select class="form-select" name="mood" required>
                    <option value="">Select mood</option>
                    <option value="great">😊 Great - Feeling strong</option>
                    <option value="good">🙂 Good - Managing well</option>
                    <option value="okay">😐 Okay - Getting by</option>
                    <option value="struggling">😔 Struggling - Need support</option>
                    <option value="hard">😢 Hard day - Facing challenges</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Craving level today</label>
                <select class="form-select" name="craving_level" required>
                    <option value="none">None - No cravings</option>
                    <option value="mild">Mild - Manageable</option>
                    <option value="moderate">Moderate - Noticeable</option>
                    <option value="strong">Strong - Challenging</option>
                    <option value="severe">Severe - Very difficult</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Stayed clean/sober?</label>
                <select class="form-select" name="stayed_clean" required>
                    <option value="yes">✅ Yes - Stayed strong</option>
                    <option value="no">❌ No - Had a setback</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Challenges faced (optional)</label>
                <textarea class="form-input" name="challenges" rows="2" placeholder="What was difficult today?"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">What helped you today?</label>
                <textarea class="form-input" name="successes" rows="2" placeholder="What strategies or support helped you?"></textarea>
            </div>
            <div class="modal-buttons">
                <button type="button" class="modal-btn modal-btn-secondary" onclick="closeModal('checkinModal')">Cancel</button>
                <button type="button" class="modal-btn modal-btn-primary" onclick="submitCheckin()">Submit</button>
            </div>
        </form>
    </div>
</div>

<!-- Program Setup Modal -->
<div class="modal-overlay" id="programModal">
    <div class="modal-container">
        <h3 class="modal-title"><i class="fas fa-play-circle me-2"></i>Setup Your Program</h3>
        <form id="programForm">
            <input type="hidden" name="program_type" id="selectedProgramType">
            <div class="form-group">
                <label class="form-label">What are you struggling with?</label>
                <input type="text" class="form-input" name="addiction_type" id="addictionType" placeholder="e.g., Alcohol, Social Media, Anxiety" required>
            </div>
            <div class="form-group">
                <label class="form-label">How severe is it?</label>
                <select class="form-select" name="severity" required>
                    <option value="low">🌱 Low - Occasional</option>
                    <option value="moderate" selected>🌿 Moderate - Regular</option>
                    <option value="high">🔥 High - Daily</option>
                    <option value="severe">⚠️ Severe - Multiple times daily</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Emergency contact (name)</label>
                <input type="text" class="form-input" name="emergency_name" placeholder="Someone you trust">
            </div>
            <div class="form-group">
                <label class="form-label">Emergency contact (phone)</label>
                <input type="tel" class="form-input" name="emergency_phone" placeholder="Phone number">
            </div>
            <div class="form-group">
                <label class="form-label">Preferred check-in time</label>
                <input type="time" class="form-input" name="checkin_time" value="20:00">
            </div>
            <div class="modal-buttons">
                <button type="button" class="modal-btn modal-btn-secondary" onclick="closeModal('programModal')">Cancel</button>
                <button type="button" class="modal-btn modal-btn-primary" onclick="submitProgram()">Start Journey</button>
            </div>
        </form>
    </div>
</div>

<script>
    let selectedProgramType = null;

    function selectProgram(type) {
        selectedProgramType = type;
        document.querySelectorAll('.program-card').forEach(card => {
            card.classList.remove('selected');
            if (card.dataset.type === type) {
                card.classList.add('selected');
            }
        });
        document.getElementById('startProgramBtn').disabled = false;
    }

    function openProgramModal() {
        if (!selectedProgramType) {
            alert('Please select a program first');
            return;
        }
        document.getElementById('selectedProgramType').value = selectedProgramType;
        document.getElementById('programModal').style.display = 'flex';
    }

    function openCheckinModal() {
        document.getElementById('checkinModal').style.display = 'flex';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    async function submitCheckin() {
        const form = document.getElementById('checkinForm');
        const formData = new FormData(form);
        formData.append('action', 'checkin');
        
        const btn = event.target;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
        
        try {
            const response = await fetch('addiction-actions.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (data.success) {
                showNotification('Check-in recorded! Keep going strong!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification(data.message || 'Error submitting check-in', 'error');
                btn.disabled = false;
                btn.innerHTML = 'Submit';
            }
        } catch (error) {
            showNotification('Network error. Please try again.', 'error');
            btn.disabled = false;
            btn.innerHTML = 'Submit';
        }
    }

    async function submitProgram() {
        const form = document.getElementById('programForm');
        const formData = new FormData(form);
        formData.append('action', 'start_program');
        
        const btn = event.target;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Starting...';
        
        try {
            const response = await fetch('addiction-actions.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (data.success) {
                showNotification('Program started! Your journey to freedom begins now.', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification(data.message || 'Error starting program', 'error');
                btn.disabled = false;
                btn.innerHTML = 'Start Journey';
            }
        } catch (error) {
            showNotification('Network error. Please try again.', 'error');
            btn.disabled = false;
            btn.innerHTML = 'Start Journey';
        }
    }

    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            bottom: 80px;
            left: 16px;
            right: 16px;
            background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
            color: white;
            padding: 14px 20px;
            border-radius: 60px;
            text-align: center;
            font-weight: 500;
            z-index: 2000;
            animation: slideUp 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        notification.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>${message}`;
        document.body.appendChild(notification);
        setTimeout(() => {
            notification.style.animation = 'slideDown 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Close modals on overlay click
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) this.style.display = 'none';
        });
    });

    // Add animation styles
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideUp {
            from { transform: translateY(100px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @keyframes slideDown {
            from { transform: translateY(0); opacity: 1; }
            to { transform: translateY(100px); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
</script>
</body>
</html>