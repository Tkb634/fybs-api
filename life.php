<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include "config.php";

// Get user ID from session
$user_id = $_SESSION['user_id'];

// FIX: Safely get user name to avoid "Undefined array key" warning
if (isset($_SESSION['full_name']) && !empty($_SESSION['full_name'])) {
    $user_name = $_SESSION['full_name'];
} elseif (isset($_SESSION['email'])) {
    // Extract name from email (before @) or use email
    $email = $_SESSION['email'];
    $name_part = explode('@', $email)[0];
    $user_name = ucwords(str_replace('.', ' ', $name_part));
} else {
    $user_name = "User";
}

$user_role = isset($_SESSION['user_role']) ? ucfirst($_SESSION['user_role']) : 'Member';

// Get first name for personal greeting
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

// Get user stats from database
$active_habits = 0;
$current_streak = 0;
$time_tracked = 0;
$completed_today = 0;

// Get active habits count
$habits_sql = "SELECT COUNT(*) as count FROM life_habits WHERE user_id = $user_id AND is_active = 1";
$habits_result = mysqli_query($conn, $habits_sql);
if ($habits_result) {
    $habits_row = mysqli_fetch_assoc($habits_result);
    $active_habits = $habits_row['count'];
}

// Get longest current streak
$streak_sql = "SELECT MAX(current_streak) as streak FROM life_habits WHERE user_id = $user_id";
$streak_result = mysqli_query($conn, $streak_sql);
if ($streak_result) {
    $streak_row = mysqli_fetch_assoc($streak_result);
    $current_streak = $streak_row['streak'] ?: 0;
}

// Get total time tracked in hours
$time_sql = "SELECT SUM(duration_minutes) as total_minutes FROM life_time_tracking WHERE user_id = $user_id";
$time_result = mysqli_query($conn, $time_sql);
if ($time_result) {
    $time_row = mysqli_fetch_assoc($time_result);
    $time_tracked = $time_row['total_minutes'] ? round($time_row['total_minutes'] / 60, 1) : 0;
}

// Get habits completed today
$today = date('Y-m-d');
$completed_sql = "SELECT COUNT(DISTINCT hl.habit_id) as count FROM habit_logs hl 
                  JOIN life_habits lh ON hl.habit_id = lh.id 
                  WHERE hl.user_id = $user_id AND hl.completed_date = '$today'";
$completed_result = mysqli_query($conn, $completed_sql);
if ($completed_result) {
    $completed_row = mysqli_fetch_assoc($completed_result);
    $completed_today = $completed_row['count'];
}

// Get motivational quotes
$quotes_sql = "SELECT content FROM life_business_tips WHERE is_featured = 1 
               UNION 
               SELECT content FROM life_health_tips WHERE is_featured = 1
               ORDER BY RAND() LIMIT 1";
