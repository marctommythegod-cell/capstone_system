<?php
// admin/students.php - Manage Students

require_once '../includes/session_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

if ($_SESSION['user_role'] !== 'admin') {
    redirect('/SYSTEM/index.php');
}

$admin_name = getUserName($pdo, $_SESSION['user_id']);

// Handle student registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $student_id = trim($_POST['student_id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $course = trim($_POST['course'] ?? '');
        $year = intval($_POST['year'] ?? 0);
        
        // Validation checks
        $errors = [];
        
        // Check for empty fields
        if (empty($student_id)) {
            $errors[] = 'Student ID is required.';
        } elseif (strlen($student_id) !== 8) {
            $errors[] = 'Student ID must be exactly 8 digits.';
        } elseif (!preg_match('/^[0-9]{8}$/', $student_id)) {
            $errors[] = 'Student ID can only contain numbers (0-9).';
        }
        
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
        } elseif (strlen($email) > 100) {
            $errors[] = 'Email address must not exceed 100 characters.';
        }
        
        if (empty($course)) {
            $errors[] = 'Course is required.';
        } elseif (strlen($course) > 100) {
            $errors[] = 'Course must not exceed 100 characters.';
        }
        
        if ($year === 0) {
            $errors[] = 'Year level is required.';
        } elseif ($year < 1 || $year > 4) {
            $errors[] = 'Year level must be between 1 and 4.';
        }
        
        // Check for duplicate student ID
        if (empty($errors)) {
            $stmt = $pdo->prepare('SELECT id FROM students WHERE student_id = ?');
            $stmt->execute([$student_id]);
            if ($stmt->fetch()) {
                $errors[] = 'This student ID is already registered. Please use a different ID.';
            }
        }
        
        // Check for duplicate email
        if (empty($errors)) {
            $stmt = $pdo->prepare('SELECT id FROM students WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'This email is already registered. Please use a different email.';
            }
        }
        
        if (!empty($errors)) {
            setMessage('error', implode('<br>', $errors));
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO students (student_id, name, email, course, year) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$student_id, $name, $email, $course, $year]);
                setMessage('success', 'Student added successfully.');
            } catch (Exception $e) {
                setMessage('error', 'Error adding student: ' . $e->getMessage());
            }
        }
        redirect('/SYSTEM/admin/students.php');
    } elseif ($_POST['action'] === 'delete') {
        $student_id = intval($_POST['student_id'] ?? 0);
        
        if (!$student_id) {
            setMessage('error', 'Invalid student ID.');
        } else {
            try {
                $stmt = $pdo->prepare('DELETE FROM students WHERE id = ?');
                $stmt->execute([$student_id]);
                setMessage('success', 'Student deleted successfully.');
            } catch (Exception $e) {
                setMessage('error', 'Error deleting student: ' . $e->getMessage());
            }
        }
        redirect('/SYSTEM/admin/students.php');
    }
}

// Fetch all students
$stmt = $pdo->prepare('SELECT * FROM students ORDER BY name');
$stmt->execute();
$students = $stmt->fetchAll();

$message = getMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - PhilCST</title>
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
                <a href="/SYSTEM/admin/students.php" class="nav-item active">
                    <span>Manage Students</span>
                </a>
                <a href="/SYSTEM/admin/teachers.php" class="nav-item">
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
                <h1>Manage Students</h1>
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
                
                <!-- Add Student Form -->
                <section class="section">
                    <h2>Register New Student</h2>
                    <form method="POST" class="student-form">
                        <input type="hidden" name="action" value="add">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="student_id">Student ID</label>
                                <input type="text" id="student_id" name="student_id" required placeholder="ex: 000*****" maxlength="8" inputmode="numeric">
                            </div>
                            
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" required placeholder="Enter full name">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" placeholder="example@gmail.com" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="course">Course</label>
                                <select id="course" name="course" required>
                                    <option value="">-- Select Course --</option>
                                    <optgroup label="College of Engineering and Architecture">
                                        <option value="BS in Civil Engineering (BSCE)">BS in Civil Engineering (BSCE)</option>
                                        <option value="BS in Electrical Engineering (BSEE)">BS in Electrical Engineering (BSEE)</option>
                                        <option value="BS in Mechanical Engineering (BSME)">BS in Mechanical Engineering (BSME)</option>
                                    </optgroup>
                                    <optgroup label="College of Criminology">
                                        <option value="BS in Criminology (BSCrim)">BS in Criminology (BSCrim)</option>
                                    </optgroup>
                                    <optgroup label="College of Information Technology">
                                        <option value="BS in Information Technology (BSIT)">BS in Information Technology (BSIT)</option>
                                        <option value="BS in Computer Science (BSCS)">BS in Computer Science (BSCS)</option>
                                    </optgroup>
                                    <optgroup label="College of Education">
                                        <option value="Bachelor of Elementary Education (BEEd)">Bachelor of Elementary Education (BEEd)</option>
                                        <option value="Bachelor of Secondary Education (BSEd)">Bachelor of Secondary Education (BSEd)</option>
                                    </optgroup>
                                    <optgroup label="College of Business and Management">
                                        <option value="BS in Business Administration (BSBA)">BS in Business Administration (BSBA)</option>
                                        <option value="BS in Hospitality Management (BSHM)">BS in Hospitality Management (BSHM)</option>
                                    </optgroup>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="year">Year Level</label>
                                <select id="year" name="year" required>
                                    <option value="">-- Select Year --</option>
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary">Add Student</button>
                            </div>
                        </div>
                    </form>
                </section>
                
                <!-- Students List -->
                <section class="section">
                    <h2>All Students (<?php echo count($students); ?>)</h2>
                    <?php if (count($students) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th></th>Course</th>
                                        <th>Year</th>
                                        <th>Registered</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($student['student_id'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($student['name'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($student['email'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($student['course'] ?? ''); ?></td>
                                            <td><?php echo $student['year'] ?? ''; ?></td>
                                            <td><?php echo formatDate($student['created_at'] ?? ''); ?></td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="no-data">No students registered yet.</p>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>
</body>
</html>