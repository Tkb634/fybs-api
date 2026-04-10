<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle program selection
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $program_type = $_POST['program_type'];
    $addiction_type = $_POST['addiction_type'] ?? '';
    $severity = $_POST['severity'] ?? 'moderate';
    $emergency_name = $_POST['emergency_name'] ?? '';
    $emergency_phone = $_POST['emergency_phone'] ?? '';
    
    // Insert new program
    $insert_query = "INSERT INTO addiction_breaker_programs 
                    (user_id, program_type, addiction_type, severity_level,
                     start_date, emergency_contact_name, emergency_contact_phone,
                     current_stage, progress_percentage)
                    VALUES (?, ?, ?, ?, CURDATE(), ?, ?, 'commitment', 10)";
    
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("isssss", $user_id, $program_type, $addiction_type, 
                     $severity, $emergency_name, $emergency_phone);
    
    if ($stmt->execute()) {
        header("Location: addiction.php?program_started=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start Program - Addiction Breaker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #7c3aed;
            --secondary: #8b5cf6;
        }
        .program-card {
            border: 2px solid transparent;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }
        .program-card:hover, .program-card.selected {
            border-color: var(--primary);
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .program-icon {
            width: 70px;
            height: 70px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            margin: 0 auto 1rem;
            color: white;
        }
        .program-card:nth-child(1) .program-icon {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        .program-card:nth-child(2) .program-icon {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        }
        .program-card:nth-child(3) .program-icon {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .severity-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            cursor: pointer;
            border: 2px solid transparent;
        }
        .severity-badge:hover, .severity-badge.selected {
            border-color: var(--primary);
        }
        .step-indicator {
            position: relative;
            margin: 2rem 0;
        }
        .step {
            display: inline-block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            border-radius: 50%;
            text-align: center;
            background: #e9ecef;
            margin: 0 10px;
        }
        .step.active {
            background: var(--primary);
            color: white;
        }
        @media (prefers-color-scheme: dark) {
            .program-card {
                background: #1f2937;
            }
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="text-center mb-5">
            <h1 class="display-5 fw-bold">
                <i class="fas fa-shield-heart"></i> Choose Your Program
            </h1>
            <p class="lead text-muted">Select the program that best fits your recovery needs</p>
        </div>
        
        <form method="POST" id="programForm">
            <!-- Step 1: Program Selection -->
            <div id="step1">
                <h3 class="mb-4">Step 1: Select Program Type</h3>
                <div class="row">
                    <!-- Substance Abuse Program -->
                    <div class="col-md-4">
                        <div class="program-card" data-program="substance">
                            <div class="program-icon">
                                <i class="fas fa-pills"></i>
                            </div>
                            <h4 class="text-center">Substance Abuse</h4>
                            <p class="text-muted text-center">
                                Recovery from alcohol, drugs, or medication addiction
                            </p>
                            <div class="text-center">
                                <span class="badge bg-danger">90-day program</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Focus Program -->
                    <div class="col-md-4">
                        <div class="program-card" data-program="focus">
                            <div class="program-icon">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <h4 class="text-center">Focus & Digital</h4>
                            <p class="text-muted text-center">
                                Overcome digital addiction, procrastination, and improve focus
                            </p>
                            <div class="text-center">
                                <span class="badge bg-primary">30-day program</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Healing Program -->
                    <div class="col-md-4">
                        <div class="program-card" data-program="healing">
                            <div class="program-icon">
                                <i class="fas fa-heart"></i>
                            </div>
                            <h4 class="text-center">Healing & Trauma</h4>
                            <p class="text-muted text-center">
                                Emotional healing, trauma recovery, and mental wellness
                            </p>
                            <div class="text-center">
                                <span class="badge bg-success">60-day program</span>
                            </div>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="program_type" id="selectedProgram">
            </div>
            
            <!-- Step 2: Program Details -->
            <div id="step2" class="d-none">
                <h3 class="mb-4">Step 2: Program Details</h3>
                <div class="card p-4">
                    <div class="mb-3">
                        <label class="form-label">Specific Addiction/Issue</label>
                        <input type="text" class="form-control" name="addiction_type" 
                               placeholder="e.g., Alcohol, Social Media, Anxiety" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Severity Level</label>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="severity-badge bg-success" data-severity="low">Low</span>
                            <span class="severity-badge bg-warning" data-severity="moderate">Moderate</span>
                            <span class="severity-badge bg-danger" data-severity="high">High</span>
                            <span class="severity-badge bg-dark" data-severity="severe">Severe</span>
                        </div>
                        <input type="hidden" name="severity" id="selectedSeverity" value="moderate">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Daily Check-in Time Preference</label>
                        <select class="form-select" name="checkin_time">
                            <option value="08:00:00">Morning (8 AM)</option>
                            <option value="12:00:00">Noon (12 PM)</option>
                            <option value="18:00:00">Evening (6 PM)</option>
                            <option value="21:00:00">Night (9 PM)</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Step 3: Emergency Contact -->
            <div id="step3" class="d-none">
                <h3 class="mb-4">Step 3: Safety & Support</h3>
                <div class="card p-4">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> This information is kept confidential and only used for your safety.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Emergency Contact Name</label>
                            <input type="text" class="form-control" name="emergency_name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Emergency Contact Phone</label>
                            <input type="tel" class="form-control" name="emergency_phone">
                        </div>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="termsCheck" required>
                        <label class="form-check-label" for="termsCheck">
                            I understand this is a self-help tool and not a replacement for professional treatment
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Navigation Buttons -->
            <div class="d-flex justify-content-between mt-5">
                <button type="button" class="btn btn-outline-secondary" id="prevBtn" style="display: none;">
                    <i class="fas fa-arrow-left"></i> Previous
                </button>
                <button type="button" class="btn btn-primary" id="nextBtn">
                    Next <i class="fas fa-arrow-right"></i>
                </button>
                <button type="submit" class="btn btn-success" id="submitBtn" style="display: none;">
                    <i class="fas fa-play"></i> Start Program
                </button>
            </div>
        </form>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentStep = 1;
        
        // Program selection
        document.querySelectorAll('.program-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.program-card').forEach(c => {
                    c.classList.remove('selected');
                });
                this.classList.add('selected');
                document.getElementById('selectedProgram').value = this.dataset.program;
                enableNextButton();
            });
        });
        
        // Severity selection
        document.querySelectorAll('.severity-badge').forEach(badge => {
            badge.addEventListener('click', function() {
                document.querySelectorAll('.severity-badge').forEach(b => {
                    b.classList.remove('selected');
                });
                this.classList.add('selected');
                document.getElementById('selectedSeverity').value = this.dataset.severity;
            });
        });
        
        // Set default severity
        document.querySelector('.severity-badge[data-severity="moderate"]').click();
        
        // Navigation
        document.getElementById('nextBtn').addEventListener('click', function() {
            if (validateStep(currentStep)) {
                if (currentStep < 3) {
                    document.getElementById('step' + currentStep).classList.add('d-none');
                    currentStep++;
                    document.getElementById('step' + currentStep).classList.remove('d-none');
                    updateNavigation();
                }
            }
        });
        
        document.getElementById('prevBtn').addEventListener('click', function() {
            if (currentStep > 1) {
                document.getElementById('step' + currentStep).classList.add('d-none');
                currentStep--;
                document.getElementById('step' + currentStep).classList.remove('d-none');
                updateNavigation();
            }
        });
        
        function validateStep(step) {
            switch(step) {
                case 1:
                    if (!document.getElementById('selectedProgram').value) {
                        alert('Please select a program type');
                        return false;
                    }
                    return true;
                case 2:
                    const addictionType = document.querySelector('input[name="addiction_type"]');
                    if (!addictionType.value.trim()) {
                        alert('Please specify the addiction/issue');
                        addictionType.focus();
                        return false;
                    }
                    return true;
                case 3:
                    return document.getElementById('termsCheck').checked;
            }
            return true;
        }
        
        function updateNavigation() {
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const submitBtn = document.getElementById('submitBtn');
            
            if (currentStep === 1) {
                prevBtn.style.display = 'none';
                nextBtn.style.display = 'inline-block';
                submitBtn.style.display = 'none';
            } else if (currentStep === 3) {
                prevBtn.style.display = 'inline-block';
                nextBtn.style.display = 'none';
                submitBtn.style.display = 'inline-block';
            } else {
                prevBtn.style.display = 'inline-block';
                nextBtn.style.display = 'inline-block';
                submitBtn.style.display = 'none';
            }
        }
        
        function enableNextButton() {
            document.getElementById('nextBtn').disabled = false;
        }
        
        // Initialize
        updateNavigation();
    </script>
</body>
</html>