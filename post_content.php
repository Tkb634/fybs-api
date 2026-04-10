<?php
require_once "auth.php";
requireRole('admin');
require_once "config.php";

// Determine content type safely
$type = $_GET['type'] ?? '';

$allowedTypes = ['sermon','devotional_am','devotional_pm','video'];
if (!in_array($type, $allowedTypes)) {
    die("Invalid content type");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Collect form data
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $videoUrl = !empty($_POST['video_url']) ? trim($_POST['video_url']) : null;
    $adminId = $_SESSION['user_id'];

    // Insert into database
    $stmt = $conn->prepare(
        "INSERT INTO spiritual_content 
         (type, title, content, video_url, created_by)
         VALUES (?, ?, ?, ?, ?)"
    );

    $stmt->bind_param("ssssi", $type, $title, $content, $videoUrl, $adminId);
    $stmt->execute();

    // Log admin action
    $log = $conn->prepare(
        "INSERT INTO admin_logs (admin_id, action, details)
         VALUES (?, 'CREATE_CONTENT', ?)"
    );
    $details = "Created $type: $title";
    $log->bind_param("is", $adminId, $details);
    $log->execute();

    $success = true;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Post Content</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">

    <h5 class="mb-3">Post <?php echo strtoupper(str_replace('_',' ', $type)); ?></h5>

    <?php if (isset($success)): ?>
        <div class="alert alert-success">Content posted successfully</div>
    <?php endif; ?>

    <form method="POST">

        <!-- Title -->
        <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" required>
        </div>

        <!-- Content -->
        <div class="mb-3">
            <label class="form-label">Content</label>
            <textarea name="content" class="form-control" rows="5" required></textarea>
        </div>

        <!-- Video URL -->
        <?php if ($type === 'video'): ?>
        <div class="mb-3">
            <label class="form-label">YouTube URL</label>
            <input type="url" name="video_url" class="form-control">
        </div>
        <?php endif; ?>

        <button class="btn btn-primary w-100">Publish</button>
    </form>

</div>

</body>
</html>
