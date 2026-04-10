<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's program type
$program_query = "SELECT program_type FROM addiction_breaker_programs WHERE user_id = ?";
$stmt = $conn->prepare($program_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$program_result = $stmt->get_result();

$program_type = 'all';
if ($program_result->num_rows > 0) {
    $program = $program_result->fetch_assoc();
    $program_type = $program['program_type'];
}

// Get all coping strategies
$query = "SELECT * FROM addiction_coping_strategies 
         WHERE is_active = 1 AND (program_type = ? OR program_type = 'all')
         ORDER BY sort_order, category";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $program_type);
$stmt->execute();
$result = $stmt->get_result();

$strategies_by_category = [];
while ($row = $result->fetch_assoc()) {
    $category = $row['category'];
    if (!isset($strategies_by_category[$category])) {
        $strategies_by_category[$category] = [];
    }
    $strategies_by_category[$category][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coping Strategies - Addiction Breaker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #dc2626;
            --gradient-primary: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
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
        
        .category-section {
            margin-bottom: 30px;
        }
        
        .category-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--primary-color);
            padding-left: 15px;
            border-left: 4px solid var(--primary-color);
        }
        
        .strategy-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            transition: transform 0.3s ease;
        }
        
        .strategy-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        .strategy-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .strategy-icon {
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
        
        .strategy-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #1f2937;
        }
        
        .strategy-duration {
            font-size: 14px;
            color: #6b7280;
        }
        
        .strategy-description {
            color: #4b5563;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .strategy-instructions {
            background: #f9fafb;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #3b82f6;
        }
        
        .strategy-tips {
            background: #fef3c7;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #f59e0b;
        }
        
        .btn-try {
            background: var(--gradient-primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 10px;
        }
        
        .btn-try:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
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
            <i class="fas fa-brain me-2"></i>Coping Strategies
        </h1>
        <p style="color: #6b7280;">Tools to manage cravings and stay strong</p>
    </div>
    
    <div class="container mt-4">
        <?php if (empty($strategies_by_category)): ?>
            <div class="text-center py-5">
                <i class="fas fa-tools fa-3x mb-3" style="color: #6b7280;"></i>
                <p>No strategies available for your program type.</p>
            </div>
        <?php else: ?>
            <?php foreach ($strategies_by_category as $category => $strategies): ?>
            <div class="category-section">
                <h2 class="category-title">
                    <?php 
                    $category_icons = [
                        'distraction' => 'fas fa-gamepad',
                        'mindfulness' => 'fas fa-spa',
                        'physical' => 'fas fa-running',
                        'social' => 'fas fa-users',
                        'spiritual' => 'fas fa-pray',
                        'cognitive' => 'fas fa-brain',
                        'emergency' => 'fas fa-first-aid'
                    ];
                    ?>
                    <i class="<?php echo $category_icons[$category] ?? 'fas fa-tools'; ?> me-2"></i>
                    <?php echo ucfirst($category); ?> Strategies
                </h2>
                
                <?php foreach ($strategies as $strategy): ?>
                <div class="strategy-card">
                    <div class="strategy-header">
                        <div class="strategy-icon" style="background: <?php 
                            switch($category) {
                                case 'emergency': echo 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)'; break;
                                case 'mindfulness': echo 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)'; break;
                                case 'physical': echo 'linear-gradient(135deg, #10b981 0%, #059669 100%)'; break;
                                case 'social': echo 'linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%)'; break;
                                case 'spiritual': echo 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)'; break;
                                default: echo 'linear-gradient(135deg, #6b7280 0%, #4b5563 100%)';
                            }
                        ?>;">
                            <i class="fas <?php echo $strategy['icon'] ?? 'fa-tools'; ?>"></i>
                        </div>
                        <div>
                            <h3 class="strategy-name"><?php echo htmlspecialchars($strategy['strategy_name']); ?></h3>
                            <div class="strategy-duration">
                                <i class="fas fa-clock me-1"></i>
                                <?php echo $strategy['duration_minutes']; ?> minutes
                                • 
                                <i class="fas fa-signal me-1"></i>
                                <?php echo ucfirst($strategy['difficulty_level']); ?>
                            </div>
                        </div>
                    </div>
                    
                    <p class="strategy-description"><?php echo htmlspecialchars($strategy['description']); ?></p>
                    
                    <?php if ($strategy['instructions']): ?>
                    <div class="strategy-instructions">
                        <strong><i class="fas fa-list-ol me-2"></i>Instructions:</strong>
                        <p class="mt-2 mb-0"><?php echo htmlspecialchars($strategy['instructions']); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($strategy['tips']): ?>
                    <div class="strategy-tips">
                        <strong><i class="fas fa-lightbulb me-2"></i>Tips:</strong>
                        <p class="mt-2 mb-0"><?php echo htmlspecialchars($strategy['tips']); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <button class="btn-try" onclick="tryStrategy(<?php echo $strategy['id']; ?>)">
                        <i class="fas fa-play-circle me-2"></i> Try This Strategy
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <a href="addiction.php" class="back-btn">
        <i class="fas fa-arrow-left me-2"></i> Back to Recovery
    </a>

    <script>
        function tryStrategy(strategyId) {
            // Start timer for strategy
            const minutes = 5; // Default or from strategy data
            let seconds = minutes * 60;
            
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.8);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 2000;
            `;
            
            modal.innerHTML = `
                <div style="background: white; border-radius: 20px; padding: 30px; max-width: 400px; width: 90%; text-align: center;">
                    <h2 style="color: #dc2626; margin-bottom: 20px;">
                        <i class="fas fa-clock me-2"></i>Strategy Timer
                    </h2>
                    <div id="timer" style="font-size: 48px; font-weight: 700; color: #1f2937; margin: 20px 0;">
                        ${String(minutes).padStart(2, '0')}:00
                    </div>
                    <p style="color: #6b7280; margin-bottom: 30px;">
                        Focus on the strategy until the timer completes.
                    </p>
                    <div style="display: flex; gap: 10px;">
                        <button onclick="startTimer()" style="flex: 1; background: #10b981; color: white; border: none; padding: 12px; border-radius: 10px; font-weight: 600; cursor: pointer;">
                            <i class="fas fa-play me-2"></i> Start
                        </button>
                        <button onclick="closeModal()" style="flex: 1; background: #6b7280; color: white; border: none; padding: 12px; border-radius: 10px; font-weight: 600; cursor: pointer;">
                            <i class="fas fa-times me-2"></i> Cancel
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            let timerInterval;
            
            window.startTimer = function() {
                timerInterval = setInterval(() => {
                    seconds--;
                    const mins = Math.floor(seconds / 60);
                    const secs = seconds % 60;
                    document.getElementById('timer').textContent = 
                        `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
                    
                    if (seconds <= 0) {
                        clearInterval(timerInterval);
                        document.getElementById('timer').innerHTML = `
                            <div style="color: #10b981;">
                                <i class="fas fa-check-circle fa-2x mb-2"></i>
                                <div>Complete!</div>
                            </div>
                        `;
                        
                        // Log strategy usage
                        fetch('addiction-actions.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: 'action=log_strategy&strategy_id=' + strategyId
                        });
                        
                        setTimeout(() => {
                            closeModal();
                            alert('Great job! Strategy completed successfully.');
                        }, 2000);
                    }
                }, 1000);
            };
            
            window.closeModal = function() {
                if (timerInterval) clearInterval(timerInterval);
                modal.remove();
            };
        }
    </script>
</body>
</html>