<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get emergency contact info
$query = "SELECT emergency_contact_name, emergency_contact_phone, 
                 emergency_contact_relation 
          FROM addiction_breaker_programs 
          WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$has_contact = false;
$contact_info = null;

if ($result->num_rows > 0) {
    $has_contact = true;
    $contact_info = $result->fetch_assoc();
}

// Hotlines data
$hotlines = [
    [
        'name' => 'National Helpline',
        'number' => '1-800-662-HELP',
        'description' => '24/7 treatment referral and information'
    ],
    [
        'name' => 'Crisis Text Line',
        'number' => 'Text HOME to 741741',
        'description' => 'Free 24/7 crisis counseling'
    ],
    [
        'name' => 'Suicide Prevention',
        'number' => '988',
        'description' => '24/7 confidential support'
    ],
    [
        'name' => 'SAMHSA National Helpline',
        'number' => '1-800-662-4357',
        'description' => 'Treatment referral service'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Contact - Addiction Breaker</title>
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
        
        .section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .contact-card {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            margin-bottom: 20px;
            border: 2px solid #fca5a5;
        }
        
        .contact-name {
            font-size: 24px;
            font-weight: 700;
            color: #dc2626;
            margin-bottom: 10px;
        }
        
        .contact-number {
            font-size: 28px;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 15px;
        }
        
        .contact-relation {
            font-size: 16px;
            color: #6b7280;
            margin-bottom: 20px;
        }
        
        .btn-call {
            background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            text-decoration: none;
            display: block;
        }
        
        .btn-call:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            color: white;
        }
        
        .hotline-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #3b82f6;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .hotline-name {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .hotline-number {
            font-size: 20px;
            font-weight: 700;
            color: #3b82f6;
            margin-bottom: 8px;
        }
        
        .hotline-desc {
            font-size: 14px;
            color: #6b7280;
        }
        
        .coping-tips {
            background: #fef3c7;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            border-left: 4px solid #f59e0b;
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
            <i class="fas fa-phone-alt me-2"></i>Emergency Contact
        </h1>
        <p style="color: #6b7280;">Get help when you need it most</p>
    </div>
    
    <div class="section">
        <h2 class="section-title">
            <i class="fas fa-user-friends"></i>
            Your Emergency Contact
        </h2>
        
        <?php if ($has_contact && $contact_info['emergency_contact_name']): ?>
        <div class="contact-card">
            <div class="contact-name"><?php echo htmlspecialchars($contact_info['emergency_contact_name']); ?></div>
            <?php if ($contact_info['emergency_contact_relation']): ?>
            <div class="contact-relation"><?php echo htmlspecialchars($contact_info['emergency_contact_relation']); ?></div>
            <?php endif; ?>
            <div class="contact-number"><?php echo htmlspecialchars($contact_info['emergency_contact_phone']); ?></div>
            <a href="tel:<?php echo urlencode($contact_info['emergency_contact_phone']); ?>" class="btn-call">
                <i class="fas fa-phone-alt me-2"></i> Call Now
            </a>
        </div>
        <?php else: ?>
        <div style="text-align: center; padding: 30px;">
            <i class="fas fa-user-plus fa-3x mb-3" style="color: #6b7280;"></i>
            <p style="color: #6b7280; margin-bottom: 20px;">No emergency contact set up yet.</p>
            <a href="addiction.php" class="btn-call" style="background: #6b7280;">
                <i class="fas fa-cog me-2"></i> Set Up Contact
            </a>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2 class="section-title">
            <i class="fas fa-phone-volume"></i>
            Emergency Hotlines
        </h2>
        
        <?php foreach ($hotlines as $hotline): ?>
        <div class="hotline-card">
            <div class="hotline-name"><?php echo $hotline['name']; ?></div>
            <div class="hotline-number"><?php echo $hotline['number']; ?></div>
            <div class="hotline-desc"><?php echo $hotline['description']; ?></div>
            <?php 
            $phone_number = preg_replace('/[^0-9]/', '', $hotline['number']);
            if (strlen($phone_number) >= 10): 
            ?>
            <a href="tel:<?php echo $phone_number; ?>" style="
                display: inline-block;
                background: #3b82f6;
                color: white;
                padding: 8px 16px;
                border-radius: 8px;
                text-decoration: none;
                font-size: 14px;
                font-weight: 500;
                margin-top: 10px;
            ">
                <i class="fas fa-phone me-2"></i> Call
            </a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="section">
        <h2 class="section-title">
            <i class="fas fa-first-aid"></i>
            Immediate Coping Strategies
        </h2>
        
        <div class="coping-tips">
            <strong style="color: #92400e; display: block; margin-bottom: 10px;">
                <i class="fas fa-lightbulb me-2"></i>When in crisis:
            </strong>
            <ul style="color: #92400e; padding-left: 20px; margin-bottom: 0;">
                <li>Breathe deeply for 1 minute (4-7-8 breathing)</li>
                <li>Call your emergency contact or a hotline</li>
                <li>Use the 5-4-3-2-1 grounding technique</li>
                <li>Take a cold shower or hold ice cubes</li>
                <li>Go for a 10-minute brisk walk</li>
                <li>Repeat: "This craving will pass. I am stronger than this."</li>
            </ul>
        </div>
    </div>
    
    <a href="addiction.php" class="back-btn">
        <i class="fas fa-arrow-left me-2"></i> Back to Recovery
    </a>

    <script>
        // Add click-to-call functionality
        document.querySelectorAll('a[href^="tel:"]').forEach(link => {
            link.addEventListener('click', function(e) {
                if (confirm('Are you sure you want to call this number?')) {
                    // Log emergency call
                    fetch('addiction-actions.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'action=log_emergency_call'
                    });
                } else {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>