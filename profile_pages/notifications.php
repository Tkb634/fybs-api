<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include "../config.php";
$user_id = $_SESSION['user_id'];

// Get current notification settings
$settings_query = "SELECT * FROM user_settings WHERE user_id = $user_id";
$settings_result = mysqli_query($conn, $settings_query);
$settings = mysqli_fetch_assoc($settings_result) ?? [];

// Default settings if not exists
$default_settings = [
    'email_notifications' => 1,
    'push_notifications' => 1,
    'prayer_reminders' => 1,
    'habit_reminders' => 1,
    'weekly_digest' => 1,
    'new_content' => 1
];

foreach ($default_settings as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}

$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Prepare settings array
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $push_notifications = isset($_POST['push_notifications']) ? 1 : 0;
    $prayer_reminders = isset($_POST['prayer_reminders']) ? 1 : 0;
    $habit_reminders = isset($_POST['habit_reminders']) ? 1 : 0;
    $weekly_digest = isset($_POST['weekly_digest']) ? 1 : 0;
    $new_content = isset($_POST['new_content']) ? 1 : 0;
    
    // Check if settings exist for this user
    if (mysqli_num_rows($settings_result) > 0) {
        // Update existing settings
        $update_query = "UPDATE user_settings SET 
            email_notifications = $email_notifications,
            push_notifications = $push_notifications,
            prayer_reminders = $prayer_reminders,
            habit_reminders = $habit_reminders,
            weekly_digest = $weekly_digest,
            new_content = $new_content,
            updated_at = NOW()
            WHERE user_id = $user_id";
    } else {
        // Insert new settings
        $update_query = "INSERT INTO user_settings 
            (user_id, email_notifications, push_notifications, prayer_reminders, habit_reminders, weekly_digest, new_content)
            VALUES ($user_id, $email_notifications, $push_notifications, $prayer_reminders, $habit_reminders, $weekly_digest, $new_content)";
    }
    
    if (mysqli_query($conn, $update_query)) {
        $message = "Notification settings updated successfully!";
        // Refresh settings
        $settings_result = mysqli_query($conn, $settings_query);
        $settings = mysqli_fetch_assoc($settings_result) ?? [];
    } else {
        $message = "Error updating settings: " . mysqli_error($conn);
    }
}

// Create user_settings table if not exists
$create_table = "CREATE TABLE IF NOT EXISTS user_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    email_notifications TINYINT DEFAULT 1,
    push_notifications TINYINT DEFAULT 1,
    prayer_reminders TINYINT DEFAULT 1,
    habit_reminders TINYINT DEFAULT 1,
    weekly_digest TINYINT DEFAULT 1,
    new_content TINYINT DEFAULT 1,
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
    <title>Notifications - FYBS Youth</title>
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
        
        .settings-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .setting-item:last-child {
            border-bottom: none;
        }
        
        .setting-info h4 {
            margin: 0;
            color: #374151;
            font-weight: 600;
        }
        
        .setting-info p {
            margin: 5px 0 0;
            color: #6b7280;
            font-size: 14px;
        }
        
        .form-switch {
            padding-left: 3.5em;
        }
        
        .form-switch .form-check-input {
            width: 3em;
            height: 1.5em;
            background-color: #e5e7eb;
            border: none;
        }
        
        .form-switch .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
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
        
        @media (prefers-color-scheme: dark) {
            body {
                background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
            }
            
            .settings-card {
                background: #1f2937;
            }
            
            .setting-item {
                border-bottom-color: #374151;
            }
            
            .setting-info h4 {
                color: #f9fafb;
            }
            
            .setting-info p {
                color: #d1d5db;
            }
            
            .section-title {
                color: #a78bfa;
                border-bottom-color: #374151;
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
            <h1 class="text-center">Notifications</h1>
            <p class="text-center opacity-75">Manage your notification preferences</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="settings-card">
                <h3 class="section-title">
                    <i class="fas fa-bell me-2"></i>
                    General Notifications
                </h3>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h4>Email Notifications</h4>
                        <p>Receive notifications via email</p>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="email_notifications" 
                               id="email_notifications" <?php echo $settings['email_notifications'] ? 'checked' : ''; ?>>
                    </div>
                </div>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h4>Push Notifications</h4>
                        <p>Receive push notifications on your device</p>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="push_notifications" 
                               id="push_notifications" <?php echo $settings['push_notifications'] ? 'checked' : ''; ?>>
                    </div>
                </div>
                
                <h3 class="section-title mt-4">
                    <i class="fas fa-hands-praying me-2"></i>
                    Prayer & Habits
                </h3>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h4>Prayer Reminders</h4>
                        <p>Daily reminders for prayer time</p>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="prayer_reminders" 
                               id="prayer_reminders" <?php echo $settings['prayer_reminders'] ? 'checked' : ''; ?>>
                    </div>
                </div>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h4>Habit Reminders</h4>
                        <p>Reminders for daily spiritual habits</p>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="habit_reminders" 
                               id="habit_reminders" <?php echo $settings['habit_reminders'] ? 'checked' : ''; ?>>
                    </div>
                </div>
                
                <h3 class="section-title mt-4">
                    <i class="fas fa-newspaper me-2"></i>
                    Updates & News
                </h3>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h4>Weekly Digest</h4>
                        <p>Weekly summary of activities and content</p>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="weekly_digest" 
                               id="weekly_digest" <?php echo $settings['weekly_digest'] ? 'checked' : ''; ?>>
                    </div>
                </div>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h4>New Content Alerts</h4>
                        <p>Notifications about new FYBS studies and content</p>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="new_content" 
                               id="new_content" <?php echo $settings['new_content'] ? 'checked' : ''; ?>>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-save mt-4">
                    <i class="fas fa-save me-2"></i>
                    Save Notification Settings
                </button>
            </div>
        </form>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>