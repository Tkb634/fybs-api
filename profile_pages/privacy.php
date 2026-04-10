<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include "../config.php";
$user_id = $_SESSION['user_id'];

// Get privacy settings
$privacy_query = "SELECT * FROM privacy_settings WHERE user_id = $user_id";
$privacy_result = mysqli_query($conn, $privacy_query);
$privacy = mysqli_fetch_assoc($privacy_result) ?? [];

// Default settings
$default_settings = [
    'profile_visibility' => 'public',
    'show_prayers' => 1,
    'show_testimonies' => 1,
    'show_habits' => 1,
    'show_email' => 0,
    'show_phone' => 0,
    'data_sharing' => 1
];

foreach ($default_settings as $key => $value) {
    if (!isset($privacy[$key])) {
        $privacy[$key] = $value;
    }
}

$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $profile_visibility = mysqli_real_escape_string($conn, $_POST['profile_visibility']);
    $show_prayers = isset($_POST['show_prayers']) ? 1 : 0;
    $show_testimonies = isset($_POST['show_testimonies']) ? 1 : 0;
    $show_habits = isset($_POST['show_habits']) ? 1 : 0;
    $show_email = isset($_POST['show_email']) ? 1 : 0;
    $show_phone = isset($_POST['show_phone']) ? 1 : 0;
    $data_sharing = isset($_POST['data_sharing']) ? 1 : 0;
    
    // Create or update privacy settings
    if (mysqli_num_rows($privacy_result) > 0) {
        $update_query = "UPDATE privacy_settings SET 
            profile_visibility = '$profile_visibility',
            show_prayers = $show_prayers,
            show_testimonies = $show_testimonies,
            show_habits = $show_habits,
            show_email = $show_email,
            show_phone = $show_phone,
            data_sharing = $data_sharing,
            updated_at = NOW()
            WHERE user_id = $user_id";
    } else {
        $update_query = "INSERT INTO privacy_settings 
            (user_id, profile_visibility, show_prayers, show_testimonies, show_habits, show_email, show_phone, data_sharing)
            VALUES ($user_id, '$profile_visibility', $show_prayers, $show_testimonies, $show_habits, $show_email, $show_phone, $data_sharing)";
    }
    
    if (mysqli_query($conn, $update_query)) {
        $message = "Privacy settings updated successfully!";
        $privacy_result = mysqli_query($conn, $privacy_query);
        $privacy = mysqli_fetch_assoc($privacy_result) ?? [];
    } else {
        $message = "Error updating settings: " . mysqli_error($conn);
    }
}

