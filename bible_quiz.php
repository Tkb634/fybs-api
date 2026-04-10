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

// Get day and date
$day_of_week = date('l');
$date = date('F j, Y');

// Handle quiz submission
if (isset($_POST['submit_quiz'])) {
    $quiz_id = (int)$_POST['quiz_id'];
    $answers = json_decode($_POST['answers'], true) ?? [];
    $time_spent = (int)$_POST['time_spent'] ?? 0;
    
    // Get quiz details
    $quiz_sql = "SELECT * FROM bible_quizzes WHERE id = ?";
    $stmt = $conn->prepare($quiz_sql);
    $stmt->bind_param("i", $quiz_id);
    $stmt->execute();
    $quiz_result = $stmt->get_result();
    $quiz = $quiz_result->fetch_assoc();
    
    if (!$quiz) {
        die("Quiz not found");
    }
    
    // Calculate score
    $score = 0;
    $total_questions = 0;
    
    // Get quiz questions
    $questions_sql = "SELECT * FROM bible_quiz_questions WHERE quiz_id = ? ORDER BY id";
    $stmt = $conn->prepare($questions_sql);
    $stmt->bind_param("i", $quiz_id);
    $stmt->execute();
    $questions_result = $stmt->get_result();
    
    $correct_answers = 0;
    while ($question = $questions_result->fetch_assoc()) {
        $total_questions++;
        $question_id = $question['id'];
        $correct_answer = (int)$question['correct_answer'];
        
        if (isset($answers[$question_id]) && (int)$answers[$question_id] === $correct_answer) {
            $score += $question['points'];
            $correct_answers++;
        }
    }
    
    // Check if user already took this quiz before
    $existing_sql = "SELECT id, score FROM bible_quiz_results 
                     WHERE user_id = ? AND quiz_id = ? 
                     ORDER BY score DESC LIMIT 1";
    $stmt = $conn->prepare($existing_sql);
    $stmt->bind_param("ii", $user_id, $quiz_id);
    $stmt->execute();
    $existing_result = $stmt->get_result();
    $existing = $existing_result->fetch_assoc();
    
    $is_better_score = false;
    $previous_score = 0;
    
    if ($existing) {
        $previous_score = $existing['score'];
        if ($score > $previous_score) {
            $is_better_score = true;
            // Update existing record
            $update_sql = "UPDATE bible_quiz_results 
                           SET score = ?, total_questions = ?, time_spent = ?, completed_at = NOW() 
                           WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("iiii", $score, $total_questions, $time_spent, $existing['id']);
            $stmt->execute();
        }
    } else {
        $is_better_score = true;
        // Insert new record
        $save_sql = "INSERT INTO bible_quiz_results (user_id, quiz_id, score, total_questions, time_spent) 
                     VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($save_sql);
        $stmt->bind_param("iiiii", $user_id, $quiz_id, $score, $total_questions, $time_spent);
        $stmt->execute();
    }
    
    // Store session variables
    $_SESSION['quiz_score'] = $score;
    $_SESSION['quiz_total'] = $total_questions;
    $_SESSION['quiz_id'] = $quiz_id;
    $_SESSION['quiz_title'] = $quiz['title'];
    $_SESSION['previous_score'] = $previous_score;
    $_SESSION['is_better_score'] = $is_better_score;
    $_SESSION['correct_answers'] = $correct_answers;
    
    header("Location: bible_quiz.php?completed=$quiz_id");
    exit();
}

// Get active quizzes with user's best scores
$quizzes = [];
$quiz_sql = "SELECT q.*, 
                    (SELECT MAX(score) FROM bible_quiz_results WHERE quiz_id = q.id AND user_id = ?) as user_best_score,
                    (SELECT COUNT(*) FROM bible_quiz_results WHERE quiz_id = q.id AND user_id = ? AND score > 0) as times_taken
             FROM bible_quizzes q 
             WHERE q.is_active = 1 
             ORDER BY q.created_at DESC";
