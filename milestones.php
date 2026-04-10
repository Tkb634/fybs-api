<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's program
$program_query = "SELECT id FROM addiction_breaker_programs WHERE user_id = ?";
$stmt = $conn->prepare($program_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$program_result = $stmt->get_result();

if ($program_result->num_rows === 0) {
    header("Location: addiction.php");
    exit();
}

$program = $program_result->fetch_assoc();
$program_id = $program['id'];

// Get all milestones
$query = "SELECT * FROM addiction_progress_milestones 
         WHERE program_id = ? 
         ORDER BY milestone_type, target_value";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $program_id);
$stmt->execute();
$result = $stmt->get_result();

$milestones_by_type = [];
while ($row = $result->fetch_assoc()) {
    $type = $row['milestone_type'];
    if (!isset($milestones_by_type[$type])) {
        $milestones_by_type[$type] = [];
    }
    $milestones_by_type[$type][] = $row;
}

// Get program stats
$stats_query = "SELECT current_streak, total_clean_days FROM addiction_breaker_programs WHERE id = ?";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $program_id);
$stmt->execute();
$stats_result = $stmt->get_result();
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Milestones - Addiction Breaker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #dc2626;
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
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            text-align: center;
        }
        
        .stats-value {
            font-size: 36px;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .stats-label {
            font-size: 14px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 1px;
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
        
        .milestone-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 15px;
            position: relative;
        }
        
        .milestone-card.achieved {
            border-color: #10b981;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        }
        
        .milestone-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            flex-shrink: 0;
        }
        
        .milestone-icon.achieved {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .milestone-icon.pending {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
        }
        
        .milestone-content {
            flex: 1;
        }
        
        .milestone-name {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .milestone-card.achieved .milestone-name {
            color: #065f46;
        }
        
        .milestone-progress {
            font-size: 14px;
            color: #6b7280;
        }
        
        .milestone-card.achieved .milestone-progress {
            color: #059669;
        }
        
        .progress-bar {
            height: 6px;
            background: #e5e7eb;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 8px;
        }
        
        .progress-fill {
            height: 100%;
            background: var(--primary-color);
            border-radius: 3px;
        }
        
        .milestone-card.achieved .progress-fill {
            background: #10b981;
        }
        
        .achieved-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #10b981;
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
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
            <i class="fas fa-trophy me-2"></i>Milestones
        </h1>
        <p style="color: #6b7280;">Track your recovery achievements</p>
    </div>
    
    <div class="stats-card">
        <div style="display: flex; justify-content: space-around;">
            <div>
                <div class="stats-value"><?php echo $stats['current_streak']; ?></div>
                <div class="stats-label">Day Streak</div>
            </div>
            <div>
                <div class="stats-value"><?php echo $stats['total_clean_days']; ?></div>
                <div class="stats-label">Clean Days</div>
            </div>
        </div>
    </div>
    
    <?php if (empty($milestones_by_type)): ?>
        <div class="section">
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-flag fa-3x mb-3" style="color: #6b7280;"></i>
                <p style="color: #6b7280;">No milestones set up yet.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($milestones_by_type as $type => $milestones): ?>
        <div class="section">
            <h2 class="section-title">
                <i class="fas <?php 
                    switch($type) {
                        case 'days_clean': echo 'fa-calendar-check'; break;
                        case 'skill_mastered': echo 'fa-graduation-cap'; break;
                        case 'goal_achieved': echo 'fa-bullseye'; break;
                        default: echo 'fa-flag';
                    }
                ?> me-2"></i>
                <?php echo ucwords(str_replace('_', ' ', $type)); ?>
            </h2>
            
            <?php foreach ($milestones as $milestone): 
                $is_achieved = $milestone['is_achieved'];
                $current_value = $milestone['current_value'];
                $target_value = $milestone['target_value'];
                $progress = $target_value > 0 ? min(100, ($current_value / $target_value) * 100) : 0;
            ?>
            <div class="milestone-card <?php echo $is_achieved ? 'achieved' : ''; ?>">
                <div class="milestone-icon <?php echo $is_achieved ? 'achieved' : 'pending'; ?>">
                    <i class="fas <?php 
                        switch($type) {
                            case 'days_clean': echo 'fa-calendar-day'; break;
                            case 'skill_mastered': echo 'fa-check-double'; break;
                            case 'goal_achieved': echo 'fa-bullseye'; break;
                            default: echo 'fa-flag';
                        }
                    ?>"></i>
                </div>
                
                <div class="milestone-content">
                    <div class="milestone-name"><?php echo htmlspecialchars($milestone['milestone_name']); ?></div>
                    <div class="milestone-progress">
                        <?php if ($type === 'days_clean'): ?>
                            <?php echo $current_value; ?> / <?php echo $target_value; ?> days
                        <?php elseif ($type === 'skill_mastered'): ?>
                            <?php echo $is_achieved ? 'Mastered' : 'In progress'; ?>
                        <?php else: ?>
                            <?php echo $is_achieved ? 'Achieved' : 'Target: ' . $target_value; ?>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!$is_achieved && $type === 'days_clean'): ?>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($is_achieved): ?>
                <div class="achieved-badge">
                    <i class="fas fa-check me-1"></i> Achieved
                    <?php if ($milestone['achieved_date']): ?>
                    <div style="font-size: 11px;"><?php echo date('M j', strtotime($milestone['achieved_date'])); ?></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <a href="addiction.php" class="back-btn">
        <i class="fas fa-arrow-left me-2"></i> Back to Recovery
    </a>
</body>
</html>