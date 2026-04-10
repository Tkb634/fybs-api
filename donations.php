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

// Get user's membership status
$membership_query = "SELECT m.*, t.name as tier_name FROM user_memberships m
                    LEFT JOIN membership_tiers t ON m.tier_id = t.id
                    WHERE m.user_id = ? AND m.status = 'active'";
$stmt = $conn->prepare($membership_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$membership_result = $stmt->get_result();

if ($membership_result->num_rows > 0) {
    $membership = $membership_result->fetch_assoc();
    $current_tier = $membership['tier_name'];
} else {
    $current_tier = "Free Member";
}

// Get user's donation stats
$stats_query = "SELECT 
                COALESCE(SUM(amount), 0) as total_donated,
                COUNT(DISTINCT campaign_id) as campaigns_supported,
                COUNT(*) as total_donations,
                MAX(amount) as largest_donation
                FROM donations WHERE user_id = ? AND status = 'completed'";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats_result = $stmt->get_result();
$stats = $stats_result->fetch_assoc();

// Get recent donations
$recent_donations_query = "SELECT d.*, c.title as campaign_title
                          FROM donations d
                          LEFT JOIN donation_campaigns c ON d.campaign_id = c.id
                          WHERE d.user_id = ? AND d.status = 'completed'
                          ORDER BY d.created_at DESC
                          LIMIT 5";
$stmt = $conn->prepare($recent_donations_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_donations_result = $stmt->get_result();
$recent_donations = [];
while ($row = $recent_donations_result->fetch_assoc()) {
    $recent_donations[] = $row;
}

// Get active campaigns
$active_campaigns_query = "SELECT * FROM donation_campaigns 
                          WHERE status = 'active' 
                          ORDER BY is_featured DESC, created_at DESC 
                          LIMIT 6";
$active_campaigns_result = $conn->query($active_campaigns_query);
$active_campaigns = [];
while ($row = $active_campaigns_result->fetch_assoc()) {
    $row['progress_percentage'] = ($row['current_amount'] / $row['target_amount']) * 100;
    $active_campaigns[] = $row;
}

// Get featured campaigns
$featured_campaigns_query = "SELECT * FROM donation_campaigns 
                            WHERE status = 'active' AND is_featured = 1
                            ORDER BY created_at DESC 
                            LIMIT 3";
$featured_campaigns_result = $conn->query($featured_campaigns_query);
$featured_campaigns = [];
while ($row = $featured_campaigns_result->fetch_assoc()) {
    $row['progress_percentage'] = ($row['current_amount'] / $row['target_amount']) * 100;
    $featured_campaigns[] = $row;
}

// Get donation categories
$categories_query = "SELECT DISTINCT campaign_type as category FROM donation_campaigns 
                    WHERE campaign_type IS NOT NULL AND campaign_type != '' 
                    ORDER BY campaign_type";
$categories_result = $conn->query($categories_query);
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row['category'];
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

// Get day and date
$day_of_week = date('l');
$date = date('F j, Y');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Donations - FYBS Youth App</title>
    
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
            background: var(--gradient-danger);
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
            background: var(--gradient-danger);
        }
        
        .stat-card:nth-child(2)::before {
            background: var(--gradient-primary);
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
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.05) 100%);
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
            background: var(--gradient-danger);
        }
        
        .action-card:nth-child(2) .action-icon {
            background: var(--gradient-primary);
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
        
        /* Campaigns Section */
        .campaigns-section {
            margin-bottom: 30px;
            animation: fadeInUp 0.8s ease 0.6s both;
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
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
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
            background: var(--gradient-danger);
            border-radius: 4px;
        }
        
        .campaign-stats {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #6b7280;
        }
        
        .btn-campaign {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 10px;
            background: var(--gradient-danger);
            color: white;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn-campaign:hover {
            opacity: 0.95;
        }
        
        /* Featured Campaigns */
        .featured-section {
            margin-bottom: 30px;
            animation: fadeInUp 0.8s ease 0.8s both;
        }
        
        .featured-campaigns-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 992px) {
            .featured-campaigns-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 576px) {
            .featured-campaigns-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .featured-campaign-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            border: 2px solid var(--primary-color);
            transition: all 0.3s ease;
        }
        
        .featured-campaign-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }
        
        .featured-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--gradient-warning);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            z-index: 1;
        }
        
        /* Recent Donations */
        .donations-section {
            margin-bottom: 30px;
            animation: fadeInUp 0.8s ease 1s both;
        }
        
        .donations-list {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: var(--shadow-lg);
            border: 1px solid #e5e7eb;
        }
        
        .donation-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .donation-item:last-child {
            border-bottom: none;
        }
        
        .donation-item:hover {
            background: #f8fafc;
            border-radius: 8px;
        }
        
        .donation-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: var(--gradient-danger);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .donation-content {
            flex: 1;
        }
        
        .donation-title {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 5px;
        }
        
        .donation-details {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 5px;
        }
        
        .donation-meta {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #9ca3af;
        }
        
        .donation-amount {
            font-weight: 700;
            color: var(--primary-color);
        }
        
        /* Categories Section */
        .categories-section {
            margin-bottom: 30px;
            animation: fadeInUp 0.8s ease 1.2s both;
        }
        
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .categories-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        .category-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--shadow-md);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
            text-align: center;
            cursor: pointer;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .category-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: var(--gradient-danger);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            margin: 0 auto 15px;
            box-shadow: var(--shadow-md);
        }
        
        .category-name {
            font-weight: 600;
            font-size: 16px;
            color: var(--dark-color);
            margin-bottom: 5px;
        }
        
        .category-count {
            font-size: 12px;
            color: #6b7280;
        }
        
        /* Payment Modal Styles */
        .payment-methods {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 20px;
            background: #f9fafb;
        }
        
        .payment-methods .form-check {
            padding-left: 2.5rem;
        }
        
        .payment-methods .form-check-input {
            width: 1.2rem;
            height: 1.2rem;
            margin-left: -2.5rem;
        }
        
        .payment-methods .form-check-label {
            cursor: pointer;
            width: 100%;
        }
        
        .modal-content {
            border-radius: 20px;
            overflow: hidden;
            border: none;
            box-shadow: var(--shadow-xl);
        }
        
        .modal-header {
            background: var(--gradient-danger);
            color: white;
            border: none;
        }
        
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
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
        }
        
        /* Dark Mode */
        @media (prefers-color-scheme: dark) {
            body {
                background: #111827;
                color: #f9fafb;
            }
            
            .main-header, .welcome-card, .stat-card,
            .action-card, .campaign-card, .featured-campaign-card,
            .donations-list, .category-card {
                background: #1f2937;
                border-color: #374151;
            }
            
            .user-name, .greeting-title, .section-title,
            .action-title, .stat-value, .campaign-title,
            .donation-title, .category-name {
                color: #f9fafb;
            }
            
            .greeting-subtitle, .user-role, .stat-label,
            .action-desc, .date-info, .campaign-description,
            .donation-details, .category-count {
                color: #d1d5db;
            }
            
            .user-profile:hover {
                background: #374151;
            }
            
            .progress-bar-container {
                background: #374151;
            }
            
            .donation-item:hover {
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
            
            .payment-methods {
                background: #374151;
                border-color: #4b5563;
            }
            
            .modal-content {
                background: #1f2937;
                color: #f9fafb;
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
            border: 3px solid rgba(239, 68, 68, 0.3);
            border-radius: 50%;
            border-top-color: #ef4444;
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
        
        .notification.warning {
            background: #f59e0b;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }
        
        .empty-state-icon {
            font-size: 48px;
            color: #9ca3af;
            margin-bottom: 16px;
        }
        
        .empty-state-text {
            color: #6b7280;
            margin-bottom: 20px;
        }
        
        /* Quick Donate Form */
        .quick-donate-form {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--shadow-lg);
            border: 1px solid #e5e7eb;
            margin-bottom: 30px;
            animation: fadeInUp 0.8s ease 0.3s both;
        }
        
        .donate-amounts {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 576px) {
            .donate-amounts {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        .donate-amount {
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .donate-amount:hover, .donate-amount.active {
            border-color: var(--primary-color);
            background: rgba(5, 150, 105, 0.1);
        }
        
        .custom-amount-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 16px;
            text-align: center;
        }
        
        .custom-amount-input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        /* Transaction Status Badges */
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-failed {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <!-- Main Header -->
    <header class="main-header">
        <div class="header-content">
            <div class="app-logo">
                <div class="logo-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="logo-text">Donations</div>
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
                <p class="greeting-subtitle">Support meaningful causes, make a difference, and help transform lives through your generous donations.</p>
                
                <div class="date-info">
                    <i class="fas fa-calendar-day"></i>
                    <span><?php echo $day_of_week; ?>, <?php echo $date; ?></span>
                    <span class="ms-3">Impact Level: <span class="badge bg-danger">Donor</span></span>
                </div>
            </div>
        </section>
        
        <!-- User Stats -->
        <section class="stats-section">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value">$<?php echo number_format($stats['total_donated'], 2); ?></div>
                    <div class="stat-label">Total Donated</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['campaigns_supported']; ?></div>
                    <div class="stat-label">Campaigns Supported</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['total_donations']; ?></div>
                    <div class="stat-label">Total Donations</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value">$<?php echo number_format($stats['largest_donation'], 2); ?></div>
                    <div class="stat-label">Largest Donation</div>
                </div>
            </div>
        </section>
        
        <!-- Quick Donate -->
        <div class="quick-donate-form">
            <h3 class="section-title">
                <i class="fas fa-bolt"></i>
                Quick Donate
            </h3>
            <p class="text-muted mb-3">Make a quick donation to our general fund</p>
            
            <div class="donate-amounts">
                <div class="donate-amount" onclick="setAmount(5)">
                    <div class="fw-bold">$5</div>
                    <small class="text-muted">Quick Give</small>
                </div>
                <div class="donate-amount" onclick="setAmount(10)">
                    <div class="fw-bold">$10</div>
                    <small class="text-muted">Basic Support</small>
                </div>
                <div class="donate-amount" onclick="setAmount(25)">
                    <div class="fw-bold">$25</div>
                    <small class="text-muted">Regular Donor</small>
                </div>
                <div class="donate-amount" onclick="setAmount(50)">
                    <div class="fw-bold">$50</div>
                    <small class="text-muted">Generous Gift</small>
                </div>
            </div>
            
            <div class="row g-3">
                <div class="col-md-8">
                    <input type="number" class="custom-amount-input" placeholder="Custom Amount" min="1" step="0.01" id="customAmount">
                </div>
                <div class="col-md-4">
                    <button class="btn-campaign w-100" onclick="makeQuickDonation()">
                        <i class="fas fa-donate me-2"></i>Donate Now
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <section class="actions-section">
            <h2 class="section-title">
                <i class="fas fa-bolt"></i>
                Quick Actions
            </h2>
            
            <div class="actions-grid">
                <div class="action-card" onclick="browseCampaigns()">
                    <div class="action-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3 class="action-title">Browse Campaigns</h3>
                    <p class="action-desc">Find causes to support</p>
                </div>
                
                <div class="action-card" onclick="window.location.href='recurring-donations.php'">
                    <div class="action-icon">
                        <i class="fas fa-redo"></i>
                    </div>
                    <h3 class="action-title">Recurring Donations</h3>
                    <p class="action-desc">Set up automatic giving</p>
                </div>
                
                <div class="action-card" onclick="window.location.href='donation-history.php'">
                    <div class="action-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <h3 class="action-title">Donation History</h3>
                    <p class="action-desc">View all your donations</p>
                </div>
                
                <div class="action-card" onclick="window.location.href='impact-stories.php'">
                    <div class="action-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3 class="action-title">Impact Stories</h3>
                    <p class="action-desc">See how donations help</p>
                </div>
            </div>
        </section>
        
        <!-- Featured Campaigns -->
        <section class="featured-section">
            <h2 class="section-title">
                <i class="fas fa-star"></i>
                Featured Campaigns
            </h2>
            
            <?php if (!empty($featured_campaigns)): ?>
            <div class="featured-campaigns-grid">
                <?php foreach ($featured_campaigns as $campaign): ?>
                <div class="featured-campaign-card">
                    <div class="featured-badge">
                        <i class="fas fa-star me-1"></i>Featured
                    </div>
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
                        
                        <button class="btn-campaign" onclick="donateToCampaign(<?php echo $campaign['id']; ?>, '<?php echo addslashes($campaign['title']); ?>')">
                            <i class="fas fa-donate me-2"></i>Support This Cause
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-hands-helping"></i>
                </div>
                <h4 class="empty-state-text">No featured campaigns at the moment</h4>
                <p class="text-muted">Check back soon for new donation opportunities!</p>
            </div>
            <?php endif; ?>
        </section>
        
        <!-- Active Campaigns -->
        <section class="campaigns-section">
            <h2 class="section-title">
                <i class="fas fa-heart"></i>
                Active Campaigns
            </h2>
            
            <?php if (!empty($active_campaigns)): ?>
            <div class="campaigns-grid">
                <?php foreach ($active_campaigns as $campaign): ?>
                <div class="campaign-card">
                    <div class="campaign-image">
                        <i class="fas fa-hand-holding-heart"></i>
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
                            <span><i class="fas fa-tag"></i> <?php echo ucfirst($campaign['campaign_type'] ?? 'General'); ?></span>
                        </div>
                        
                        <button class="btn-campaign" onclick="viewCampaign(<?php echo $campaign['id']; ?>)">
                            <i class="fas fa-eye me-2"></i> View Details
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h4 class="empty-state-text">No active campaigns at the moment</h4>
                <button class="btn btn-primary" onclick="browseCampaigns()">
                    <i class="fas fa-search me-2"></i> Browse All Campaigns
                </button>
            </div>
            <?php endif; ?>
        </section>
        
        <!-- Recent Donations -->
        <section class="donations-section">
            <h2 class="section-title">
                <i class="fas fa-history"></i>
                Recent Donations
            </h2>
            
            <?php if (!empty($recent_donations)): ?>
            <div class="donations-list">
                <?php foreach ($recent_donations as $donation): ?>
                <div class="donation-item">
                    <div class="donation-icon">
                        <i class="fas fa-donate"></i>
                    </div>
                    <div class="donation-content">
                        <h4 class="donation-title"><?php echo htmlspecialchars($donation['campaign_title'] ?? 'General Donation'); ?></h4>
                        <p class="donation-details">
                            <?php echo date('F j, Y', strtotime($donation['donation_date'])); ?>
                        </p>
                        <div class="donation-meta">
                            <span>Transaction #<?php echo substr($donation['transaction_id'] ?? 'N/A', 0, 8); ?></span>
                            <span class="donation-amount">$<?php echo number_format($donation['amount'], 2); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-history"></i>
                </div>
                <h4 class="empty-state-text">No donation history yet</h4>
                <p class="text-muted">Make your first donation to see it here!</p>
                <button class="btn btn-primary mt-3" onclick="makeQuickDonation()">
                    <i class="fas fa-donate me-2"></i> Make Your First Donation
                </button>
            </div>
            <?php endif; ?>
        </section>
        
        <!-- Donation Categories -->
        <section class="categories-section">
            <h2 class="section-title">
                <i class="fas fa-tags"></i>
                Donation Categories
            </h2>
            
            <?php if (!empty($categories)): ?>
            <div class="categories-grid">
                <?php 
                $category_icons = [
                    'mission' => 'fas fa-globe',
                    'project' => 'fas fa-project-diagram',
                    'emergency' => 'fas fa-first-aid',
                    'scholarship' => 'fas fa-graduation-cap',
                    'general' => 'fas fa-heart'
                ];
                
                foreach ($categories as $category): 
                    $icon = $category_icons[strtolower($category)] ?? 'fas fa-heart';
                ?>
                <div class="category-card" onclick="filterByCategory('<?php echo $category; ?>')">
                    <div class="category-icon">
                        <i class="<?php echo $icon; ?>"></i>
                    </div>
                    <div class="category-name"><?php echo ucfirst($category); ?></div>
                    <div class="category-count">
                        <?php 
                        $count_query = "SELECT COUNT(*) as count FROM donation_campaigns 
                                      WHERE campaign_type = ? AND status = 'active'";
                        $stmt = $conn->prepare($count_query);
                        $stmt->bind_param("s", $category);
                        $stmt->execute();
                        $count_result = $stmt->get_result();
                        $count = $count_result->fetch_assoc()['count'];
                        echo $count . ' campaigns';
                        ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <h4 class="empty-state-text">No donation categories available</h4>
            </div>
            <?php endif; ?>
        </section>
    </main>
    
    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Complete Your Donation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="paymentDetails">
                        <h6 class="mb-3">Donation Summary</h6>
                        <div class="row mb-3">
                            <div class="col-6">
                                <small class="text-muted">Amount:</small>
                                <div class="fw-bold" id="paymentAmount">$0.00</div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Campaign:</small>
                                <div class="fw-bold" id="paymentCampaign">General Fund</div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h6 class="mb-3">Select Payment Method</h6>
                        
                        <div class="payment-methods">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="paymentMethod" id="ecocash" value="ecocash" checked>
                                <label class="form-check-label" for="ecocash">
                                    <i class="fas fa-mobile-alt text-success me-2"></i>
                                    <strong>Ecocash</strong>
                                    <small class="d-block text-muted">Pay with your mobile money</small>
                                </label>
                            </div>
                            
                            <div id="ecocashDetails" class="mt-3">
                                <label for="phoneNumber" class="form-label">Ecocash Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text">+263</span>
                                    <input type="tel" class="form-control" id="phoneNumber" placeholder="77 123 4567" maxlength="9">
                                </div>
                                <div class="form-text">You'll receive a prompt on your phone to confirm payment</div>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="paymentMethod" id="bank" value="bank">
                                <label class="form-check-label" for="bank">
                                    <i class="fas fa-university text-primary me-2"></i>
                                    <strong>Bank Transfer</strong>
                                    <small class="d-block text-muted">Transfer to our bank account</small>
                                </label>
                            </div>
                            
                            <div id="bankDetails" class="mt-3" style="display: none;">
                                <div class="alert alert-info">
                                    <strong>Bank Details:</strong><br>
                                    Bank: CBZ Bank<br>
                                    Account Name: FYBS Youth Foundation<br>
                                    Account Number: 4567890123<br>
                                    Branch: Harare Main<br>
                                    <small>Use your name as reference</small>
                                </div>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="paymentMethod" id="card" value="card">
                                <label class="form-check-label" for="card">
                                    <i class="fas fa-credit-card text-warning me-2"></i>
                                    <strong>Credit/Debit Card</strong>
                                    <small class="d-block text-muted">Pay with Visa/Mastercard</small>
                                </label>
                            </div>
                            
                            <div id="cardDetails" class="mt-3" style="display: none;">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="cardNumber" class="form-label">Card Number</label>
                                        <input type="text" class="form-control" id="cardNumber" placeholder="1234 5678 9012 3456" maxlength="19">
                                    </div>
                                    <div class="col-6">
                                        <label for="expiryDate" class="form-label">Expiry Date</label>
                                        <input type="text" class="form-control" id="expiryDate" placeholder="MM/YY" maxlength="5">
                                    </div>
                                    <div class="col-6">
                                        <label for="cvv" class="form-label">CVV</label>
                                        <input type="text" class="form-control" id="cvv" placeholder="123" maxlength="3">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <small>Your donation will be confirmed once payment is received</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmPayment">
                        <i class="fas fa-lock me-2"></i>Confirm Payment
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Processing Modal -->
    <div class="modal fade" id="processingModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-5">
                    <div class="spinner-border text-danger mb-3" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5>Processing Your Payment</h5>
                    <p class="text-muted">Please wait while we process your donation...</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-5">
                    <div class="mb-4">
                        <div class="rounded-circle bg-success d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                            <i class="fas fa-check fa-2x text-white"></i>
                        </div>
                    </div>
                    <h4 class="mb-3">Donation Successful!</h4>
                    <p id="successMessage" class="text-muted mb-4"></p>
                    <div class="alert alert-success" id="paymentInstructions"></div>
                    <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal">
                        <i class="fas fa-thumbs-up me-2"></i>Continue
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <div class="nav-items">
            <a href="dashboard.php" class="nav-item">
                <i class="fas fa-home nav-icon"></i>
                <span class="nav-label">Home</span>
            </a>
            
            <a href="partnership.php" class="nav-item">
                <i class="fas fa-handshake nav-icon"></i>
                <span class="nav-label">Partnership</span>
            </a>
            
            <a href="membership.php" class="nav-item">
                <i class="fas fa-crown nav-icon"></i>
                <span class="nav-label">Membership</span>
            </a>
            
            <a href="donations.php" class="nav-item active">
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
        // Payment variables
        let currentDonationAmount = 10; // Default amount
        let currentCampaignId = 0; // 0 for general fund
        let currentCampaignTitle = "General Fund";
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Auto select $10 by default
            setTimeout(() => {
                const defaultAmount = document.querySelectorAll('.donate-amount')[1];
                if (defaultAmount) {
                    defaultAmount.click();
                }
            }, 100);
            
            // Payment method toggle
            document.querySelectorAll('input[name="paymentMethod"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    document.getElementById('ecocashDetails').style.display = 
                        this.value === 'ecocash' ? 'block' : 'none';
                    document.getElementById('bankDetails').style.display = 
                        this.value === 'bank' ? 'block' : 'none';
                    document.getElementById('cardDetails').style.display = 
                        this.value === 'card' ? 'block' : 'none';
                });
            });
            
            // Format phone number input
            document.getElementById('phoneNumber').addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 9) value = value.substring(0, 9);
                e.target.value = value;
            });
            
            // Format card number input
            document.getElementById('cardNumber').addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                value = value.replace(/(.{4})/g, '$1 ').trim();
                if (value.length > 19) value = value.substring(0, 19);
                e.target.value = value;
            });
            
            // Format expiry date input
            document.getElementById('expiryDate').addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 2) {
                    value = value.substring(0, 2) + '/' + value.substring(2);
                }
                if (value.length > 5) value = value.substring(0, 5);
                e.target.value = value;
            });
            
            // Format CVV input
            document.getElementById('cvv').addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 3) value = value.substring(0, 3);
                e.target.value = value;
            });
            
            // Confirm payment button click
            document.getElementById('confirmPayment').addEventListener('click', processPayment);
        });
        
        // Quick donate functions
        function setAmount(amount) {
            currentDonationAmount = amount;
            document.getElementById('customAmount').value = amount;
            
            // Highlight selected amount
            document.querySelectorAll('.donate-amount').forEach(el => {
                el.classList.remove('active');
            });
            event.target.closest('.donate-amount').classList.add('active');
        }
        
        function makeQuickDonation() {
            const customAmount = parseFloat(document.getElementById('customAmount').value);
            currentDonationAmount = customAmount || currentDonationAmount;
            
            if (!currentDonationAmount || currentDonationAmount <= 0) {
                showNotification('Please enter a valid donation amount', 'error');
                return;
            }
            
            // Set general donation details
            currentCampaignId = 0;
            currentCampaignTitle = "General Fund";
            
            // Show payment modal
            showPaymentModal();
        }
        
        function donateToCampaign(campaignId, campaignTitle) {
            currentCampaignId = campaignId;
            currentCampaignTitle = campaignTitle;
            
            // Show payment modal with campaign info
            showPaymentModal();
        }
        
        function showPaymentModal() {
            document.getElementById('paymentAmount').textContent = '$' + currentDonationAmount.toFixed(2);
            document.getElementById('paymentCampaign').textContent = currentCampaignTitle;
            
            const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
            paymentModal.show();
        }
        
        // Process payment function
        async function processPayment() {
            const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked').value;
            let phone = '';
            let cardData = {};
            
            // Validate inputs based on payment method
            if (paymentMethod === 'ecocash') {
                phone = document.getElementById('phoneNumber').value.trim();
                if (!phone || phone.length !== 9) {
                    showNotification('Please enter a valid 9-digit Ecocash number', 'error');
                    return;
                }
            } else if (paymentMethod === 'card') {
                const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
                const expiryDate = document.getElementById('expiryDate').value;
                const cvv = document.getElementById('cvv').value;
                
                if (!cardNumber || cardNumber.length !== 16) {
                    showNotification('Please enter a valid 16-digit card number', 'error');
                    return;
                }
                
                if (!expiryDate || !expiryDate.match(/^\d{2}\/\d{2}$/)) {
                    showNotification('Please enter a valid expiry date (MM/YY)', 'error');
                    return;
                }
                
                if (!cvv || cvv.length !== 3) {
                    showNotification('Please enter a valid 3-digit CVV', 'error');
                    return;
                }
                
                cardData = { cardNumber, expiryDate, cvv };
            }
            
            // Show processing modal
            const processingModal = new bootstrap.Modal(document.getElementById('processingModal'));
            processingModal.show();
            
            // Close payment modal
            const paymentModal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
            paymentModal.hide();
            
            try {
                const formData = new FormData();
                formData.append('amount', currentDonationAmount);
                formData.append('campaign_id', currentCampaignId);
                formData.append('payment_method', paymentMethod);
                formData.append('phone', phone);
                formData.append('card_data', JSON.stringify(cardData));
                formData.append('action', 'process_donation');
                
                const response = await fetch('process-payment.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                // Hide processing modal
                processingModal.hide();
                
                if (result.success) {
                    // Show success message
                    showSuccessModal(result, paymentMethod);
                    
                    // Reset form
                    resetDonationForm();
                    
                } else {
                    showNotification(result.error || 'Payment failed. Please try again.', 'error');
                }
                
            } catch (error) {
                processingModal.hide();
                showNotification('Network error. Please check your connection.', 'error');
            }
        }
        
        function showSuccessModal(result, paymentMethod) {
            const successMessage = document.getElementById('successMessage');
            const paymentInstructions = document.getElementById('paymentInstructions');
            
            successMessage.textContent = `Thank you for your donation of $${result.amount.toFixed(2)} to ${currentCampaignTitle}!`;
            
            if (paymentMethod === 'ecocash') {
                paymentInstructions.innerHTML = `
                    <strong>Ecocash Payment Instructions:</strong><br>
                    1. Check your phone for payment prompt<br>
                    2. Enter your Ecocash PIN when prompted<br>
                    3. You'll receive an SMS confirmation<br>
                    <small>Transaction ID: ${result.transaction_id}</small>
                `;
            } else if (paymentMethod === 'bank') {
                paymentInstructions.innerHTML = `
                    <strong>Bank Transfer Instructions:</strong><br>
                    1. Transfer $${result.amount.toFixed(2)} to our bank account<br>
                    2. Use reference: ${result.transaction_id}<br>
                    3. Email receipt to donations@fybs.org<br>
                    <small>Transaction ID: ${result.transaction_id}</small>
                `;
            } else if (paymentMethod === 'card') {
                paymentInstructions.innerHTML = `
                    <strong>Card Payment Confirmed!</strong><br>
                    Your card has been charged $${result.amount.toFixed(2)}<br>
                    A receipt has been sent to your email<br>
                    <small>Transaction ID: ${result.transaction_id}</small>
                `;
            }
            
            const successModal = new bootstrap.Modal(document.getElementById('successModal'));
            successModal.show();
            
            // Refresh page after modal is closed
            document.getElementById('successModal').addEventListener('hidden.bs.modal', function() {
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            });
        }
        
        function resetDonationForm() {
            document.getElementById('customAmount').value = '';
            document.querySelectorAll('.donate-amount').forEach(el => {
                el.classList.remove('active');
            });
            currentDonationAmount = 10; // Reset to default
            document.getElementById('phoneNumber').value = '';
            document.getElementById('cardNumber').value = '';
            document.getElementById('expiryDate').value = '';
            document.getElementById('cvv').value = '';
        }
        
        // Navigation functions
        function browseCampaigns() {
            window.location.href = 'browse-campaigns.php';
        }
        
        function viewCampaign(campaignId) {
            window.location.href = `campaign-details.php?id=${campaignId}`;
        }
        
        function filterByCategory(category) {
            window.location.href = `browse-campaigns.php?category=${encodeURIComponent(category)}`;
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
            e.preventDefault();
            deferredPrompt = e;
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
            showNotification('You are offline. Some features may not work.', 'warning');
        });
        
        // Add loading state to buttons
        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('.btn-campaign');
            buttons.forEach(button => {
                button.addEventListener('click', function() {
                    if (!this.disabled) {
                        const originalText = this.innerHTML;
                        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Processing...';
                        this.disabled = true;
                        
                        setTimeout(() => {
                            if (this.disabled) {
                                this.innerHTML = originalText;
                                this.disabled = false;
                            }
                        }, 3000);
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