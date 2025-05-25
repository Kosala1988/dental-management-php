<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard/" . $_SESSION['role'] . "/");
    exit();
}

require_once 'includes/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];

            header("Location: dashboard/" . $user['role'] . "/");
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | The Family Dentist</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login-style.css">
    <!-- Add favicon -->
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="logo text-center mb-4">
                <img src="assets/images/logo.png" alt="The Family Dentist Logo">
                <h1 class="mt-3">The Family Dentist</h1>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                    </svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" id="loginForm">
                <div class="form-floating">
                    <input type="text" class="form-control" id="username" name="username" placeholder=" " required autocomplete="username" aria-label="Username">
                    <label for="username">Username</label>
                </div>

                <div class="form-floating">
                    <input type="password" class="form-control" id="password" name="password" placeholder=" " required autocomplete="current-password" aria-label="Password">
                    <label for="password">Password</label>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">Sign In</button>
                </div>

                <div class="text-center mt-3">
                    <a href="reset-password.php" class="text-muted small">Forgot password?</a>
                </div>
            </form>
        </div>
        <div class="login-footer">
            <p>© <?= date('Y') ?> The Family Dentist. All rights reserved.</p>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Autofocus the username field
        document.getElementById('username').focus();
        
        // Add form validation
        const form = document.getElementById('loginForm');
        form.addEventListener('submit', function(event) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!username || !password) {
                event.preventDefault();
                const error = document.querySelector('.alert-danger') || 
                    document.createElement('div');
                
                if (!document.querySelector('.alert-danger')) {
                    error.className = 'alert alert-danger';
                    error.setAttribute('role', 'alert');
                    form.parentNode.insertBefore(error, form);
                }
                
                error.innerHTML = 'Please enter both username and password.';
            }
        });
    });
    </script>
</body>
</html>