$stmt = $conn->prepare($quiz_sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$quiz_result = $stmt->get_result();
while ($quiz_row = $quiz_result->fetch_assoc()) {
    $quizzes[] = $quiz_row;
}

// Get user stats - now calculate total points from all quizzes (only highest per category)
$user_stats_sql = "SELECT 
                    SUM(highest_score) as total_points,
                    MAX(highest_score) as highest_score,
                    COUNT(DISTINCT quiz_id) as total_quizzes_taken
                   FROM (
                       SELECT quiz_id, MAX(score) as highest_score
                       FROM bible_quiz_results 
                       WHERE user_id = ?
                       GROUP BY quiz_id
                   ) as best_scores";
$stmt = $conn->prepare($user_stats_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats_result = $stmt->get_result();
if ($stats_result->num_rows > 0) {
    $stats_row = $stats_result->fetch_assoc();
    $user_total_points = $stats_row['total_points'] ?? 0;
    $user_highest_score = $stats_row['highest_score'] ?? 0;
    $user_total_quizzes = $stats_row['total_quizzes_taken'] ?? 0;
} else {
    $user_total_points = 0;
    $user_highest_score = 0;
    $user_total_quizzes = 0;
}

// Get total available points from all quizzes
$total_available_sql = "SELECT SUM(total_points) as total_available FROM bible_quizzes WHERE is_active = 1";
$total_available_result = $conn->query($total_available_sql);
$total_available_row = $total_available_result->fetch_assoc();
$total_available = $total_available_row['total_available'] ?? 0;

// Calculate overall progress percentage (fix division by zero)
$overall_progress = 0;
if ($total_available > 0) {
    $overall_progress = round(($user_total_points / $total_available) * 100);
}

// Get category breakdown
$category_stats_sql = "SELECT 
                        q.category,
                        SUM(best.highest_score) as total_points,
                        MAX(best.highest_score) as max_in_category
                       FROM bible_quizzes q
                       LEFT JOIN (
                           SELECT quiz_id, MAX(score) as highest_score
                           FROM bible_quiz_results 
                           WHERE user_id = ?
                           GROUP BY quiz_id
                       ) best ON q.id = best.quiz_id
                       WHERE q.is_active = 1
                       GROUP BY q.category";
$stmt = $conn->prepare($category_stats_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$category_result = $stmt->get_result();
$category_stats = [];
while ($row = $category_result->fetch_assoc()) {
    if ($row['total_points'] !== null) {
        $category_stats[] = $row;
    }
}

// Check if showing quiz completion
$show_completion = false;
$completion_score = 0;
$completion_total = 0;
$completion_quiz_id = 0;
$completion_quiz_title = '';
$previous_score = 0;
$is_better_score = false;
$correct_answers = 0;

if (isset($_GET['completed'])) {
    $show_completion = true;
    $completion_score = $_SESSION['quiz_score'] ?? 0;
    $completion_total = $_SESSION['quiz_total'] ?? 0;
    $completion_quiz_id = $_SESSION['quiz_id'] ?? 0;
    $completion_quiz_title = $_SESSION['quiz_title'] ?? '';
    $previous_score = $_SESSION['previous_score'] ?? 0;
    $is_better_score = $_SESSION['is_better_score'] ?? false;
    $correct_answers = $_SESSION['correct_answers'] ?? 0;
    
    unset($_SESSION['quiz_score']);
    unset($_SESSION['quiz_total']);
    unset($_SESSION['quiz_id']);
    unset($_SESSION['quiz_title']);
    unset($_SESSION['previous_score']);
    unset($_SESSION['is_better_score']);
    unset($_SESSION['correct_answers']);
}

// Get default quiz for display
$current_quiz = null;
if (count($quizzes) > 0) {
    $current_quiz = $quizzes[0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
    <title>Bible Quiz - FYBS Youth App</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#1d4ed8">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="manifest.json">
    
    <!-- Bootstrap CSS (lightweight) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
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
            --danger: #ef4444;
            --success: #22c55e;
            --warning: #f97316;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.12);
            --radius: 20px;
            --radius-sm: 14px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: var(--dark);
            padding-bottom: 70px;
            overflow-x: hidden;
        }

        /* Mobile-first container */
        .app-container {
            max-width: 500px;
            margin: 0 auto;
            padding: 0 16px;
        }

        /* Header - compact for mobile */
        .app-header {
            background: var(--white);
            padding: 12px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.96);
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
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            box-shadow: var(--shadow-sm);
        }

        .logo-text {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 20px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
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
            transition: all 0.2s;
        }

        .user-avatar-sm {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--secondary) 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
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
            border: 1px solid rgba(0, 0, 0, 0.03);
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
            font-family: 'Poppins', sans-serif;
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
        }

        .greeting-sub {
            font-size: 13px;
            color: var(--gray);
            margin-bottom: 12px;
        }

        .date-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--gray-light);
            padding: 6px 14px;
            border-radius: 40px;
            font-size: 12px;
            color: var(--gray-dark);
        }

        /* Progress Ring Card */
        .progress-ring-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 16px;
            text-align: center;
            margin-bottom: 20px;
            box-shadow: var(--shadow-sm);
        }

        .ring-wrapper {
            position: relative;
            width: 100px;
            height: 100px;
            margin: 0 auto 12px;
        }

        .ring-svg {
            transform: rotate(-90deg);
            width: 100px;
            height: 100px;
        }

        .ring-bg {
            stroke: #e2e8f0;
            stroke-width: 8;
            fill: none;
        }

        .ring-fill {
            stroke: var(--secondary);
            stroke-width: 8;
            fill: none;
            stroke-linecap: round;
            stroke-dasharray: 283;
            stroke-dashoffset: 283;
            transition: stroke-dashoffset 0.6s ease;
        }

        .ring-percent {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 22px;
            font-weight: 800;
            color: var(--dark);
        }

        .points-stats {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin-top: 8px;
            font-size: 13px;
            color: var(--gray);
        }

        /* Stats Grid - Mobile friendly 2 cols */
        .stats-grid-mobile {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 24px;
        }

        .stat-tile {
            background: var(--white);
            border-radius: var(--radius-sm);
            padding: 16px 12px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s;
            cursor: pointer;
            border: 1px solid rgba(0,0,0,0.02);
        }

        .stat-tile:active {
            transform: scale(0.97);
        }

        .stat-tile-value {
            font-size: 28px;
            font-weight: 800;
            color: var(--primary);
            line-height: 1.2;
        }

        .stat-tile-label {
            font-size: 12px;
            color: var(--gray);
            font-weight: 500;
            margin-top: 4px;
        }

        /* Section Headers */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin: 20px 0 14px 0;
        }

        .section-title {
            font-family: 'Poppins', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title i {
            color: var(--primary);
            font-size: 18px;
        }

        /* Quiz Cards - full width */
        .quiz-card-mobile {
            background: var(--white);
            border-radius: var(--radius);
            margin-bottom: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: all 0.2s;
            border: 1px solid rgba(0,0,0,0.03);
        }

        .quiz-card-mobile:active {
            transform: scale(0.99);
        }

        .quiz-card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            padding: 16px;
            color: white;
        }

        .quiz-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .quiz-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 4px 10px;
            border-radius: 40px;
            font-size: 11px;
            font-weight: 500;
        }

        .quiz-card-body {
            padding: 16px;
        }

        .quiz-desc {
            font-size: 13px;
            color: var(--gray);
            margin-bottom: 12px;
            line-height: 1.4;
        }

        .quiz-meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 12px;
            font-size: 12px;
            color: var(--gray);
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .best-score-chip {
            background: var(--gray-light);
            padding: 8px 12px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            margin-bottom: 14px;
            font-size: 13px;
            font-weight: 500;
        }

        .btn-quiz {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--secondary) 0%, #059669 100%);
            border: none;
            border-radius: 14px;
            color: white;
            font-weight: 600;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-quiz:active {
            transform: scale(0.97);
        }

        /* Quiz Container - Mobile Optimized */
        .quiz-panel {
            background: var(--white);
            border-radius: var(--radius);
            overflow: hidden;
            margin: 16px 0;
            box-shadow: var(--shadow-md);
            display: none;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .quiz-panel-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            padding: 16px;
            color: white;
        }

        .quiz-progress-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 12px;
        }

        .progress-track {
            flex: 1;
            height: 6px;
            background: rgba(255,255,255,0.3);
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: white;
            width: 0%;
            border-radius: 10px;
        }

        .timer-mobile {
            background: rgba(0,0,0,0.2);
            padding: 6px 12px;
            border-radius: 40px;
            font-family: monospace;
            font-weight: 700;
            font-size: 16px;
        }

        .question-area {
            padding: 24px 20px;
        }

        .q-num {
            font-size: 13px;
            color: var(--gray);
            margin-bottom: 12px;
            font-weight: 500;
        }

        .q-text {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 24px;
            line-height: 1.4;
        }

        .options-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 28px;
        }

        .option-mobile {
            background: var(--gray-light);
            border-radius: 16px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            cursor: pointer;
            transition: all 0.2s;
            border: 1.5px solid transparent;
        }

        .option-mobile.selected {
            background: var(--primary);
            border-color: var(--primary-dark);
        }

        .option-mobile.selected .opt-letter {
            background: white;
            color: var(--primary);
        }

        .option-mobile.selected .opt-text {
            color: white;
        }

        .opt-letter {
            width: 34px;
            height: 34px;
            background: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: var(--primary);
        }

        .opt-text {
            flex: 1;
            font-size: 15px;
            font-weight: 500;
            color: var(--dark);
        }

        .quiz-actions {
            display: flex;
            gap: 10px;
            padding: 16px 20px 24px;
            background: white;
            border-top: 1px solid #eef2ff;
        }

        .action-btn {
            flex: 1;
            padding: 12px;
            border-radius: 40px;
            font-weight: 600;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 14px;
        }

        .btn-pause {
            background: #f1f5f9;
            color: var(--gray-dark);
        }

        .btn-next {
            background: var(--primary);
            color: white;
        }

        .btn-stop {
            background: #fee2e2;
            color: var(--danger);
        }

        /* Results card */
        .results-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 28px 20px;
            text-align: center;
            margin: 16px 0;
            box-shadow: var(--shadow-md);
        }

        .result-score {
            font-size: 48px;
            font-weight: 800;
            color: var(--primary);
        }

        .improvement-badge {
            background: #d1fae5;
            color: #065f46;
            padding: 10px;
            border-radius: 40px;
            font-size: 13px;
            margin: 16px 0;
        }

        /* Bottom Nav - True Mobile */
        .bottom-nav-mobile {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(12px);
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding: 8px 20px 20px;
            max-width: 500px;
            margin: 0 auto;
            z-index: 1000;
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
            font-size: 12px;
            transition: color 0.2s;
            padding: 6px 12px;
            border-radius: 40px;
        }

        .nav-link-item i {
            font-size: 22px;
        }

        .nav-link-item.active {
            color: var(--primary);
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 48px 20px;
            color: var(--gray);
        }

        @media (prefers-color-scheme: dark) {
            body {
                background: #0f172a;
            }
            .app-header, .welcome-card, .progress-ring-card, .stat-tile, 
            .quiz-card-mobile, .quiz-panel, .results-card {
                background: #1e293b;
                border-color: #334155;
            }
            .greeting-title, .stat-tile-value, .section-title, .q-text {
                color: #f1f5f9;
            }
            .greeting-sub, .stat-tile-label, .quiz-desc, .q-num {
                color: #94a3b8;
            }
            .option-mobile {
                background: #334155;
            }
            .opt-text {
                color: #e2e8f0;
            }
            .best-score-chip {
                background: #334155;
                color: #cbd5e1;
            }
            .btn-pause {
                background: #334155;
                color: #cbd5e1;
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
                <div class="logo-icon"><i class="fas fa-book-bible"></i></div>
                <span class="logo-text">FYBS Quiz</span>
            </div>
            <div class="user-badge" onclick="window.location.href='profile.php'">
                <div class="user-avatar-sm"><?php echo strtoupper(substr($first_name ?? 'U', 0, 1)); ?></div>
                <span class="user-name-sm"><?php echo htmlspecialchars($first_name ?? 'User'); ?></span>
                <i class="fas fa-chevron-right" style="font-size: 10px; color: #94a3b8;"></i>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div id="mainContent">
        <!-- Welcome Card -->
        <div class="welcome-card">
            <div class="greeting-row">
                <span class="greeting-icon"><?php echo $greeting_icon ?? '☀️'; ?></span>
                <h2 class="greeting-title"><?php echo $greeting ?? 'Hello' ?>, <?php echo htmlspecialchars($first_name ?? 'Friend'); ?>!</h2>
            </div>
            <p class="greeting-sub">Test your Bible knowledge — best scores count!</p>
            <div class="date-chip"><i class="far fa-calendar-alt"></i> <?php echo date('l, M j'); ?></div>
        </div>

        <!-- Progress Ring Card -->
        <div class="progress-ring-card">
            <div class="ring-wrapper">
                <svg class="ring-svg" viewBox="0 0 100 100">
                    <circle class="ring-bg" cx="50" cy="50" r="45"></circle>
                    <circle class="ring-fill" id="ringFill" cx="50" cy="50" r="45"></circle>
                </svg>
                <div class="ring-percent" id="progressPercent">0%</div>
            </div>
            <div class="points-stats">
                <span><i class="fas fa-star text-warning"></i> <?php echo $user_total_points ?? 0; ?> pts</span>
                <span><i class="fas fa-trophy text-primary"></i> <?php echo $total_available ?? 0; ?> max</span>
            </div>
        </div>

        <!-- Stats Tiles (2x2 style mobile) -->
        <div class="stats-grid-mobile">
            <div class="stat-tile" onclick="window.location.href='leaderboard.php'">
                <div class="stat-tile-value"><?php echo $user_total_points ?? 0; ?></div>
                <div class="stat-tile-label">Total Points</div>
            </div>
            <div class="stat-tile" onclick="window.location.href='leaderboard.php'">
                <div class="stat-tile-value"><?php echo $user_highest_score ?? 0; ?></div>
                <div class="stat-tile-label">Best Quiz</div>
            </div>
            <div class="stat-tile" onclick="window.location.href='leaderboard.php'">
                <div class="stat-tile-value"><?php echo $user_total_quizzes ?? 0; ?></div>
                <div class="stat-tile-label">Completed</div>
            </div>
            <div class="stat-tile" onclick="window.location.href='leaderboard.php'">
                <div class="stat-tile-value"><i class="fas fa-chart-line"></i></div>
                <div class="stat-tile-label">Leaderboard</div>
            </div>
        </div>

        <!-- Completion Results (if shown) -->
        <?php if($show_completion && $completion_quiz_id): ?>
        <div class="results-card" id="resultsBlock">
            <i class="fas fa-check-circle" style="font-size: 48px; color: #10b981;"></i>
            <div class="result-score"><?php echo $completion_score; ?>/<?php echo $completion_total; ?></div>
            <div class="fw-bold mb-2"><?php echo round(($completion_score / max(1, $completion_total)) * 100); ?>%</div>
            <?php if($is_better_score): ?>
            <div class="improvement-badge"><i class="fas fa-chart-line"></i> New Personal Best! +<?php echo $completion_score - ($previous_score ?? 0); ?> pts</div>
            <?php elseif($previous_score > 0): ?>
            <div class="improvement-badge" style="background:#fef3c7;color:#b45309;">Previous best: <?php echo $previous_score; ?> pts — Keep going!</div>
            <?php endif; ?>
            <p class="mt-3 text-muted"><?php echo $correct_answers; ?> correct answers</p>
            <button class="btn-quiz mt-3" onclick="location.reload()"><i class="fas fa-list"></i> Try Other Quizzes</button>
        </div>
        <?php endif; ?>

        <!-- Available Quizzes Section -->
        <div class="section-header">
            <div class="section-title"><i class="fas fa-scroll"></i> Bible Quizzes</div>
            <button class="btn-quiz" style="background: var(--primary); padding: 8px 16px; font-size: 12px; width: auto;" onclick="window.location.href='leaderboard.php'"><i class="fas fa-trophy"></i> Rank</button>
        </div>

        <div id="quizzesList">
            <?php if(count($quizzes) > 0): ?>
                <?php foreach($quizzes as $quiz): $best = $quiz['user_best_score'] ?? 0; ?>
                <div class="quiz-card-mobile" onclick="loadQuiz(<?php echo $quiz['id']; ?>, '<?php echo addslashes($quiz['title']); ?>', <?php echo $quiz['time_limit']; ?>, <?php echo $quiz['total_points']; ?>)">
                    <div class="quiz-card-header">
                        <div class="quiz-title"><?php echo htmlspecialchars($quiz['title']); ?></div>
                        <span class="quiz-badge"><?php echo ucfirst($quiz['difficulty'] ?? 'Medium'); ?></span>
                    </div>
                    <div class="quiz-card-body">
                        <p class="quiz-desc"><?php echo htmlspecialchars($quiz['description'] ?? 'Test your Bible knowledge'); ?></p>
                        <div class="quiz-meta-row">
                            <span class="meta-item"><i class="fas fa-question-circle"></i> <?php echo $quiz['total_questions']; ?> Q</span>
                            <span class="meta-item"><i class="fas fa-clock"></i> <?php echo $quiz['time_limit']; ?>s</span>
                            <span class="meta-item"><i class="fas fa-star"></i> <?php echo $quiz['total_points']; ?> pts</span>
                        </div>
                        <?php if($best > 0): ?>
                        <div class="best-score-chip"><span>🏆 Your Best</span> <strong><?php echo $best; ?>/<?php echo $quiz['total_points']; ?></strong></div>
                        <?php endif; ?>
                        <button class="btn-quiz" onclick="event.stopPropagation(); loadQuiz(<?php echo $quiz['id']; ?>, '<?php echo addslashes($quiz['title']); ?>', <?php echo $quiz['time_limit']; ?>, <?php echo $quiz['total_points']; ?>)"><i class="fas fa-play"></i> <?php echo $best > 0 ? 'Improve Score' : 'Start'; ?></button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state"><i class="fas fa-book-open fa-2x mb-3"></i><p>No quizzes available yet</p></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quiz Panel (dynamic) -->
    <div id="quizPanel" class="quiz-panel" style="display: none;">
        <div class="quiz-panel-header">
            <div class="quiz-progress-row">
                <span id="quizProgressText">Q1/10</span>
                <div class="progress-track"><div class="progress-fill" id="quizProgressFill" style="width:0%"></div></div>
                <div class="timer-mobile" id="quizTimer">00:00</div>
            </div>
            <div id="quizTitleHeader" style="font-size: 14px; opacity:0.9;"></div>
        </div>
        <div class="question-area">
            <div class="q-num" id="qNumber">Question 1</div>
            <div class="q-text" id="qText"></div>
            <div class="options-list" id="optionsList"></div>
        </div>
        <div class="quiz-actions">
            <button class="action-btn btn-pause" id="pauseQuizBtn"><i class="fas fa-pause"></i> Pause</button>
            <button class="action-btn btn-stop" id="stopQuizBtn"><i class="fas fa-stop"></i> Stop</button>
            <button class="action-btn btn-next" id="nextQuizBtn" disabled>Next <i class="fas fa-arrow-right"></i></button>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <div class="bottom-nav-mobile">
        <div class="nav-links">
            <a href="index.php" class="nav-link-item"><i class="fas fa-home"></i><span>Home</span></a>
            <a href="fybs.php" class="nav-link-item"><i class="fas fa-book-bible"></i><span>FYBS</span></a>
            <a href="bible_quiz.php" class="nav-link-item active"><i class="fas fa-question-circle"></i><span>Quiz</span></a>
            <a href="leaderboard.php" class="nav-link-item"><i class="fas fa-trophy"></i><span>Rank</span></a>
            <a href="profile.php" class="nav-link-item"><i class="fas fa-user"></i><span>Profile</span></a>
        </div>
    </div>
</div>

<script>
    // Set ring progress
    const circumference = 2 * Math.PI * 45;
    const ringFill = document.getElementById('ringFill');
    const overall = <?php echo $overall_progress ?? 0; ?>;
    if(ringFill) {
        const offset = circumference - (overall / 100) * circumference;
        ringFill.style.strokeDasharray = `${circumference} ${circumference}`;
        ringFill.style.strokeDashoffset = offset;
        document.getElementById('progressPercent').innerText = overall + '%';
    }

    // Quiz state
    let activeQuiz = null, questionsData = [], currentIdx = 0, userSelections = {}, scoreAcc = 0, timeLeftSec = 0;
    let timerInt = null, paused = false, quizStarted = false, quizTotalPoints = 0, correctCount = 0, startTimestamp = null;
    let currentQuizDbId = null, currentQuizTitle = '', currentTimeLimit = 0;

    const mainDiv = document.getElementById('mainContent');
    const quizPanel = document.getElementById('quizPanel');
    const quizzesListDiv = document.getElementById('quizzesList');

    async function loadQuiz(quizId, title, timeLimit, totalPoints) {
        if(quizStarted) return;
        currentQuizDbId = quizId;
        currentQuizTitle = title;
        currentTimeLimit = timeLimit;
        quizTotalPoints = totalPoints;
        try {
            const resp = await fetch(`get_quiz_data.php?id=${quizId}`);
            const data = await resp.json();
            if(data.success && data.quiz && data.quiz.questions) {
                questionsData = data.quiz.questions;
                startQuizSession();
            } else {
                alert("Unable to load quiz data");
            }
        } catch(e) { alert("Network error"); }
    }

    function startQuizSession() {
        currentIdx = 0;
        scoreAcc = 0;
        correctCount = 0;
        userSelections = {};
        timeLeftSec = currentTimeLimit;
        paused = false;
        quizStarted = true;
        startTimestamp = new Date();
        if(timerInt) clearInterval(timerInt);
        mainDiv.style.display = 'none';
        quizPanel.style.display = 'block';
        document.getElementById('quizTitleHeader').innerText = currentQuizTitle;
        startTimer();
        renderCurrentQuestion();
    }

    function startTimer() {
        timerInt = setInterval(() => {
            if(!paused && timeLeftSec > 0) {
                timeLeftSec--;
                updateTimerUI();
                if(timeLeftSec <= 0) endQuiz();
            } else if(timeLeftSec <= 0) clearInterval(timerInt);
        }, 1000);
    }

    function updateTimerUI() {
        const mins = Math.floor(timeLeftSec / 60);
        const secs = timeLeftSec % 60;
        document.getElementById('quizTimer').innerText = `${mins.toString().padStart(2,'0')}:${secs.toString().padStart(2,'0')}`;
    }

    function renderCurrentQuestion() {
        if(!questionsData.length) return;
        const q = questionsData[currentIdx];
        const total = questionsData.length;
        const progressPercent = ((currentIdx+1)/total)*100;
        document.getElementById('quizProgressText').innerText = `Q${currentIdx+1}/${total}`;
        document.getElementById('quizProgressFill').style.width = `${progressPercent}%`;
        document.getElementById('qNumber').innerHTML = `Question ${currentIdx+1} of ${total}`;
        document.getElementById('qText').innerText = q.question;
        const optionsDiv = document.getElementById('optionsList');
        optionsDiv.innerHTML = '';
        q.options.forEach((opt, idx) => {
            const optDiv = document.createElement('div');
            optDiv.className = 'option-mobile';
            if(userSelections[q.id] === idx) optDiv.classList.add('selected');
            optDiv.innerHTML = `<div class="opt-letter">${String.fromCharCode(65+idx)}</div><div class="opt-text">${escapeHtml(opt)}</div>`;
            optDiv.onclick = (e) => { selectAnswer(q.id, idx); };
            optionsDiv.appendChild(optDiv);
        });
        const nextBtn = document.getElementById('nextQuizBtn');
        nextBtn.disabled = (userSelections[q.id] === undefined);
    }

    function selectAnswer(questionId, selectedIdx) {
        userSelections[questionId] = selectedIdx;
        document.querySelectorAll('.option-mobile').forEach(opt => opt.classList.remove('selected'));
        const opts = document.querySelectorAll('.option-mobile');
        if(opts[selectedIdx]) opts[selectedIdx].classList.add('selected');
        document.getElementById('nextQuizBtn').disabled = false;
    }

    function nextQuestion() {
        const q = questionsData[currentIdx];
        const selected = userSelections[q.id];
        if(selected !== undefined) {
            if(selected === q.correct_answer) {
                scoreAcc += q.points;
                correctCount++;
            }
        }
        currentIdx++;
        if(currentIdx < questionsData.length) {
            renderCurrentQuestion();
        } else {
            endQuiz();
        }
    }

    function endQuiz() {
        if(timerInt) clearInterval(timerInt);
        const timeSpent = Math.floor((new Date() - startTimestamp) / 1000);
        const answersObj = {};
        questionsData.forEach(q => { answersObj[q.id] = (userSelections[q.id] !== undefined) ? userSelections[q.id] : -1; });
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'bible_quiz.php';
        const fields = {
            quiz_id: currentQuizDbId,
            answers: JSON.stringify(answersObj),
            time_spent: timeSpent,
            submit_quiz: '1'
        };
        for(let [k,v] of Object.entries(fields)) {
            let inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = k;
            inp.value = v;
            form.appendChild(inp);
        }
        document.body.appendChild(form);
        form.submit();
    }

    function stopQuiz() {
        if(confirm("Stop quiz? Progress will be lost.")) {
            if(timerInt) clearInterval(timerInt);
            quizPanel.style.display = 'none';
            mainDiv.style.display = 'block';
            quizStarted = false;
        }
    }

    function togglePauseQuiz() {
        paused = !paused;
        const btn = document.getElementById('pauseQuizBtn');
        if(paused) {
            btn.innerHTML = '<i class="fas fa-play"></i> Resume';
            btn.style.background = "#10b981";
            btn.style.color = "white";
        } else {
            btn.innerHTML = '<i class="fas fa-pause"></i> Pause';
            btn.style.background = "#f1f5f9";
            btn.style.color = "#334155";
        }
    }

    document.getElementById('nextQuizBtn').addEventListener('click', nextQuestion);
    document.getElementById('stopQuizBtn').addEventListener('click', stopQuiz);
    document.getElementById('pauseQuizBtn').addEventListener('click', togglePauseQuiz);

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
</script>
</body>
</html>