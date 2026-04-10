<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$app_version = "1.0.0";
$last_updated = "January 2024";
$total_users = "1,000+";
$prayers_count = "10,000+";
$testimonies_count = "500+";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About App - FYBS Youth</title>
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
        
        .about-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .app-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            border-radius: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            color: white;
            font-size: 48px;
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.3);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 30px 0;
        }
        
        .stat-item {
            background: #f9fafb;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            border: 1px solid #e5e7eb;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 12px;
            color: #6b7280;
            font-weight: 500;
        }
        
        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 20px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 15px;
            border-left: 4px solid var(--primary-color);
        }
        
        .feature-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color), #6366f1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            flex-shrink: 0;
        }
        
        .feature-content h4 {
            margin: 0 0 8px 0;
            color: #374151;
            font-weight: 600;
        }
        
        .feature-content p {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }
        
        .team-member {
            text-align: center;
            padding: 20px;
        }
        
        .team-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 32px;
            font-weight: bold;
        }
        
        .btn-outline-primary {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white;
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
            
            .about-card {
                background: #1f2937;
            }
            
            .stat-item {
                background: #374151;
                border-color: #4b5563;
            }
            
            .stat-number {
                color: #a78bfa;
            }
            
            .feature-item {
                background: #374151;
                border-left-color: #a78bfa;
            }
            
            .feature-content h4 {
                color: #f9fafb;
            }
            
            .feature-content p {
                color: #d1d5db;
            }
            
            .section-title {
                color: #a78bfa;
                border-bottom-color: #374151;
            }
            
            .btn-outline-primary {
                border-color: #a78bfa;
                color: #a78bfa;
            }
            
            .btn-outline-primary:hover {
                background: #a78bfa;
                color: #1f2937;
            }
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
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
            <h1 class="text-center">About the App</h1>
            <p class="text-center opacity-75">Version <?php echo $app_version; ?> · FYBS Youth</p>
        </div>
        
        <div class="about-card">
            <div class="app-icon">
                <i class="fas fa-cross"></i>
            </div>
            
            <h2 class="text-center mb-3">FYBS Youth App</h2>
            <p class="text-center text-muted mb-4">
                A spiritual growth platform designed for youth to deepen their faith, 
                connect with others, and develop daily spiritual habits.
            </p>
            
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $app_version; ?></div>
                    <div class="stat-label">Version</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $total_users; ?></div>
                    <div class="stat-label">Active Users</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $last_updated; ?></div>
                    <div class="stat-label">Last Updated</div>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $prayers_count; ?></div>
                    <div class="stat-label">Prayers Shared</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $testimonies_count; ?></div>
                    <div class="stat-label">Testimonies</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Available</div>
                </div>
            </div>
        </div>
        
        <div class="about-card">
            <h3 class="section-title">
                <i class="fas fa-star me-2"></i>
                Key Features
            </h3>
            
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-book-bible"></i>
                </div>
                <div class="feature-content">
                    <h4>FYBS Studies</h4>
                    <p>Daily Bible study plans and devotionals designed specifically for youth spiritual growth.</p>
                </div>
            </div>
            
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-hands-praying"></i>
                </div>
                <div class="feature-content">
                    <h4>Prayer Community</h4>
                    <p>Share prayer requests, pray for others, and receive prayer support from the community.</p>
                </div>
            </div>
            
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="feature-content">
                    <h4>GYC Groups</h4>
                    <p>Join Growth & Youth Connection groups for fellowship, discussion, and mutual encouragement.</p>
                </div>
            </div>
            
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-bolt"></i>
                </div>
                <div class="feature-content">
                    <h4>LIFE Habits</h4>
                    <p>Track your daily spiritual habits and build consistent practices for lifelong faith.</p>
                </div>
            </div>
            
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="feature-content">
                    <h4>Safe Space</h4>
                    <p>A secure, moderated environment for youth to grow spiritually without judgment.</p>
                </div>
            </div>
        </div>
        
        <div class="about-card">
            <h3 class="section-title">
                <i class="fas fa-code me-2"></i>
                Technical Information
            </h3>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <div class="feature-content">
                            <h4>Platform</h4>
                            <p>Progressive Web App (PWA)</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-database"></i>
                        </div>
                        <div class="feature-content">
                            <h4>Backend</h4>
                            <p>PHP & MySQL</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <div class="feature-content">
                            <h4>Security</h4>
                            <p>Encrypted Data & Secure Login</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-sync"></i>
                        </div>
                        <div class="feature-content">
                            <h4>Updates</h4>
                            <p>Automatic & Manual Sync</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="#" class="btn btn-outline-primary me-2">
                    <i class="fas fa-download me-2"></i>
                    Check for Updates
                </a>
                <a href="help.php" class="btn btn-outline-primary">
                    <i class="fas fa-question-circle me-2"></i>
                    Get Help
                </a>
            </div>
        </div>
        
        <div class="about-card">
            <h3 class="section-title">
                <i class="fas fa-heart me-2"></i>
                Made With Love
            </h3>
            
            <p class="text-center mb-4">
                This app is developed by a dedicated team of Christian developers and youth leaders 
                passionate about helping young people grow in their faith.
            </p>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="team-member">
                        <div class="team-avatar">F</div>
                        <h5>FYBS Team</h5>
                        <p class="text-muted">Content & Theology</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="team-member">
                        <div class="team-avatar">D</div>
                        <h5>Dev Team</h5>
                        <p class="text-muted">Development & Design</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="team-member">
                        <div class="team-avatar">Y</div>
                        <h5>Youth Leaders</h5>
                        <p class="text-muted">Community & Support</p>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <p class="text-muted">
                    &copy; <?php echo date('Y'); ?> FYBS Youth Ministry. All rights reserved.<br>
                    Scripture taken from various translations.
                </p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>