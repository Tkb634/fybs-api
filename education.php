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

// Get educational content
$query = "SELECT * FROM addiction_educational_content 
         WHERE program_type IN (?, 'all')
         ORDER BY category, order_index";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $program_type);
$stmt->execute();
$result = $stmt->get_result();

$content_by_category = [];
while ($row = $result->fetch_assoc()) {
    $category = $row['category'] ?: 'general';
    if (!isset($content_by_category[$category])) {
        $content_by_category[$category] = [];
    }
    $content_by_category[$category][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Education - Addiction Breaker</title>
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
        
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            transition: transform 0.3s ease;
            cursor: pointer;
        }
        
        .content-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        .content-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .content-icon {
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
        
        .content-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #1f2937;
        }
        
        .content-meta {
            font-size: 14px;
            color: #6b7280;
        }
        
        .content-description {
            color: #4b5563;
            line-height: 1.6;
        }
        
        .badge {
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
            <i class="fas fa-graduation-cap me-2"></i>Educational Resources
        </h1>
        <p style="color: #6b7280;">Learn about addiction and recovery</p>
    </div>
    
    <div class="container mt-4">
        <?php if (empty($content_by_category)): ?>
            <div class="text-center py-5">
                <i class="fas fa-book-open fa-3x mb-3" style="color: #6b7280;"></i>
                <p>No educational content available.</p>
            </div>
        <?php else: ?>
            <?php foreach ($content_by_category as $category => $contents): ?>
            <div class="category-section">
                <h2 class="category-title">
                    <i class="fas fa-folder me-2"></i>
                    <?php echo ucwords(str_replace('_', ' ', $category)); ?>
                </h2>
                
                <?php foreach ($contents as $content): ?>
                <div class="content-card" onclick="openContent(<?php echo $content['id']; ?>)">
                    <div class="content-header">
                        <div class="content-icon" style="background: <?php 
                            switch($content['content_type']) {
                                case 'video': echo 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)'; break;
                                case 'audio': echo 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)'; break;
                                case 'quiz': echo 'linear-gradient(135deg, #10b981 0%, #059669 100%)'; break;
                                case 'worksheet': echo 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)'; break;
                                default: echo 'linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%)';
                            }
                        ?>;">
                            <i class="fas <?php 
                                switch($content['content_type']) {
                                    case 'video': echo 'fa-video'; break;
                                    case 'audio': echo 'fa-headphones'; break;
                                    case 'quiz': echo 'fa-question-circle'; break;
                                    case 'worksheet': echo 'fa-file-alt'; break;
                                    case 'infographic': echo 'fa-chart-bar'; break;
                                    default: echo 'fa-file-alt';
                                }
                            ?>"></i>
                        </div>
                        <div style="flex: 1;">
                            <h3 class="content-title"><?php echo htmlspecialchars($content['title']); ?></h3>
                            <div class="content-meta">
                                <span class="badge" style="background: #e5e7eb; color: #4b5563;">
                                    <?php echo ucfirst($content['content_type']); ?>
                                </span>
                                <?php if ($content['duration_minutes'] > 0): ?>
                                <span style="margin-left: 10px;">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php echo $content['duration_minutes']; ?> min
                                </span>
                                <?php endif; ?>
                                <?php if ($content['views'] > 0): ?>
                                <span style="margin-left: 10px;">
                                    <i class="fas fa-eye me-1"></i>
                                    <?php echo $content['views']; ?> views
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <p class="content-description"><?php echo htmlspecialchars($content['description']); ?></p>
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
        function openContent(contentId) {
            window.location.href = 'education-detail.php?id=' + contentId;
        }
    </script>
</body>
</html>