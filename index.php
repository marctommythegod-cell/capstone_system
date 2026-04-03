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
        $stmt = $pdo->prepare('SELECT id, email, password, role, status FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && verifyPassword($password, $user['password'])) {
            // Check if user is inactive
            if (isset($user['status']) && $user['status'] === 'inactive') {
                $error = 'Your account is currently inactive. Please contact the administrator.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];

                if ($user['role'] === 'admin') {
                    header('Location: /CLASS_CARD_DROPPING_SYSTEM/admin/dashboard.php');
                } else {
                    header('Location: /CLASS_CARD_DROPPING_SYSTEM/teacher/dashboard.php');
                }
                exit;
            }
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="login-page">
    <!-- Animated Background -->
    <div class="login-background">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>

    <div class="login-wrapper">
        <div class="login-card">
            <!-- Left Side - Branding -->
            <div class="card-left">
                <div class="left-brand">
                    <div class="brand-logo">
                        <img src="/CLASS_CARD_DROPPING_SYSTEM/images/philcst-bg.png" alt="PhilCST Logo" class="logo-image">
                    </div>
                    <h1 class="brand-title">PhilCST</h1>
                    <p class="brand-subtitle">Class Card Dropping System</p>
                </div>
            </div>

            <!-- Right Side - Login Form -->
            <div class="card-right">
                <div class="form-header">
                    <h2>Welcome</h2>
                    <p>Sign in to access your account</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <svg class="alert-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="modern-form">
                    <!-- Email Field -->
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-input" 
                                placeholder="example@gmail.com" 
                                required
                                aria-label="Email Address"
                            >
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="form-group">
                        <div class="label-row">
                            <label for="password" class="form-label">Password</label>
                            <a href="#forgot-password" class="forgot-link">Forgot Password?</a>
                        </div>
                        <div class="input-wrapper">
                            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-input" 
                                placeholder="••••••••" 
                                required
                                aria-label="Password"
                            >
                            <button type="button" class="toggle-password" onclick="togglePassword('password')" aria-label="Toggle password visibility">
                                <svg class="eye-icon eye-show" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                <svg class="eye-icon eye-hide" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me Checkbox -->
                    <div class="checkbox-group">
                        <input type="checkbox" id="remember" name="remember" class="checkbox-input">
                        <label for="remember" class="checkbox-label">Keep me logged in</label>
                    </div>

                    <!-- Sign In Button -->
                    <button type="submit" class="btn-signin">
                        <span>Sign In</span>
                        <svg class="btn-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    </button>
                </form>

                <!-- Footer -->
                <div class="form-footer">
                    <p>&copy; <?php echo date('Y'); ?> Philippine College of Science and Technology. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const toggleBtn = passwordField.closest('.input-wrapper').querySelector('.toggle-password');
            const eyeShow = toggleBtn.querySelector('.eye-show');
            const eyeHide = toggleBtn.querySelector('.eye-hide');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeShow.style.display = 'none';
                eyeHide.style.display = 'block';
            } else {
                passwordField.type = 'password';
                eyeShow.style.display = 'block';
                eyeHide.style.display = 'none';
            }
        }

        // Add focus effect to inputs
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.closest('.input-wrapper').classList.add('focused');
            });
            input.addEventListener('blur', function() {
                this.closest('.input-wrapper').classList.remove('focused');
            });
        });
    </script>
</body>

</html>