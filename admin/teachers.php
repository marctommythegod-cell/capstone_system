<?php
// admin/teachers.php - Manage Teachers

require_once '../includes/session_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

if ($_SESSION['user_role'] !== 'admin') {
    redirect('/SYSTEM/index.php');
}

$admin_name = getUserName($pdo, $_SESSION['user_id']);

// Handle teacher registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');
        
        // Validation checks
        $errors = [];
        
        // Check for empty fields
        if (empty($name)) {
            $errors[] = 'Full name is required.';
        } elseif (strlen($name) < 2) {
            $errors[] = 'Full name must be at least 2 characters.';
        } elseif (strlen($name) > 100) {
            $errors[] = 'Full name must not exceed 100 characters.';
        }
        
        if (empty($email)) {
            $errors[] = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } elseif (!preg_match('/@gmail\.com$/', $email)) {
            $errors[] = 'Email must be a Gmail account (@gmail.com only).';
        } elseif (strlen($email) > 100) {
            $errors[] = 'Email address must not exceed 100 characters.';
        }

        // Password validation (runs before confirm check)
        if (empty($password)) {
            $errors[] = 'Password is required.';
        } else {
            if (strlen($password) < 6) {
                $errors[] = 'Password must be at least 6 characters long.';
            }
            if (strlen($password) > 255) {
                $errors[] = 'Password must not exceed 255 characters.';
            }
            if (!preg_match('/[A-Z]/', $password)) {
                $errors[] = 'Password must contain at least one uppercase letter (A–Z).';
            }
            if (!preg_match('/[a-z]/', $password)) {
                $errors[] = 'Password must contain at least one lowercase letter (a–z).';
            }
            if (!preg_match('/[0-9]/', $password)) {
                $errors[] = 'Password must contain at least one number (0–9).';
            }
            if (!preg_match('/[!@#$%]/', $password)) {
                $errors[] = 'Password must contain at least one special character (!, @, #, $, %).';
            }
        }

        // Confirm password match
        if (empty($confirm_password)) {
            $errors[] = 'Password confirmation is required.';
        } elseif ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match. Please ensure both password fields are identical.';
        }
        
        // Check for duplicate email
        if (empty($errors)) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'This email is already registered. Please use a different email.';
            }
        }
        
        if (!empty($errors)) {
            setMessage('error', implode('<br>', $errors));
        } else {
            try {
                $hashed_password = securePassword($password);
                $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
                $stmt->execute([$name, $email, $hashed_password, 'teacher']);
                setMessage('success', 'Teacher added successfully.');
            } catch (Exception $e) {
                setMessage('error', 'Error adding teacher: ' . $e->getMessage());
            }
        }
        redirect('/SYSTEM/admin/teachers.php');
    } elseif ($_POST['action'] === 'delete') {
        $teacher_id = intval($_POST['teacher_id'] ?? 0);
        
        if (!$teacher_id) {
            setMessage('error', 'Invalid teacher ID.');
        } else {
            try {
                $stmt = $pdo->prepare('DELETE FROM users WHERE id = ? AND role = "teacher"');
                $stmt->execute([$teacher_id]);
                setMessage('success', 'Teacher deleted successfully.');
            } catch (Exception $e) {
                setMessage('error', 'Error deleting teacher: ' . $e->getMessage());
            }
        }
        redirect('/SYSTEM/admin/teachers.php');
    }
}

// Fetch all teachers
$stmt = $pdo->prepare('SELECT id, name, email, created_at FROM users WHERE role = "teacher" ORDER BY name');
$stmt->execute();
$teachers = $stmt->fetchAll();

