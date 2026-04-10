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

// Get stats from database
$online_count = 0;
$prayer_count = 0;
$testimony_count = 0;
$answered_count = 0;

// Get prayer count
$prayer_sql = "SELECT COUNT(*) as count FROM prayer_requests";
$prayer_result = mysqli_query($conn, $prayer_sql);
if ($prayer_result) {
    $prayer_row = mysqli_fetch_assoc($prayer_result);
    $prayer_count = $prayer_row['count'];
}

// Get testimony count
$testimony_sql = "SELECT COUNT(*) as count FROM testimonies";
$testimony_result = mysqli_query($conn, $testimony_sql);
if ($testimony_result) {
    $testimony_row = mysqli_fetch_assoc($testimony_result);
    $testimony_count = $testimony_row['count'];
}

// Get answered prayers count
$answered_sql = "SELECT COUNT(*) as count FROM prayer_requests WHERE status = 'answered'";
$answered_result = mysqli_query($conn, $answered_sql);
if ($answered_result) {
    $answered_row = mysqli_fetch_assoc($answered_result);
    $answered_count = $answered_row['count'];
}

// Get online users (users active in last 5 minutes) - FIXED: Use admin_id instead of user_id
$online_sql = "SELECT COUNT(DISTINCT admin_id) as count FROM admin_logs 
               WHERE created_at >= NOW() - INTERVAL 5 MINUTE";
$online_result = mysqli_query($conn, $online_sql);
if ($online_result) {
    $online_row = mysqli_fetch_assoc($online_result);
    $online_count = $online_row['count'] ?: 1; // At least current user
}

