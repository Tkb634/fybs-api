<?php
// Include database connection
include "config.php";

// Check if form was submitted
if (isset($_POST['register'])) {

    // Get form inputs safely
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // Hash the password for security
   $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert user into database
    $sql = "INSERT INTO users (full_name, email, password)
            VALUES ('$full_name', '$email', '$hashed_password')";

    if (mysqli_query($conn, $sql)) {
        // Redirect to login page after successful registration
        header("Location: login.php");
        exit();
    } else {
        $error = "Email already exists!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap for styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <h3 class="text-center mb-4">Create Account</h3>

    <!-- Registration form -->
    <form method="POST" class="card p-4 shadow-sm">

        <!-- Show error if exists -->
        <?php if (isset($error)) { ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php } ?>

        <input type="text" name="full_name" class="form-control mb-3"
               placeholder="Full Name" required>

        <input type="email" name="email" class="form-control mb-3"
               placeholder="Email Address" required>

        <input type="password" name="password" class="form-control mb-3"
               placeholder="Password" required>

        <button type="submit" name="register" class="btn btn-primary w-100">
            Register
        </button>

        <p class="text-center mt-3">
            Already have an account? <a href="login.php">Login</a>
        </p>
    </form>
</div>

</body>
</html>