$quotes_result = mysqli_query($conn, $quotes_sql);
if ($quotes_result && mysqli_num_rows($quotes_result) > 0) {
    $quote_row = mysqli_fetch_assoc($quotes_result);
    $daily_quote = $quote_row['content'];
} else {
    // Fallback quotes
    $quotes = [
        "Small daily improvements lead to stunning results.",
        "Your habits determine your future. Choose them wisely.",
        "Success is the sum of small efforts, repeated day in and day out."
    ];
    $daily_quote = $quotes[array_rand($quotes)];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>LIFE Hacks - FYBS Youth App</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#10b981">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="manifest.json">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary-color: #10b981;
            --primary-light: #34d399;
            --primary-dark: #059669;
            --secondary-color: #8b5cf6;
            --accent-color: #f59e0b;
            --dark-color: #1f2937;
            --light-color: #f9fafb;
            --gradient-primary: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            --gradient-secondary: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);
            --gradient-warning: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
            --gradient-danger: linear-gradient(135deg, #ef4444 0%, #f87171 100%);
            --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
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
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--primary-light);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
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
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
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
            background: var(--gradient-secondary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
            box-shadow: var(--shadow-md);
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 14px;
            color: var(--dark-color);
        }
        
        .user-role {
            font-size: 12px;
            color: #6b7280;
            font-weight: 500;
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
            animation: fadeInDown 0.8s ease;
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
        
        .greeting-text {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .greeting-icon {
            font-size: 28px;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        
        .greeting-title {
            font-family: 'Poppins', sans-serif;
            font-size: 24px;
            font-weight: 700;
            color: var(--dark-color);
        }
        
        .greeting-subtitle {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 15px;
        }
        
        .date-info {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 15px;
        }
        
        .daily-quote {
            background: #f0fdf4;
            border-left: 4px solid var(--primary-color);
            padding: 12px;
            border-radius: 8px;
            font-style: italic;
            color: #065f46;
            margin-top: 15px;
            font-size: 14px;
        }
        
        /* Quick Stats */
        .stats-section {
            margin-bottom: 25px;
            animation: fadeInUp 0.8s ease 0.2s both;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--shadow-md);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            text-align: center;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
        }
        
        .stat-card:nth-child(1)::before {
            background: var(--gradient-primary);
        }
        
        .stat-card:nth-child(2)::before {
            background: var(--gradient-secondary);
        }
        
        .stat-card:nth-child(3)::before {
            background: var(--gradient-warning);
        }
        
        .stat-card:nth-child(4)::before {
            background: #6366f1;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 12px;
            color: #6b7280;
            font-weight: 500;
        }
        
        /* Quick Actions */
        .actions-section {
            margin-bottom: 30px;
            animation: fadeInUp 0.8s ease 0.4s both;
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
        
        .section-title i {
            color: var(--primary-color);
        }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        
        @media (max-width: 768px) {
            .actions-grid {
                grid-template-columns: repeat(2, 1fr);
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
            position: relative;
            overflow: hidden;
            cursor: pointer;
            text-align: center;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            color: inherit;
        }
        
        .action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(52, 211, 153, 0.05) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .action-card:hover::before {
            opacity: 1;
        }
        
        .action-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin: 0 auto 15px;
            color: white;
            box-shadow: var(--shadow-md);
        }
        
        .action-card:nth-child(1) .action-icon {
            background: var(--gradient-primary);
        }
        
        .action-card:nth-child(2) .action-icon {
            background: var(--gradient-secondary);
        }
        
        .action-card:nth-child(3) .action-icon {
            background: var(--gradient-warning);
        }
        
        .action-card:nth-child(4) .action-icon {
            background: #6366f1;
        }
        
        .action-card:nth-child(5) .action-icon {
            background: #10b981;
        }
        
        .action-card:nth-child(6) .action-icon {
            background: #8b5cf6;
        }
        
        .action-title {
            font-family: 'Poppins', sans-serif;
            font-size: 15px;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
        }
        
        /* Tab Content */
        .tab-content {
            display: none;
            animation: fadeIn 0.5s ease;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Habit Cards */
        .habit-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: var(--shadow-md);
            border: 1px solid #e5e7eb;
            position: relative;
            overflow: hidden;
        }
        
        .habit-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }
        
        .habit-card.health::before {
            background: var(--gradient-primary);
        }
        
        .habit-card.productivity::before {
            background: var(--gradient-secondary);
        }
        
        .habit-card.spiritual::before {
            background: #f59e0b;
        }
        
        .habit-card.business::before {
            background: #8b5cf6;
        }
        
        .habit-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .habit-title {
            font-weight: 600;
            color: var(--dark-color);
            font-size: 16px;
        }
        
        .habit-category {
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 20px;
            background: #f0fdf4;
            color: #065f46;
            font-weight: 500;
        }
        
        .habit-progress {
            margin-bottom: 15px;
        }
        
        .progress-bar {
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .progress-fill.health {
            background: var(--gradient-primary);
        }
        
        .progress-fill.productivity {
            background: var(--gradient-secondary);
        }
        
        .habit-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .habit-streak {
            font-size: 12px;
            color: #6b7280;
            font-weight: 500;
        }
        
        .habit-streak i {
            color: #f59e0b;
            margin-right: 5px;
        }
        
        .btn-success {
            background: var(--gradient-primary);
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        /* Time Tracker */
        .timer-display {
            font-size: 48px;
            font-weight: 700;
            text-align: center;
            color: var(--primary-color);
            margin: 20px 0;
            font-family: 'Poppins', sans-serif;
        }
        
        .timer-controls {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .btn-timer {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            border: none;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-timer:hover {
            transform: scale(1.1);
            box-shadow: var(--shadow-lg);
        }
        
        .btn-timer.start {
            background: var(--gradient-primary);
        }
        
        .btn-timer.pause {
            background: var(--gradient-warning);
        }
        
        .btn-timer.stop {
            background: var(--gradient-danger);
        }
        
        /* Business Tips */
        .tip-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: var(--shadow-md);
            border: 1px solid #e5e7eb;
            position: relative;
            overflow: hidden;
        }
        
        .tip-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--gradient-warning);
        }
        
        .tip-title {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .tip-content {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.6;
        }
        
        /* Health Metrics */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        
        .metric-card {
            background: white;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            border: 1px solid #e5e7eb;
        }
        
        .metric-value {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .metric-label {
            font-size: 12px;
            color: #6b7280;
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
            background: rgba(16, 185, 129, 0.1);
        }
        
        .nav-icon {
            font-size: 20px;
            margin-bottom: 4px;
        }
        
        .nav-label {
            font-size: 12px;
            font-weight: 500;
        }
        
        /* Modal Styling */
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: var(--shadow-xl);
        }
        
        .modal-header {
            border-bottom: 1px solid #e5e7eb;
            padding: 20px;
        }
        
        .modal-title {
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .modal-body {
            padding: 20px;
        }
        
        /* Animations */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }
            
            .user-profile {
                width: 100%;
                justify-content: center;
            }
            
            .actions-grid {
                grid-template-columns: 1fr;
            }
            
            .greeting-title {
                font-size: 22px;
            }
            
            .main-container {
                padding: 20px 15px 100px;
            }
            
            .welcome-card {
                padding: 20px;
            }
            
            .section-title {
                font-size: 20px;
            }
            
            .timer-display {
                font-size: 36px;
            }
            
            .metrics-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Dark Mode */
        @media (prefers-color-scheme: dark) {
            body {
                background: #111827;
                color: #f9fafb;
            }
            
            .main-header, .welcome-card, .stat-card, 
            .action-card, .habit-card, .tip-card,
            .metric-card, .modal-content {
                background: #1f2937;
                border-color: #374151;
            }
            
            .user-name, .greeting-title, .section-title,
            .action-title, .habit-title, .tip-title,
            .stat-value, .metric-value {
                color: #f9fafb;
            }
            
            .greeting-subtitle, .user-role, .stat-label,
            .habit-streak, .tip-content, .metric-label {
                color: #d1d5db;
            }
            
            .date-info {
                color: #9ca3af;
            }
            
            .daily-quote {
                background: #064e3b;
                color: #a7f3d0;
            }
            
            .user-profile:hover {
                background: #374151;
            }
            
            .progress-bar {
                background: #374151;
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
                background: rgba(52, 211, 153, 0.1);
            }
            
            .habit-category {
                background: #064e3b;
                color: #a7f3d0;
            }
        }
        
        /* PWA Specific */
        @media (display-mode: standalone) {
            .main-header {
                padding-top: max(20px, env(safe-area-inset-top));
            }
            
            .bottom-nav {
                padding-bottom: max(12px, env(safe-area-inset-bottom));
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
                    <i class="fas fa-bolt"></i>
                </div>
                <div class="logo-text">LIFE Hacks</div>
            </div>
            
            <div class="user-profile" onclick="window.location.href='profile.php'">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($first_name); ?></div>
                    <div class="user-role">LIFE Master</div>
                </div>
                <i class="fas fa-chevron-right text-muted"></i>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="main-container">
        <!-- Welcome Section -->
        <section class="welcome-section">
            <div class="welcome-card">
                <div class="greeting-text">
                    <span class="greeting-icon"><?php echo $greeting_icon; ?></span>
                    <h1 class="greeting-title">LIFE Hacks, <?php echo htmlspecialchars($first_name); ?>!</h1>
                </div>
                <p class="greeting-subtitle">Transform your life with daily habits and practical wisdom for success.</p>
                
                <div class="date-info">
                    <i class="fas fa-calendar-day"></i>
                    <span><?php echo $day_of_week; ?>, <?php echo $date; ?></span>
                </div>
                
                <div class="daily-quote">
                    <i class="fas fa-quote-left me-2"></i>
                    <?php echo $daily_quote; ?>
                </div>
            </div>
        </section>
        
        <!-- Quick Stats -->
        <section class="stats-section">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value" id="activeHabits"><?php echo $active_habits; ?></div>
                    <div class="stat-label">Active Habits</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value" id="currentStreak"><?php echo $current_streak; ?>🔥</div>
                    <div class="stat-label">Day Streak</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value" id="timeTracked"><?php echo $time_tracked; ?>h</div>
                    <div class="stat-label">Time Tracked</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value" id="completedToday"><?php echo $completed_today; ?>/<?php echo $active_habits; ?></div>
                    <div class="stat-label">Today's Goals</div>
                </div>
            </div>
        </section>
        
        <!-- Quick Actions -->
        <section class="actions-section">
            <h2 class="section-title">
                <i class="fas fa-bolt"></i>
                Life Areas
            </h2>
            
            <div class="actions-grid">
                <div class="action-card" onclick="showTab('habits')">
                    <div class="action-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="action-title">Habit Tracker</h3>
                </div>
                
                <div class="action-card" onclick="showTab('time')">
                    <div class="action-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="action-title">Time Tracker</h3>
                </div>
                
                <div class="action-card" onclick="showTab('business')">
                    <div class="action-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <h3 class="action-title">Business</h3>
                </div>
                
                <div class="action-card" onclick="showTab('health')">
                    <div class="action-icon">
                        <i class="fas fa-heartbeat"></i>
                    </div>
                    <h3 class="action-title">Health</h3>
                </div>
                
                <div class="action-card" onclick="showTab('knowledge')">
                    <div class="action-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <h3 class="action-title">Knowledge</h3>
                </div>
                
                <div class="action-card" onclick="showTab('analytics')">
                    <div class="action-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3 class="action-title">Analytics</h3>
                </div>
            </div>
        </section>
        
        <!-- Tab Content -->
        <div id="tabContent">
            <!-- Habits Tab (Default) -->
            <div class="tab-content active" id="habitsTab">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="section-title">
                        <i class="fas fa-chart-line"></i>
                        My Habits
                    </h3>
                    <button class="btn btn-success" onclick="showAddHabitModal()">
                        <i class="fas fa-plus me-1"></i>Add Habit
                    </button>
                </div>
                
                <div id="habitsList">
                    <?php
                    // Load habits from database
                    $user_habits_sql = "SELECT * FROM life_habits WHERE user_id = $user_id AND is_active = 1 ORDER BY created_at DESC";
                    $user_habits_result = mysqli_query($conn, $user_habits_sql);
                    
                    if (mysqli_num_rows($user_habits_result) > 0) {
                        while ($habit = mysqli_fetch_assoc($user_habits_result)) {
                            // Check if habit is completed today
                            $today_check_sql = "SELECT id FROM habit_logs WHERE habit_id = {$habit['id']} AND completed_date = '$today'";
                            $today_check_result = mysqli_query($conn, $today_check_sql);
                            $is_completed_today = mysqli_num_rows($today_check_result) > 0;
                            $progress = $is_completed_today ? 100 : 0;
                            
                            // Get category color
                            $category_class = $habit['category'];
                            $category_names = [
                                'health' => 'Health',
                                'productivity' => 'Productivity',
                                'spiritual' => 'Spiritual',
                                'business' => 'Business',
                                'finance' => 'Finance'
                            ];
                            ?>
                            <div class="habit-card <?php echo $category_class; ?>">
                                <div class="habit-header">
                                    <div class="habit-title"><?php echo htmlspecialchars($habit['habit_name'] ?: $habit['name']); ?></div>
                                    <span class="habit-category"><?php echo $category_names[$habit['category']]; ?></span>
                                </div>
                                <div class="habit-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill <?php echo $category_class; ?>" style="width: <?php echo $progress; ?>%"></div>
                                    </div>
                                </div>
                                <div class="habit-actions">
                                    <div class="habit-streak">
                                        <i class="fas fa-fire"></i> <?php echo $habit['current_streak']; ?> day streak
                                    </div>
                                    <button class="btn btn-success btn-sm" onclick="completeHabit(<?php echo $habit['id']; ?>)">
                                        <i class="fas fa-check"></i> <?php echo $is_completed_today ? 'Completed' : 'Complete'; ?>
                                    </button>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<div class="text-center py-5">
                                <div class="mb-3">
                                    <i class="fas fa-chart-line fa-3x text-muted"></i>
                                </div>
                                <p class="text-muted">No habits yet. Start by adding your first habit!</p>
                            </div>';
                    }
                    ?>
                </div>
                
                <div class="mt-4">
                    <h6 class="fw-bold mb-3">Filter by Category</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-sm btn-outline-success active" onclick="filterHabits('all')">All</button>
                        <button class="btn btn-sm btn-outline-primary" onclick="filterHabits('health')">Health</button>
                        <button class="btn btn-sm btn-outline-warning" onclick="filterHabits('productivity')">Productivity</button>
                        <button class="btn btn-sm btn-outline-info" onclick="filterHabits('spiritual')">Spiritual</button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="filterHabits('business')">Business</button>
                    </div>
                </div>
            </div>
            
            <!-- Time Tracker Tab -->
            <div class="tab-content" id="timeTab">
                <h3 class="section-title">
                    <i class="fas fa-clock"></i>
                    Time Tracking
                </h3>
                
                <!-- Timer Display -->
                <div class="timer-display" id="timerDisplay">00:00:00</div>
                
                <!-- Timer Controls -->
                <div class="timer-controls">
                    <button class="btn-timer start" onclick="startTimer()">
                        <i class="fas fa-play"></i>
                    </button>
                    <button class="btn-timer pause" onclick="pauseTimer()">
                        <i class="fas fa-pause"></i>
                    </button>
                    <button class="btn-timer stop" onclick="stopTimer()">
                        <i class="fas fa-stop"></i>
                    </button>
                </div>
                
                <!-- Activity Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form id="timeTrackingForm" onsubmit="saveTimeEntry(event)">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Activity</label>
                                    <input type="text" class="form-control" id="activityName" 
                                           placeholder="What are you working on?" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Category</label>
                                    <select class="form-select" id="activityCategory" required>
                                        <option value="work">Work</option>
                                        <option value="study">Study</option>
                                        <option value="exercise">Exercise</option>
                                        <option value="prayer">Prayer</option>
                                        <option value="business">Business</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-save me-2"></i>Save Time Entry
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Recent Entries -->
                <h6 class="fw-bold mb-3">Recent Time Entries</h6>
                <div id="timeEntries">
                    <?php
                    // Load recent time entries
                    $time_entries_sql = "SELECT * FROM life_time_tracking 
                                        WHERE user_id = $user_id 
                                        ORDER BY created_at DESC 
                                        LIMIT 10";
                    $time_entries_result = mysqli_query($conn, $time_entries_sql);
                    
                    if (mysqli_num_rows($time_entries_result) > 0) {
                        while ($entry = mysqli_fetch_assoc($time_entries_result)) {
                            $duration = $entry['duration_minutes'] ? round($entry['duration_minutes'] / 60, 1) . 'h' : 'Ongoing';
                            $category_icons = [
                                'work' => '💼',
                                'study' => '📚',
                                'exercise' => '🏋️',
                                'prayer' => '🙏',
                                'business' => '💼',
                                'leisure' => '🎮'
                            ];
                            ?>
                            <div class="habit-card">
                                <div class="habit-header">
                                    <div class="habit-title">
                                        <?php echo $category_icons[$entry['category']] ?? '📝'; ?>
                                        <?php echo htmlspecialchars($entry['activity_name']); ?>
                                    </div>
                                    <span class="habit-category"><?php echo ucfirst($entry['category']); ?></span>
                                </div>
                                <div class="habit-progress">
                                    <small class="text-muted">
                                        <?php echo date('M j, g:i A', strtotime($entry['start_time'])); ?>
                                        <?php if ($entry['end_time']): ?>
                                            - <?php echo date('g:i A', strtotime($entry['end_time'])); ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <div class="habit-actions">
                                    <div class="habit-streak">
                                        Duration: <?php echo $duration; ?>
                                    </div>
                                    <?php if ($entry['notes']): ?>
                                        <small class="text-muted"><?php echo htmlspecialchars($entry['notes']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<div class="text-center py-3">
                                <div class="mb-2">
                                    <i class="fas fa-clock fa-2x text-muted"></i>
                                </div>
                                <p class="text-muted">No time entries yet. Start tracking your time!</p>
                            </div>';
                    }
                    ?>
                </div>
            </div>
            
            <!-- Business Tab -->
            <div class="tab-content" id="businessTab">
                <h3 class="section-title">
                    <i class="fas fa-briefcase"></i>
                    Business Tools
                </h3>
                
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="action-card" onclick="generateBusinessIdea()">
                            <div class="action-icon">
                                <i class="fas fa-lightbulb"></i>
                            </div>
                            <h3 class="action-title">Startup Ideas</h3>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="action-card" onclick="loadBusinessTips()">
                            <div class="action-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h3 class="action-title">Business Tips</h3>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="action-card" onclick="showFinanceTools()">
                            <div class="action-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <h3 class="action-title">Finance Tools</h3>
                        </div>
                    </div>
                </div>
                
                <div id="businessTipsContainer">
                    <?php
                    // Load business tips
                    $business_tips_sql = "SELECT * FROM life_business_tips ORDER BY is_featured DESC, created_at DESC LIMIT 5";
                    $business_tips_result = mysqli_query($conn, $business_tips_sql);
                    
                    if (mysqli_num_rows($business_tips_result) > 0) {
                        echo '<h6 class="fw-bold mb-3">Business Tips</h6>';
                        while ($tip = mysqli_fetch_assoc($business_tips_result)) {
                            ?>
                            <div class="tip-card">
                                <div class="tip-title"><?php echo htmlspecialchars($tip['title']); ?></div>
                                <div class="tip-content"><?php echo htmlspecialchars($tip['content']); ?></div>
                                <small class="text-muted mt-2 d-block">
                                    <i class="fas fa-tag me-1"></i><?php echo ucfirst($tip['category']); ?>
                                    <?php if ($tip['author']): ?>
                                        <i class="fas fa-user ms-3 me-1"></i><?php echo htmlspecialchars($tip['author']); ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
            </div>
            
            <!-- Health Tab -->
            <div class="tab-content" id="healthTab">
                <h3 class="section-title">
                    <i class="fas fa-heartbeat"></i>
                    Health & Wellness
                </h3>
                
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title">🏋️‍♂️ Exercise Planner</h6>
                                <?php
                                // Get exercise tips
                                $exercise_tips_sql = "SELECT * FROM life_health_tips WHERE category = 'exercise' ORDER BY RAND() LIMIT 1";
                                $exercise_result = mysqli_query($conn, $exercise_tips_sql);
                                $exercise_tip = mysqli_fetch_assoc($exercise_result);
                                ?>
                                <div id="exercisePlan">
                                    <p class="small"><?php echo $exercise_tip ? htmlspecialchars($exercise_tip['content']) : 'Today\'s workout: 15 min cardio + 10 min strength'; ?></p>
                                </div>
                                <button class="btn btn-sm btn-success mt-2" onclick="generateWorkout()">
                                    New Workout
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title">🥗 Nutrition Guide</h6>
                                <?php
                                // Get nutrition tips
                                $nutrition_tips_sql = "SELECT * FROM life_health_tips WHERE category = 'nutrition' ORDER BY RAND() LIMIT 1";
                                $nutrition_result = mysqli_query($conn, $nutrition_tips_sql);
                                $nutrition_tip = mysqli_fetch_assoc($nutrition_result);
                                ?>
                                <div id="nutritionTip">
                                    <p class="small"><?php echo $nutrition_tip ? htmlspecialchars($nutrition_tip['content']) : 'Drink 8 glasses of water daily'; ?></p>
                                </div>
                                <button class="btn btn-sm btn-success mt-2" onclick="showNutritionTips()">
                                    More Tips
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Health Metrics -->
                <div class="metrics-grid">
                    <?php
                    // Calculate health metrics from habits
                    $water_sql = "SELECT COUNT(*) as count FROM habit_logs hl 
                                 JOIN life_habits lh ON hl.habit_id = lh.id 
                                 WHERE hl.user_id = $user_id AND hl.completed_date = '$today' 
                                 AND (lh.habit_name LIKE '%water%' OR lh.name LIKE '%water%')";
                    $water_result = mysqli_query($conn, $water_sql);
                    $water_count = $water_result ? mysqli_fetch_assoc($water_result)['count'] : 0;
                    
                    $exercise_sql = "SELECT COUNT(*) as count FROM habit_logs hl 
                                    JOIN life_habits lh ON hl.habit_id = lh.id 
                                    WHERE hl.user_id = $user_id AND hl.completed_date = '$today' 
                                    AND (lh.habit_name LIKE '%exercise%' OR lh.name LIKE '%exercise%' OR lh.category = 'health')";
                    $exercise_result = mysqli_query($conn, $exercise_sql);
                    $exercise_count = $exercise_result ? mysqli_fetch_assoc($exercise_result)['count'] : 0;
                    
                    // This would need actual sleep tracking data - for now, estimate based on habits
                    $sleep_sql = "SELECT COUNT(*) as count FROM habit_logs hl 
                                 JOIN life_habits lh ON hl.habit_id = lh.id 
                                 WHERE hl.user_id = $user_id AND hl.completed_date = '$today' 
                                 AND (lh.habit_name LIKE '%sleep%' OR lh.name LIKE '%sleep%' OR lh.habit_name LIKE '%rest%')";
                    $sleep_result = mysqli_query($conn, $sleep_sql);
                    $sleep_done = $sleep_result ? mysqli_fetch_assoc($sleep_result)['count'] : 0;
                    $sleep_hours = $sleep_done > 0 ? '8' : '0';
                    ?>
                    <div class="metric-card">
                        <div class="metric-value text-success" id="waterCount"><?php echo $water_count; ?></div>
                        <div class="metric-label">Water Today</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value text-primary" id="stepsCount"><?php echo $exercise_count; ?></div>
                        <div class="metric-label">Workouts Today</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value text-warning" id="sleepHours"><?php echo $sleep_hours; ?></div>
                        <div class="metric-label">Hours Slept</div>
                    </div>
                </div>
            </div>
            
            <!-- Knowledge Base Tab -->
            <div class="tab-content" id="knowledgeTab">
                <h3 class="section-title">
                    <i class="fas fa-book"></i>
                    Knowledge Base
                </h3>
                
                <div class="row">
                    <div class="col-md-8">
                        <div id="knowledgeArticles">
                            <?php
                            // Load knowledge articles
                            $knowledge_sql = "SELECT * FROM life_knowledge_base ORDER BY created_at DESC LIMIT 10";
                            $knowledge_result = mysqli_query($conn, $knowledge_sql);
                            
                            if (mysqli_num_rows($knowledge_result) > 0) {
                                while ($article = mysqli_fetch_assoc($knowledge_result)) {
                                    $category_icons = [
                                        'business' => '💼',
                                        'health' => '🏥',
                                        'productivity' => '⚡',
                                        'finance' => '💰',
                                        'spiritual' => '🙏'
                                    ];
                                    ?>
                                    <div class="habit-card">
                                        <div class="habit-header">
                                            <div class="habit-title">
                                                <?php echo $category_icons[$article['category']] ?? '📚'; ?>
                                                <?php echo htmlspecialchars($article['title']); ?>
                                            </div>
                                            <span class="habit-category"><?php echo ucfirst($article['category']); ?></span>
                                        </div>
                                        <div class="habit-progress">
                                            <p class="mb-0"><?php echo htmlspecialchars(substr($article['content'], 0, 150)); ?>...</p>
                                        </div>
                                        <div class="habit-actions">
                                            <div class="habit-streak">
                                                <i class="fas fa-eye me-1"></i><?php echo $article['views']; ?> views
                                            </div>
                                            <small class="text-muted">
                                                <?php echo date('M j, Y', strtotime($article['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <?php
                                }
                            } else {
                                echo '<div class="text-center py-4">
                                        <div class="mb-3">
                                            <i class="fas fa-book fa-3x text-muted"></i>
                                        </div>
                                        <p class="text-muted">No knowledge articles yet.</p>
                                    </div>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">🔍 Search Knowledge</h6>
                                <input type="text" class="form-control mb-3" placeholder="Search articles..." 
                                       onkeyup="searchKnowledge(this.value)">
                                <h6 class="card-title mt-3">🏷️ Categories</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php
                                    $categories_sql = "SELECT category, COUNT(*) as count FROM life_knowledge_base GROUP BY category";
                                    $categories_result = mysqli_query($conn, $categories_sql);
                                    $category_colors = [
                                        'business' => 'success',
                                        'health' => 'primary',
                                        'productivity' => 'warning',
                                        'finance' => 'info',
                                        'spiritual' => 'secondary'
                                    ];
                                    
                                    while ($cat = mysqli_fetch_assoc($categories_result)) {
                                        $color = $category_colors[$cat['category']] ?? 'secondary';
                                        echo '<span class="badge bg-' . $color . '">' . ucfirst($cat['category']) . ' (' . $cat['count'] . ')</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Analytics Tab -->
            <div class="tab-content" id="analyticsTab">
                <h3 class="section-title">
                    <i class="fas fa-chart-bar"></i>
                    Analytics
                </h3>
                
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title">📈 Habit Completion</h6>
                                <canvas id="habitChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title">⏱️ Time Distribution</h6>
                                <canvas id="timeChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Monthly Report -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">📅 Monthly Report</h6>
                        <div class="row" id="monthlyReport">
                            <?php
                            // Get monthly stats
                            $month = date('Y-m');
                            $monthly_habits_sql = "SELECT COUNT(DISTINCT hl.habit_id) as habits_completed, 
                                                  COUNT(hl.id) as total_completions,
                                                  DATE(hl.completed_date) as date
                                                  FROM habit_logs hl
                                                  JOIN life_habits lh ON hl.habit_id = lh.id
                                                  WHERE hl.user_id = $user_id 
                                                  AND hl.completed_date LIKE '$month%'
                                                  GROUP BY DATE(hl.completed_date)
                                                  ORDER BY hl.completed_date DESC
                                                  LIMIT 7";
                            $monthly_result = mysqli_query($conn, $monthly_habits_sql);
                            
                            if (mysqli_num_rows($monthly_result) > 0) {
                                echo '<div class="col-12"><table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Habits Completed</th>
                                                <th>Total Completions</th>
                                            </tr>
                                        </thead>
                                        <tbody>';
                                
                                while ($row = mysqli_fetch_assoc($monthly_result)) {
                                    echo '<tr>
                                            <td>' . date('M j', strtotime($row['date'])) . '</td>
                                            <td>' . $row['habits_completed'] . '</td>
                                            <td>' . $row['total_completions'] . '</td>
                                          </tr>';
                                }
                                
                                echo '</tbody></table></div>';
                            } else {
                                echo '<div class="text-center py-3">
                                        <p class="text-muted">No data for this month yet.</p>
                                      </div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <div class="nav-items">
            <a href="index.php" class="nav-item">
                <i class="fas fa-home nav-icon"></i>
                <span class="nav-label">Home</span>
            </a>
            
            <a href="fybs.php" class="nav-item">
                <i class="fas fa-book-bible nav-icon"></i>
                <span class="nav-label">FYBS</span>
            </a>
            
            <a href="gyc.php" class="nav-item">
                <i class="fas fa-comments nav-icon"></i>
                <span class="nav-label">GYC</span>
            </a>
            
            <a href="life.php" class="nav-item active">
                <i class="fas fa-bolt nav-icon"></i>
                <span class="nav-label">LIFE</span>
            </a>
            
            <a href="profile.php" class="nav-item">
                <i class="fas fa-user nav-icon"></i>
                <span class="nav-label">Profile</span>
            </a>
        </div>
    </nav>

    <!-- Modals -->
    <!-- Add Habit Modal -->
    <div class="modal fade" id="addHabitModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Habit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addHabitForm" onsubmit="saveHabit(event)">
                        <div class="mb-3">
                            <label class="form-label">Habit Name</label>
                            <input type="text" class="form-control" id="habitName" 
                                   placeholder="e.g., Morning Prayer, Exercise, Reading" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" id="habitCategory" required>
                                <option value="health">Health & Fitness</option>
                                <option value="productivity">Productivity</option>
                                <option value="spiritual">Spiritual Growth</option>
                                <option value="business">Business</option>
                                <option value="finance">Finance</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Frequency</label>
                            <select class="form-select" id="habitFrequency" required>
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Target (per session)</label>
                            <input type="number" class="form-control" id="habitTarget" 
                                   value="1" min="1" max="10">
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-plus me-2"></i>Create Habit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Add Modal -->
    <div class="modal fade" id="quickAddModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-body p-0">
                    <div class="list-group list-group-flush">
                        <a href="#" class="list-group-item list-group-item-action" onclick="quickAdd('water')">
                            <i class="fas fa-glass-water me-2 text-primary"></i>Drink Water
                        </a>
                        <a href="#" class="list-group-item list-group-item-action" onclick="quickAdd('prayer')">
                            <i class="fas fa-pray me-2 text-success"></i>Prayer Time
                        </a>
                        <a href="#" class="list-group-item list-group-item-action" onclick="quickAdd('exercise')">
                            <i class="fas fa-running me-2 text-warning"></i>Quick Exercise
                        </a>
                        <a href="#" class="list-group-item list-group-item-action" onclick="quickAdd('reading')">
                            <i class="fas fa-book me-2 text-info"></i>Read 10 Pages
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize charts
            initializeCharts();
            
            // Add animations
            const elements = document.querySelectorAll('.stat-card, .action-card');
            elements.forEach((el, index) => {
                el.style.animation = 'fadeInUp 0.6s ease forwards';
                el.style.animationDelay = `${index * 0.1}s`;
                el.style.opacity = '0';
            });
            
            // Update greeting based on time
            function updateGreeting() {
                const hour = new Date().getHours();
                const greetingElement = document.querySelector('.greeting-title');
                const greetingIcon = document.querySelector('.greeting-icon');
                
                if (greetingElement && greetingIcon) {
                    if (hour < 12) {
                        greetingElement.innerHTML = `LIFE Hacks, <?php echo htmlspecialchars($first_name); ?>!`;
                        greetingIcon.textContent = '☀️';
                    } else if (hour < 17) {
                        greetingElement.innerHTML = `LIFE Hacks, <?php echo htmlspecialchars($first_name); ?>!`;
                        greetingIcon.textContent = '🌤️';
                    } else {
                        greetingElement.innerHTML = `LIFE Hacks, <?php echo htmlspecialchars($first_name); ?>!`;
                        greetingIcon.textContent = '🌙';
                    }
                }
            }
            
            setInterval(updateGreeting, 60000);
        });
        
        // Tab switching function
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + 'Tab').classList.add('active');
            
            // Update button states
            document.querySelectorAll('.action-card').forEach(card => {
                card.classList.remove('active');
            });
            
            // Reinitialize charts when analytics tab is shown
            if (tabName === 'analytics') {
                setTimeout(() => {
                    initializeCharts();
                }, 100);
            }
        }
        
        // Timer functions
        let timerInterval;
        let seconds = 0;
        let isTimerRunning = false;
        let currentActivity = null;
        
        function startTimer() {
            if (!isTimerRunning) {
                isTimerRunning = true;
                const activityName = document.getElementById('activityName').value || 'General Activity';
                const category = document.getElementById('activityCategory').value || 'work';
                currentActivity = { name: activityName, category: category, start: new Date() };
                
                timerInterval = setInterval(() => {
                    seconds++;
                    updateTimerDisplay();
                }, 1000);
            }
        }
        
        function pauseTimer() {
            if (isTimerRunning) {
                clearInterval(timerInterval);
                isTimerRunning = false;
            }
        }
        
        function stopTimer() {
            clearInterval(timerInterval);
            isTimerRunning = false;
            
            if (currentActivity && seconds > 0) {
                saveTimeEntryFromTimer(currentActivity, seconds);
            }
            
            seconds = 0;
            updateTimerDisplay();
            currentActivity = null;
        }
        
        function updateTimerDisplay() {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            
            document.getElementById('timerDisplay').textContent = 
                `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }
        
        async function saveTimeEntryFromTimer(activity, totalSeconds) {
            const duration = Math.round(totalSeconds / 60);
            const formData = new FormData();
            formData.append('action', 'save_time_entry');
            formData.append('activity_name', activity.name);
            formData.append('category', activity.category);
            formData.append('duration_minutes', duration);
            formData.append('start_time', activity.start.toISOString());
            formData.append('end_time', new Date().toISOString());
            
            try {
                const response = await fetch('life-actions.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                if (result.success) {
                    showToast('Time entry saved successfully!', 'success');
                    // Reload time entries
                    location.reload();
                } else {
                    showToast(result.message || 'Failed to save time entry', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error. Please try again.', 'error');
            }
        }
        
        async function saveTimeEntry(event) {
            event.preventDefault();
            
            const activityName = document.getElementById('activityName').value;
            const category = document.getElementById('activityCategory').value;
            
            if (!activityName) {
                showToast('Please enter an activity name', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'save_time_entry');
            formData.append('activity_name', activityName);
            formData.append('category', category);
            formData.append('duration_minutes', 0); // Manual entry
            
            try {
                const response = await fetch('life-actions.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                if (result.success) {
                    showToast('Time entry saved successfully!', 'success');
                    document.getElementById('timeTrackingForm').reset();
                    // Reload time entries
                    location.reload();
                } else {
                    showToast(result.message || 'Failed to save time entry', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error. Please try again.', 'error');
            }
        }
        
        // Modal functions
        function showAddHabitModal() {
            const modal = new bootstrap.Modal(document.getElementById('addHabitModal'));
            modal.show();
        }
        
        function showQuickAdd() {
            const modal = new bootstrap.Modal(document.getElementById('quickAddModal'));
            modal.show();
        }
        
        async function saveHabit(event) {
            event.preventDefault();
            
            const habitName = document.getElementById('habitName').value;
            const category = document.getElementById('habitCategory').value;
            const frequency = document.getElementById('habitFrequency').value;
            const target = document.getElementById('habitTarget').value;
            
            const formData = new FormData();
            formData.append('action', 'save_habit');
            formData.append('habit_name', habitName);
            formData.append('category', category);
            formData.append('frequency', frequency);
            formData.append('target_value', target);
            
            try {
                const response = await fetch('life-actions.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                if (result.success) {
                    showToast('Habit created successfully!', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('addHabitModal')).hide();
                    document.getElementById('addHabitForm').reset();
                    // Reload habits
                    location.reload();
                } else {
                    showToast(result.message || 'Failed to create habit', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error. Please try again.', 'error');
            }
        }
        
        async function completeHabit(habitId) {
            if (!confirm('Mark this habit as completed for today?')) return;
            
            const formData = new FormData();
            formData.append('action', 'complete_habit');
            formData.append('habit_id', habitId);
            
            try {
                const response = await fetch('life-actions.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                if (result.success) {
                    showToast('Habit completed!', 'success');
                    // Reload habits and stats
                    location.reload();
                } else {
                    showToast(result.message || 'Failed to complete habit', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error. Please try again.', 'error');
            }
        }
        
        // Initialize charts with real data
        function initializeCharts() {
            <?php
            // Get habit completion data for last 7 days
            $week_dates = [];
            $week_completions = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $week_dates[] = date('D', strtotime($date));
                
                $completion_sql = "SELECT COUNT(DISTINCT hl.habit_id) as count 
                                  FROM habit_logs hl 
                                  JOIN life_habits lh ON hl.habit_id = lh.id 
                                  WHERE hl.user_id = $user_id AND hl.completed_date = '$date'";
                $completion_result = mysqli_query($conn, $completion_sql);
                $completion = $completion_result ? mysqli_fetch_assoc($completion_result)['count'] : 0;
                $week_completions[] = $completion;
            }
            
            // Get time distribution by category
            $time_dist_sql = "SELECT category, SUM(duration_minutes) as total 
                             FROM life_time_tracking 
                             WHERE user_id = $user_id 
                             GROUP BY category";
            $time_dist_result = mysqli_query($conn, $time_dist_sql);
            $time_categories = [];
            $time_totals = [];
            $time_colors = [
                'work' => 'rgba(16, 185, 129, 0.8)',
                'study' => 'rgba(139, 92, 246, 0.8)',
                'exercise' => 'rgba(245, 158, 11, 0.8)',
                'prayer' => 'rgba(99, 102, 241, 0.8)',
                'business' => 'rgba(139, 92, 246, 0.8)',
                'leisure' => 'rgba(156, 163, 175, 0.8)'
            ];
            
            while ($row = mysqli_fetch_assoc($time_dist_result)) {
                $time_categories[] = ucfirst($row['category']);
                $time_totals[] = round($row['total'] / 60, 1);
            }
            ?>
            
            // Habit Chart
            const habitCtx = document.getElementById('habitChart')?.getContext('2d');
            if (habitCtx) {
                new Chart(habitCtx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($week_dates); ?>,
                        datasets: [{
                            label: 'Habits Completed',
                            data: <?php echo json_encode($week_completions); ?>,
                            backgroundColor: 'rgba(16, 185, 129, 0.8)',
                            borderColor: 'rgb(16, 185, 129)',
                            borderWidth: 1,
                            borderRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }
            
            // Time Chart
            const timeCtx = document.getElementById('timeChart')?.getContext('2d');
            if (timeCtx) {
                new Chart(timeCtx, {
                    type: 'doughnut',
                    data: {
                        labels: <?php echo json_encode($time_categories); ?>,
                        datasets: [{
                            data: <?php echo json_encode($time_totals); ?>,
                            backgroundColor: [
                                <?php 
                                foreach ($time_categories as $index => $cat) {
                                    $lower = strtolower($cat);
                                    echo "'" . ($time_colors[$lower] ?? 'rgba(156, 163, 175, 0.8)') . "',";
                                }
                                ?>
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        }
        
        // Quick add function
        async function quickAdd(type) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('quickAddModal'));
            modal.hide();
            
            const habitNames = {
                'water': 'Drink Water',
                'prayer': 'Prayer Time',
                'exercise': 'Quick Exercise',
                'reading': 'Read 10 Pages'
            };
            
            const categories = {
                'water': 'health',
                'prayer': 'spiritual',
                'exercise': 'health',
                'reading': 'productivity'
            };
            
            const formData = new FormData();
            formData.append('action', 'save_habit');
            formData.append('habit_name', habitNames[type]);
            formData.append('category', categories[type]);
            formData.append('frequency', 'daily');
            formData.append('target_value', 1);
            
            try {
                const response = await fetch('life-actions.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                if (result.success) {
                    showToast('Habit added successfully!', 'success');
                    // Reload habits
                    location.reload();
                } else {
                    showToast(result.message || 'Failed to add habit', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error. Please try again.', 'error');
            }
        }
        
        // Load business tips
        function loadBusinessTips() {
            // Already loaded from database
            document.getElementById('businessTipsContainer').scrollIntoView({ behavior: 'smooth' });
        }
        
        // Search knowledge
        function searchKnowledge(query) {
            // This would need AJAX call to search in database
            // For now, just filter existing content
            const articles = document.querySelectorAll('#knowledgeArticles .habit-card');
            articles.forEach(article => {
                const text = article.textContent.toLowerCase();
                article.style.display = text.includes(query.toLowerCase()) ? 'block' : 'none';
            });
        }
        
        // Toast notification
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `position-fixed top-0 end-0 p-3`;
            toast.style.zIndex = '9999';
            toast.innerHTML = `
                <div class="toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">${message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            document.body.appendChild(toast);
            
            const bsToast = new bootstrap.Toast(toast.querySelector('.toast'));
            bsToast.show();
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
    </script>
</body>
</html>