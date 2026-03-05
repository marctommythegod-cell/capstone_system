<?php
// index.php - Login Page

session_start();

require_once 'config/db.php';
require_once 'includes/functions.php';

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: /CLASS_CARD_DROPPING_SYSTEM/admin/dashboard.php');
    } else {
        header('Location: /CLASS_CARD_DROPPING_SYSTEM/teacher/dashboard.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {
        $stmt = $pdo->prepare('SELECT id, email, password, role FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && verifyPassword($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header('Location: /CLASS_CARD_DROPPING_SYSTEM/admin/dashboard.php');
            } else {
                header('Location: /CLASS_CARD_DROPPING_SYSTEM/teacher/dashboard.php');
            }
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PhilCST - Class Card Dropping System</title>
    <link rel="stylesheet" href="/CLASS_CARD_DROPPING_SYSTEM/css/style.css?v=<?php echo time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="login-body">
    <div class="login-container">
        <!-- Left Panel - Image / Branding -->
        <div class="login-left-panel">
            <div class="login-left-overlay"></div>
            <div class="login-left-content">
                <div class="login-brand-top">
                    <h2 class="login-brand-logo">PhilCST</h2>
                </div>
                <div class="login-brand-center">
                    <img src="/CLASS_CARD_DROPPING_SYSTEM/images/philcst-bg.png" alt="PhilCST Logo" class="login-logo">
                </div>
                <div class="login-brand-bottom">
                    <h2>Class Card Dropping System</h2>
                    <p>Streamlined academic management for teachers and administrators.</p>
                </div>
            </div>
        </div>

        <!-- Right Panel - Login Form -->
        <div class="login-right-panel">
            <div class="login-form-wrapper">
                <div class="login-header">
                    <h1>Welcome back</h1>
                    <p>Sign in to your account to continue</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" class="login-form">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                <svg class="eye-icon eye-show" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                <svg class="eye-icon eye-hide" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                </form>

                <div class="login-footer">
                    <p>&copy; <?php echo date('Y'); ?> Philippine College of Science and Technology</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const toggleBtn = passwordField.closest('.password-input-wrapper').querySelector('.password-toggle');
            const eyeShow = toggleBtn.querySelector('.eye-show');
            const eyeHide = toggleBtn.querySelector('.eye-hide');
            const isPassword = passwordField.type === 'password';
            passwordField.type = isPassword ? 'text' : 'password';
            eyeShow.style.display = isPassword ? 'none' : 'block';
            eyeHide.style.display = isPassword ? 'block' : 'none';
            toggleBtn.classList.toggle('active', isPassword);
        }
    </script>
</body>

</html>