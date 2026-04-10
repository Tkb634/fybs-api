<?php
// Start session to track logged-in users
session_start();

// Include database connection
include "config.php";

// Check if login form submitted
if (isset($_POST['login'])) {
    // Get user inputs
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // Fetch user from database
    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $sql);

    // Check if user exists
    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);

        // Verify password
        if (password_verify($password, $user['password'])) {
            // Save user info in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['full_name'] = isset($user['full_name']) ? $user['full_name'] : $user['email'];

            // Redirect to homepage
            header("Location: index.php");
            exit();
        } else {
            $error = "Incorrect password!";
        }
    } else {
        $error = "Account not found!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
    <title>Login - FYBS Youth App</title>
    <meta name="theme-color" content="#4f46e5">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="manifest" href="manifest.json">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo-circle {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        .logo-circle i {
            font-size: 40px;
            color: #4f46e5;
        }

        .app-name {
            font-size: 28px;
            font-weight: 800;
            color: white;
        }

        .app-tagline {
            font-size: 14px;
            color: rgba(255,255,255,0.8);
        }

        .login-card {
            background: white;
            border-radius: 24px;
            padding: 32px 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .card-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .card-title {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .card-subtitle {
            font-size: 14px;
            color: #6b7280;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 16px;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px 14px 48px;
            font-size: 16px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            font-size: 16px;
        }

        .alert-message {
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            margin-top: 8px;
        }

        .login-btn:active {
            transform: scale(0.98);
        }

        .install-btn {
            width: 100%;
            padding: 12px;
            background: transparent;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            color: #6b7280;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            margin-top: 16px;
        }

        .register-link {
            text-align: center;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .register-link p {
            font-size: 14px;
            color: #6b7280;
        }

        .register-link a {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 600;
        }

        .footer {
            text-align: center;
            margin-top: 24px;
        }

        .footer small {
            font-size: 12px;
            color: rgba(255,255,255,0.6);
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 24px 20px;
            }
            .card-title {
                font-size: 24px;
            }
        }

        @media (prefers-color-scheme: dark) {
            .login-card {
                background: #1f2937;
            }
            .card-title {
                color: #f9fafb;
            }
            .form-control {
                background: #374151;
                border-color: #4b5563;
                color: #f9fafb;
            }
            .register-link {
                border-top-color: #374151;
            }
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="logo-section">
        <div class="logo-circle">
            <i class="fas fa-book-bible"></i>
        </div>
        <h1 class="app-name">CYIC</h1>
        <p class="app-tagline">Spiritual Growth • Community • Empowerment</p>
    </div>

    <div class="login-card">
        <div class="card-header">
            <h2 class="card-title">Welcome Back</h2>
            <p class="card-subtitle">Sign in to continue your journey</p>
        </div>

        <?php if (isset($_GET['registered']) && $_GET['registered'] == 'true'): ?>
        <div class="alert-message alert-success">
            <i class="fas fa-check-circle"></i>
            Registration successful! Please login.
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert-message alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <div class="input-wrapper">
                    <span class="input-icon"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="Email address" required autofocus>
                </div>
            </div>

            <div class="form-group">
                <div class="input-wrapper">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" name="login" class="login-btn">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
        </form>

        <button type="button" id="installBtn" class="install-btn">
            <i class="fas fa-download"></i> Install App
        </button>

        <div class="register-link">
            <p>Don't have an account? <a href="register.php">Create Account</a></p>
        </div>
    </div>

    <div class="footer">
        <small>© 2024 FYBS Youth App. All rights reserved.</small>
    </div>
</div>

<script>
function togglePassword() {
    const password = document.getElementById('password');
    const icon = document.querySelector('.password-toggle i');
    if (password.type === 'password') {
        password.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        password.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// PWA Install
let deferredPrompt;
const installBtn = document.getElementById('installBtn');

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    installBtn.style.display = 'flex';
});

installBtn.addEventListener('click', () => {
    if (deferredPrompt) {
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then(() => {
            deferredPrompt = null;
        });
    } else {
        alert('To install, tap the menu button and select "Add to Home Screen"');
    }
});

if (window.matchMedia('(display-mode: standalone)').matches) {
    installBtn.style.display = 'none';
}

// Service Worker
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('service-worker.js');
}
</script>
</body>
</html>