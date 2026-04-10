<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week'));
$month_start = date('Y-m-d', strtotime('first day of this month'));

// Get weekly statistics
$weekly_stats = [
    'prayer' => 0,
    'bible' => 0,
    'evangelism' => 0
];

// Prayer this week
$prayer_week = $conn->prepare("SELECT COUNT(*) as count FROM prayer_sessions WHERE user_id = ? AND session_date >= ?");
$prayer_week->bind_param("is", $user_id, $week_start);
$prayer_week->execute();
$weekly_stats['prayer'] = $prayer_week->get_result()->fetch_assoc()['count'];

// Bible study this week
$bible_week = $conn->prepare("SELECT COUNT(*) as count FROM bible_study_sessions WHERE user_id = ? AND session_date >= ?");
$bible_week->bind_param("is", $user_id, $week_start);
$bible_week->execute();
$weekly_stats['bible'] = $bible_week->get_result()->fetch_assoc()['count'];

// Evangelism this week
$evangelism_week = $conn->prepare("SELECT COUNT(*) as count FROM evangelism_activities WHERE user_id = ? AND activity_date >= ?");
$evangelism_week->bind_param("is", $user_id, $week_start);
$evangelism_week->execute();
$weekly_stats['evangelism'] = $evangelism_week->get_result()->fetch_assoc()['count'];

// Get recent activities
$recent_activities = [];

