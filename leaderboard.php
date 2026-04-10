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

// Get leaderboard data - using BEST SCORE PER QUIZ (not total of all attempts)
// This ensures points add up across different categories, but repeating same quiz doesn't stack points
$leaderboard = [];
$leaderboard_sql = "SELECT 
    u.id as user_id,
    u.full_name,
    u.profile_image,
    COALESCE(SUM(best_scores.best_score), 0) as total_points,
    COUNT(DISTINCT best_scores.quiz_id) as total_quizzes,
    COALESCE(MAX(best_scores.best_score), 0) as highest_score
FROM users u
LEFT JOIN (
    SELECT 
        user_id,
        quiz_id,
        MAX(score) as best_score
    FROM bible_quiz_results
    GROUP BY user_id, quiz_id
) best_scores ON u.id = best_scores.user_id
GROUP BY u.id, u.full_name, u.profile_image
HAVING total_points > 0 OR total_quizzes > 0
ORDER BY total_points DESC, highest_score DESC
LIMIT 100";
$leaderboard_result = $conn->query($leaderboard_sql);

if (!$leaderboard_result) {
    die("Query failed: " . $conn->error);
}

while ($leader_row = $leaderboard_result->fetch_assoc()) {
    $leaderboard[] = $leader_row;
}

// Get user ranking and points using the same logic
$user_ranking = 0;
$user_total_points = 0;
$user_highest_score = 0;
$user_total_quizzes = 0;
$user_profile_image = '';

// Find user in leaderboard
$rank = 1;
foreach ($leaderboard as $player) {
    if ($player['user_id'] == $user_id) {
        $user_ranking = $rank;
        $user_total_points = $player['total_points'];
        $user_highest_score = $player['highest_score'];
        $user_total_quizzes = $player['total_quizzes'];
        $user_profile_image = $player['profile_image'];
        break;
    }
    $rank++;
}

// If user not in top 100, get their stats separately
if ($user_ranking == 0) {
    // Get user's best scores per quiz
    $user_stats_sql = "SELECT 
        SUM(best_score) as total_points,
        MAX(best_score) as highest_score,
        COUNT(*) as total_quizzes
    FROM (
        SELECT quiz_id, MAX(score) as best_score
        FROM bible_quiz_results
        WHERE user_id = ?
        GROUP BY quiz_id
    ) as best_scores";
    $stmt = $conn->prepare($user_stats_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $user_total_points = $row['total_points'] ?? 0;
        $user_highest_score = $row['highest_score'] ?? 0;
        $user_total_quizzes = $row['total_quizzes'] ?? 0;
    }
    
    // Get user profile image
    $profile_sql = "SELECT profile_image FROM users WHERE id = ?";
    $stmt = $conn->prepare($profile_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $profile_result = $stmt->get_result();
    if ($profile_row = $profile_result->fetch_assoc()) {
        $user_profile_image = $profile_row['profile_image'];
    }
    
    // Get user's rank by counting users with more points
    $user_rank_sql = "SELECT COUNT(*) as rank FROM (
        SELECT u.id, COALESCE(SUM(best_scores.best_score), 0) as total_points
        FROM users u
        LEFT JOIN (
            SELECT user_id, quiz_id, MAX(score) as best_score
            FROM bible_quiz_results
            GROUP BY user_id, quiz_id
        ) best_scores ON u.id = best_scores.user_id
        GROUP BY u.id
        HAVING total_points > ?
    ) as ranked_users";
    $stmt = $conn->prepare($user_rank_sql);
    $stmt->bind_param("i", $user_total_points);
    $stmt->execute();
    $rank_result = $stmt->get_result();
    if ($rank_row = $rank_result->fetch_assoc()) {
        $user_ranking = $rank_row['rank'] + 1;
    } else {
        $user_ranking = 1;
    }
}

// Get total number of users who have taken at least one quiz
$total_users_sql = "SELECT COUNT(DISTINCT user_id) as total_users FROM bible_quiz_results";
$total_result = $conn->query($total_users_sql);
$total_users = $total_result->fetch_assoc()['total_users'] ?? 0;

// Get the top 3 for podium display
$top_3 = array_slice($leaderboard, 0, 3);

