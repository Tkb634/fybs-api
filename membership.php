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

// Get user's membership status
$membership_query = "SELECT m.*, t.name as tier_name, t.monthly_price, t.yearly_price 
                    FROM user_memberships m
                    LEFT JOIN membership_tiers t ON m.tier_id = t.id
                    WHERE m.user_id = ? AND m.status = 'active'";
$stmt = $conn->prepare($membership_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$membership_result = $stmt->get_result();

if ($membership_result->num_rows > 0) {
    $membership = $membership_result->fetch_assoc();
    $current_tier = $membership['tier_name'];
    $membership_status = "Active";
    $membership_class = "success";
    $renewal_date = date('M j, Y', strtotime($membership['renewal_date']));
} else {
    $membership = null;
    $current_tier = "Free Member";
    $membership_status = "Not Active";
    $membership_class = "secondary";
    $renewal_date = "N/A";
}

// Get user's donation stats
$donation_stats = [];
$donation_query = "SELECT * FROM user_donation_stats WHERE user_id = ?";
$stmt = $conn->prepare($donation_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$donation_result = $stmt->get_result();

if ($donation_result->num_rows > 0) {
    $donation_stats = $donation_result->fetch_assoc();
} else {
    $donation_stats = [
        'total_donated' => 0.00,
        'total_campaigns' => 0,
        'last_donation_date' => null,
        'largest_donation' => 0.00
    ];
}

// Get all membership tiers
$tiers_query = "SELECT * FROM membership_tiers WHERE is_active = 1 ORDER BY sort_order ASC";
$tiers_result = $conn->query($tiers_query);
$membership_tiers = [];
while ($row = $tiers_result->fetch_assoc()) {
    $membership_tiers[] = $row;
}

// Get active campaigns
$campaigns_query = "SELECT * FROM donation_campaigns 
                   WHERE status = 'active' 
                   ORDER BY is_featured DESC, created_at DESC 
                   LIMIT 3";
$campaigns_result = $conn->query($campaigns_query);
$active_campaigns = [];
while ($row = $campaigns_result->fetch_assoc()) {
    $row['progress_percentage'] = ($row['current_amount'] / $row['target_amount']) * 100;
    $active_campaigns[] = $row;
}

// Get featured projects
$projects_query = "SELECT p.*, u.full_name as creator_name 
                  FROM partnership_projects p
                  LEFT JOIN users u ON p.user_id = u.id
                  WHERE p.status = 'active' AND p.is_featured = 1
                  ORDER BY p.created_at DESC 
                  LIMIT 3";
$projects_result = $conn->query($projects_query);
$featured_projects = [];
while ($row = $projects_result->fetch_assoc()) {
    $row['progress_percentage'] = ($row['current_amount'] / $row['target_amount']) * 100;
    $featured_projects[] = $row;
}

// Get upcoming events
$events_query = "SELECT * FROM fundraising_events 
                WHERE status = 'upcoming' 
                ORDER BY event_date ASC 
                LIMIT 3";
$events_result = $conn->query($events_query);
$upcoming_events = [];
while ($row = $events_result->fetch_assoc()) {
    $row['days_left'] = ceil((strtotime($row['event_date']) - time()) / (60 * 60 * 24));
    $upcoming_events[] = $row;
}

// Get user's active projects
$user_projects_query = "SELECT COUNT(*) as count FROM partnership_projects WHERE user_id = ? AND status = 'active'";
$stmt = $conn->prepare($user_projects_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_projects_result = $stmt->get_result();
$user_projects_count = $user_projects_result->fetch_assoc()['count'];

// Get user's volunteer count
$volunteer_query = "SELECT COUNT(*) as count FROM project_volunteers WHERE user_id = ? AND status = 'approved'";
$stmt = $conn->prepare($volunteer_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$volunteer_result = $stmt->get_result();
$volunteer_count = $volunteer_result->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Membership - FYBS Youth App</title>
    
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
            --secondary-color: #8b5cf6;
            --accent-color: #f59e0b;
            --dark-color: #1f2937;
            --light-color: #f9fafb;
            --gradient-primary: linear-gradient(135deg, #059669 0%, #10b981 100%);
            --gradient-secondary: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            --gradient-warning: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --gradient-success: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --gradient-purple: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
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
        
        /* Membership Status */
        .status-badge {
            background: var(--gradient-success);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .status-badge.secondary {
            background: #6b7280;
        }
        
        /* Stats Section */
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
            background: var(--gradient-purple);
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
            background: linear-gradient(135deg, rgba(5, 150, 105, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%);
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
            background: var(--gradient-purple);
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
        
        /* Membership Tiers */
        .tiers-section {
            margin-bottom: 30px;
            animation: fadeInUp 0.8s ease 0.6s both;
        }
        
        .tiers-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 992px) {
            .tiers-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 576px) {
            .tiers-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .tier-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--shadow-md);
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .tier-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .tier-card.featured {
            border-color: var(--primary-color);
            transform: scale(1.05);
        }
        
        .tier-card.featured:hover {
            transform: scale(1.05) translateY(-5px);
        }
        
        .tier-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--gradient-primary);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .tier-name {
            font-family: 'Poppins', sans-serif;
            font-size: 20px;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 10px;
        }
        
        .tier-price {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .tier-period {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 20px;
        }
        
        .tier-features {
            list-style: none;
            padding: 0;
            margin-bottom: 25px;
        }
        
        .tier-feature {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 14px;
            color: #4b5563;
        }
        
        .tier-feature i {
            color: var(--primary-color);
        }
        
        .btn-tier {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-tier-primary {
            background: var(--gradient-primary);
            color: white;
        }
        
        .btn-tier-primary:hover {
            opacity: 0.95;
        }
        
        .btn-tier-outline {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-tier-outline:hover {
            background: var(--primary-color);
            color: white;
        }
        
        /* Campaigns Section */
        .campaigns-section {
            margin-bottom: 30px;
            animation: fadeInUp 0.8s ease 0.8s both;
        }
        
        .campaigns-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 992px) {
            .campaigns-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 576px) {
            .campaigns-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .campaign-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .campaign-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .campaign-image {
            height: 150px;
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
        }
        
        .campaign-content {
            padding: 20px;
        }
        
        .campaign-title {
            font-weight: 600;
            font-size: 16px;
            color: var(--dark-color);
            margin-bottom: 10px;
        }
        
        .campaign-description {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .campaign-progress {
            margin-bottom: 15px;
        }
        
        .progress-text {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 5px;
        }
        
        .progress-bar-container {
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            background: var(--gradient-primary);
            border-radius: 4px;
        }
        
        .campaign-stats {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #6b7280;
        }
        
        /* Projects Section */
        .projects-section {
            margin-bottom: 30px;
            animation: fadeInUp 0.8s ease 1s both;
        }
        
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 992px) {
            .projects-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 576px) {
            .projects-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .project-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: var(--shadow-md);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .project-title {
            font-weight: 600;
            font-size: 16px;
            color: var(--dark-color);
            margin-bottom: 5px;
        }
        
        .project-category {
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 20px;
            background: #dbeafe;
            color: #1d4ed8;
        }
        
        .project-description {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .project-meta {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 15px;
        }
        
        .btn-project {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 10px;
            background: var(--gradient-primary);
            color: white;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-project:hover {
            opacity: 0.95;
        }
        
        /* Events Section */
        .events-section {
            margin-bottom: 30px;
            animation: fadeInUp 0.8s ease 1.2s both;
        }
        
        .events-list {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: var(--shadow-lg);
            border: 1px solid #e5e7eb;
        }
        
        .event-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .event-item:last-child {
            border-bottom: none;
        }
        
        .event-item:hover {
            background: #f8fafc;
            border-radius: 8px;
        }
        
        .event-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .event-content {
            flex: 1;
        }
        
        .event-title {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 5px;
        }
        
        .event-details {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 5px;
        }
        
        .event-meta {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #9ca3af;
        }
        
        .btn-event {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            background: var(--gradient-primary);
            color: white;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-event:hover {
            opacity: 0.95;
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
            
            .welcome-card {
                padding: 20px;
            }
            
            .section-title {
                font-size: 20px;
            }
            
            .tier-card.featured {
                transform: scale(1);
            }
            
            .tier-card.featured:hover {
                transform: scale(1) translateY(-5px);
            }
        }
        
        /* Dark Mode */
        @media (prefers-color-scheme: dark) {
            body {
                background: #111827;
                color: #f9fafb;
            }
            
            .main-header, .welcome-card, .stat-card,
            .action-card, .tier-card, .campaign-card,
            .project-card, .events-list, .modal-content {
                background: #1f2937;
                border-color: #374151;
            }
            
            .user-name, .greeting-title, .section-title,
            .action-title, .stat-value, .tier-name,
            .campaign-title, .project-title, .event-title,
            .modal-title {
                color: #f9fafb;
            }
            
            .greeting-subtitle, .user-role, .stat-label,
            .action-desc, .date-info, .tier-period,
            .tier-feature, .campaign-description, .project-description,
            .event-details {
                color: #d1d5db;
            }
            
            .user-profile:hover {
                background: #374151;
            }
            
            .progress-bar-container {
                background: #374151;
            }
            
            .project-category {
                background: #1e3a8a;
                color: #dbeafe;
            }
            
            .event-item:hover {
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
                background: rgba(16, 185, 129, 0.1);
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
            border: 3px solid rgba(5, 150, 105, 0.3);
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
                    <i class="fas fa-handshake"></i>
                </div>
                <div class="logo-text">Membership</div>
            </div>
            
            <div class="user-profile" onclick="window.location.href='profile.php'">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($first_name); ?></div>
                    <div class="user-role"><?php echo $current_tier; ?></div>
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
                <p class="greeting-subtitle">Join projects, partnerships, and support our mission through donations & fundraisers.</p>
                
                <div class="date-info">
                    <i class="fas fa-calendar-day"></i>
                    <span><?php echo $day_of_week; ?>, <?php echo $date; ?></span>
                    <span class="ms-3">Status: <span class="status-badge <?php echo $membership_class; ?>">
                        <i class="fas fa-<?php echo $membership ? 'check-circle' : 'user'; ?>"></i>
                        <?php echo $membership_status; ?>
                    </span></span>
                    <?php if ($membership): ?>
                    <span class="ms-3">Renews: <?php echo $renewal_date; ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        
        <!-- User Stats -->
        <section class="stats-section">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value">$<?php echo number_format($donation_stats['total_donated'], 2); ?></div>
                    <div class="stat-label">Total Donated</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo $donation_stats['total_campaigns']; ?></div>
                    <div class="stat-label">Campaigns Supported</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo $user_projects_count; ?></div>
                    <div class="stat-label">Active Projects</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo $volunteer_count; ?></div>
                    <div class="stat-label">Volunteer Roles</div>
                </div>
            </div>
        </section>
        
        <!-- Quick Actions -->
        <section class="actions-section">
            <h2 class="section-title">
                <i class="fas fa-bolt"></i>
                Get Involved
            </h2>
            
            <div class="actions-grid">
                <div class="action-card" onclick="openPartnership()">
                    <div class="action-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3 class="action-title">Partnership</h3>
                    <p class="action-desc">Join or create ministry projects</p>
                </div>
                
                <div class="action-card" onclick="openDonations()">
                    <div class="action-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3 class="action-title">Donations</h3>
                    <p class="action-desc">Support campaigns & missions</p>
                </div>
                
                <div class="action-card" onclick="openFundraising()">
                    <div class="action-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="action-title">Fundraising</h3>
                    <p class="action-desc">Join events & raise funds</p>
                </div>
                
                <div class="action-card" onclick="openMembership()">
                    <div class="action-icon">
                        <i class="fas fa-crown"></i>
                    </div>
                    <h3 class="action-title">Upgrade</h3>
                    <p class="action-desc">Upgrade your membership</p>
                </div>
            </div>
        </section>
        
        <!-- Membership Tiers -->
        <section class="tiers-section">
            <h2 class="section-title">
                <i class="fas fa-crown"></i>
                Membership Tiers
            </h2>
            
            <div class="tiers-grid">
                <?php foreach ($membership_tiers as $tier): 
                    $is_current = $current_tier == $tier['name'];
                    $is_featured = $tier['name'] == 'Partner';
                ?>
                <div class="tier-card <?php echo $is_featured ? 'featured' : ''; ?>">
                    <?php if ($is_featured): ?>
                    <div class="tier-badge">Most Popular</div>
                    <?php endif; ?>
                    
                    <h3 class="tier-name"><?php echo htmlspecialchars($tier['name']); ?></h3>
                    
                    <?php if ($tier['monthly_price'] > 0): ?>
                    <div class="tier-price">$<?php echo number_format($tier['monthly_price'], 2); ?></div>
                    <div class="tier-period">per month</div>
                    <?php else: ?>
                    <div class="tier-price">Free</div>
                    <div class="tier-period">&nbsp;</div>
                    <?php endif; ?>
                    
                    <ul class="tier-features">
                        <?php 
                        $features = json_decode($tier['features'] ?? '[]', true);
                        foreach (array_slice($features, 0, 4) as $feature): 
                        ?>
                        <li class="tier-feature">
                            <i class="fas fa-check"></i>
                            <?php echo htmlspecialchars($feature); ?>
                        </li>
                        <?php endforeach; ?>
                        <?php if (count($features) > 4): ?>
                        <li class="tier-feature">
                            <i class="fas fa-plus"></i>
                            <?php echo (count($features) - 4); ?> more benefits
                        </li>
                        <?php endif; ?>
                    </ul>
                    
                    <button class="btn-tier <?php echo $is_current ? 'btn-tier-outline' : 'btn-tier-primary'; ?>" 
                            onclick="selectTier(<?php echo $tier['id']; ?>)">
                        <?php echo $is_current ? 'Current Plan' : ($tier['monthly_price'] > 0 ? 'Upgrade Now' : 'Join Free'); ?>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        
        <!-- Active Campaigns -->
        <section class="campaigns-section">
            <h2 class="section-title">
                <i class="fas fa-heart"></i>
                Active Donation Campaigns
            </h2>
            
            <?php if (!empty($active_campaigns)): ?>
            <div class="campaigns-grid">
                <?php foreach ($active_campaigns as $campaign): ?>
                <div class="campaign-card" onclick="viewCampaign(<?php echo $campaign['id']; ?>)">
                    <div class="campaign-image">
                        <i class="fas fa-hands-helping"></i>
                    </div>
                    <div class="campaign-content">
                        <h4 class="campaign-title"><?php echo htmlspecialchars($campaign['title']); ?></h4>
                        <p class="campaign-description">
                            <?php echo htmlspecialchars(substr($campaign['description'], 0, 100)); ?>...
                        </p>
                        
                        <div class="campaign-progress">
                            <div class="progress-text">
                                <span>Raised: $<?php echo number_format($campaign['current_amount'], 2); ?></span>
                                <span><?php echo number_format($campaign['progress_percentage'], 1); ?>%</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar" style="width: <?php echo min($campaign['progress_percentage'], 100); ?>%"></div>
                            </div>
                        </div>
                        
                        <div class="campaign-stats">
                            <span><i class="fas fa-users"></i> <?php echo $campaign['donors_count']; ?> donors</span>
                            <span><i class="fas fa-clock"></i> 
                                <?php 
                                if ($campaign['end_date']) {
                                    $days_left = ceil((strtotime($campaign['end_date']) - time()) / (60 * 60 * 24));
                                    echo $days_left > 0 ? $days_left . ' days left' : 'Ended';
                                } else {
                                    echo 'Ongoing';
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-hands-helping fa-3x mb-3" style="color: #6b7280;"></i>
                <p>No active campaigns at the moment. Check back soon!</p>
            </div>
            <?php endif; ?>
        </section>
        
        <!-- Partnership Projects -->
        <section class="projects-section">
            <h2 class="section-title">
                <i class="fas fa-project-diagram"></i>
                Featured Partnership Projects
            </h2>
            
            <?php if (!empty($featured_projects)): ?>
            <div class="projects-grid">
                <?php foreach ($featured_projects as $project): ?>
                <div class="project-card">
                    <div class="project-header">
                        <div>
                            <h4 class="project-title"><?php echo htmlspecialchars($project['title']); ?></h4>
                            <div class="project-category"><?php echo ucfirst($project['category']); ?></div>
                        </div>
                        <div class="text-end">
                            <?php if ($project['progress_percentage'] > 0): ?>
                            <div style="font-size: 12px; color: var(--primary-color); font-weight: 600;">
                                <?php echo number_format($project['progress_percentage'], 1); ?>%
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <p class="project-description">
                        <?php echo htmlspecialchars(substr($project['description'], 0, 120)); ?>...
                    </p>
                    
                    <div class="project-meta">
                        <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($project['location'] ?: 'Various Locations'); ?></span>
                        <span><i class="fas fa-users"></i> <?php echo $project['team_size']; ?> volunteers</span>
                    </div>
                    
                    <button class="btn-project" onclick="viewProject(<?php echo $project['id']; ?>)">
                        <i class="fas fa-eye me-2"></i> View Project
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-project-diagram fa-3x mb-3" style="color: #6b7280;"></i>
                <p>No featured projects available. Be the first to start one!</p>
                <button class="btn btn-primary mt-3" onclick="open                <button class="btn btn-primary mt-3" onclick="openPartnership()">
                    <i class="fas fa-plus me-2"></i> Start a Project
                </button>
            </div>
            <?php endif; ?>
        </section>
        
        <!-- Upcoming Events -->
        <section class="events-section">
            <h2 class="section-title">
                <i class="fas fa-calendar-alt"></i>
                Upcoming Events
            </h2>
            
            <div class="events-list">
                <?php if (!empty($upcoming_events)): ?>
                    <?php foreach ($upcoming_events as $event): ?>
                    <div class="event-item">
                        <div class="event-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="event-content">
                            <h4 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h4>
                            <p class="event-details"><?php echo htmlspecialchars(substr($event['description'], 0, 80)); ?>...</p>
                            <div class="event-meta">
                                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location'] ?: 'Online'); ?></span>
                                <span><i class="fas fa-clock"></i> <?php echo date('M j, Y', strtotime($event['event_date'])); ?></span>
                                <span><?php echo $event['days_left']; ?> days left</span>
                            </div>
                        </div>
                        <button class="btn-event" onclick="viewEvent(<?php echo $event['id']; ?>)">
                            Register
                        </button>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-alt fa-3x mb-3" style="color: #6b7280;"></i>
                        <p>No upcoming events scheduled. Check back soon!</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
    
    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <div class="nav-items">
            <a href="index.php" class="nav-item">
                <i class="fas fa-home nav-icon"></i>
                <span class="nav-label">Home</span>
            </a>
            
            <a href="membership.php" class="nav-item">
                 <i class="fas fa-crown nav-icon"></i>
               
                <span class="nav-label">Membership</span>
            </a>
            
            <a href="partnership.php" class="nav-item active">
                <i class="fas fa-handshake nav-icon"></i>
                <span class="nav-label">Patnership</span>
            </a>
            
            <a href="donations.php" class="nav-item">
                <i class="fas fa-heart nav-icon"></i>
                <span class="nav-label">Donations</span>
            </a>
            
            <a href="profile.php" class="nav-item">
                <i class="fas fa-user nav-icon"></i>
                <span class="nav-label">Profile</span>
            </a>
        </div>
    </nav>
    
    <!-- Modals and Scripts -->
    <script>
        // Function to handle tier selection
        function selectTier(tierId) {
            // Show loading
            showNotification('Processing your request...', 'success');
            
            // In a real app, you would make an AJAX call here
            setTimeout(() => {
                // Simulate API call
                fetch('api/upgrade-membership.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        tier_id: tierId,
                        user_id: <?php echo $user_id; ?>
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Membership upgraded successfully!', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showNotification(data.message || 'Upgrade failed. Please try again.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Network error. Please check your connection.', 'error');
                });
            }, 1000);
        }
        
        // Navigation functions
        function openPartnership() {
            window.location.href = 'partnership.php';
        }
        
        function openDonations() {
            window.location.href = 'donations.php';
        }
        
        function openFundraising() {
            window.location.href = 'fundraising.php';
        }
        
        function openMembership() {
            // Scroll to membership tiers section
            document.querySelector('.tiers-section').scrollIntoView({
                behavior: 'smooth'
            });
        }
        
        function viewCampaign(campaignId) {
            window.location.href = `campaign-details.php?id=${campaignId}`;
        }
        
        function viewProject(projectId) {
            window.location.href = `project-details.php?id=${projectId}`;
        }
        
        function viewEvent(eventId) {
            window.location.href = `event-details.php?id=${eventId}`;
        }
        
        // Notification function
        function showNotification(message, type = 'success') {
            // Remove existing notifications
            const existing = document.querySelector('.notification');
            if (existing) existing.remove();
            
            // Create new notification
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        // PWA Installation
        let deferredPrompt;
        
        window.addEventListener('beforeinstallprompt', (e) => {
            // Prevent the mini-infobar from appearing on mobile
            e.preventDefault();
            // Stash the event so it can be triggered later
            deferredPrompt = e;
            // Show install button or notification
            setTimeout(() => {
                if (confirm('Install FYBS Youth App for better experience?')) {
                    deferredPrompt.prompt();
                    deferredPrompt.userChoice.then((choiceResult) => {
                        if (choiceResult.outcome === 'accepted') {
                            console.log('User accepted the install prompt');
                        } else {
                            console.log('User dismissed the install prompt');
                        }
                        deferredPrompt = null;
                    });
                }
            }, 3000);
        });
        
        // Service Worker Registration for PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js').then(registration => {
                    console.log('SW registered: ', registration);
                }).catch(registrationError => {
                    console.log('SW registration failed: ', registrationError);
                });
            });
        }
        
        // Offline detection
        window.addEventListener('online', () => {
            showNotification('You are back online!', 'success');
        });
        
        window.addEventListener('offline', () => {
            showNotification('You are offline. Some features may not work.', 'error');
        });
        
        // Pull to refresh prevention for PWA
        let touchstartY = 0;
        
        document.addEventListener('touchstart', e => {
            touchstartY = e.touches[0].clientY;
        }, { passive: true });
        
        document.addEventListener('touchmove', e => {
            const touchY = e.touches[0].clientY;
            const diff = touchY - touchstartY;
            
            if (window.scrollY === 0 && diff > 0) {
                e.preventDefault();
            }
        }, { passive: false });
        
        // Add loading state to buttons
        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('button, .action-card');
            buttons.forEach(button => {
                button.addEventListener('click', function() {
                    if (this.classList.contains('btn-tier') || this.classList.contains('btn-project') || this.classList.contains('btn-event')) {
                        const originalText = this.innerHTML;
                        this.innerHTML = '<span class="loading-spinner"></span>';
                        this.disabled = true;
                        
                        // Reset after 2 seconds if still disabled
                        setTimeout(() => {
                            if (this.disabled) {
                                this.innerHTML = originalText;
                                this.disabled = false;
                            }
                        }, 2000);
                    }
                });
            });
        });
    </script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>
<?php
// Close database connection
$conn->close();
?>