// Create privacy_settings table if not exists
$create_table = "CREATE TABLE IF NOT EXISTS privacy_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    profile_visibility ENUM('public', 'members_only', 'private') DEFAULT 'public',
    show_prayers TINYINT DEFAULT 1,
    show_testimonies TINYINT DEFAULT 1,
    show_habits TINYINT DEFAULT 1,
    show_email TINYINT DEFAULT 0,
    show_phone TINYINT DEFAULT 0,
    data_sharing TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user (user_id)
)";
mysqli_query($conn, $create_table);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy & Security - FYBS Youth</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4f46e5;
            --dark-color: #1f2937;
        }
        
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
            color: white;
            padding: 25px 20px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            font-size: 20px;
            text-decoration: none;
        }
        
        .privacy-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .privacy-item {
            padding: 20px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .privacy-item:last-child {
            border-bottom: none;
        }
        
        .privacy-info h4 {
            margin: 0;
            color: #374151;
            font-weight: 600;
        }
        
        .privacy-info p {
            margin: 8px 0 0;
            color: #6b7280;
            font-size: 14px;
        }
        
        .privacy-control {
            margin-top: 10px;
        }
        
        .form-select {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px;
            width: 100%;
            max-width: 300px;
        }
        
        .form-check-input {
            width: 1.2em;
            height: 1.2em;
            margin-top: 0.3em;
        }
        
        .form-check-label {
            margin-left: 8px;
            font-weight: 500;
        }
        
        .btn-save {
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
            color: white;
            border: none;
            padding: 14px 30px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.2);
        }
        
        .message {
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 500;
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        
        .section-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .security-note {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            font-size: 14px;
            color: #92400e;
        }
        
        @media (prefers-color-scheme: dark) {
            body {
                background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
            }
            
            .privacy-card {
                background: #1f2937;
            }
            
            .privacy-item {
                border-bottom-color: #374151;
            }
            
            .privacy-info h4 {
                color: #f9fafb;
            }
            
            .privacy-info p {
                color: #d1d5db;
            }
            
            .section-title {
                color: #a78bfa;
                border-bottom-color: #374151;
            }
            
            .form-select {
                background: #374151;
                border-color: #4b5563;
                color: #f9fafb;
            }
            
            .security-note {
                background: #78350f;
                border-color: #f59e0b;
                color: #fef3c7;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="../profile.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="text-center">Privacy & Security</h1>
            <p class="text-center opacity-75">Control your privacy settings</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="privacy-card">
                <h3 class="section-title">
                    <i class="fas fa-user-shield me-2"></i>
                    Profile Privacy
                </h3>
                
                <div class="privacy-item">
                    <div class="privacy-info">
                        <h4>Profile Visibility</h4>
                        <p>Control who can see your profile</p>
                    </div>
                    <div class="privacy-control">
                        <select name="profile_visibility" class="form-select">
                            <option value="public" <?php echo $privacy['profile_visibility'] == 'public' ? 'selected' : ''; ?>>Public - Everyone can see</option>
                            <option value="members_only" <?php echo $privacy['profile_visibility'] == 'members_only' ? 'selected' : ''; ?>>Members Only - Only logged in users</option>
                            <option value="private" <?php echo $privacy['profile_visibility'] == 'private' ? 'selected' : ''; ?>>Private - Only you</option>
                        </select>
                    </div>
                </div>
                
                <h3 class="section-title mt-4">
                    <i class="fas fa-eye me-2"></i>
                    Content Visibility
                </h3>
                
                <div class="privacy-item">
                    <div class="privacy-info">
                        <h4>Show My Prayers</h4>
                        <p>Allow others to see your prayer requests</p>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="show_prayers" 
                               id="show_prayers" <?php echo $privacy['show_prayers'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="show_prayers">
                            <?php echo $privacy['show_prayers'] ? 'Visible' : 'Hidden'; ?>
                        </label>
                    </div>
                </div>
                
                <div class="privacy-item">
                    <div class="privacy-info">
                        <h4>Show My Testimonies</h4>
                        <p>Allow others to see your testimonies</p>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="show_testimonies" 
                               id="show_testimonies" <?php echo $privacy['show_testimonies'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="show_testimonies">
                            <?php echo $privacy['show_testimonies'] ? 'Visible' : 'Hidden'; ?>
                        </label>
                    </div>
                </div>
                
                <div class="privacy-item">
                    <div class="privacy-info">
                        <h4>Show My Habits</h4>
                        <p>Allow others to see your habit progress</p>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="show_habits" 
                               id="show_habits" <?php echo $privacy['show_habits'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="show_habits">
                            <?php echo $privacy['show_habits'] ? 'Visible' : 'Hidden'; ?>
                        </label>
                    </div>
                </div>
                
                <h3 class="section-title mt-4">
                    <i class="fas fa-address-card me-2"></i>
                    Contact Information
                </h3>
                
                <div class="privacy-item">
                    <div class="privacy-info">
                        <h4>Show Email Address</h4>
                        <p>Allow others to see your email</p>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="show_email" 
                               id="show_email" <?php echo $privacy['show_email'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="show_email">
                            <?php echo $privacy['show_email'] ? 'Visible' : 'Hidden'; ?>
                        </label>
                    </div>
                </div>
                
                <div class="privacy-item">
                    <div class="privacy-info">
                        <h4>Show Phone Number</h4>
                        <p>Allow others to see your phone number</p>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="show_phone" 
                               id="show_phone" <?php echo $privacy['show_phone'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="show_phone">
                            <?php echo $privacy['show_phone'] ? 'Visible' : 'Hidden'; ?>
                        </label>
                    </div>
                </div>
                
                <h3 class="section-title mt-4">
                    <i class="fas fa-database me-2"></i>
                    Data & Analytics
                </h3>
                
                <div class="privacy-item">
                    <div class="privacy-info">
                        <h4>Data Sharing</h4>
                        <p>Allow anonymous usage data to improve the app</p>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="data_sharing" 
                               id="data_sharing" <?php echo $privacy['data_sharing'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="data_sharing">
                            <?php echo $privacy['data_sharing'] ? 'Enabled' : 'Disabled'; ?>
                        </label>
                    </div>
                </div>
                
                <div class="security-note">
                    <i class="fas fa-shield-alt me-2"></i>
                    <strong>Security Note:</strong> Your data is encrypted and stored securely. 
                    We never share your personal information with third parties without your consent.
                </div>
                
                <button type="submit" class="btn btn-save mt-4">
                    <i class="fas fa-lock me-2"></i>
                    Save Privacy Settings
                </button>
            </div>
        </form>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>