<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Get user's active program
$program_query = "SELECT * FROM addiction_breaker_programs WHERE user_id = ? AND current_stage != 'graduated' ORDER BY created_at DESC LIMIT 1";
$stmt = $conn->prepare($program_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$program = $stmt->get_result()->fetch_assoc();

if (!$program) {
    header("Location: addiction-start.php");
    exit();
}

// Check if today's check-in exists
$checkin_query = "SELECT * FROM addiction_daily_checkins WHERE program_id = ? AND checkin_date = ?";
$stmt = $conn->prepare($checkin_query);
$stmt->bind_param("is", $program['id'], $today);
$stmt->execute();
$existing_checkin = $stmt->get_result()->fetch_assoc();

// Calculate current streak
$current_streak = $program['current_streak'];
$day_clean = $program['total_clean_days'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $craving_intensity = $_POST['craving_intensity'] ?? 'none';
    $mood_before = $_POST['mood_before'] ?? 'neutral';
    $substance_free = isset($_POST['substance_free']) ? 1 : 0;
    $sleep_hours = $_POST['sleep_hours'] ?? 0;
    $exercise_minutes = $_POST['exercise_minutes'] ?? 0;
    $gratitude_entry = $_POST['gratitude_entry'] ?? '';
    $challenges_faced = $_POST['challenges_faced'] ?? '';
    $victories_achieved = $_POST['victories_achieved'] ?? '';
    
    // Handle relapse
    $relapse_occurred = isset($_POST['relapse_occurred']) ? 1 : 0;
    $relapse_substance = $_POST['relapse_substance'] ?? '';
    $relapse_amount = $_POST['relapse_amount'] ?? '';
    
    // Calculate new streak
    $new_streak = $substance_free ? $current_streak + 1 : 0;
    $new_clean_days = $substance_free ? $day_clean + 1 : $day_clean;
    
    if ($existing_checkin) {
        // Update existing check-in
        $update_query = "UPDATE addiction_daily_checkins SET 
                         craving_intensity = ?, mood_before = ?, substance_free = ?,
                         sleep_hours = ?, exercise_minutes = ?, gratitude_entry = ?,
                         challenges_faced = ?, victories_achieved = ?, relapse_occurred = ?,
                         relapse_substance = ?, relapse_amount = ?, is_completed = 1,
                         completed_at = NOW()
                         WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssiddssissi", 
            $craving_intensity, $mood_before, $substance_free,
            $sleep_hours, $exercise_minutes, $gratitude_entry,
            $challenges_faced, $victories_achieved, $relapse_occurred,
            $relapse_substance, $relapse_amount, $existing_checkin['id']
        );
        $stmt->execute();
    } else {
        // Insert new check-in
        $insert_query = "INSERT INTO addiction_daily_checkins 
                        (program_id, checkin_date, day_clean, craving_intensity, 
                         mood_before, substance_free, sleep_hours, exercise_minutes,
                         gratitude_entry, challenges_faced, victories_achieved,
                         relapse_occurred, relapse_substance, relapse_amount,
                         checkin_streak, is_completed, completed_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("isisisdississi",
            $program['id'], $today, $day_clean, $craving_intensity,
            $mood_before, $substance_free, $sleep_hours, $exercise_minutes,
            $gratitude_entry, $challenges_faced, $victories_achieved,
            $relapse_occurred, $relapse_substance, $relapse_amount, $new_streak
        );
        $stmt->execute();
        $checkin_id = $stmt->insert_id;
    }
    
    // Update program stats
    $update_program = "UPDATE addiction_breaker_programs SET 
                      current_streak = ?, total_clean_days = ?,
                      relapse_count = relapse_count + ?,
                      last_relapse_date = CASE WHEN ? = 1 THEN ? ELSE last_relapse_date END,
                      progress_percentage = LEAST(progress_percentage + 0.5, 100)
                      WHERE id = ?";
    $stmt = $conn->prepare($update_program);
    $last_relapse_date = $relapse_occurred ? $today : null;
    $stmt->bind_param("iiiisii", 
        $new_streak, $new_clean_days, $relapse_occurred,
        $relapse_occurred, $last_relapse_date, $program['id']
    );
    $stmt->execute();
    
    // Check for milestones
    checkMilestones($program['id'], $new_streak, $new_clean_days, $conn);
    
    header("Location: addiction.php?checkin=success");
    exit();
}

function checkMilestones($program_id, $streak, $clean_days, $conn) {
    $milestones = [
        ['days_clean', '1 Day Clean', 1],
        ['days_clean', '3 Days Clean', 3],
        ['days_clean', '1 Week Clean', 7],
        ['days_clean', '2 Weeks Clean', 14],
        ['days_clean', '1 Month Clean', 30],
        ['days_clean', '90 Days Clean', 90],
        ['days_clean', '6 Months Clean', 180],
        ['days_clean', '1 Year Clean', 365]
    ];
    
    foreach ($milestones as $milestone) {
        if ($clean_days >= $milestone[2]) {
            // Check if milestone already exists
            $check_query = "SELECT id FROM addiction_progress_milestones 
                           WHERE program_id = ? AND milestone_name = ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("is", $program_id, $milestone[1]);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows == 0) {
                // Insert new milestone
                $insert_query = "INSERT INTO addiction_progress_milestones 
                                (program_id, milestone_type, milestone_name, 
                                 target_value, current_value, achieved_date, is_achieved)
                                VALUES (?, ?, ?, ?, ?, CURDATE(), 1)";
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("issiii", 
                    $program_id, $milestone[0], $milestone[1],
                    $milestone[2], $clean_days
                );
                $stmt->execute();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Check-in - Addiction Breaker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #7c3aed;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }
        .checkin-header {
            background: linear-gradient(135deg, var(--primary) 0%, #8b5cf6 100%);
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 1.5rem;
        }
        .mood-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 3px solid transparent;
            transition: all 0.3s;
        }
        .mood-btn:hover, .mood-btn.active {
            transform: scale(1.1);
            border-color: var(--primary);
        }
        .craving-slider {
            -webkit-appearance: none;
            width: 100%;
            height: 10px;
            border-radius: 5px;
            background: #e9ecef;
            outline: none;
        }
        .craving-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            background: var(--primary);
            cursor: pointer;
        }
        .form-section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        @media (prefers-color-scheme: dark) {
            .form-section {
                background: #1f2937;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="checkin-header">
        <div class="container">
            <div class="d-flex align-items-center">
                <a href="addiction.php" class="text-white me-3">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h4 class="mb-1"><i class="fas fa-check-circle"></i> Daily Check-in</h4>
                    <small>Day <?php echo $current_streak + 1; ?> • <?php echo date('F j, Y'); ?></small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <form method="POST" id="checkinForm">
            <!-- Mood Section -->
            <div class="form-section">
                <h6 class="mb-3"><i class="fas fa-smile text-primary"></i> How are you feeling?</h6>
                <div class="row g-2 mb-3">
                    <?php
                    $moods = [
                        ['very_happy', 'fa-grin-stars', 'Excellent', 'success'],
                        ['happy', 'fa-smile', 'Good', 'success'],
                        ['neutral', 'fa-meh', 'Neutral', 'warning'],
                        ['sad', 'fa-frown', 'Sad', 'warning'],
                        ['very_sad', 'fa-sad-cry', 'Very Sad', 'danger'],
                        ['anxious', 'fa-flushed', 'Anxious', 'danger']
                    ];
                    foreach ($moods as $mood):
                    ?>
                    <div class="col-4 col-md-2">
                        <button type="button" class="btn btn-outline-<?php echo $mood[3]; ?> w-100 mood-btn" 
                                data-mood="<?php echo $mood[0]; ?>">
                            <i class="fas <?php echo $mood[1]; ?> fa-2x"></i>
                            <div class="small mt-1"><?php echo $mood[2]; ?></div>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="mood_before" id="selectedMood" value="neutral">
            </div>
            
            <!-- Cravings Section -->
            <div class="form-section">
                <h6 class="mb-3"><i class="fas fa-fire text-warning"></i> Cravings Today</h6>
                <div class="mb-3">
                    <label class="form-label">Intensity (0 = None, 10 = Overwhelming)</label>
                    <input type="range" class="craving-slider" name="craving_intensity" 
                           min="0" max="10" value="0" id="cravingSlider">
                    <div class="d-flex justify-content-between mt-2">
                        <small>None</small>
                        <small id="cravingValue">0/10</small>
                        <small>Overwhelming</small>
                    </div>
                </div>
            </div>
            
            <!-- Substance Free Section -->
            <div class="form-section">
                <h6 class="mb-3"><i class="fas fa-shield-alt text-success"></i> Stayed Clean Today?</h6>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="substance_free" 
                           id="substanceFree" checked style="transform: scale(1.5);">
                    <label class="form-check-label h5 ms-3" for="substanceFree">
                        Yes, I stayed substance-free today
                    </label>
                </div>
                
                <!-- Relapse Details (Hidden by default) -->
                <div id="relapseSection" class="mt-3 d-none">
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle"></i> Relapse Details</h6>
                        <input type="hidden" name="relapse_occurred" value="1">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Substance</label>
                                <input type="text" class="form-control" name="relapse_substance" 
                                       placeholder="What did you use?">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Amount</label>
                                <input type="text" class="form-control" name="relapse_amount" 
                                       placeholder="How much?">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Wellness Section -->
            <div class="form-section">
                <h6 class="mb-3"><i class="fas fa-heart text-danger"></i> Wellness Check</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            <i class="fas fa-moon"></i> Sleep (hours)
                        </label>
                        <input type="number" class="form-control" name="sleep_hours" 
                               min="0" max="24" step="0.5" value="7">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            <i class="fas fa-running"></i> Exercise (minutes)
                        </label>
                        <input type="number" class="form-control" name="exercise_minutes" 
                               min="0" max="300" value="0">
                    </div>
                </div>
            </div>
            
            <!-- Gratitude Section -->
            <div class="form-section">
                <h6 class="mb-3"><i class="fas fa-sun text-warning"></i> Today's Gratitude</h6>
                <textarea class="form-control" name="gratitude_entry" rows="3" 
                          placeholder="What are you grateful for today?"></textarea>
            </div>
            
            <!-- Challenges & Victories -->
            <div class="form-section">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <h6><i class="fas fa-mountain text-primary"></i> Challenges</h6>
                        <textarea class="form-control" name="challenges_faced" rows="3" 
                                  placeholder="What was difficult today?"></textarea>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-trophy text-success"></i> Victories</h6>
                        <textarea class="form-control" name="victories_achieved" rows="3" 
                                  placeholder="What went well today?"></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Submit Button -->
            <div class="fixed-bottom bg-white p-3 border-top">
                <div class="container">
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-check-circle"></i> Submit Daily Check-in
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mood selection
        document.querySelectorAll('.mood-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.mood-btn').forEach(b => {
                    b.classList.remove('active');
                    b.classList.remove('btn-success', 'btn-warning', 'btn-danger');
                    b.classList.add('btn-outline-' + b.classList[1].replace('btn-outline-', ''));
                });
                
                this.classList.remove('btn-outline-' + this.classList[1].replace('btn-outline-', ''));
                this.classList.add('btn-' + this.classList[1].replace('btn-outline-', ''));
                this.classList.add('active');
                document.getElementById('selectedMood').value = this.dataset.mood;
            });
        });
        
        // Cravings slider
        const slider = document.getElementById('cravingSlider');
        const valueDisplay = document.getElementById('cravingValue');
        slider.addEventListener('input', function() {
            valueDisplay.textContent = this.value + '/10';
        });
        
        // Relapse section toggle
        const substanceFree = document.getElementById('substanceFree');
        const relapseSection = document.getElementById('relapseSection');
        
        substanceFree.addEventListener('change', function() {
            if (!this.checked) {
                relapseSection.classList.remove('d-none');
            } else {
                relapseSection.classList.add('d-none');
            }
        });
        
        // Form submission
        document.getElementById('checkinForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show confirmation
            if (!substanceFree.checked) {
                if (!confirm("You're reporting a relapse. This will reset your streak to 0. Continue?")) {
                    return;
                }
            }
            
            this.submit();
        });
        
        // Set initial mood
        document.querySelector('.mood-btn[data-mood="neutral"]').click();
    </script>
</body>
</html>