<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include "../config.php";
$user_id = $_SESSION['user_id'];

// Get current user data
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

$full_name = $user['full_name'] ?? '';
$email = $user['email'] ?? '';
$phone = $user['phone'] ?? '';
$bio = $user['bio'] ?? '';

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $bio = mysqli_real_escape_string($conn, $_POST['bio']);
    
    // Update user data
    $update_query = "UPDATE users SET 
        full_name = '$full_name',
        phone = '$phone',
        bio = '$bio',
        updated_at = NOW()
        WHERE id = $user_id";
    
    if (mysqli_query($conn, $update_query)) {
        // Update session data
        $_SESSION['full_name'] = $full_name;
        $message = "Profile updated successfully!";
        $message_type = "success";
        
        // Refresh user data
        $user_result = mysqli_query($conn, $user_query);
        $user = mysqli_fetch_assoc($user_result);
    } else {
        $message = "Error updating profile: " . mysqli_error($conn);
        $message_type = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - FYBS Youth</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-light: #6366f1;
            --dark-color: #1f2937;
        }
        
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }
        
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .profile-header {
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
            color: white;
            padding: 30px 20px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            font-size: 20px;
            text-decoration: none;
        }
        
        .avatar-section {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 48px;
            font-weight: bold;
            border: 5px solid white;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .form-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-control {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
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
        }
        
        .message.success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        
        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        
        @media (prefers-color-scheme: dark) {
            body {
                background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
            }
            
            .form-card {
                background: #1f2937;
                color: #f9fafb;
            }
            
            .form-label {
                color: #d1d5db;
            }
            
            .form-control {
                background: #374151;
                border-color: #4b5563;
                color: #f9fafb;
            }
            
            .form-control:focus {
                background: #374151;
                border-color: var(--primary-light);
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <a href="../profile.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="text-center">Edit Profile</h1>
            <p class="text-center opacity-75">Update your personal information</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="avatar-section">
            <div class="avatar">
                <?php echo strtoupper(substr($user['full_name'] ?? 'U', 0, 1)); ?>
            </div>
            <p class="text-muted">Click on the fields below to edit your information</p>
        </div>
        
        <form method="POST" action="">
            <div class="form-card">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-user"></i>
                        Full Name
                    </label>
                    <input type="text" name="full_name" class="form-control" 
                           value="<?php echo htmlspecialchars($full_name); ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-envelope"></i>
                        Email Address
                    </label>
                    <input type="email" class="form-control" 
                           value="<?php echo htmlspecialchars($email); ?>" 
                           disabled style="background-color: #f3f4f6;">
                    <small class="text-muted">Email cannot be changed</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-phone"></i>
                        Phone Number
                    </label>
                    <input type="tel" name="phone" class="form-control" 
                           value="<?php echo htmlspecialchars($phone); ?>"
                           placeholder="Enter your phone number">
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-edit"></i>
                        Bio / About Me
                    </label>
                    <textarea name="bio" class="form-control" rows="4" 
                              placeholder="Tell us about yourself..."><?php echo htmlspecialchars($bio); ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-save">
                    <i class="fas fa-save me-2"></i>
                    Save Changes
                </button>
            </div>
        </form>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>