<?php
// Start session to access logged-in user data
session_start();

// If user is not logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
include "config.php";
$user_id = $_SESSION['user_id'];

// Get user name - using available session data
if (isset($_SESSION['full_name']) && !empty($_SESSION['full_name'])) {
    $user_name = $_SESSION['full_name'];
} elseif (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];
    $name_part = explode('@', $email)[0];
    $user_name = ucwords(str_replace('.', ' ', $name_part));
} elseif (isset($_SESSION['username'])) {
    $user_name = $_SESSION['username'];
} else {
    $user_name = "User";
}

// Get user role for display
$user_role = isset($_SESSION['user_role']) ? ucfirst($_SESSION['user_role']) : 'Member';

// Get profile image from database
$profile_image = null;
$profile_sql = "SELECT profile_image, created_at FROM users WHERE id = $user_id LIMIT 1";
$profile_result = mysqli_query($conn, $profile_sql);
if ($profile_result && mysqli_num_rows($profile_result) > 0) {
    $profile_row = mysqli_fetch_assoc($profile_result);
    $profile_image = $profile_row['profile_image'];
    $join_date = !empty($profile_row['created_at']) ? date('M Y', strtotime($profile_row['created_at'])) : date('M Y');
} else {
    $join_date = date('M Y');
}

// Get user statistics
$prayer_count = 0;
$testimony_count = 0;
$habits_completed = 0;

// Get prayer count
$prayer_sql = "SELECT COUNT(*) as count FROM prayer_requests WHERE user_id = $user_id";
$prayer_result = mysqli_query($conn, $prayer_sql);
if ($prayer_result) {
    $prayer_row = mysqli_fetch_assoc($prayer_result);
    $prayer_count = $prayer_row['count'];
}

// Get testimony count
$testimony_sql = "SELECT COUNT(*) as count FROM testimonies WHERE user_id = $user_id";
$testimony_result = mysqli_query($conn, $testimony_sql);
if ($testimony_result) {
    $testimony_row = mysqli_fetch_assoc($testimony_result);
    $testimony_count = $testimony_row['count'];
}

// Get total habits completed
$habits_sql = "SELECT COUNT(*) as count FROM habit_logs WHERE user_id = $user_id";
$habits_result = mysqli_query($conn, $habits_sql);
if ($habits_result) {
    $habits_row = mysqli_fetch_assoc($habits_result);
    $habits_completed = $habits_row['count'];
}

// Handle profile image upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_image'])) {
    $upload_dir = "uploads/profile_images/";
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_name = time() . '_' . basename($_FILES['profile_image']['name']);
    $target_file = $upload_dir . $file_name;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    $check = getimagesize($_FILES['profile_image']['tmp_name']);
    if ($check !== false) {
        if ($_FILES['profile_image']['size'] <= 2097152) {
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($imageFileType, $allowed_types)) {
                if (!empty($profile_image) && file_exists($profile_image)) {
                    unlink($profile_image);
                }
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                    $update_sql = "UPDATE users SET profile_image = '$target_file' WHERE id = $user_id";
                    if (mysqli_query($conn, $update_sql)) {
                        $profile_image = $target_file;
                        $_SESSION['success'] = "Profile picture updated!";
                    }
                }
            } else {
                $_SESSION['error'] = "Only JPG, JPEG, PNG & GIF files allowed.";
            }
        } else {
            $_SESSION['error'] = "File too large. Max 2MB.";
        }
    } else {
        $_SESSION['error'] = "File is not an image.";
    }
    
    header("Location: profile.php");
    exit();
}