$message = getMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers - PhilCST</title>
    <link rel="stylesheet" href="/SYSTEM/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>PhilCST</h2>
                <p>Admin Portal</p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="/SYSTEM/admin/dashboard.php" class="nav-item">
                    <span>Dashboard</span>
                </a>
                <a href="/SYSTEM/admin/dropped_cards.php" class="nav-item">
                    <span>Dropped Cards</span>
                </a>
                <a href="/SYSTEM/admin/students.php" class="nav-item">
                    <span>Manage Students</span>
                </a>
                <a href="/SYSTEM/admin/teachers.php" class="nav-item active">
                    <span>Manage Teachers</span>
                </a>
                <a href="/SYSTEM/admin/drop_history.php" class="nav-item">
                    <span>Drop History</span>
                </a>
                <a href="/SYSTEM/includes/logout.php" class="nav-item">
                    <span>Logout</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <p>Welcome, <strong><?php echo htmlspecialchars($admin_name); ?></strong></p>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="top-bar">
                <h1>Manage Teachers</h1>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($admin_name); ?> (Administrator)</span>
                </div>
            </header>
            
            <div class="content-wrapper">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message['type']; ?>">
                        <?php echo $message['text']; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Add Teacher Form -->
                <section class="section">
                    <h2>Register New Teacher</h2>
                    <form method="POST" class="teacher-form" id="teacherForm">
                        <input type="hidden" name="action" value="add">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" required placeholder="Enter Your Full Name">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" required placeholder="example@gmail.com">
                            </div>
                            
                            <div class="form-group">
                                <label for="password">Password</label>
                                <div class="password-input-wrapper">
                                    <input type="password" id="password" name="password" required minlength="6" placeholder="Put a strong password here" oninput="checkPasswordStrength(this.value)">
                                    <button type="button" class="password-toggle" onclick="togglePassword('password')">👁️</button>
                                </div>
                                <small style="display: block; margin-top: 5px; color: #666;">
                                    <strong>Password Requirements:</strong><br>
                                    • At least 6–8 characters long<br>
                                    • At least one uppercase letter (A–Z)<br>
                                    • At least one lowercase letter (a–z)<br>
                                    • At least one number (0–9)<br>
                                    • At least one special character (!, @, #, $, %)
                                </small>
                                <!-- Live strength indicator -->
                                <div id="password-strength" style="margin-top: 6px; font-size: 0.85em;"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm Password</label>
                                <div class="password-input-wrapper">
                                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Re-enter your password">
                                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">👁️</button>
                                </div>
                                <div id="confirm-match" style="margin-top: 5px; font-size: 0.85em;"></div>
                            </div>
                            
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary">Add Teacher</button>
                            </div>
                        </div>
                    </form>
                </section>
                
                <!-- Teachers List -->
                <section class="section">
                    <h2>All Teachers (<?php echo count($teachers); ?>)</h2>
                    <?php if (count($teachers) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Registered</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($teacher['name']); ?></td>
                                            <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                            <td><?php echo formatDate($teacher['created_at']); ?></td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="teacher_id" value="<?php echo $teacher['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="no-data">No teachers registered yet.</p>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>

    <script>
        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const toggleButton = passwordField.nextElementSibling;
            const isPassword = passwordField.type === 'password';
            passwordField.type = isPassword ? 'text' : 'password';
            toggleButton.textContent = isPassword ? '👁️‍🗨️' : '👁️';
            toggleButton.classList.toggle('active', isPassword);
        }

        function checkPasswordStrength(value) {
            const strengthEl = document.getElementById('password-strength');
            const rules = [
                { regex: /.{6,}/, label: 'At least 6 characters' },
                { regex: /[A-Z]/, label: 'Uppercase letter' },
                { regex: /[a-z]/, label: 'Lowercase letter' },
                { regex: /[0-9]/, label: 'Number' },
                { regex: /[!@#$%]/, label: 'Special character (!, @, #, $, %)' },
            ];

            if (value.length === 0) {
                strengthEl.innerHTML = '';
                return;
            }

            const passed = rules.filter(r => r.regex.test(value)).length;
            const colors = ['#e74c3c', '#e67e22', '#f1c40f', '#2ecc71', '#27ae60'];
            const labels = ['Very Weak', 'Weak', 'Fair', 'Strong', 'Very Strong'];

            strengthEl.innerHTML = `
                <div style="display:flex; gap:4px; margin-bottom:4px;">
                    ${rules.map((_, i) => `<div style="flex:1; height:5px; border-radius:3px; background:${i < passed ? colors[passed - 1] : '#ddd'};"></div>`).join('')}
                </div>
                <span style="color:${colors[passed - 1]}; font-weight:600;">${labels[passed - 1]}</span>
            `;

            // Also trigger confirm match check
            checkConfirmMatch();
        }

        function checkConfirmMatch() {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            const matchEl = document.getElementById('confirm-match');

            if (confirm.length === 0) {
                matchEl.innerHTML = '';
                return;
            }

            if (password === confirm) {
                matchEl.innerHTML = '<span style="color:#27ae60;">✔ Passwords match</span>';
            } else {
                matchEl.innerHTML = '<span style="color:#e74c3c;">✘ Passwords do not match</span>';
            }
        }

        document.getElementById('confirm_password').addEventListener('input', checkConfirmMatch);
    </script>
</body>
</html>