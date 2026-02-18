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
            $errors[] = 'Email address must not exceed 100 characters.';;
        }
        
        // Confirm password match
        if (empty($confirm_password)) {
            $errors[] = 'Password confirmation is required.';
        } elseif ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match. Please ensure both password fields are identical.';
        }
        
        // Strong password validation
        if (empty($password)) {
            $errors[] = 'Password is required.';
        } else {
            $password_length = strlen($password);
            $has_uppercase = preg_match('/[A-Z]/', $password);
            $has_lowercase = preg_match('/[a-z]/', $password);
            $has_numbers = preg_match('/[0-9]/', $password);
            $has_symbols = preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'"<>,.?/\\|`~]/', $password);
            
            if ($password_length < 12) {
                $errors[] = 'Password must be at least 12 characters long.';
            } elseif ($password_length > 255) {
                $errors[] = 'Password must not exceed 255 characters.';
            }
            
            if (!$has_uppercase) {
                $errors[] = 'Password must contain at least one uppercase letter (A-Z).';
            }
            if (!$has_lowercase) {
                $errors[] = 'Password must contain at least one lowercase letter (a-z).';
            }
            if (!$has_numbers) {
                $errors[] = 'Password must contain at least one number (0-9).';
            }
            if (!$has_symbols) {
                $errors[] = 'Password must contain at least one symbol (!@#$%^&* etc).';
            }
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
                        <?php echo htmlspecialchars($message['text']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Add Teacher Form -->
                <section class="section">
                    <h2>Register New Teacher</h2>
                    <form method="POST" class="teacher-form">
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
                                <input type="password" id="password" name="password" required placeholder="Put a strong password here">
                                <small style="display: block; margin-top: 5px; color: #666;">
                                    <strong>Password Requirements:</strong><br>
                                    • Minimum 12 characters<br>
                                    • At least one UPPERCASE letter<br>
                                    • At least one lowercase letter<br>
                                    • At least one number (0-9)<br>
                                    • At least one symbol (!@#$%^&* etc)
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Re-enter your password">
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
</body>
</html>
