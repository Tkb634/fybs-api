<?php
// Start session to check login state
session_start();

// If user is not logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection for stats
include "config.php";
$user_id = $_SESSION['user_id'];

// Get user name - using available session data
if (isset($_SESSION['full_name']) && !empty($_SESSION['full_name'])) {
    $user_name = $_SESSION['full_name'];
} elseif (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];
    $name_part = explode('@', $email)[0];
    $user_name = ucwords(str_replace('.', ' ', $name_part));
} else {
    $user_name = "User";
}

// Get user role for display
$user_role = isset($_SESSION['user_role']) ? ucfirst($_SESSION['user_role']) : 'Member';

// Get user stats
$prayer_count = 0;
$testimony_count = 0;
$habits_completed = 0;
$gyc_messages = 0;
$sermons_watched = 0;
$donations_count = 0;
$membership_tier = "Free";
$current_streak = 0;

// Get prayer count
$prayer_sql = "SELECT COUNT(*) as count FROM prayer_requests WHERE user_id = $user_id";
$prayer_result = mysqli_query($conn, $prayer_sql);
if ($prayer_result) {
    $prayer_row = mysqli_fetch_assoc($prayer_result);
    $prayer_count = $prayer_row['count'];
}

// Get prayer interactions count (prayers for others)
$prayer_interactions_sql = "SELECT COUNT(*) as count FROM prayer_interactions WHERE user_id = $user_id";
$prayer_interactions_result = mysqli_query($conn, $prayer_interactions_sql);
if ($prayer_interactions_result) {
    $prayer_interactions_row = mysqli_fetch_assoc($prayer_interactions_result);
    $prayer_interactions_count = $prayer_interactions_row['count'];
}

// Get testimony count
$testimony_sql = "SELECT COUNT(*) as count FROM testimonies WHERE user_id = $user_id";
$testimony_result = mysqli_query($conn, $testimony_sql);
if ($testimony_result) {
    $testimony_row = mysqli_fetch_assoc($testimony_result);
    $testimony_count = $testimony_row['count'];
}

// Get habits completed today
$today = date('Y-m-d');
$habits_sql = "SELECT COUNT(*) as count FROM habit_logs hl 
               JOIN life_habits lh ON hl.habit_id = lh.id 
               WHERE hl.user_id = $user_id AND hl.completed_date = '$today'";
$habits_result = mysqli_query($conn, $habits_sql);
if ($habits_result) {
    $habits_row = mysqli_fetch_assoc($habits_result);
    $habits_completed = $habits_row['count'];
}

// Get GYC messages count
$gyc_sql = "SELECT COUNT(*) as count FROM gyc_messages WHERE user_id = $user_id";
$gyc_result = mysqli_query($conn, $gyc_sql);
if ($gyc_result) {
    $gyc_row = mysqli_fetch_assoc($gyc_result);
    $gyc_messages = $gyc_row['count'];
}

// Get sermons watched
$sermons_sql = "SELECT COUNT(*) as count FROM spiritual_devotional_logs WHERE user_id = $user_id";
$sermons_result = mysqli_query($conn, $sermons_sql);
if ($sermons_result) {
    $sermons_row = mysqli_fetch_assoc($sermons_result);
    $sermons_watched = $sermons_row['count'];
}

// Get donations count
$donations_sql = "SELECT COUNT(*) as count, SUM(amount) as total FROM donations WHERE user_id = $user_id AND payment_status = 'completed'";
$donations_result = mysqli_query($conn, $donations_sql);
if ($donations_result) {
    $donations_row = mysqli_fetch_assoc($donations_result);
    $donations_count = $donations_row['count'];
    $donations_total = $donations_row['total'] ? $donations_row['total'] : 0;
}

// Get membership info
$membership_sql = "SELECT mt.name, um.start_date FROM user_memberships um 
                  JOIN membership_tiers mt ON um.tier_id = mt.id 
                  WHERE um.user_id = $user_id AND um.status = 'active' LIMIT 1";
$membership_result = mysqli_query($conn, $membership_sql);
if ($membership_result && mysqli_num_rows($membership_result) > 0) {
    $membership_row = mysqli_fetch_assoc($membership_result);
    $membership_tier = $membership_row['name'];
    $membership_start = $membership_row['start_date'];
}

