<?php
include('../includes/db.php');  // Adjusted path
session_start();

if (isset($_POST['register'])) {
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password before saving
    
    // Secret admin creation with a special keyword
    $role = (isset($_POST['secret_admin']) && $_POST['secret_admin'] === 'AdminCreate2024!') ? 'admin' : 'user';

    // Check if the email already exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $error_message = "Email is already registered!";
    } else {
        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
        $stmt->execute([$email, $password, $role]);

        // After registration, log the user in and redirect to the main page
        $_SESSION['user_id'] = $conn->lastInsertId(); // Store the user ID in session
        
        // If admin, set admin session
        if ($role === 'admin') {
            $_SESSION['admin_id'] = $_SESSION['user_id'];
        }
        
        header("Location: ../index.php"); // Redirect to the main page
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="card-title text-center mb-4">Register</h2>
                        <?php
                        if (isset($error_message)) {
                            echo "<div class='alert alert-danger'>$error_message</div>";
                        }
                        ?>
                        <form method="post">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="admin-check" name="secret_admin" value="AdminCreate2024!">
                                <label class="form-check-label" for="admin-check">Create Admin Account</label>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="register" class="btn btn-success">Register</button>
                            </div>
                        </form>
                        <div class="text-center mt-3">
                            <p class="small">Already have an account? <a href="login.php">Login here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