// Get rank suffix
function getRankSuffix($rank) {
    if ($rank == 1) return "st";
    if ($rank == 2) return "nd";
    if ($rank == 3) return "rd";
    return "th";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
    <title>Leaderboard - FYBS Youth App</title>
    
    <meta name="theme-color" content="#1d4ed8">
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
            --primary: #1d4ed8;
            --primary-dark: #1e40af;
            --primary-light: #3b82f6;
            --secondary: #10b981;
            --accent: #f59e0b;
            --dark: #0f172a;
            --gray-dark: #334155;
            --gray: #64748b;
            --gray-light: #f1f5f9;
            --white: #ffffff;
            --gold: #fbbf24;
            --silver: #94a3b8;
            --bronze: #b45309;
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
            padding: 0 16px;
        }

        /* Header */
        .app-header {
            background: var(--white);
            padding: 12px 0;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.96);
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .header-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 500px;
            margin: 0 auto;
            padding: 0 16px;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }

        .logo-text {
            font-weight: 700;
            font-size: 18px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .user-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--gray-light);
            padding: 6px 12px 6px 8px;
            border-radius: 40px;
            cursor: pointer;
        }

        .user-avatar-sm {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--secondary), #059669);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
            background-size: cover;
            background-position: center;
        }

        .user-name-sm {
            font-size: 14px;
            font-weight: 600;
            color: var(--dark);
        }

        /* Welcome Card */
        .welcome-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 20px;
            margin: 20px 0;
            box-shadow: var(--shadow-sm);
        }

        .greeting-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }

        .greeting-icon {
            font-size: 28px;
        }

        .greeting-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
        }

        .greeting-sub {
            font-size: 13px;
            color: var(--gray);
        }

        /* Podium Section */
        .podium-section {
            margin: 20px 0 24px;
        }

        .podium {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            gap: 12px;
            flex-wrap: wrap;
        }

        .podium-item {
            text-align: center;
            flex: 1;
            min-width: 100px;
            transition: transform 0.2s;
        }

        .podium-item:active {
            transform: scale(0.98);
        }

        .podium-rank {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--gray);
        }

        .podium-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 600;
            color: white;
            box-shadow: var(--shadow-md);
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .podium-name {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding: 0 8px;
        }

        .podium-points {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
        }

        .podium-label {
            font-size: 10px;
            color: var(--gray);
        }

        .podium-1 .podium-avatar {
            width: 100px;
            height: 100px;
            font-size: 36px;
            background: linear-gradient(135deg, var(--gold), #f59e0b);
            box-shadow: 0 8px 20px rgba(245, 158, 11, 0.3);
        }

        .podium-2 .podium-avatar {
            background: linear-gradient(135deg, var(--silver), #6b7280);
        }

        .podium-3 .podium-avatar {
            background: linear-gradient(135deg, var(--bronze), #92400e);
        }

        /* Your Rank Card */
        .rank-card {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: var(--radius);
            padding: 24px 20px;
            margin-bottom: 24px;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .rank-card::before {
            content: "🏆";
            position: absolute;
            bottom: -20px;
            right: -10px;
            font-size: 100px;
            opacity: 0.1;
            pointer-events: none;
        }

        .rank-title {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 8px;
        }

        .rank-number {
            font-size: 48px;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 8px;
        }

        .rank-number small {
            font-size: 18px;
            font-weight: 500;
        }

        .rank-points {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .rank-stats {
            display: flex;
            gap: 24px;
            margin-top: 16px;
        }

        .rank-stat {
            text-align: center;
        }

        .rank-stat-value {
            font-size: 20px;
            font-weight: 700;
        }

        .rank-stat-label {
            font-size: 11px;
            opacity: 0.8;
        }

        /* Leaderboard List */
        .leaderboard-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 20px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #eef2ff;
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
            color: var(--accent);
        }

        .players-count {
            font-size: 12px;
            color: var(--gray);
        }

        /* Leaderboard Items */
        .leaderboard-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .leaderboard-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: var(--gray-light);
            border-radius: var(--radius-sm);
            transition: all 0.2s;
            cursor: pointer;
        }

        .leaderboard-item:active {
            transform: scale(0.98);
            background: #e5e7eb;
        }

        .leaderboard-item.you {
            background: linear-gradient(135deg, rgba(29, 78, 216, 0.1), rgba(59, 130, 246, 0.1));
            border: 1px solid rgba(29, 78, 216, 0.3);
        }

        .rank-badge {
            width: 44px;
            text-align: center;
            font-weight: 700;
            font-size: 16px;
        }

        .rank-badge.top-1 { color: var(--gold); }
        .rank-badge.top-2 { color: var(--silver); }
        .rank-badge.top-3 { color: var(--bronze); }

        .player-avatar-list {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 18px;
            flex-shrink: 0;
            background-size: cover;
            background-position: center;
        }

        .player-info-list {
            flex: 1;
            min-width: 0;
        }

        .player-name-list {
            font-weight: 600;
            font-size: 15px;
            color: var(--dark);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .player-badge {
            font-size: 10px;
            background: var(--primary);
            color: white;
            padding: 2px 8px;
            border-radius: 20px;
            margin-left: 8px;
        }

        .player-stats-list {
            font-size: 11px;
            color: var(--gray);
            margin-top: 2px;
        }

        .player-points-list {
            font-weight: 700;
            font-size: 16px;
            color: var(--primary);
            text-align: right;
            min-width: 70px;
        }

        .player-quizzes-list {
            font-size: 10px;
            color: var(--gray);
            text-align: right;
        }

        /* Play Button */
        .play-btn-section {
            margin-top: 20px;
            text-align: center;
        }

        .btn-play {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--secondary), #059669);
            border: none;
            border-radius: 60px;
            color: white;
            font-weight: 700;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: transform 0.1s;
        }

        .btn-play:active {
            transform: scale(0.97);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 48px 20px;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            color: #d1d5db;
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
            transition: all 0.2s;
        }

        .nav-link-item i {
            font-size: 20px;
        }

        .nav-link-item.active {
            color: var(--primary);
            font-weight: 600;
        }

        .nav-link-item:active {
            background: var(--gray-light);
        }

        /* Dark Mode */
        @media (prefers-color-scheme: dark) {
            body {
                background: #0f172a;
            }
            .app-header, .welcome-card, .leaderboard-card {
                background: #1e293b;
            }
            .greeting-title, .section-title, .player-name-list {
                color: #f1f5f9;
            }
            .greeting-sub, .players-count, .player-stats-list, .player-quizzes-list {
                color: #94a3b8;
            }
            .leaderboard-item {
                background: #334155;
            }
            .leaderboard-item:active {
                background: #475569;
            }
            .leaderboard-item.you {
                background: rgba(59, 130, 246, 0.2);
            }
            .section-header {
                border-bottom-color: #334155;
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
        }

        /* PWA Support */
        @media (display-mode: standalone) {
            .app-header {
                padding-top: max(12px, env(safe-area-inset-top));
            }
            .bottom-nav {
                padding-bottom: max(20px, env(safe-area-inset-bottom));
            }
        }
    </style>
</head>
<body>
<div class="app-container">
    <!-- Header -->
    <div class="app-header">
        <div class="header-inner">
            <div class="logo-area">
                <div class="logo-icon"><i class="fas fa-trophy"></i></div>
                <span class="logo-text">Leaderboard</span>
            </div>
            <div class="user-badge" onclick="window.location.href='profile.php'">
                <div class="user-avatar-sm" style="<?php 
                    if(!empty($user_profile_image) && file_exists($user_profile_image)) {
                        echo 'background-image: url(\'' . htmlspecialchars($user_profile_image) . '\'); background-size: cover;';
                    }
                ?>">
                    <?php if(empty($user_profile_image) || !file_exists($user_profile_image)): ?>
                        <?php echo strtoupper(substr($first_name, 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <span class="user-name-sm"><?php echo htmlspecialchars($first_name); ?></span>
                <i class="fas fa-chevron-right" style="font-size: 10px; color: #94a3b8;"></i>
            </div>
        </div>
    </div>

    <!-- Welcome Section -->
    <div class="welcome-card">
        <div class="greeting-row">
            <span class="greeting-icon"><?php echo $greeting_icon; ?></span>
            <h2 class="greeting-title"><?php echo $greeting; ?>, <?php echo htmlspecialchars($first_name); ?>!</h2>
        </div>
        <p class="greeting-sub">Compete with others and see where you rank. Only your best scores count!</p>
    </div>

    <?php if(count($leaderboard) > 0): ?>
        <!-- Podium (Top 3) -->
        <?php if(count($top_3) > 0): ?>
        <div class="podium-section">
            <div class="podium">
                <?php if(isset($top_3[1])): ?>
                <div class="podium-item podium-2" onclick="viewProfile(<?php echo $top_3[1]['user_id']; ?>)">
                    <div class="podium-rank">🥈 2nd</div>
                    <div class="podium-avatar" style="<?php 
                        if(!empty($top_3[1]['profile_image']) && file_exists($top_3[1]['profile_image'])) {
                            echo 'background-image: url(\'' . htmlspecialchars($top_3[1]['profile_image']) . '\'); background-size: cover;';
                        }
                    ?>">
                        <?php if(empty($top_3[1]['profile_image']) || !file_exists($top_3[1]['profile_image'])): ?>
                            <?php echo strtoupper(substr($top_3[1]['full_name'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <div class="podium-name"><?php echo htmlspecialchars(explode(' ', $top_3[1]['full_name'])[0]); ?></div>
                    <div class="podium-points"><?php echo $top_3[1]['total_points']; ?></div>
                    <div class="podium-label">pts</div>
                </div>
                <?php endif; ?>

                <?php if(isset($top_3[0])): ?>
                <div class="podium-item podium-1" onclick="viewProfile(<?php echo $top_3[0]['user_id']; ?>)">
                    <div class="podium-rank">👑 1st</div>
                    <div class="podium-avatar" style="<?php 
                        if(!empty($top_3[0]['profile_image']) && file_exists($top_3[0]['profile_image'])) {
                            echo 'background-image: url(\'' . htmlspecialchars($top_3[0]['profile_image']) . '\'); background-size: cover;';
                        }
                    ?>">
                        <?php if(empty($top_3[0]['profile_image']) || !file_exists($top_3[0]['profile_image'])): ?>
                            <?php echo strtoupper(substr($top_3[0]['full_name'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <div class="podium-name"><?php echo htmlspecialchars(explode(' ', $top_3[0]['full_name'])[0]); ?></div>
                    <div class="podium-points"><?php echo $top_3[0]['total_points']; ?></div>
                    <div class="podium-label">pts</div>
                </div>
                <?php endif; ?>

                <?php if(isset($top_3[2])): ?>
                <div class="podium-item podium-3" onclick="viewProfile(<?php echo $top_3[2]['user_id']; ?>)">
                    <div class="podium-rank">🥉 3rd</div>
                    <div class="podium-avatar" style="<?php 
                        if(!empty($top_3[2]['profile_image']) && file_exists($top_3[2]['profile_image'])) {
                            echo 'background-image: url(\'' . htmlspecialchars($top_3[2]['profile_image']) . '\'); background-size: cover;';
                        }
                    ?>">
                        <?php if(empty($top_3[2]['profile_image']) || !file_exists($top_3[2]['profile_image'])): ?>
                            <?php echo strtoupper(substr($top_3[2]['full_name'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <div class="podium-name"><?php echo htmlspecialchars(explode(' ', $top_3[2]['full_name'])[0]); ?></div>
                    <div class="podium-points"><?php echo $top_3[2]['total_points']; ?></div>
                    <div class="podium-label">pts</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Your Rank Card -->
        <div class="rank-card">
            <div class="rank-title">YOUR RANK</div>
            <div class="rank-number">#<?php echo $user_ranking; ?><small><?php echo getRankSuffix($user_ranking); ?></small></div>
            <div class="rank-points">
                <i class="fas fa-star"></i> <?php echo $user_total_points; ?> Total Points
            </div>
            <div class="rank-stats">
                <div class="rank-stat">
                    <div class="rank-stat-value"><?php echo $user_total_quizzes; ?></div>
                    <div class="rank-stat-label">Quizzes</div>
                </div>
                <div class="rank-stat">
                    <div class="rank-stat-value"><?php echo $user_highest_score; ?></div>
                    <div class="rank-stat-label">Best Score</div>
                </div>
                <div class="rank-stat">
                    <div class="rank-stat-value"><?php echo $total_users; ?></div>
                    <div class="rank-stat-label">Players</div>
                </div>
            </div>
        </div>

        <!-- Leaderboard List -->
        <div class="leaderboard-card">
            <div class="section-header">
                <div class="section-title">
                    <i class="fas fa-trophy"></i>
                    Top Players
                </div>
                <div class="players-count"><?php echo count($leaderboard); ?> players</div>
            </div>

            <div class="leaderboard-list">
                <?php 
                $display_rank = 1;
                foreach($leaderboard as $player): 
                    $rank_class = '';
                    if($display_rank == 1) $rank_class = 'top-1';
                    elseif($display_rank == 2) $rank_class = 'top-2';
                    elseif($display_rank == 3) $rank_class = 'top-3';
                ?>
                <div class="leaderboard-item <?php echo $player['user_id'] == $user_id ? 'you' : ''; ?>" onclick="viewProfile(<?php echo $player['user_id']; ?>)">
                    <div class="rank-badge <?php echo $rank_class; ?>">
                        <?php 
                        if($display_rank == 1) echo "🥇";
                        elseif($display_rank == 2) echo "🥈";
                        elseif($display_rank == 3) echo "🥉";
                        else echo "#" . $display_rank;
                        ?>
                    </div>
                    <div class="player-avatar-list" style="<?php 
                        if(!empty($player['profile_image']) && file_exists($player['profile_image'])) {
                            echo 'background-image: url(\'' . htmlspecialchars($player['profile_image']) . '\'); background-size: cover;';
                        }
                    ?>">
                        <?php if(empty($player['profile_image']) || !file_exists($player['profile_image'])): ?>
                            <?php echo strtoupper(substr($player['full_name'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <div class="player-info-list">
                        <div class="player-name-list">
                            <?php echo htmlspecialchars($player['full_name']); ?>
                            <?php if($player['user_id'] == $user_id): ?>
                                <span class="player-badge">You</span>
                            <?php endif; ?>
                        </div>
                        <div class="player-stats-list">
                            <i class="fas fa-book-open"></i> <?php echo $player['total_quizzes']; ?> quizzes mastered
                        </div>
                    </div>
                    <div class="player-points-list">
                        <?php echo $player['total_points']; ?>
                        <div class="player-quizzes-list">pts</div>
                    </div>
                </div>
                <?php 
                $display_rank++;
                endforeach; 
                ?>
            </div>

            <!-- Play Button -->
            <div class="play-btn-section">
                <button class="btn-play" onclick="window.location.href='bible_quiz.php'">
                    <i class="fas fa-play-circle"></i> Take a Quiz & Climb the Ranks!
                </button>
            </div>
        </div>

    <?php else: ?>
        <!-- Empty State -->
        <div class="leaderboard-card">
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <h5>No leaderboard yet</h5>
                <p class="text-muted mt-2">Be the first to take a quiz and claim the top spot!</p>
                <button class="btn-play mt-3" onclick="window.location.href='bible_quiz.php'" style="width: auto; padding: 12px 24px;">
                    <i class="fas fa-play-circle"></i> Start Your First Quiz
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Bottom Navigation -->
<div class="bottom-nav">
    <div class="nav-links">
        <a href="index.php" class="nav-link-item"><i class="fas fa-home"></i><span>Home</span></a>
        <a href="fybs.php" class="nav-link-item"><i class="fas fa-book-bible"></i><span>FYBS</span></a>
        <a href="bible_quiz.php" class="nav-link-item"><i class="fas fa-question-circle"></i><span>Quiz</span></a>
        <a href="leaderboard.php" class="nav-link-item active"><i class="fas fa-trophy"></i><span>Rank</span></a>
        <a href="profile.php" class="nav-link-item"><i class="fas fa-user"></i><span>Profile</span></a>
    </div>
</div>

<script>
    function viewProfile(userId) {
        window.location.href = 'profile.php?id=' + userId;
    }
    
    // Add haptic feedback on tap (if supported)
    function hapticFeedback() {
        if (window.navigator && window.navigator.vibrate) {
            window.navigator.vibrate(10);
        }
    }
    
    // Add tap feedback to interactive elements
    document.querySelectorAll('.leaderboard-item, .podium-item, .btn-play, .user-badge').forEach(el => {
        el.addEventListener('click', hapticFeedback);
    });
</script>
</body>
</html>