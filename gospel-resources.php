<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get all resources by type
$query = "SELECT * FROM gospel_resources ORDER BY resource_type, created_at DESC";
$result = $conn->query($query);

$resources_by_type = [];
while ($row = $result->fetch_assoc()) {
    $type = $row['resource_type'];
    if (!isset($resources_by_type[$type])) {
        $resources_by_type[$type] = [];
    }
    $resources_by_type[$type][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gospel Resources - FYBS Youth App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #059669;
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
        
        .resource-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            transition: transform 0.3s ease;
            cursor: pointer;
        }
        
        .resource-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        .resource-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .resource-icon {
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
        
        .resource-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #1f2937;
        }
        
        .resource-meta {
            font-size: 14px;
            color: #6b7280;
        }
        
        .resource-description {
            color: #4b5563;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .resource-stats {
            display: flex;
            gap: 15px;
            font-size: 12px;
            color: #6b7280;
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
            <i class="fas fa-tools me-2"></i>Gospel Resources
        </h1>
        <p style="color: #6b7280;">Tools and guides for effective ministry</p>
    </div>
    
    <div class="container mt-4">
        <?php if (empty($resources_by_type)): ?>
            <div class="text-center py-5">
                <i class="fas fa-box-open fa-3x mb-3" style="color: #6b7280;"></i>
                <p>No resources available yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($resources_by_type as $type => $resources): ?>
            <div class="category-section">
                <h2 class="category-title">
                    <i class="fas <?php 
                        switch($type) {
                            case 'prayer_guide': echo 'fa-hands-praying'; break;
                            case 'bible_study': echo 'fa-book-bible'; break;
                            case 'evangelism_tool': echo 'fa-bullhorn'; break;
                            default: echo 'fa-book-open';
                        }
                    ?> me-2"></i>
                    <?php echo ucwords(str_replace('_', ' ', $type)); ?>
                </h2>
                
                <?php foreach ($resources as $resource): ?>
                <div class="resource-card" onclick="openResource(<?php echo $resource['id']; ?>)">
                    <div class="resource-header">
                        <div class="resource-icon" style="background: <?php 
                            switch($type) {
                                case 'prayer_guide': echo 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)'; break;
                                case 'bible_study': echo 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)'; break;
                                case 'evangelism_tool': echo 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)'; break;
                                default: echo 'linear-gradient(135deg, #059669 0%, #047857 100%)';
                            }
                        ?>;">
                            <i class="fas <?php 
                                switch($type) {
                                    case 'prayer_guide': echo 'fa-hands-praying'; break;
                                    case 'bible_study': echo 'fa-book-bible'; break;
                                    case 'evangelism_tool': echo 'fa-bullhorn'; break;
                                    default: echo 'fa-book-open';
                                }
                            ?>"></i>
                        </div>
                        <div style="flex: 1;">
                            <h3 class="resource-title"><?php echo htmlspecialchars($resource['title']); ?></h3>
                            <div class="resource-meta">
                                <?php if ($resource['author']): ?>
                                <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($resource['author']); ?>
                                <?php endif; ?>
                                <?php if ($resource['duration_minutes'] > 0): ?>
                                • <i class="fas fa-clock me-1"></i> <?php echo $resource['duration_minutes']; ?> min
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <p class="resource-description"><?php echo htmlspecialchars($resource['description']); ?></p>
                    
                    <?php if ($resource['scripture_reference']): ?>
                    <div style="font-size: 14px; color: #059669; font-weight: 500; margin-bottom: 10px;">
                        <i class="fas fa-book-bible me-1"></i> <?php echo htmlspecialchars($resource['scripture_reference']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="resource-stats">
                        <?php if ($resource['views'] > 0): ?>
                        <span><i class="fas fa-eye me-1"></i> <?php echo $resource['views']; ?> views</span>
                        <?php endif; ?>
                        <?php if ($resource['downloads'] > 0): ?>
                        <span><i class="fas fa-download me-1"></i> <?php echo $resource['downloads']; ?> downloads</span>
                        <?php endif; ?>
                        <?php if ($resource['is_featured']): ?>
                        <span><i class="fas fa-star me-1"></i> Featured</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <a href="gospel.php" class="back-btn">
        <i class="fas fa-arrow-left me-2"></i> Back to Gospel
    </a>

    <script>
        function openResource(resourceId) {
            window.location.href = 'gospel-resource-detail.php?id=' + resourceId;
        }
    </script>
</body>
</html>