// Prayer sessions
$prayer_query = "SELECT *, 'prayer' as type FROM prayer_sessions WHERE user_id = ? ORDER BY session_date DESC, id DESC LIMIT 10";
$stmt = $conn->prepare($prayer_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$prayer_results = $stmt->get_result();
while ($row = $prayer_results->fetch_assoc()) {
    $recent_activities[] = $row;
}

// Bible studies
$bible_query = "SELECT *, 'bible' as type FROM bible_study_sessions WHERE user_id = ? ORDER BY session_date DESC, id DESC LIMIT 10";
$stmt = $conn->prepare($bible_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bible_results = $stmt->get_result();
while ($row = $bible_results->fetch_assoc()) {
    $recent_activities[] = $row;
}

// Evangelism activities
$evangelism_query = "SELECT *, 'evangelism' as type FROM evangelism_activities WHERE user_id = ? ORDER BY activity_date DESC, id DESC LIMIT 10";
$stmt = $conn->prepare($evangelism_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$evangelism_results = $stmt->get_result();
while ($row = $evangelism_results->fetch_assoc()) {
    $recent_activities[] = $row;
}

// Sort all activities by date
usort($recent_activities, function($a, $b) {
    $dateA = $a['type'] == 'prayer' ? $a['session_date'] : $a['activity_date'];
    $dateB = $b['type'] == 'prayer' ? $b['session_date'] : $b['activity_date'];
    return strtotime($dateB) - strtotime($dateA);
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gospel Activities - FYBS Youth App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #059669;
            --prayer-color: #3b82f6;
            --bible-color: #f59e0b;
            --evangelism-color: #ef4444;
        }
        
        body {
            background: #f8fafc;
            font-family: 'Inter', sans-serif;
            padding-bottom: 80px;
        }
        
        .header {
            background: white;
            padding: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 20px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #6b7280;
        }
        
        .section {
            margin: 20px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--primary-color);
            padding-left: 15px;
            border-left: 4px solid var(--primary-color);
        }
        
        .activity-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            border-left: 4px solid var(--primary-color);
        }
        
        .activity-card.prayer {
            border-left-color: var(--prayer-color);
        }
        
        .activity-card.bible {
            border-left-color: var(--bible-color);
        }
        
        .activity-card.evangelism {
            border-left-color: var(--evangelism-color);
        }
        
        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .activity-type {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            color: white;
        }
        
        .activity-type.prayer {
            background: var(--prayer-color);
        }
        
        .activity-type.bible {
            background: var(--bible-color);
        }
        
        .activity-type.evangelism {
            background: var(--evangelism-color);
        }
        
        .activity-date {
            font-size: 14px;
            color: #6b7280;
        }
        
        .activity-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #1f2937;
        }
        
        .activity-details {
            font-size: 14px;
            color: #4b5563;
            margin-bottom: 10px;
        }
        
        .back-btn {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            background: white;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            z-index: 100;
        }
        
        .back-btn:hover {
            background: var(--primary-color);
            color: white;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="font-size: 24px; font-weight: 700; margin-bottom: 5px;">
            <i class="fas fa-history me-2"></i>Gospel Activities
        </h1>
        <p style="color: #6b7280;">Track your prayer, Bible study, and evangelism</p>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value" style="color: var(--prayer-color);"><?php echo $weekly_stats['prayer']; ?></div>
            <div class="stat-label">Prayer Sessions<br>This Week</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value" style="color: var(--bible-color);"><?php echo $weekly_stats['bible']; ?></div>
            <div class="stat-label">Bible Studies<br>This Week</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value" style="color: var(--evangelism-color);"><?php echo $weekly_stats['evangelism']; ?></div>
            <div class="stat-label">Evangelism<br>This Week</div>
        </div>
    </div>
    
    <div class="section">
        <h2 class="section-title">
            <i class="fas fa-clock me-2"></i>
            Recent Activities
        </h2>
        
        <?php if (empty($recent_activities)): ?>
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-inbox fa-3x mb-3" style="color: #6b7280;"></i>
                <p style="color: #6b7280;">No activities recorded yet.</p>
                <p style="color: #6b7280; font-size: 14px;">Start by logging your first prayer, Bible study, or evangelism activity!</p>
            </div>
        <?php else: ?>
            <?php foreach (array_slice($recent_activities, 0, 10) as $activity): 
                $type = $activity['type'];
                $date = $type == 'prayer' ? $activity['session_date'] : $activity['activity_date'];
                $formatted_date = date('M j, Y', strtotime($date));
            ?>
            <div class="activity-card <?php echo $type; ?>">
                <div class="activity-header">
                    <span class="activity-type <?php echo $type; ?>">
                        <?php echo ucfirst($type); ?>
                    </span>
                    <span class="activity-date"><?php echo $formatted_date; ?></span>
                </div>
                
                <div class="activity-title">
                    <?php if ($type == 'prayer'): ?>
                        <?php echo htmlspecialchars($activity['topic'] ?: ucfirst($activity['prayer_type']) . ' Prayer'); ?>
                    <?php elseif ($type == 'bible'): ?>
                        <?php echo htmlspecialchars($activity['book']); ?> 
                        <?php echo $activity['chapter']; ?>
                        <?php if ($activity['verse_from']): ?>
                            :<?php echo $activity['verse_from']; ?>
                            <?php if ($activity['verse_to'] && $activity['verse_to'] != $activity['verse_from']): ?>
                                -<?php echo $activity['verse_to']; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php echo htmlspecialchars($activity['title']); ?>
                    <?php endif; ?>
                </div>
                
                <div class="activity-details">
                    <?php if ($type == 'prayer'): ?>
                        <i class="fas fa-clock me-1"></i> <?php echo $activity['duration_minutes']; ?> min
                        <?php if ($activity['scripture_reference']): ?>
                            • <i class="fas fa-book-bible me-1"></i> <?php echo $activity['scripture_reference']; ?>
                        <?php endif; ?>
                    <?php elseif ($type == 'bible'): ?>
                        <i class="fas fa-clock me-1"></i> <?php echo $activity['duration_minutes']; ?> min
                        • <i class="fas fa-book-open me-1"></i> <?php echo ucfirst($activity['study_method']); ?> Study
                    <?php else: ?>
                        <i class="fas fa-users me-1"></i> <?php echo $activity['people_reached']; ?> people reached
                        <?php if ($activity['decisions_made'] > 0): ?>
                            • <i class="fas fa-check-circle me-1"></i> <?php echo $activity['decisions_made']; ?> decisions
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <?php if ($type == 'prayer' && $activity['prayer_points']): ?>
                <div style="font-size: 14px; color: #4b5563; margin-top: 10px; font-style: italic;">
                    "<?php echo htmlspecialchars(substr($activity['prayer_points'], 0, 100)); ?>..."
                </div>
                <?php endif; ?>
                
                <?php if ($type == 'bible' && $activity['key_verses']): ?>
                <div style="font-size: 14px; color: #4b5563; margin-top: 10px; font-style: italic;">
                    "<?php echo htmlspecialchars(substr($activity['key_verses'], 0, 100)); ?>..."
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <a href="gospel.php" class="back-btn">
        <i class="fas fa-arrow-left me-2"></i> Back to Gospel
    </a>
</body>
</html>