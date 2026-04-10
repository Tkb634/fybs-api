<?php
// RBAC protection
require_once "auth.php";

// Only admins can access
requireRole('admin');

// Database connection
require_once "config.php";

// Initialize message variable
$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Collect and sanitize form inputs
    $title = trim($_POST['title']);
    $preacher = trim($_POST['preacher']);
    $description = trim($_POST['description']);
    $audio_url = trim($_POST['audio_url']);

    // Basic validation
    if (empty($title) || empty($preacher)) {
        $message = "Title and Preacher are required.";
    } else {
        // Prepare SQL to prevent SQL injection
        $stmt = $conn->prepare(
            "INSERT INTO sermons (title, preacher, description, audio_url)
             VALUES (?, ?, ?, ?)"
        );

        // Bind parameters
        $stmt->bind_param("ssss", $title, $preacher, $description, $audio_url);

        // Execute query
        if ($stmt->execute()) {
            $message = "Sermon posted successfully.";
        } else {
            $message = "Error posting sermon.";
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
    <title>Post Sermon - FYBS Admin</title>

    <!-- Mobile responsiveness -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">

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
            border: none;
            border-radius: 18px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }

        .btn-gradient {
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
        }
    </style>
</head>

<body>

<!-- HEADER -->
<div class="admin-header">
    <h5 class="mb-0">Post New Sermon</h5>
    <small>Admin Panel</small>
</div>

<!-- FORM -->
<div class="container py-4">

    <div class="card form-card p-4">

        <!-- Show success or error message -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-info">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <!-- Sermon Title -->
            <div class="mb-3">
                <label class="form-label">Sermon Title</label>
                <input type="text" name="title" class="form-control" required>
            </div>

            <!-- Preacher -->
            <div class="mb-3">
                <label class="form-label">Preacher</label>
                <input type="text" name="preacher" class="form-control" required>
            </div>

            <!-- Description -->
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4"></textarea>
            </div>

            <!-- Audio URL -->
            <div class="mb-3">
                <label class="form-label">Audio URL (optional)</label>
                <input type="url" name="audio_url" class="form-control">
            </div>

            <!-- Submit -->
            <button type="submit" class="btn btn-gradient w-100">
                <i class="fas fa-upload"></i> Publish Sermon
            </button>

        </form>

    </div>

    <!-- Back button -->
    <div class="text-center mt-3">
        <a href="admin_dashboard.php" class="text-decoration-none">
            ← Back to Dashboard
        </a>
    </div>

</div>

</body>
</html>