// Get daily Bible verse
$verses = [
    "For where two or three gather in my name, there am I with them. - Matthew 18:20",
    "Pray without ceasing. - 1 Thessalonians 5:17",
    "Rejoice always, pray continually, give thanks in all circumstances. - 1 Thessalonians 5:16-18",
    "Do not be anxious about anything, but in every situation, by prayer and petition, with thanksgiving, present your requests to God. - Philippians 4:6",
    "Therefore confess your sins to each other and pray for each other so that you may be healed. - James 5:16"
];
$daily_verse = $verses[date('z') % count($verses)];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>GYC - Global Youth Chat - FYBS Youth App</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#3b82f6">
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
            --primary-color: #3b82f6;
            --primary-light: #60a5fa;
            --primary-dark: #1d4ed8;
            --secondary-color: #10b981;
            --accent-color: #f59e0b;
            --dark-color: #1f2937;
            --light-color: #f9fafb;
            --gradient-primary: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            --gradient-secondary: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --gradient-warning: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --gradient-danger: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
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
        
        .daily-verse {
            background: #eff6ff;
            border-left: 4px solid var(--primary-color);
            padding: 12px;
            border-radius: 8px;
            font-style: italic;
            color: #1e40af;
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
            background: var(--gradient-danger);
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
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
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
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(29, 78, 216, 0.05) 100%);
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
            background: var(--gradient-danger);
        }
        
        .action-title {
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
        }
        
        .action-desc {
            font-size: 13px;
            color: #6b7280;
        }
        
        /* Tab Navigation */
        .tabs-container {
            margin-bottom: 25px;
            animation: fadeInUp 0.8s ease 0.6s both;
        }
        
        .tabs-nav {
            display: flex;
            overflow-x: auto;
            gap: 10px;
            padding-bottom: 10px;
            scrollbar-width: none;
        }
        
        .tabs-nav::-webkit-scrollbar {
            display: none;
        }
        
        .tab-btn {
            padding: 12px 20px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            font-weight: 500;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .tab-btn:hover {
            background: #f8fafc;
            color: var(--primary-color);
        }
        
        .tab-btn.active {
            background: var(--gradient-primary);
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .tab-btn i {
            font-size: 16px;
        }
        
        /* Tab Content */
        .tab-content {
            display: none;
            animation: fadeIn 0.5s ease;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Prayer & Testimony Cards */
        .content-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: var(--shadow-md);
            border: 1px solid #e5e7eb;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.5s ease;
        }
        
        .content-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }
        
        .content-card.prayer::before {
            background: var(--gradient-primary);
        }
        
        .content-card.testimony::before {
            background: var(--gradient-warning);
        }
        
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .content-title {
            font-weight: 600;
            color: var(--dark-color);
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .content-meta {
            font-size: 12px;
            color: #6b7280;
        }
        
        .content-body {
            margin-bottom: 15px;
            color: #4b5563;
            line-height: 1.6;
        }
        
        .content-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }
        
        .content-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-action {
            padding: 6px 15px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            background: white;
            color: #6b7280;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }
        
        .btn-action.pray {
            border-color: #dbeafe;
            background: #eff6ff;
            color: #1d4ed8;
        }
        
        .btn-action.pray:hover {
            background: #dbeafe;
        }
        
        .btn-action.encourage {
            border-color: #dcfce7;
            background: #f0fdf4;
            color: #059669;
        }
        
        .btn-action.encourage:hover {
            background: #dcfce7;
        }
        
        .btn-action.like {
            border-color: #fef3c7;
            background: #fffbeb;
            color: #d97706;
        }
        
        .btn-action.like:hover {
            background: #fef3c7;
        }
        
        .badge-status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            background: #dcfce7;
            color: #059669;
        }
        
        .badge-status.answered {
            background: #dcfce7;
            color: #059669;
        }
        
        .badge-status.pending {
            background: #fef3c7;
            color: #d97706;
        }
        
        /* Chat Container */
        .chat-container {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--shadow-md);
            border: 1px solid #e5e7eb;
            height: 400px;
            display: flex;
            flex-direction: column;
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding-right: 10px;
            margin-bottom: 15px;
        }
        
        .chat-message {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }
        
        .chat-message.self {
            align-items: flex-end;
        }
        
        .chat-message-content {
            max-width: 80%;
            padding: 12px 16px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
        }
        
        .chat-message.self .chat-message-content {
            background: var(--gradient-primary);
            color: white;
            border: none;
        }
        
        .chat-message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .chat-message-sender {
            font-weight: 600;
            font-size: 13px;
        }
        
        .chat-message-time {
            font-size: 11px;
            color: #9ca3af;
        }
        
        .chat-message.self .chat-message-time {
            color: rgba(255, 255, 255, 0.8);
        }
        
        .chat-input-group {
            display: flex;
            gap: 10px;
        }
        
        .chat-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #f8fafc;
            font-family: 'Inter', sans-serif;
        }
        
        .chat-input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: white;
        }
        
        .btn-send {
            padding: 12px 20px;
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-send:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
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
            background: rgba(59, 130, 246, 0.1);
        }
        
        .nav-icon {
            font-size: 20px;
            margin-bottom: 4px;
        }
        
        .nav-label {
            font-size: 12px;
            font-weight: 500;
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
            
            .tabs-nav {
                gap: 8px;
            }
            
            .tab-btn {
                padding: 10px 15px;
                font-size: 14px;
            }
            
            .chat-container {
                height: 350px;
            }
        }
        
        /* Dark Mode */
        @media (prefers-color-scheme: dark) {
            body {
                background: #111827;
                color: #f9fafb;
            }
            
            .main-header, .welcome-card, .stat-card, 
            .action-card, .content-card, .chat-container,
            .tab-btn, .modal-content {
                background: #1f2937;
                border-color: #374151;
            }
            
            .user-name, .greeting-title, .section-title,
            .action-title, .content-title, .chat-message-sender,
            .stat-value, .modal-title {
                color: #f9fafb;
            }
            
            .greeting-subtitle, .user-role, .stat-label,
            .action-desc, .content-meta, .content-body,
            .chat-message-time, .daily-verse {
                color: #d1d5db;
            }
            
            .date-info {
                color: #9ca3af;
            }
            
            .daily-verse {
                background: #1e3a8a;
                color: #dbeafe;
            }
            
            .user-profile:hover {
                background: #374151;
            }
            
            .tab-btn {
                background: #374151;
                border-color: #4b5563;
            }
            
            .tab-btn:hover {
                background: #4b5563;
            }
            
            .chat-message-content {
                background: #374151;
                border-color: #4b5563;
            }
            
            .chat-input {
                background: #374151;
                border-color: #4b5563;
                color: #f9fafb;
            }
            
            .chat-input:focus {
                background: #1f2937;
                border-color: var(--primary-light);
            }
            
            .btn-action {
                background: #374151;
                border-color: #4b5563;
                color: #d1d5db;
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
                background: rgba(59, 130, 246, 0.1);
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
        
        /* Loading Spinner */
        .loading-spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 3px solid rgba(59, 130, 246, 0.3);
            border-radius: 50%;
            border-top-color: var(--primary-color);
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Main Header -->
    <header class="main-header">
        <div class="header-content">
            <div class="app-logo">
                <div class="logo-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="logo-text">GYC</div>
            </div>
            
            <div class="user-profile" onclick="window.location.href='profile.php'">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($first_name); ?></div>
                    <div class="user-role">Community Member</div>
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
                    <h1 class="greeting-title">Welcome to GYC, <?php echo htmlspecialchars($first_name); ?>!</h1>
                </div>
                <p class="greeting-subtitle">Connect, share, pray, and grow together with the global youth community.</p>
                
                <div class="date-info">
                    <i class="fas fa-calendar-day"></i>
                    <span><?php echo $day_of_week; ?>, <?php echo $date; ?></span>
                </div>
                
                <div class="daily-verse">
                    <i class="fas fa-book-bible me-2"></i>
                    <?php echo $daily_verse; ?>
                </div>
            </div>
        </section>
        
        <!-- Quick Stats -->
        <section class="stats-section">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value" id="onlineCount"><?php echo $online_count; ?></div>
                    <div class="stat-label">Online</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value" id="prayerCount"><?php echo $prayer_count; ?></div>
                    <div class="stat-label">Prayers</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value" id="testimonyCount"><?php echo $testimony_count; ?></div>
                    <div class="stat-label">Testimonies</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value" id="answeredCount"><?php echo $answered_count; ?></div>
                    <div class="stat-label">Answered</div>
                </div>
            </div>
        </section>
        
        <!-- Quick Actions -->
        <section class="actions-section">
            <h2 class="section-title">
                <i class="fas fa-bolt"></i>
                Quick Actions
            </h2>
            
            <div class="actions-grid">
                <div class="action-card" data-bs-toggle="modal" data-bs-target="#prayerModal">
                    <div class="action-icon">
                        <i class="fas fa-hands-praying"></i>
                    </div>
                    <h3 class="action-title">Prayer Request</h3>
                    <p class="action-desc">Share your prayer need</p>
                </div>
                
                <div class="action-card" data-bs-toggle="modal" data-bs-target="#testimonyModal">
                    <div class="action-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3 class="action-title">Share Testimony</h3>
                    <p class="action-desc">Share God's goodness</p>
                </div>
                
                <div class="action-card" onclick="showTab('chat')">
                    <div class="action-icon">
                        <i class="fas fa-comment"></i>
                    </div>
                    <h3 class="action-title">Live Chat</h3>
                    <p class="action-desc">Real-time discussion</p>
                </div>
                
                <div class="action-card" onclick="showConfessions()">
                    <div class="action-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h3 class="action-title">Confessions</h3>
                    <p class="action-desc">Share anonymously</p>
                </div>
            </div>
        </section>
        
        <!-- Tab Navigation -->
        <section class="tabs-container">
            <div class="tabs-nav">
                <button class="tab-btn active" onclick="showTab('prayer')">
                    <i class="fas fa-hands-praying"></i>
                    Prayer Wall
                </button>
                <button class="tab-btn" onclick="showTab('testimony')">
                    <i class="fas fa-star"></i>
                    Testimonies
                </button>
                <button class="tab-btn" onclick="showTab('chat')">
                    <i class="fas fa-comment"></i>
                    Live Chat
                </button>
                <button class="tab-btn" onclick="showTab('confession')">
                    <i class="fas fa-lock"></i>
                    Confessions
                </button>
            </div>
        </section>
        
        <!-- Tab Content -->
        <div id="tabContent">
            <!-- Prayer Requests Tab -->
            <div class="tab-content active" id="prayerTab">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="section-title">
                        <i class="fas fa-hands-praying"></i>
                        Prayer Requests
                    </h3>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#prayerModal">
                        <i class="fas fa-plus me-1"></i>New Request
                    </button>
                </div>
                
                <div id="prayerRequests">
                    <?php
                    // Load prayer requests
                    $prayers_sql = "SELECT p.*, u.full_name FROM prayer_requests p 
                                   LEFT JOIN users u ON p.user_id = u.id 
                                   ORDER BY p.created_at DESC LIMIT 10";
                    $prayers_result = mysqli_query($conn, $prayers_sql);
                    
                    if (mysqli_num_rows($prayers_result) > 0) {
                        while ($prayer = mysqli_fetch_assoc($prayers_result)) {
                            $is_anonymous = $prayer['is_anonymous'] == 1;
                            $author_name = $is_anonymous ? 'Anonymous' : htmlspecialchars($prayer['full_name'] ?? 'User');
                            $time_ago = timeAgo($prayer['created_at']);
                            $status_class = $prayer['status'] == 'answered' ? 'answered' : 'pending';
                            $status_text = $prayer['status'] == 'answered' ? 'Answered' : 'Needs Prayer';
                            
                            // Get interaction counts
                            $pray_count_sql = "SELECT COUNT(*) as count FROM prayer_interactions 
                                              WHERE prayer_id = {$prayer['id']} AND interaction_type = 'prayed'";
                            $pray_result = mysqli_query($conn, $pray_count_sql);
                            $pray_count = $pray_result ? mysqli_fetch_assoc($pray_result)['count'] : 0;
                            
                            $encourage_count_sql = "SELECT COUNT(*) as count FROM prayer_interactions 
                                                   WHERE prayer_id = {$prayer['id']} AND interaction_type = 'encouraged'";
                            $encourage_result = mysqli_query($conn, $encourage_count_sql);
                            $encourage_count = $encourage_result ? mysqli_fetch_assoc($encourage_result)['count'] : 0;
                            ?>
                            <div class="content-card prayer" data-id="<?php echo $prayer['id']; ?>">
                                <div class="content-header">
                                    <div>
                                        <div class="content-title"><?php echo htmlspecialchars($prayer['title']); ?></div>
                                        <div class="content-meta">By: <?php echo $author_name; ?> • <?php echo $time_ago; ?></div>
                                    </div>
                                    <span class="badge-status <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                </div>
                                <div class="content-body">
                                    <?php echo nl2br(htmlspecialchars($prayer['content'])); ?>
                                    <?php if ($prayer['praise_report']): ?>
                                        <div class="alert alert-success mt-3 mb-0">
                                            <strong>🙏 Praise Report:</strong> <?php echo htmlspecialchars($prayer['praise_report']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="content-footer">
                                    <div class="content-actions">
                                        <button class="btn-action pray" onclick="handlePray(<?php echo $prayer['id']; ?>)">
                                            <i class="fas fa-hands-praying"></i> Pray (<?php echo $pray_count; ?>)
                                        </button>
                                        <button class="btn-action encourage" onclick="handleEncourage(<?php echo $prayer['id']; ?>)">
                                            <i class="fas fa-heart"></i> Encourage (<?php echo $encourage_count; ?>)
                                        </button>
                                    </div>
                                    <?php if ($prayer['comment_count'] > 0): ?>
                                        <button class="btn-action" onclick="showComments(<?php echo $prayer['id']; ?>)">
                                            <i class="fas fa-comment"></i> Comments (<?php echo $prayer['comment_count']; ?>)
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<div class="text-center py-5">
                                <div class="mb-3">
                                    <i class="fas fa-hands-praying fa-3x text-muted"></i>
                                </div>
                                <p class="text-muted">No prayer requests yet. Be the first to share!</p>
                              </div>';
                    }
                    ?>
                </div>
                
                <?php if (mysqli_num_rows($prayers_result) >= 10): ?>
                <div class="text-center mt-3">
                    <button class="btn btn-outline-primary" onclick="loadMore('prayers')">
                        <i class="fas fa-sync me-2"></i>Load More
                    </button>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Testimonies Tab -->
            <div class="tab-content" id="testimonyTab">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="section-title">
                        <i class="fas fa-star"></i>
                        Testimonies
                    </h3>
                    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#testimonyModal">
                        <i class="fas fa-plus me-1"></i>Share Testimony
                    </button>
                </div>
                
                <div id="testimoniesList">
                    <?php
                    // Load testimonies
                    $testimonies_sql = "SELECT t.*, u.full_name FROM testimonies t 
                                       JOIN users u ON t.user_id = u.id 
                                       ORDER BY t.created_at DESC LIMIT 10";
                    $testimonies_result = mysqli_query($conn, $testimonies_sql);
                    
                    if (mysqli_num_rows($testimonies_result) > 0) {
                        while ($testimony = mysqli_fetch_assoc($testimonies_result)) {
                            $time_ago = timeAgo($testimony['created_at']);
                            
                            // Get like count
                            $like_count_sql = "SELECT COUNT(*) as count FROM testimony_likes 
                                              WHERE testimony_id = {$testimony['id']}";
                            $like_result = mysqli_query($conn, $like_count_sql);
                            $like_count = $like_result ? mysqli_fetch_assoc($like_result)['count'] : 0;
                            ?>
                            <div class="content-card testimony" data-id="<?php echo $testimony['id']; ?>">
                                <div class="content-header">
                                    <div>
                                        <div class="content-title"><?php echo htmlspecialchars($testimony['title']); ?></div>
                                        <div class="content-meta">By: <?php echo htmlspecialchars($testimony['full_name']); ?> • <?php echo $time_ago; ?></div>
                                    </div>
                                    <span class="badge bg-warning"><?php echo ucfirst($testimony['category']); ?></span>
                                </div>
                                <div class="content-body">
                                    <?php echo nl2br(htmlspecialchars($testimony['content'])); ?>
                                </div>
                                <div class="content-footer">
                                    <div class="content-actions">
                                        <button class="btn-action like" onclick="handleLike(<?php echo $testimony['id']; ?>)">
                                            <i class="fas fa-heart"></i> Bless This (<?php echo $like_count; ?>)
                                        </button>
                                        <button class="btn-action" onclick="shareTestimony(<?php echo $testimony['id']; ?>)">
                                            <i class="fas fa-share"></i> Share
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<div class="text-center py-5">
                                <div class="mb-3">
                                    <i class="fas fa-star fa-3x text-muted"></i>
                                </div>
                                <p class="text-muted">No testimonies yet. Share your story of God\'s faithfulness!</p>
                              </div>';
                    }
                    ?>
                </div>
            </div>
            
            <!-- Live Chat Tab -->
            <div class="tab-content" id="chatTab">
                <h3 class="section-title mb-4">
                    <i class="fas fa-comment"></i>
                    Live Chat
                </h3>
                
                <div class="chat-container">
                    <div class="chat-messages" id="chatMessages">
                        <?php
                        // Load chat messages
                        $messages_sql = "SELECT m.*, u.full_name FROM gyc_messages m 
                                        JOIN users u ON m.user_id = u.id 
                                        ORDER BY m.created_at DESC LIMIT 50";
                        $messages_result = mysqli_query($conn, $messages_sql);
                        
                        if (mysqli_num_rows($messages_result) > 0) {
                            $messages = [];
                            while ($msg = mysqli_fetch_assoc($messages_result)) {
                                $messages[] = $msg;
                            }
                            $messages = array_reverse($messages); // Show oldest first
                            
                            foreach ($messages as $msg) {
                                $is_self = $msg['user_id'] == $user_id;
                                $time_ago = timeAgo($msg['created_at']);
                                ?>
                                <div class="chat-message <?php echo $is_self ? 'self' : ''; ?>">
                                    <div class="chat-message-content">
                                        <div class="chat-message-header">
                                            <span class="chat-message-sender"><?php echo $is_self ? 'You' : htmlspecialchars($msg['full_name']); ?></span>
                                            <span class="chat-message-time"><?php echo $time_ago; ?></span>
                                        </div>
                                        <p class="mb-0"><?php echo htmlspecialchars($msg['message']); ?></p>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<div class="text-center py-5">
                                    <p class="text-muted">No messages yet. Start the conversation!</p>
                                  </div>';
                        }
                        ?>
                    </div>
                    
                    <div class="chat-input-group">
                        <input type="text" class="chat-input" id="chatInput" 
                               placeholder="Type your message..." 
                               onkeypress="if(event.key === 'Enter') sendMessage()">
                        <button class="btn-send" onclick="sendMessage()">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Confessions Tab -->
            <div class="tab-content" id="confessionTab">
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-lock fa-4x text-muted"></i>
                    </div>
                    <h4 class="mb-3">Anonymous Confessions</h4>
                    <p class="text-muted mb-4">Share what's on your heart anonymously with the community.</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#confessionModal">
                        <i class="fas fa-plus me-2"></i>Share Confession
                    </button>
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
            
            <a href="gyc.php" class="nav-item active">
                <i class="fas fa-comments nav-icon"></i>
                <span class="nav-label">GYC</span>
            </a>
            
            <a href="life.php" class="nav-item">
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
    <!-- Prayer Request Modal -->
    <div class="modal fade" id="prayerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Prayer Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="prayerForm" onsubmit="submitPrayer(event)">
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" id="prayerTitle" 
                                   placeholder="Brief title for your prayer request" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Prayer Request</label>
                            <textarea class="form-control" id="prayerContent" rows="4" 
                                      placeholder="Share your prayer need..." required></textarea>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="anonymousPrayer">
                            <label class="form-check-label">Post anonymously</label>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary" id="submitPrayerBtn">
                                <i class="fas fa-paper-plane me-2"></i>Submit Prayer Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Testimony Modal -->
    <div class="modal fade" id="testimonyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Share Testimony</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="testimonyForm" onsubmit="submitTestimony(event)">
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" id="testimonyTitle" 
                                   placeholder="What God has done..." required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Your Testimony</label>
                            <textarea class="form-control" id="testimonyContent" rows="4" 
                                      placeholder="Share your story of God's faithfulness..." required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" id="testimonyCategory">
                                <option value="healing">Healing</option>
                                <option value="provision">Provision</option>
                                <option value="deliverance">Deliverance</option>
                                <option value="guidance">Guidance</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-warning" id="submitTestimonyBtn">
                                <i class="fas fa-star me-2"></i>Share Testimony
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Confession Modal -->
    <div class="modal fade" id="confessionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Share Confession</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="confessionForm" onsubmit="submitConfession(event)">
                        <div class="mb-3">
                            <label class="form-label">Your Confession (Anonymous)</label>
                            <textarea class="form-control" id="confessionContent" rows="6" 
                                      placeholder="Share what's on your heart... (will be posted anonymously)" 
                                      required></textarea>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary" id="submitConfessionBtn">
                                <i class="fas fa-lock me-2"></i>Share Anonymously
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add animations
            const elements = document.querySelectorAll('.stat-card, .action-card, .content-card');
            elements.forEach((el, index) => {
                el.style.animation = 'fadeInUp 0.6s ease forwards';
                el.style.animationDelay = `${index * 0.1}s`;
                el.style.opacity = '0';
            });
            
            // Auto-refresh chat every 10 seconds if chat tab is active
            setInterval(() => {
                if (document.getElementById('chatTab').classList.contains('active')) {
                    loadMessages();
                }
            }, 10000);
            
            // Auto-refresh prayers and testimonies every 30 seconds
            setInterval(() => {
                if (document.getElementById('prayerTab').classList.contains('active')) {
                    loadPrayers();
                }
                if (document.getElementById('testimonyTab').classList.contains('active')) {
                    loadTestimonies();
                }
            }, 30000);
        });
        
        // Tab switching function
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            const tabElement = document.getElementById(tabName + 'Tab');
            const tabButtons = document.querySelectorAll('.tab-btn');
            
            tabButtons.forEach(btn => {
                if (btn.textContent.includes(tabName.charAt(0).toUpperCase() + tabName.slice(1))) {
                    btn.classList.add('active');
                }
            });
            
            if (tabElement) {
                tabElement.classList.add('active');
                
                // Load data for the tab if needed
                switch(tabName) {
                    case 'prayer':
                        loadPrayers();
                        break;
                    case 'testimony':
                        loadTestimonies();
                        break;
                    case 'chat':
                        scrollToBottom();
                        break;
                }
            }
        }
        
        // Show confessions tab
        function showConfessions() {
            showTab('confession');
            const modal = new bootstrap.Modal(document.getElementById('confessionModal'));
            modal.show();
        }
        
        // Submit prayer request
        async function submitPrayer(event) {
            event.preventDefault();
            
            const title = document.getElementById('prayerTitle').value;
            const content = document.getElementById('prayerContent').value;
            const isAnonymous = document.getElementById('anonymousPrayer').checked;
            
            if (!title || !content) {
                showToast('Please fill in all fields', 'error');
                return;
            }
            
            const submitBtn = document.getElementById('submitPrayerBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';
            
            try {
                const formData = new FormData();
                formData.append('action', 'add_prayer');
                formData.append('title', title);
                formData.append('content', content);
                formData.append('anonymous', isAnonymous ? '1' : '0');
                
                const response = await fetch('gyc-actions.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                if (result.success) {
                    showToast('Prayer request submitted! 🙏', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('prayerModal')).hide();
                    document.getElementById('prayerForm').reset();
                    loadPrayers();
                    updateStats();
                } else {
                    showToast(result.message || 'Failed to submit prayer', 'error');
                }
            } catch (error) {
                showToast('Network error. Please try again.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit Prayer Request';
            }
        }
        
        // Submit testimony
        async function submitTestimony(event) {
            event.preventDefault();
            
            const title = document.getElementById('testimonyTitle').value;
            const content = document.getElementById('testimonyContent').value;
            const category = document.getElementById('testimonyCategory').value;
            
            if (!title || !content) {
                showToast('Please fill in all fields', 'error');
                return;
            }
            
            const submitBtn = document.getElementById('submitTestimonyBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';
            
            try {
                const formData = new FormData();
                formData.append('action', 'add_testimony');
                formData.append('title', title);
                formData.append('content', content);
                formData.append('category', category);
                
                const response = await fetch('gyc-actions.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                if (result.success) {
                    showToast('Testimony shared! 🌟', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('testimonyModal')).hide();
                    document.getElementById('testimonyForm').reset();
                    loadTestimonies();
                    updateStats();
                } else {
                    showToast(result.message || 'Failed to share testimony', 'error');
                }
            } catch (error) {
                showToast('Network error. Please try again.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-star me-2"></i>Share Testimony';
            }
        }
        
        // Submit confession
        async function submitConfession(event) {
            event.preventDefault();
            
            const content = document.getElementById('confessionContent').value;
            
            if (!content) {
                showToast('Please write something', 'error');
                return;
            }
            
            const submitBtn = document.getElementById('submitConfessionBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';
            
            try {
                const formData = new FormData();
                formData.append('action', 'add_confession');
                formData.append('content', content);
                
                const response = await fetch('gyc-actions.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                if (result.success) {
                    showToast('Confession shared anonymously 🔒', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('confessionModal')).hide();
                    document.getElementById('confessionForm').reset();
                } else {
                    showToast(result.message || 'Failed to share confession', 'error');
                }
            } catch (error) {
                showToast('Network error. Please try again.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-lock me-2"></i>Share Anonymously';
            }
        }
        
        // Handle pray action
        async function handlePray(prayerId) {
            try {
                const formData = new FormData();
                formData.append('action', 'pray_for');
                formData.append('prayer_id', prayerId);
                
                const response = await fetch('gyc-actions.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                if (result.success) {
                    showToast('Prayed for this request 🙏', 'success');
                    loadPrayers();
                } else {
                    showToast(result.message || 'Already prayed for this request', 'info');
                }
            } catch (error) {
                showToast('Network error', 'error');
            }
        }
        
        // Handle encourage action
        async function handleEncourage(prayerId) {
            try {
                const formData = new FormData();
                formData.append('action', 'encourage');
                formData.append('prayer_id', prayerId);
                
                const response = await fetch('gyc-actions.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                if (result.success) {
                    showToast('Encouragement sent ❤️', 'success');
                    loadPrayers();
                } else {
                    showToast(result.message || 'Already encouraged this request', 'info');
                }
            } catch (error) {
                showToast('Network error', 'error');
            }
        }
        
        // Handle like testimony
        async function handleLike(testimonyId) {
            try {
                const formData = new FormData();
                formData.append('action', 'like_testimony');
                formData.append('testimony_id', testimonyId);
                
                const response = await fetch('gyc-actions.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                if (result.success) {
                    showToast('Testimony blessed 🌟', 'success');
                    loadTestimonies();
                } else {
                    showToast(result.message || 'Already blessed this testimony', 'info');
                }
            } catch (error) {
                showToast('Network error', 'error');
            }
        }
        
        // Send chat message
        async function sendMessage() {
            const messageInput = document.getElementById('chatInput');
            const message = messageInput.value.trim();
            
            if (!message) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'send_message');
                formData.append('message', message);
                
                const response = await fetch('gyc-actions.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                if (result.success) {
                    messageInput.value = '';
                    loadMessages();
                } else {
                    showToast('Failed to send message', 'error');
                }
            } catch (error) {
                showToast('Network error', 'error');
            }
        }
        
        // Load prayers via AJAX
        async function loadPrayers() {
            try {
                const response = await fetch('gyc-actions.php?action=get_prayers');
                const result = await response.json();
                
                if (result.success && result.html) {
                    document.getElementById('prayerRequests').innerHTML = result.html;
                }
            } catch (error) {
                console.error('Error loading prayers:', error);
            }
        }
        
        // Load testimonies via AJAX
        async function loadTestimonies() {
            try {
                const response = await fetch('gyc-actions.php?action=get_testimonies');
                const result = await response.json();
                
                if (result.success && result.html) {
                    document.getElementById('testimoniesList').innerHTML = result.html;
                }
            } catch (error) {
                console.error('Error loading testimonies:', error);
            }
        }
        
        // Load messages via AJAX
        async function loadMessages() {
            try {
                const response = await fetch('gyc-actions.php?action=get_messages');
                const result = await response.json();
                
                if (result.success && result.html) {
                    document.getElementById('chatMessages').innerHTML = result.html;
                    scrollToBottom();
                }
            } catch (error) {
                console.error('Error loading messages:', error);
            }
        }
        
        // Update stats
        async function updateStats() {
            try {
                const response = await fetch('gyc-actions.php?action=get_stats');
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('onlineCount').textContent = result.online || 0;
                    document.getElementById('prayerCount').textContent = result.prayers || 0;
                    document.getElementById('testimonyCount').textContent = result.testimonies || 0;
                    document.getElementById('answeredCount').textContent = result.answered || 0;
                }
            } catch (error) {
                console.error('Error updating stats:', error);
            }
        }
        
        // Load more content
        function loadMore(type) {
            showToast('Loading more...', 'info');
            // Implement pagination here
        }
        
        // Show comments
        function showComments(prayerId) {
            showToast('Comments feature coming soon!', 'info');
        }
        
        // Share testimony
        function shareTestimony(testimonyId) {
            if (navigator.share) {
                navigator.share({
                    title: 'Check out this amazing testimony!',
                    text: 'Testimony from FYBS GYC Community',
                    url: window.location.href
                });
            } else {
                navigator.clipboard.writeText(window.location.href);
                showToast('Link copied to clipboard', 'success');
            }
        }
        
        // Scroll to bottom of chat
        function scrollToBottom() {
            const container = document.getElementById('chatMessages');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        }
        
        // Toast notification
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `position-fixed top-0 end-0 p-3`;
            toast.style.zIndex = '9999';
            toast.innerHTML = `
                <div class="toast align-items-center text-white bg-${type === 'success' ? 'success' : 
                              type === 'error' ? 'danger' : 
                              type === 'warning' ? 'warning' : 'primary'} border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">${message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            document.body.appendChild(toast);
            
            const bsToast = new bootstrap.Toast(toast.querySelector('.toast'));
            bsToast.show();
            
            setTimeout(() => toast.remove(), 3000);
        }
    </script>
</body>
</html>

<?php
// Helper function for time ago
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $time_difference = time() - $time;
    
    if ($time_difference < 1) { return 'just now'; }
    
    $condition = [
        12 * 30 * 24 * 60 * 60  => 'year',
        30 * 24 * 60 * 60       => 'month',
        24 * 60 * 60            => 'day',
        60 * 60                 => 'hour',
        60                      => 'minute',
        1                       => 'second'
    ];
    
    foreach ($condition as $secs => $str) {
        $d = $time_difference / $secs;
        
        if ($d >= 1) {
            $t = round($d);
            return $t . ' ' . $str . ($t > 1 ? 's' : '') . ' ago';
        }
    }
}
?>