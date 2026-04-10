<?php
// Protect page using RBAC
require_once "auth.php";

// Only admins can access
requireRole('admin');

// Database connection
require_once "config.php";

// Message holder
$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Collect and sanitize inputs
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $video_url = trim($_POST['video_url']);

    // Validate required fields
    if (empty($title) || empty($video_url)) {
        $message = "Title and Video URL are required.";
    } else {

        // Prepare SQL statement to prevent SQL injection
        $stmt = $conn->prepare(
            "INSERT INTO videos (title, description, video_url)
             VALUES (?, ?, ?)"
        );

        // Bind parameters
        $stmt->bind_param("sss", $title, $description, $video_url);

        // Execute query
        if ($stmt->execute()) {
            $message = "Video added successfully.";
        } else {
            $message = "Failed to add video.";
        }

        // Close statement
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Video - FYBS Admin</title>

    <!-- Mobile responsiveness -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Fonts & Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Poppins:wght@600&display=swap" rel="stylesheet">

    <style>
        :root {
            --gradient: linear-gradient(135deg, #667eea, #764ba2);
        }
        body {
            font-family: 'Inter', sans-serif;
            background: #f9fafb;
        }
        .admin-header {
            background: var(--gradient);
            color: white;
            padding: 18px;
            text-align: center;
            font-family: 'Poppins', sans-serif;
        }
        .form-card {
            border-radius: 18px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }
        .btn-gradient {
            background: var(--gradient);
            color: white;
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
        }
    </style>
</head>

<body>

<div class="admin-header">
    <h5 class="mb-0">Add Video</h5>
    <small>Sermons • Teachings • Encouragement</small>
</div>

<div class="container py-4">
    <div class="card form-card p-4">

        <!-- Feedback message -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-info">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <!-- Video title -->
            <div class="mb-3">
                <label class="form-label">Video Title</label>
                <input type="text" name="title" class="form-control" required>
            </div>

            <!-- Description -->
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4"></textarea>
            </div>

            <!-- Video URL -->
            <div class="mb-3">
                <label class="form-label">Video URL (YouTube / Vimeo)</label>
                <input type="url" name="video_url" class="form-control" required>
            </div>

            <!-- Submit -->
            <button class="btn btn-gradient w-100">
                <i class="fas fa-video"></i> Publish Video
            </button>

        </form>
    </div>

    <div class="text-center mt-3">
        <a href="admin_dashboard.php">← Back to Dashboard</a>
    </div>
</div>

</body>
</html>