$first_name = explode(' ', $user_name)[0];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
    <title>Profile - CYIC Youth App</title>
    
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
        }

        /* Header with Gradient */
        .profile-header {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            padding: 40px 20px 60px;
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: "✨";
            position: absolute;
            bottom: -30px;
            right: -20px;
            font-size: 120px;
            opacity: 0.1;
        }

        .back-button {
            position: absolute;
            top: 16px;
            left: 16px;
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            backdrop-filter: blur(10px);
        }

        .profile-avatar-wrapper {
            text-align: center;
            position: relative;
            margin-bottom: 16px;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #f59e0b, #ec4899);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 42px;
            font-weight: 600;
            color: white;
            margin: 0 auto;
            border: 4px solid rgba(255,255,255,0.3);
            box-shadow: var(--shadow-lg);
            background-size: cover;
            background-position: center;
        }

        .camera-btn {
            position: absolute;
            bottom: 0;
            right: calc(50% - 60px);
            width: 36px;
            height: 36px;
            background: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            box-shadow: var(--shadow-md);
            cursor: pointer;
            color: var(--primary);
        }

        .profile-name {
            text-align: center;
            font-size: 24px;
            font-weight: 700;
            color: white;
            margin-bottom: 4px;
        }

        .profile-role {
            text-align: center;
            font-size: 14px;
            color: rgba(255,255,255,0.8);
            margin-bottom: 12px;
        }

        .member-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 12px;
            color: white;
            text-align: center;
        }

        /* Stats Cards - Overlapping Header */
        .stats-section {
            padding: 0 16px;
            margin-top: -30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--radius-sm);
            padding: 16px 12px;
            text-align: center;
            box-shadow: var(--shadow-md);
            transition: transform 0.1s;
        }

        .stat-card:active {
            transform: scale(0.97);
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            color: white;
            font-size: 18px;
        }

        .stat-number {
            font-size: 24px;
            font-weight: 800;
            color: var(--dark);
        }

        .stat-label {
            font-size: 11px;
            color: var(--gray);
        }

        /* Menu Section */
        .menu-section {
            padding: 24px 16px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 16px;
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

        .menu-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .menu-item {
            background: var(--white);
            border-radius: var(--radius-sm);
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
            color: inherit;
            box-shadow: var(--shadow-sm);
            transition: transform 0.1s;
        }

        .menu-item:active {
            transform: scale(0.98);
        }

        .menu-icon {
            width: 48px;
            height: 48px;
            background: var(--gray-light);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: var(--primary);
        }

        .menu-content {
            flex: 1;
        }

        .menu-title {
            font-weight: 600;
            font-size: 15px;
            color: var(--dark);
            margin-bottom: 2px;
        }

        .menu-desc {
            font-size: 12px;
            color: var(--gray);
        }

        .menu-arrow {
            color: var(--gray);
            font-size: 14px;
        }

        /* Logout Card */
        .logout-section {
            padding: 0 16px 24px;
        }

        .logout-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 24px;
            text-align: center;
            box-shadow: var(--shadow-sm);
        }

        .logout-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            color: white;
            font-size: 24px;
        }

        .logout-title {
            font-weight: 700;
            font-size: 18px;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .logout-desc {
            font-size: 13px;
            color: var(--gray);
            margin-bottom: 20px;
        }

        .logout-btn {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border: none;
            padding: 12px 28px;
            border-radius: 40px;
            color: white;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: transform 0.1s;
        }

        .logout-btn:active {
            transform: scale(0.96);
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

        /* Modal Styles */
        .modal-backdrop-custom {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.2s ease;
        }

        .modal-card {
            background: var(--white);
            border-radius: 24px;
            width: 300px;
            padding: 24px;
            text-align: center;
            animation: slideUp 0.2s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Toast */
        .toast-message {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--dark);
            color: white;
            padding: 12px 20px;
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
            .stat-card, .menu-item, .logout-card {
                background: #1e293b;
            }
            .stat-number, .menu-title, .logout-title, .section-title {
                color: #f1f5f9;
            }
            .stat-label, .menu-desc, .logout-desc {
                color: #94a3b8;
            }
            .menu-icon {
                background: #334155;
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
            .modal-card {
                background: #1e293b;
            }
        }

        @media (display-mode: standalone) {
            .bottom-nav {
                padding-bottom: max(20px, env(safe-area-inset-bottom));
            }
            .profile-header {
                padding-top: max(40px, env(safe-area-inset-top));
            }
        }
    </style>
</head>
<body>
<div class="app-container">
    <!-- Profile Header -->
    <div class="profile-header">
        <a href="index.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
        </a>
        
        <div class="profile-avatar-wrapper">
            <div class="profile-avatar" id="profileAvatar" style="<?php 
                if(!empty($profile_image) && file_exists($profile_image)) {
                    echo 'background-image: url(\'' . htmlspecialchars($profile_image) . '\'); background-size: cover;';
                }
            ?>">
                <?php if(empty($profile_image) || !file_exists($profile_image)): ?>
                    <?php echo strtoupper(substr($first_name, 0, 1)); ?>
                <?php endif; ?>
            </div>
            <button class="camera-btn" onclick="document.getElementById('imageUpload').click()">
                <i class="fas fa-camera"></i>
            </button>
        </div>
        
        <h2 class="profile-name"><?php echo htmlspecialchars($first_name); ?></h2>
        <div class="profile-role"><?php echo htmlspecialchars($user_role); ?></div>
        <div class="member-badge">
            <i class="far fa-calendar-alt me-1"></i> Member since <?php echo $join_date; ?>
        </div>
    </div>

    <!-- Hidden File Input -->
    <form id="uploadForm" method="POST" enctype="multipart/form-data" style="display: none;">
        <input type="file" id="imageUpload" name="profile_image" accept="image/*" onchange="uploadImage(this)">
    </form>

    <!-- Stats Section -->
    <div class="stats-section">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-hands-praying"></i></div>
                <div class="stat-number"><?php echo $prayer_count; ?></div>
                <div class="stat-label">Prayers</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-star"></i></div>
                <div class="stat-number"><?php echo $testimony_count; ?></div>
                <div class="stat-label">Testimonies</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-number"><?php echo $habits_completed; ?></div>
                <div class="stat-label">Habits</div>
            </div>
        </div>
    </div>

    <!-- Menu Section -->
    <div class="menu-section">
        <div class="section-header">
            <div class="section-title"><i class="fas fa-sliders-h"></i> Settings</div>
        </div>
        
        <div class="menu-list">
            <a href="profile_pages/edit_profile.php" class="menu-item">
                <div class="menu-icon"><i class="fas fa-user-edit"></i></div>
                <div class="menu-content">
                    <div class="menu-title">Edit Profile</div>
                    <div class="menu-desc">Update your personal info</div>
                </div>
                <div class="menu-arrow"><i class="fas fa-chevron-right"></i></div>
            </a>
            
            <a href="profile_pages/notifications.php" class="menu-item">
                <div class="menu-icon"><i class="fas fa-bell"></i></div>
                <div class="menu-content">
                    <div class="menu-title">Notifications</div>
                    <div class="menu-desc">Manage alerts and reminders</div>
                </div>
                <div class="menu-arrow"><i class="fas fa-chevron-right"></i></div>
            </a>
            
            <a href="profile_pages/privacy.php" class="menu-item">
                <div class="menu-icon"><i class="fas fa-lock"></i></div>
                <div class="menu-content">
                    <div class="menu-title">Privacy & Security</div>
                    <div class="menu-desc">Control your data privacy</div>
                </div>
                <div class="menu-arrow"><i class="fas fa-chevron-right"></i></div>
            </a>
            
            <a href="profile_pages/help.php" class="menu-item">
                <div class="menu-icon"><i class="fas fa-question-circle"></i></div>
                <div class="menu-content">
                    <div class="menu-title">Help & Support</div>
                    <div class="menu-desc">FAQs and contact support</div>
                </div>
                <div class="menu-arrow"><i class="fas fa-chevron-right"></i></div>
            </a>
            
            <a href="profile_pages/about.php" class="menu-item">
                <div class="menu-icon"><i class="fas fa-info-circle"></i></div>
                <div class="menu-content">
                    <div class="menu-title">About</div>
                    <div class="menu-desc">Version 1.0.0 · CYIC Youth</div>
                </div>
                <div class="menu-arrow"><i class="fas fa-chevron-right"></i></div>
            </a>
        </div>
    </div>

    <!-- Logout Section -->
    <div class="logout-section">
        <div class="logout-card">
            <div class="logout-icon"><i class="fas fa-sign-out-alt"></i></div>
            <h4 class="logout-title">Logout Account</h4>
            <p class="logout-desc">You can always come back to continue your journey</p>
            <button class="logout-btn" onclick="showLogoutModal()">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </div>
</div>

<!-- Bottom Navigation -->
<div class="bottom-nav">
    <div class="nav-links">
        <a href="index.php" class="nav-link-item"><i class="fas fa-home"></i><span>Home</span></a>
        <a href="fybs.php" class="nav-link-item"><i class="fas fa-book-bible"></i><span>FYBS</span></a>
        <a href="gyc.php" class="nav-link-item"><i class="fas fa-comments"></i><span>GYC</span></a>
        <a href="life.php" class="nav-link-item"><i class="fas fa-bolt"></i><span>LIFE</span></a>
        <a href="profile.php" class="nav-link-item active"><i class="fas fa-user"></i><span>Profile</span></a>
    </div>
</div>

<script>
    // Image upload handling
    function uploadImage(input) {
        const file = input.files[0];
        if (!file) return;
        
        if (file.size > 2097152) {
            showToast("Image too large. Max 2MB", "error");
            return;
        }
        
        const allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!allowed.includes(file.type)) {
            showToast("Only JPG, PNG, GIF allowed", "error");
            return;
        }
        
        // Preview
        const reader = new FileReader();
        reader.onload = function(e) {
            const avatar = document.getElementById('profileAvatar');
            avatar.style.backgroundImage = `url('${e.target.result}')`;
            avatar.style.backgroundSize = 'cover';
            avatar.innerHTML = '';
        };
        reader.readAsDataURL(file);
        
        showToast("Uploading...", "info");
        document.getElementById('uploadForm').submit();
    }
    
    // Show toast notification
    function showToast(message, type = "info") {
        const colors = { success: "#10b981", error: "#ef4444", info: "#3b82f6", warning: "#f59e0b" };
        const toast = document.createElement('div');
        toast.className = 'toast-message';
        toast.style.background = colors[type] || colors.info;
        toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>${message}`;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }
    
    // Logout modal
    function showLogoutModal() {
        const modal = document.createElement('div');
        modal.className = 'modal-backdrop-custom';
        modal.innerHTML = `
            <div class="modal-card">
                <div style="width: 56px; height: 56px; background: linear-gradient(135deg, #ef4444, #dc2626); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; color: white; font-size: 24px;">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <h5 style="font-weight: 700; margin-bottom: 8px;">Logout?</h5>
                <p style="color: var(--gray); font-size: 13px; margin-bottom: 20px;">Are you sure you want to logout?</p>
                <div style="display: flex; gap: 10px;">
                    <button onclick="this.closest('.modal-backdrop-custom').remove()" style="flex: 1; padding: 12px; background: var(--gray-light); border: none; border-radius: 40px; font-weight: 600; cursor: pointer;">Cancel</button>
                    <button onclick="performLogout()" style="flex: 1; padding: 12px; background: linear-gradient(135deg, #ef4444, #dc2626); border: none; border-radius: 40px; color: white; font-weight: 600; cursor: pointer;">Logout</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    function performLogout() {
        window.location.href = 'logout.php';
    }
    
    // Display PHP messages
    <?php if (isset($_SESSION['success'])): ?>
        showToast("<?php echo $_SESSION['success']; ?>", "success");
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        showToast("<?php echo $_SESSION['error']; ?>", "error");
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    // Add ripple effect to menu items
    document.querySelectorAll('.menu-item, .stat-card, .logout-btn').forEach(el => {
        el.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size/2;
            const y = e.clientY - rect.top - size/2;
            ripple.style.cssText = `position:absolute; border-radius:50%; background:rgba(79,70,229,0.2); width:${size}px; height:${size}px; top:${y}px; left:${x}px; pointer-events:none; animation:ripple 0.4s ease-out;`;
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