// Get current streak from habits
$streak_sql = "SELECT current_streak FROM life_habits WHERE user_id = $user_id AND is_active = 1 ORDER BY current_streak DESC LIMIT 1";
$streak_result = mysqli_query($conn, $streak_sql);
if ($streak_result && mysqli_num_rows($streak_result) > 0) {
    $streak_row = mysqli_fetch_assoc($streak_result);
    $current_streak = $streak_row['current_streak'];
}

// Get total active habits
$active_habits_sql = "SELECT COUNT(*) as count FROM life_habits WHERE user_id = $user_id AND is_active = 1";
$active_habits_result = mysqli_query($conn, $active_habits_sql);
if ($active_habits_result) {
    $active_habits_row = mysqli_fetch_assoc($active_habits_result);
    $active_habits_count = $active_habits_row['count'];
}

// Get today's devotional
$today_date = date('Y-m-d');
$devotional_sql = "SELECT * FROM spiritual_devotionals WHERE scheduled_date = '$today_date' AND is_published = 1 LIMIT 1";
$devotional_result = mysqli_query($conn, $devotional_sql);
$todays_devotional = null;
if ($devotional_result && mysqli_num_rows($devotional_result) > 0) {
    $todays_devotional = mysqli_fetch_assoc($devotional_result);
}

// Get recent prayer requests
$recent_prayers_sql = "SELECT pr.*, u.full_name FROM prayer_requests pr 
                      LEFT JOIN users u ON pr.user_id = u.id 
                      WHERE (pr.user_id = $user_id OR pr.is_anonymous = 0) 
                      ORDER BY pr.created_at DESC LIMIT 3";
$recent_prayers_result = mysqli_query($conn, $recent_prayers_sql);
$recent_prayers = [];
if ($recent_prayers_result) {
    while($row = mysqli_fetch_assoc($recent_prayers_result)) {
        $recent_prayers[] = $row;
    }
}

// Get weekly habit completion data
$week_data = [];
for($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $day_name = date('D', strtotime($date));
    
    $day_sql = "SELECT COUNT(*) as count FROM habit_logs hl 
                JOIN life_habits lh ON hl.habit_id = lh.id 
                WHERE hl.user_id = $user_id AND hl.completed_date = '$date'";
    $day_result = mysqli_query($conn, $day_sql);
    $completed = 0;
    if ($day_result) {
        $day_row = mysqli_fetch_assoc($day_result);
        $completed = $day_row['count'];
    }
    
    $week_data[] = [
        'day' => $day_name,
        'completed' => $completed,
        'percentage' => $active_habits_count > 0 ? round(($completed / $active_habits_count) * 100) : 0
    ];
}

// Get addiction breaker program status
$addiction_sql = "SELECT * FROM addiction_breaker_programs WHERE user_id = $user_id AND current_stage != 'graduated' ORDER BY id DESC LIMIT 1";
$addiction_result = mysqli_query($conn, $addiction_sql);
$has_addiction_program = false;
if ($addiction_result && mysqli_num_rows($addiction_result) > 0) {
    $has_addiction_program = true;
}

// Get emergency contacts
$emergency_contacts = [];
$emergency_contacts_sql = "SELECT * FROM counseling_emergency_contacts WHERE is_active = 1 ORDER BY type";
$emergency_contacts_result = mysqli_query($conn, $emergency_contacts_sql);
if ($emergency_contacts_result) {
    while($row = mysqli_fetch_assoc($emergency_contacts_result)) {
        $emergency_contacts[] = $row;
    }
}

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

// Get day of week
$day_of_week = date('l');
$date = date('F j, Y');

// Get first name for personal greeting
$first_name = explode(' ', $user_name)[0];

