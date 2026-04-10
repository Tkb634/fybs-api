<?php
// Protect page using RBAC
require_once "config.php";
session_start();

// Only allow admins
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Include database connection
include "config.php";

// Get admin info
$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['full_name'] ?? 'Admin';

// Get stats
$stats = [];

// Devotionals count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM spiritual_devotionals");
$stmt->execute();
$result = $stmt->get_result();
$stats['devotionals'] = $result->fetch_assoc()['count'];

// Sermons count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM spiritual_sermons");
$stmt->execute();
$result = $stmt->get_result();
$stats['sermons'] = $result->fetch_assoc()['count'];

// Preachers count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM spiritual_preachers");
$stmt->execute();
$result = $stmt->get_result();
$stats['preachers'] = $result->fetch_assoc()['count'];

// Reading plans count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM spiritual_reading_plans");
$stmt->execute();
$result = $stmt->get_result();
$stats['plans'] = $result->fetch_assoc()['count'];

// Recent devotionals
$recent_devotionals = [];
$stmt = $conn->prepare("SELECT * FROM spiritual_devotionals WHERE scheduled_date >= CURDATE() ORDER BY scheduled_date LIMIT 5");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_devotionals[] = $row;
}

// Recent sermons
$recent_sermons = [];
$stmt = $conn->prepare("SELECT * FROM spiritual_sermons ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_sermons[] = $row;
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

// Get first name
$first_name = explode(' ', $admin_name)[0];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Admin - Spiritual Content - FYBS Youth</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#4f46e5">
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
            --primary-color: #4f46e5;
            --primary-light: #6366f1;
            --primary-dark: #4338ca;
            --secondary-color: #10b981;
            --accent-color: #f59e0b;
            --dark-color: #1f2937;
            --light-color: #f9fafb;
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-secondary: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-success: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --gradient-warning: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
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
        
        /* Stats Grid */
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
            background: var(--gradient-success);
        }
        
        .stat-card:nth-child(3)::before {
            background: var(--gradient-warning);
        }
        
        .stat-card:nth-child(4)::before {
            background: var(--gradient-secondary);
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
            background: linear-gradient(135deg, rgba(124, 58, 237, 0.1) 0%, rgba(139, 92, 246, 0.05) 100%);
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
            background: var(--gradient-success);
        }
        
        .action-card:nth-child(3) .action-icon {
            background: var(--gradient-warning);
        }
        
        .action-card:nth-child(4) .action-icon {
            background: var(--gradient-secondary);
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
        
        /* Recent Content */
        .recent-section {
            margin-bottom: 30px;
            animation: fadeInUp 0.8s ease 0.6s both;
        }
        
        .content-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--shadow-lg);
            border: 1px solid #e5e7eb;
            position: relative;
            overflow: hidden;
        }
        
        .content-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: var(--gradient-primary);
        }
        
        .content-list {
            list-style: none;
            padding: 0;
        }
        
        .content-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .content-item:last-child {
            border-bottom: none;
        }
        
        .content-item:hover {
            background: #f8fafc;
            border-radius: 8px;
            transform: translateX(5px);
        }
        
        .content-info {
            flex: 1;
        }
        
        .content-title {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 4px;
        }
        
        .content-details {
            font-size: 13px;
            color: #6b7280;
        }
        
        .content-date {
            font-size: 12px;
            color: #9ca3af;
        }
        
        .content-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-edit {
            background: #dbeafe;
            color: #1d4ed8;
        }
        
        .btn-edit:hover {
            background: #bfdbfe;
        }
        
        .btn-delete {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .btn-delete:hover {
            background: #fecaca;
        }
        
        /* Modals */
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
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-body {
            padding: 20px;
            max-height: 60vh;
            overflow-y: auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--dark-color);
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border-radius: 12px;
            padding: 12px 16px;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .btn-submit {
            background: var(--gradient-primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-submit:hover {
            opacity: 0.95;
            transform: translateY(-2px);
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
            background: rgba(124, 58, 237, 0.1);
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
            
            .welcome-card, .content-card {
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
            
            .main-header, .welcome-card, .content-card,
            .stat-card, .action-card, .modal-content {
                background: #1f2937;
                border-color: #374151;
            }
            
            .user-name, .greeting-title, .section-title,
            .action-title, .content-title, .stat-value,
            .modal-title {
                color: #f9fafb;
            }
            
            .greeting-subtitle, .user-role, .stat-label,
            .action-desc, .content-details, .date-info {
                color: #d1d5db;
            }
            
            .content-date {
                color: #9ca3af;
            }
            
            .user-profile:hover {
                background: #374151;
            }
            
            .content-item:hover {
                background: #374151;
            }
            
            .form-control, .form-select {
                background: #374151;
                border-color: #4b5563;
                color: #f9fafb;
            }
            
            .form-label {
                color: #d1d5db;
            }
            
            .btn-edit {
                background: #1e3a8a;
                color: #dbeafe;
            }
            
            .btn-delete {
                background: #7f1d1d;
                color: #fecaca;
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
                background: rgba(99, 102, 241, 0.1);
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
            border: 3px solid rgba(79, 70, 229, 0.3);
            border-radius: 50%;
            border-top-color: var(--primary-color);
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Notification */
        .notification {
            position: fixed;
            top: 100px;
            right: 20px;
            background: #10b981;
            color: white;
            padding: 15px 20px;
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            z-index: 9999;
            animation: slideIn 0.3s ease;
            max-width: 300px;
        }
        
        .notification.error {
            background: #ef4444;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
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
                <div class="logo-text">Spiritual Admin</div>
            </div>
            
            <div class="user-profile" onclick="window.location.href='profile.php'">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($first_name); ?></div>
                    <div class="user-role">Administrator</div>
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
                    <h1 class="greeting-title"><?php echo $greeting; ?>, <?php echo htmlspecialchars($first_name); ?>!</h1>
                </div>
                <p class="greeting-subtitle">Manage spiritual content including devotionals, sermons, and mentoring.</p>
                
                <div class="date-info">
                    <i class="fas fa-calendar-day"></i>
                    <span><?php echo date('l, F j, Y'); ?> • <?php echo date('g:i A'); ?></span>
                </div>
            </div>
        </section>
        
        <!-- Stats Section -->
        <section class="stats-section">
            <h2 class="section-title">
                <i class="fas fa-chart-line"></i>
                Content Overview
            </h2>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['devotionals']; ?></div>
                    <div class="stat-label">Devotionals</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['sermons']; ?></div>
                    <div class="stat-label">Sermons</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['preachers']; ?></div>
                    <div class="stat-label">Preachers</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['plans']; ?></div>
                    <div class="stat-label">Reading Plans</div>
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
                <div class="action-card" onclick="openModal('devotional')">
                    <div class="action-icon">
                        <i class="fas fa-book-bible"></i>
                    </div>
                    <h3 class="action-title">Add Devotional</h3>
                    <p class="action-desc">Create daily devotional content</p>
                </div>
                
                <div class="action-card" onclick="openModal('sermon')">
                    <div class="action-icon">
                        <i class="fas fa-video"></i>
                    </div>
                    <h3 class="action-title">Add Sermon</h3>
                    <p class="action-desc">Upload sermon video/audio</p>
                </div>
                
                <div class="action-card" onclick="openModal('preacher')">
                    <div class="action-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h3 class="action-title">Add Preacher</h3>
                    <p class="action-desc">Add recommended preacher</p>
                </div>
                
                <div class="action-card" onclick="openModal('plan')">
                    <div class="action-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3 class="action-title">Add Reading Plan</h3>
                    <p class="action-desc">Create Bible reading plan</p>
                </div>
            </div>
        </section>
        
        <!-- Recent Devotionals -->
        <section class="recent-section">
            <h2 class="section-title">
                <i class="fas fa-clock-rotate-left"></i>
                Upcoming Devotionals
            </h2>
            
            <div class="content-card">
                <?php if (!empty($recent_devotionals)): ?>
                <ul class="content-list">
                    <?php foreach ($recent_devotionals as $devotional): ?>
                    <li class="content-item">
                        <div class="content-info">
                            <div class="content-title"><?php echo htmlspecialchars($devotional['title']); ?></div>
                            <div class="content-details">
                                <?php echo ucfirst($devotional['devotional_type']); ?> • 
                                <?php echo htmlspecialchars($devotional['verse']); ?>
                            </div>
                            <div class="content-date">
                                Scheduled: <?php echo date('M j, Y', strtotime($devotional['scheduled_date'])); ?>
                            </div>
                        </div>
                        <div class="content-actions">
                            <button class="btn-action btn-edit" onclick="editDevotional(<?php echo $devotional['id']; ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn-action btn-delete" onclick="deleteContent('devotional', <?php echo $devotional['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-calendar-plus fa-3x mb-3" style="color: #6b7280;"></i>
                    <p>No upcoming devotionals scheduled.</p>
                    <button class="btn-submit mt-3" onclick="openModal('devotional')">
                        <i class="fas fa-plus me-2"></i> Add First Devotional
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </section>
        
        <!-- Recent Sermons -->
        <section class="recent-section">
            <h2 class="section-title">
                <i class="fas fa-film"></i>
                Recent Sermons
            </h2>
            
            <div class="content-card">
                <?php if (!empty($recent_sermons)): ?>
                <ul class="content-list">
                    <?php foreach ($recent_sermons as $sermon): ?>
                    <li class="content-item">
                        <div class="content-info">
                            <div class="content-title"><?php echo htmlspecialchars($sermon['title']); ?></div>
                            <div class="content-details">
                                <?php echo ucfirst($sermon['media_type']); ?> • 
                                <?php echo $sermon['duration_minutes']; ?> min
                            </div>
                            <div class="content-date">
                                Posted: <?php echo date('M j, Y', strtotime($sermon['created_at'])); ?>
                            </div>
                        </div>
                        <div class="content-actions">
                            <button class="btn-action btn-edit" onclick="editSermon(<?php echo $sermon['id']; ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn-action btn-delete" onclick="deleteContent('sermon', <?php echo $sermon['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-video-slash fa-3x mb-3" style="color: #6b7280;"></i>
                    <p>No sermons uploaded yet.</p>
                    <button class="btn-submit mt-3" onclick="openModal('sermon')">
                        <i class="fas fa-plus me-2"></i> Add First Sermon
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
    
    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <div class="nav-items">
            <a href="admin_dashboard.php" class="nav-item">
                <i class="fas fa-home nav-icon"></i>
                <span class="nav-label">Dashboard</span>
            </a>
            
            <a href="admin_spiritual.php" class="nav-item active">
                <i class="fas fa-cross nav-icon"></i>
                <span class="nav-label">Spiritual</span>
            </a>
            
            <a href="admin_content.php" class="nav-item">
                <i class="fas fa-file-alt nav-icon"></i>
                <span class="nav-label">Content</span>
            </a>
            
            <a href="admin_users.php" class="nav-item">
                <i class="fas fa-users nav-icon"></i>
                <span class="nav-label">Users</span>
            </a>
            
            <a href="logout.php" class="nav-item">
                <i class="fas fa-sign-out-alt nav-icon"></i>
                <span class="nav-label">Logout</span>
            </a>
        </div>
    </nav>

    <!-- Devotional Modal -->
    <div class="modal fade" id="devotionalModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-book-bible"></i>
                        Add Daily Devotional
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="devotionalForm" onsubmit="saveDevotional(event)">
                        <div class="form-group">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Devotional Type</label>
                                    <select class="form-select" name="devotional_type" required>
                                        <option value="morning">Morning Devotional</option>
                                        <option value="afternoon">Afternoon Reflection</option>
                                        <option value="evening">Evening Devotional</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Scheduled Date</label>
                                    <input type="date" class="form-control" name="scheduled_date" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Bible Verse</label>
                            <input type="text" class="form-control" name="verse" placeholder="e.g., John 3:16" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Scripture Text</label>
                            <textarea class="form-control" name="content" rows="3" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Reflection</label>
                            <textarea class="form-control" name="reflection" rows="3" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Prayer</label>
                            <textarea class="form-control" name="prayer" rows="2" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Author</label>
                                    <input type="text" class="form-control" name="author">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Duration (minutes)</label>
                                    <input type="number" class="form-control" name="duration_minutes" value="5" min="1">
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save me-2"></i> Save Devotional
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sermon Modal -->
    <div class="modal fade" id="sermonModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-video"></i>
                        Add Sermon
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="sermonForm" onsubmit="saveSermon(event)">
                        <div class="form-group">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Media Type</label>
                                    <select class="form-select" name="media_type" required>
                                        <option value="video">Video</option>
                                        <option value="audio">Audio</option>
                                        <option value="text">Text</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Duration (minutes)</label>
                                    <input type="number" class="form-control" name="duration_minutes" min="1">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Video/Audio URL</label>
                                    <input type="url" class="form-control" name="video_url" placeholder="https://">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Thumbnail URL</label>
                                    <input type="url" class="form-control" name="thumbnail_url" placeholder="https://">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Scripture Reference</label>
                                    <input type="text" class="form-control" name="scripture_reference">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Topic</label>
                                    <input type="text" class="form-control" name="topic">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Series Name</label>
                                    <input type="text" class="form-control" name="series_name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Series Part</label>
                                    <input type="number" class="form-control" name="series_part" min="1">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Preacher Name</label>
                            <input type="text" class="form-control" name="preacher_name" required>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_featured" id="featuredSermon">
                            <label class="form-check-label" for="featuredSermon">
                                Feature this sermon
                            </label>
                        </div>
                        
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save me-2"></i> Save Sermon
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Preacher Modal -->
    <div class="modal fade" id="preacherModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-graduate"></i>
                        Add Preacher
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="preacherForm" onsubmit="savePreacher(event)">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Title</label>
                                    <input type="text" class="form-control" name="title" placeholder="Pastor, Rev., Dr.">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Specialization</label>
                                    <input type="text" class="form-control" name="specialization" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Bio</label>
                            <textarea class="form-control" name="bio" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Website</label>
                                    <input type="url" class="form-control" name="website" placeholder="https://">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Social Media</label>
                                    <input type="text" class="form-control" name="social_media" placeholder="@username">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_recommended" id="recommendedPreacher" checked>
                            <label class="form-check-label" for="recommendedPreacher">
                                Recommend this preacher
                            </label>
                        </div>
                        
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save me-2"></i> Save Preacher
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Reading Plan Modal -->
    <div class="modal fade" id="planModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-calendar-alt"></i>
                        Add Reading Plan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="planForm" onsubmit="savePlan(event)">
                        <div class="form-group">
                            <label class="form-label">Plan Title</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="2" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Duration (days)</label>
                                    <input type="number" class="form-control" name="duration_days" value="30" min="1" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Bible Books</label>
                                    <input type="text" class="form-control" name="bible_books" placeholder="Genesis, Exodus, ..." required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_featured" id="featuredPlan" checked>
                            <label class="form-check-label" for="featuredPlan">
                                Feature this plan
                            </label>
                        </div>
                        
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save me-2"></i> Save Reading Plan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        let editId = null;
        let editType = null;
        
        // Modal functions
        function openModal(type) {
            editId = null;
            editType = null;
            
            const modals = {
                devotional: new bootstrap.Modal(document.getElementById('devotionalModal')),
                sermon: new bootstrap.Modal(document.getElementById('sermonModal')),
                preacher: new bootstrap.Modal(document.getElementById('preacherModal')),
                plan: new bootstrap.Modal(document.getElementById('planModal'))
            };
            
            // Reset form
            const formId = type + 'Form';
            document.getElementById(formId).reset();
            
            // Set default date to today for devotional
            if (type === 'devotional') {
                document.querySelector('input[name="scheduled_date"]').valueAsDate = new Date();
            }
            
            modals[type].show();
        }
        
        // Save devotional
        async function saveDevotional(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            
            // Add edit info if needed
            if (editId && editType === 'devotional') {
                data.id = editId;
                data.action = 'edit';
            } else {
                data.action = 'add';
            }
            
            const btn = form.querySelector('.btn-submit');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="loading-spinner"></span>';
            btn.disabled = true;
            
            try {
                const response = await fetch('admin_spiritual_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Devotional saved successfully!', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification('Error: ' + result.message, 'error');
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
        
        // Save sermon
        async function saveSermon(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            
            if (editId && editType === 'sermon') {
                data.id = editId;
                data.action = 'edit';
            } else {
                data.action = 'add';
            }
            
            const btn = form.querySelector('.btn-submit');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="loading-spinner"></span>';
            btn.disabled = true;
            
            try {
                const response = await fetch('admin_spiritual_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Sermon saved successfully!', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification('Error: ' + result.message, 'error');
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
        
        // Save preacher
        async function savePreacher(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            data.action = 'add_preacher';
            
            const btn = form.querySelector('.btn-submit');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="loading-spinner"></span>';
            btn.disabled = true;
            
            try {
                const response = await fetch('admin_spiritual_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Preacher added successfully!', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification('Error: ' + result.message, 'error');
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
        
        // Save reading plan
        async function savePlan(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            data.action = 'add_plan';
            
            const btn = form.querySelector('.btn-submit');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="loading-spinner"></span>';
            btn.disabled = true;
            
            try {
                const response = await fetch('admin_spiritual_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Reading plan added successfully!', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification('Error: ' + result.message, 'error');
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
        
        // Edit functions
        async function editDevotional(id) {
            editId = id;
            editType = 'devotional';
            
            try {
                const response = await fetch(`admin_spiritual_actions.php?action=get_devotional&id=${id}`);
                const data = await response.json();
                
                if (data.success) {
                    const devotional = data.data;
                    const form = document.getElementById('devotionalForm');
                    
                    // Fill form
                    form.querySelector('input[name="title"]').value = devotional.title;
                    form.querySelector('select[name="devotional_type"]').value = devotional.devotional_type;
                    form.querySelector('input[name="scheduled_date"]').value = devotional.scheduled_date;
                    form.querySelector('input[name="verse"]').value = devotional.verse;
                    form.querySelector('textarea[name="content"]').value = devotional.content;
                    form.querySelector('textarea[name="reflection"]').value = devotional.reflection;
                    form.querySelector('textarea[name="prayer"]').value = devotional.prayer;
                    form.querySelector('input[name="author"]').value = devotional.author || '';
                    form.querySelector('input[name="duration_minutes"]').value = devotional.duration_minutes || 5;
                    
                    // Update modal title
                    document.querySelector('#devotionalModal .modal-title').innerHTML = 
                        '<i class="fas fa-edit"></i> Edit Devotional';
                    
                    // Show modal
                    new bootstrap.Modal(document.getElementById('devotionalModal')).show();
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Error loading devotional data', 'error');
            }
        }
        
        async function editSermon(id) {
            editId = id;
            editType = 'sermon';
            
            try {
                const response = await fetch(`admin_spiritual_actions.php?action=get_sermon&id=${id}`);
                const data = await response.json();
                
                if (data.success) {
                    const sermon = data.data;
                    const form = document.getElementById('sermonForm');
                    
                    // Fill form
                    form.querySelector('input[name="title"]').value = sermon.title;
                    form.querySelector('textarea[name="description"]').value = sermon.description;
                    form.querySelector('select[name="media_type"]').value = sermon.media_type;
                    form.querySelector('input[name="duration_minutes"]').value = sermon.duration_minutes || '';
                    form.querySelector('input[name="video_url"]').value = sermon.video_url || '';
                    form.querySelector('input[name="thumbnail_url"]').value = sermon.thumbnail_url || '';
                    form.querySelector('input[name="scripture_reference"]').value = sermon.scripture_reference || '';
                    form.querySelector('input[name="topic"]').value = sermon.topic || '';
                    form.querySelector('input[name="series_name"]').value = sermon.series_name || '';
                    form.querySelector('input[name="series_part"]').value = sermon.series_part || '';
                    form.querySelector('input[name="preacher_name"]').value = sermon.preacher_name || '';
                    form.querySelector('input[name="is_featured"]').checked = sermon.is_featured == 1;
                    
                    // Update modal title
                    document.querySelector('#sermonModal .modal-title').innerHTML = 
                        '<i class="fas fa-edit"></i> Edit Sermon';
                    
                    // Show modal
                    new bootstrap.Modal(document.getElementById('sermonModal')).show();
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Error loading sermon data', 'error');
            }
        }
        
        // Delete content
        async function deleteContent(type, id) {
            if (!confirm(`Are you sure you want to delete this ${type}?`)) {
                return;
            }
            
            try {
                const response = await fetch('admin_spiritual_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete&type=${type}&id=${id}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification(`${type.charAt(0).toUpperCase() + type.slice(1)} deleted successfully!`, 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification('Error: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Network error. Please try again.', 'error');
            }
        }
        
        // Show notification
        function showNotification(message, type = 'success') {
            // Remove existing notification
            const existing = document.querySelector('.notification');
            if (existing) existing.remove();
            
            // Create notification
            const notification = document.createElement('div');
            notification.className = `notification ${type === 'error' ? 'error' : ''}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
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
        
        // Detect and handle dark mode
        function detectDarkMode() {
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.body.classList.add('dark-mode');
            }
        }
        
        // Initialize
        detectDarkMode();
        
        // Listen for dark mode changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            if (e.matches) {
                document.body.classList.add('dark-mode');
            } else {
                document.body.classList.remove('dark-mode');
            }
        });
    </script>
</body>
</html>