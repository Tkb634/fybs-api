<?php
// Protect page using RBAC
require_once "auth.php";

// Only admins allowed
requireRole('admin');

// Database connection
require_once "config.php";

// Message holder
$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get and sanitize inputs
    $title = trim($_POST['title']);
    $scripture = trim($_POST['scripture']);
    $content = trim($_POST['content']);
    $devotion_time = $_POST['devotion_time'];

    // Validate required fields
    if (empty($title) || empty($scripture) || empty($content)) {
        $message = "All fields are required.";
    } else {
        // Prepare SQL insert
        $stmt = $conn->prepare(
            "INSERT INTO devotionals (title, scripture, content, devotion_time)
             VALUES (?, ?, ?, ?)"
        );

        // Bind parameters
        $stmt->bind_param("ssss", $title, $scripture, $content, $devotion_time);

        // Execute query
        if ($stmt->execute()) {
            $message = "Devotional published successfully.";
        } else {
            $message = "Error publishing devotional.";
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
    <title>Post Devotional - FYBS Admin</title>

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
    <h5 class="mb-0">Post Daily Devotional</h5>
    <small>AM / PM Devotions</small>
</div>

<!-- MAIN FORM -->
<div class="container py-4">

    <div class="card form-card p-4">

        <!-- Feedback message -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-info">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <!-- Devotional Title -->
            <div class="mb-3">
                <label class="form-label">Devotional Title</label>
                <input type="text" name="title" class="form-control" required>
            </div>

            <!-- Scripture -->
            <div class="mb-3">
                <label class="form-label">Scripture Reference</label>
                <input type="text" name="scripture" class="form-control" placeholder="e.g. Psalm 23:1" required>
            </div>

            <!-- Devotion Time -->
            <div class="mb-3">
                <label class="form-label">Devotion Time</label>
                <select name="devotion_time" class="form-select" required>
                    <option value="AM">Morning (AM)</option>
                    <option value="PM">Evening (PM)</option>
                </select>
            </div>

            <!-- Content -->
            <div class="mb-3">
                <label class="form-label">Devotional Content</label>
                <textarea name="content" class="form-control" rows="6" required></textarea>
            </div>

            <!-- Submit -->
            <button type="submit" class="btn btn-gradient w-100">
                <i class="fas fa-book-open"></i> Publish Devotional
            </button>

        </form>
    </div>

    <!-- Back -->
    <div class="text-center mt-3">
        <a href="admin_dashboard.php" class="text-decoration-none">
            ← Back to Dashboard
        </a>
    </div>

</div>

</body>
</html>