// Get motivational quote
$daily_quote = "The journey of a thousand miles begins with a single step.";
$quote_author = " - Lao Tzu";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
    <title>Dashboard - CYIC Youth App</title>
    
    <meta name="theme-color" content="#4f46e5">
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
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --primary-light: #6366f1;
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
            position: relative;
            overflow: hidden;
        }

        .program-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: linear-gradient(135deg, #ec4899, #db2777);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
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
            gap: 16px;
            font-size: 12px;
            color: var(--gray);
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .streak-badge {
            background: #fef3c7;
            color: #d97706;
            padding: 4px 8px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .daily-quote {
            background: #f8fafc;
            border-left: 4px solid var(--primary);
            padding: 12px;
            border-radius: 12px;
            font-size: 13px;
            color: var(--gray-dark);
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
            box-shadow: var(--shadow-sm);
            transition: transform 0.1s;
            cursor: pointer;
        }

        .stat-card:active {
            transform: scale(0.97);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
        }

        .stat-icon.prayer { background: linear-gradient(135deg, var(--primary), var(--primary-light)); }
        .stat-icon.testimony { background: linear-gradient(135deg, var(--secondary), #059669); }
        .stat-icon.habits { background: linear-gradient(135deg, var(--accent), #d97706); }
        .stat-icon.donations { background: linear-gradient(135deg, #8b5cf6, #ec4899); }

        .stat-change {
            font-size: 10px;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 20px;
            background: var(--secondary);
            color: white;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 12px;
            color: var(--gray);
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
            font-size: 18px;
        }

        .see-all {
            font-size: 12px;
            color: var(--primary);
            text-decoration: none;
        }

        /* Action Cards */
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
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            margin-bottom: 12px;
        }

        .action-card:nth-child(1) .action-icon { background: linear-gradient(135deg, var(--primary), var(--primary-light)); }
        .action-card:nth-child(2) .action-icon { background: linear-gradient(135deg, var(--secondary), #059669); }
        .action-card:nth-child(3) .action-icon { background: linear-gradient(135deg, var(--accent), #d97706); }
        .action-card:nth-child(4) .action-icon { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
        .action-card:nth-child(5) .action-icon { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .action-card:nth-child(6) .action-icon { background: linear-gradient(135deg, #ec4899, #db2777); }
        .action-card:nth-child(7) .action-icon { background: linear-gradient(135deg, #06b6d4, #0891b2); }
        .action-card:nth-child(8) .action-icon { background: linear-gradient(135deg, #f97316, #ea580c); }

        .action-title {
            font-weight: 600;
            font-size: 14px;
            color: var(--dark);
            margin-bottom: 4px;
        }

        .action-desc {
            font-size: 11px;
            color: var(--gray);
            line-height: 1.3;
        }

        /* Activity Card */
        .activity-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 16px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
        }

        .activity-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #eef2ff;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 36px;
            height: 36px;
            background: var(--gray-light);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            flex-shrink: 0;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 600;
            font-size: 14px;
            color: var(--dark);
            margin-bottom: 2px;
        }

        .activity-desc {
            font-size: 12px;
            color: var(--gray);
            margin-bottom: 2px;
        }

        .activity-time {
            font-size: 10px;
            color: #94a3b8;
        }

        /* Progress Chart */
        .progress-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 16px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
        }

        .chart-container {
            height: 180px;
            margin-top: 12px;
        }

        canvas {
            max-height: 100%;
            width: 100%;
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

        .nav-link-item:active {
            background: var(--gray-light);
        }

        /* Chatbot */
        .chatbot-container {
            position: fixed;
            bottom: 80px;
            right: 16px;
            z-index: 1000;
        }

        .chatbot-toggle {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 22px;
            cursor: pointer;
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s;
        }

        .chatbot-toggle:active {
            transform: scale(0.92);
        }

        .chatbot-window {
            position: absolute;
            bottom: 70px;
            right: 0;
            width: 320px;
            max-width: calc(100vw - 32px);
            height: 450px;
            background: var(--white);
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            display: none;
            flex-direction: column;
            overflow: hidden;
        }

        .chatbot-window.active {
            display: flex;
            animation: slideUp 0.2s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .chat-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            padding: 14px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-header h6 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chat-actions {
            display: flex;
            gap: 8px;
        }

        .chat-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            color: white;
            cursor: pointer;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            background: #f9fafb;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .message-bubble {
            max-width: 85%;
            padding: 10px 14px;
            border-radius: 18px;
            font-size: 13px;
        }

        .user-message {
            align-self: flex-end;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            border-bottom-right-radius: 4px;
        }

        .bot-message {
            align-self: flex-start;
            background: white;
            color: var(--dark);
            border: 1px solid #e5e7eb;
            border-bottom-left-radius: 4px;
        }

        .message-time {
            font-size: 9px;
            opacity: 0.7;
            margin-top: 4px;
            text-align: right;
        }

        .quick-questions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }

        .quick-q {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            padding: 6px 12px;
            font-size: 11px;
            cursor: pointer;
        }

        .chat-input-area {
            padding: 12px;
            border-top: 1px solid #e5e7eb;
            background: white;
            display: flex;
            gap: 8px;
        }

        .chat-input-area input {
            flex: 1;
            border: 1px solid #e5e7eb;
            border-radius: 24px;
            padding: 10px 14px;
            font-size: 13px;
        }

        .chat-input-area button {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border: none;
            color: white;
            cursor: pointer;
        }

        /* Emergency Modal */
        .emergency-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1100;
            padding: 20px;
        }

        .emergency-modal.active {
            display: flex;
        }

        .emergency-content {
            background: var(--white);
            border-radius: 24px;
            width: 100%;
            max-width: 350px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .emergency-header {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 16px;
            border-radius: 24px 24px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px;
            border-bottom: 1px solid #eef2ff;
        }

        .contact-icon {
            width: 40px;
            height: 40px;
            background: var(--gray-light);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .contact-info h6 {
            margin: 0 0 4px;
            font-size: 14px;
        }

        .contact-phone {
            font-size: 12px;
            color: var(--gray);
        }

        .call-btn {
            padding: 6px 12px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 20px;
            font-size: 12px;
            text-decoration: none;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 32px 20px;
            color: var(--gray);
        }

        /* Dark Mode */
        @media (prefers-color-scheme: dark) {
            body {
                background: #0f172a;
            }
            .app-header, .welcome-card, .stat-card, .action-card, .activity-card, .progress-card, .chatbot-window, .emergency-content {
                background: #1e293b;
            }
            .greeting-title, .section-title, .action-title, .activity-title, .stat-value {
                color: #f1f5f9;
            }
            .greeting-sub, .action-desc, .activity-desc, .stat-label, .date-info {
                color: #94a3b8;
            }
            .daily-quote {
                background: #334155;
                color: #cbd5e1;
            }
            .activity-item {
                border-bottom-color: #334155;
            }
            .activity-icon {
                background: #334155;
            }
            .chat-messages {
                background: #0f172a;
            }
            .bot-message {
                background: #334155;
                color: #e2e8f0;
                border-color: #475569;
            }
            .quick-q {
                background: #334155;
                border-color: #475569;
                color: #e2e8f0;
            }
            .chat-input-area {
                background: #1e293b;
                border-top-color: #334155;
            }
            .chat-input-area input {
                background: #334155;
                border-color: #475569;
                color: #e2e8f0;
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
            .chatbot-container {
                bottom: max(80px, calc(80px + env(safe-area-inset-bottom)));
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
                <span class="logo-text">CYIC</span>
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
        <?php if($has_addiction_program): ?>
            <span class="program-badge"><i class="fas fa-heart-pulse"></i> Recovery Active</span>
        <?php endif; ?>
        <div class="greeting-row">
            <span class="greeting-icon"><?php echo $greeting_icon; ?></span>
            <h2 class="greeting-title"><?php echo $greeting; ?>, <?php echo htmlspecialchars($first_name); ?>!</h2>
        </div>
        <p class="greeting-sub">Your spiritual growth hub. Continue your journey of faith.</p>
        <div class="date-info">
            <span><i class="far fa-calendar-alt"></i> <?php echo $day_of_week; ?>, <?php echo $date; ?></span>
            <span class="streak-badge"><i class="fas fa-fire"></i> <?php echo $current_streak; ?> day streak</span>
        </div>
        <div class="daily-quote">
            <i class="fas fa-quote-left me-2"></i>
            <?php echo $daily_quote; ?><?php echo $quote_author; ?>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card" onclick="window.location.href='gyc.php?room=prayer'">
            <div class="stat-header">
                <div class="stat-icon prayer"><i class="fas fa-hands-praying"></i></div>
                <span class="stat-change"><?php echo $prayer_interactions_count; ?> prayers</span>
            </div>
            <div class="stat-value"><?php echo $prayer_count; ?></div>
            <div class="stat-label">Prayer Requests</div>
        </div>
        <div class="stat-card" onclick="window.location.href='gyc.php?room=testimony'">
            <div class="stat-header">
                <div class="stat-icon testimony"><i class="fas fa-star"></i></div>
                <span class="stat-change">Shared</span>
            </div>
            <div class="stat-value"><?php echo $testimony_count; ?></div>
            <div class="stat-label">Testimonies</div>
        </div>
        <div class="stat-card" onclick="window.location.href='life.php'">
            <div class="stat-header">
                <div class="stat-icon habits"><i class="fas fa-check-circle"></i></div>
                <span class="stat-change"><?php echo $habits_completed; ?>/<?php echo $active_habits_count; ?></span>
            </div>
            <div class="stat-value"><?php echo $habits_completed; ?>/<?php echo $active_habits_count; ?></div>
            <div class="stat-label">Today's Habits</div>
        </div>
        <div class="stat-card" onclick="window.location.href='membership.php'">
            <div class="stat-header">
                <div class="stat-icon donations"><i class="fas fa-hand-holding-heart"></i></div>
                <span class="stat-change"><?php echo $membership_tier; ?></span>
            </div>
            <div class="stat-value">$<?php echo number_format($donations_total, 2); ?></div>
            <div class="stat-label">Total Donated</div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="section-header">
        <div class="section-title"><i class="fas fa-bolt"></i> Quick Actions</div>
    </div>
    <div class="actions-grid">
        <a href="fybs.php" class="action-card">
            <div class="action-icon"><i class="fas fa-book-bible"></i></div>
            <div class="action-title">FYBS Bible School</div>
            <div class="action-desc">Structured Bible classes</div>
        </a>
        <a href="gyc.php" class="action-card">
            <div class="action-icon"><i class="fas fa-comments"></i></div>
            <div class="action-title">Global Youth Chat</div>
            <div class="action-desc"><?php echo $gyc_messages; ?> messages sent</div>
        </a>
        <a href="life.php" class="action-card">
            <div class="action-icon"><i class="fas fa-bolt"></i></div>
            <div class="action-title">LIFE Hacks</div>
            <div class="action-desc"><?php echo $active_habits_count; ?> active habits</div>
        </a>
        <a href="spiritual.php" class="action-card">
            <div class="action-icon"><i class="fas fa-cross"></i></div>
            <div class="action-title">Spiritual Growth</div>
            <div class="action-desc"><?php echo $sermons_watched; ?> devotionals</div>
        </a>
        <a href="membership.php" class="action-card">
            <div class="action-icon"><i class="fas fa-users"></i></div>
            <div class="action-title">Membership</div>
            <div class="action-desc"><?php echo $membership_tier; ?> member</div>
        </a>
        <a href="gospel.php" class="action-card">
            <div class="action-icon"><i class="fas fa-globe"></i></div>
            <div class="action-title">Gospel Movement</div>
            <div class="action-desc">Join the mission</div>
        </a>
        <a href="addiction.php" class="action-card">
            <div class="action-icon"><i class="fas fa-heart-pulse"></i></div>
            <div class="action-title">Addiction Breaker</div>
            <div class="action-desc"><?php echo $has_addiction_program ? 'Program active' : 'Start healing'; ?></div>
        </a>
        <a href="bible_quiz.php" class="action-card">
            <div class="action-icon"><i class="fas fa-question-circle"></i></div>
            <div class="action-title">Bible Quiz</div>
            <div class="action-desc">Test your knowledge</div>
        </a>
    </div>

    <!-- Recent Activity -->
    <div class="section-header">
        <div class="section-title"><i class="fas fa-history"></i> Recent Activity</div>
        <a href="gyc.php" class="see-all">See all <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="activity-card">
        <?php if(count($recent_prayers) > 0): ?>
            <div class="activity-list">
                <?php foreach($recent_prayers as $prayer): ?>
                <div class="activity-item">
                    <div class="activity-icon"><i class="fas fa-pray"></i></div>
                    <div class="activity-content">
                        <div class="activity-title"><?php echo $prayer['is_anonymous'] ? 'Anonymous Prayer' : htmlspecialchars($prayer['full_name']); ?></div>
                        <div class="activity-desc"><?php echo htmlspecialchars(substr($prayer['title'], 0, 40)); ?>...</div>
                        <div class="activity-time">
                            <?php 
                            $time_ago = strtotime($prayer['created_at']);
                            $diff = time() - $time_ago;
                            if($diff < 3600) echo round($diff/60) . ' min ago';
                            elseif($diff < 86400) echo round($diff/3600) . ' hours ago';
                            else echo round($diff/86400) . ' days ago';
                            ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox fa-2x mb-2"></i>
                <p>No recent activity. Start by posting a prayer!</p>
                <button class="btn btn-sm btn-primary mt-2" onclick="window.location.href='gyc.php?room=prayer'">
                    <i class="fas fa-plus"></i> Post Prayer
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Weekly Progress -->
    <div class="section-header">
        <div class="section-title"><i class="fas fa-chart-line"></i> Weekly Progress</div>
    </div>
    <div class="progress-card">
        <div class="chart-container">
            <canvas id="progressChart"></canvas>
        </div>
    </div>
</div>

<!-- Bottom Navigation -->
<div class="bottom-nav">
    <div class="nav-links">
        <a href="index.php" class="nav-link-item active"><i class="fas fa-home"></i><span>Home</span></a>
        <a href="fybs.php" class="nav-link-item"><i class="fas fa-book-bible"></i><span>FYBS</span></a>
        <a href="gyc.php" class="nav-link-item"><i class="fas fa-comments"></i><span>GYC</span></a>
        <a href="life.php" class="nav-link-item"><i class="fas fa-bolt"></i><span>LIFE</span></a>
        <a href="profile.php" class="nav-link-item"><i class="fas fa-user"></i><span>Profile</span></a>
    </div>
</div>

<!-- Chatbot -->
<div class="chatbot-container">
    <button class="chatbot-toggle" id="chatToggle"><i class="fas fa-robot"></i></button>
    <div class="chatbot-window" id="chatWindow">
        <div class="chat-header">
            <h6><i class="fas fa-heart-pulse"></i> CYIC Counselor</h6>
            <div class="chat-actions">
                <button class="chat-btn" id="emergencyBtn"><i class="fas fa-phone-alt"></i></button>
                <button class="chat-btn" id="closeChat"><i class="fas fa-times"></i></button>
            </div>
        </div>
        <div class="chat-messages" id="chatMessages">
            <div class="message-bubble bot-message">
                Hello! I'm here to provide support. How can I help you today?
                <div class="message-time">Just now</div>
            </div>
            <div class="quick-questions">
                <span class="quick-q" data-q="I'm feeling anxious">😰 Feeling anxious</span>
                <span class="quick-q" data-q="I need prayer">🙏 Need prayer</span>
                <span class="quick-q" data-q="I'm struggling">💪 Need help</span>
            </div>
        </div>
        <div class="chat-input-area">
            <input type="text" id="chatInput" placeholder="Type your message..." maxlength="300">
            <button id="sendMsg"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
</div>

<!-- Emergency Modal -->
<div class="emergency-modal" id="emergencyModal">
    <div class="emergency-content">
        <div class="emergency-header">
            <h6><i class="fas fa-phone-alt"></i> Emergency Contacts</h6>
            <button class="chat-btn" id="closeEmergency" style="background: rgba(255,255,255,0.2);"><i class="fas fa-times"></i></button>
        </div>
        <div class="emergency-contacts" style="max-height: 400px; overflow-y: auto;">
            <?php foreach($emergency_contacts as $contact): ?>
            <div class="contact-item">
                <div class="contact-icon">
                    <i class="fas fa-<?php echo $contact['type'] == 'emergency' ? 'exclamation-triangle' : ($contact['type'] == 'helpline' ? 'phone' : 'user-md'); ?>"></i>
                </div>
                <div class="contact-info">
                    <h6><?php echo htmlspecialchars($contact['name']); ?></h6>
                    <div class="contact-phone"><?php echo htmlspecialchars($contact['phone']); ?></div>
                    <small><?php echo htmlspecialchars($contact['availability']); ?></small>
                </div>
                <a href="tel:<?php echo htmlspecialchars($contact['phone']); ?>" class="call-btn">Call</a>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="p-3 border-top">
            <small class="text-muted"><i class="fas fa-info-circle"></i> Confidential support available 24/7</small>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Chart Data
    const weekDays = <?php echo json_encode(array_column($week_data, 'day')); ?>;
    const habitData = <?php echo json_encode(array_column($week_data, 'completed')); ?>;
    const maxHabits = <?php echo $active_habits_count > 0 ? $active_habits_count : 5; ?>;

    const ctx = document.getElementById('progressChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: weekDays,
            datasets: [{
                label: 'Habits Completed',
                data: habitData,
                backgroundColor: '#4f46e5',
                borderRadius: 8,
                barPercentage: 0.65
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => `${ctx.raw}/${maxHabits} habits`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: maxHabits,
                    grid: { color: '#e2e8f0' },
                    ticks: { stepSize: 1 }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // Chatbot functionality
    const chatToggle = document.getElementById('chatToggle');
    const chatWindow = document.getElementById('chatWindow');
    const closeChat = document.getElementById('closeChat');
    const chatInput = document.getElementById('chatInput');
    const sendMsg = document.getElementById('sendMsg');
    const chatMessages = document.getElementById('chatMessages');
    const emergencyBtn = document.getElementById('emergencyBtn');
    const emergencyModal = document.getElementById('emergencyModal');
    const closeEmergency = document.getElementById('closeEmergency');

    chatToggle.onclick = () => chatWindow.classList.toggle('active');
    closeChat.onclick = () => chatWindow.classList.remove('active');
    emergencyBtn.onclick = () => emergencyModal.classList.add('active');
    closeEmergency.onclick = () => emergencyModal.classList.remove('active');
    emergencyModal.onclick = (e) => { if(e.target === emergencyModal) emergencyModal.classList.remove('active'); };

    function addMessage(text, isUser = false) {
        const div = document.createElement('div');
        div.className = `message-bubble ${isUser ? 'user-message' : 'bot-message'}`;
        const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        div.innerHTML = `${text}<div class="message-time">${time}</div>`;
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    async function sendMessage() {
        const msg = chatInput.value.trim();
        if (!msg) return;
        addMessage(msg, true);
        chatInput.value = '';
        
        // Simulate bot response (in production, call API)
        setTimeout(() => {
            const responses = [
                "I hear you. Would you like to talk more about this?",
                "Thank you for sharing. Let me pray for you.",
                "You're not alone. Many young people face similar challenges.",
                "Would you like me to connect you with a counselor?"
            ];
            addMessage(responses[Math.floor(Math.random() * responses.length)]);
        }, 800);
    }

    sendMsg.onclick = sendMessage;
    chatInput.onkeypress = (e) => { if(e.key === 'Enter') sendMessage(); };
    
    document.querySelectorAll('.quick-q').forEach(q => {
        q.onclick = () => {
            chatInput.value = q.dataset.q;
            sendMessage();
        };
    });

    // Ripple effect for cards
    document.querySelectorAll('.stat-card, .action-card').forEach(card => {
        card.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size/2;
            const y = e.clientY - rect.top - size/2;
            ripple.style.cssText = `position:absolute; border-radius:50%; background:rgba(0,0,0,0.1); width:${size}px; height:${size}px; top:${y}px; left:${x}px; pointer-events:none; animation:ripple 0.4s ease-out;`;
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 400);
        });
    });

    const style = document.createElement('style');
    style.textContent = `@keyframes ripple { to { transform: scale(4); opacity: 0; } }`;
    document.head.appendChild(style);
</script>
</body>
</html>