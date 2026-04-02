<?php
// admin/students.php - Manage Students

require_once '../includes/session_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

if ($_SESSION['user_role'] !== 'admin') {
    redirect('/CLASS_CARD_DROPPING_SYSTEM/index.php');
}

$admin_name = getUserName($pdo, $_SESSION['user_id']);
$user_info = getUserInfo($pdo, $_SESSION['user_id']);

// Handle student registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $student_id = trim($_POST['student_id'] ?? '');
        $lastname = trim($_POST['lastname'] ?? '');
        $firstname = trim($_POST['firstname'] ?? '');
        $middlename = trim($_POST['middlename'] ?? '');
        $name = $lastname . ', ' . $firstname . ', ' . $middlename;
        $address = trim($_POST['address'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $guardian_name = trim($_POST['guardian_name'] ?? '');
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

        if (empty($lastname)) {
            $errors[] = 'Last name is required.';
        } elseif (strlen($lastname) < 2) {
            $errors[] = 'Last name must be at least 2 characters.';
        } elseif (strlen($lastname) > 100) {
            $errors[] = 'Last name must not exceed 100 characters.';
        }

        if (empty($firstname)) {
            $errors[] = 'First name is required.';
        } elseif (strlen($firstname) < 2) {
            $errors[] = 'First name must be at least 2 characters.';
        } elseif (strlen($firstname) > 100) {
            $errors[] = 'First name must not exceed 100 characters.';
        }

        if (empty($middlename)) {
            $errors[] = 'Middle name is required.';
        } elseif (strlen($middlename) < 2) {
            $errors[] = 'Middle name must be at least 2 characters.';
        } elseif (strlen($middlename) > 100) {
            $errors[] = 'Middle name must not exceed 100 characters.';
        }

        if (empty($email)) {
            $errors[] = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } elseif (strlen($email) > 100) {
            $errors[] = 'Email address must not exceed 100 characters.';
        }

        if (empty($address)) {
            $errors[] = 'Complete address is required.';
        } elseif (strlen($address) < 5) {
            $errors[] = 'Complete address must be at least 5 characters.';
        } elseif (strlen($address) > 255) {
            $errors[] = 'Complete address must not exceed 255 characters.';
        }

        if (empty($guardian_name)) {
            $errors[] = 'Guardian name is required.';
        } elseif (strlen($guardian_name) < 2) {
            $errors[] = 'Guardian name must be at least 2 characters.';
        } elseif (strlen($guardian_name) > 100) {
            $errors[] = 'Guardian name must not exceed 100 characters.';
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
                $stmt = $pdo->prepare('INSERT INTO students (student_id, name, email, address, guardian_name, course, year) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$student_id, $name, $email, $address, $guardian_name, $course, $year]);
                setMessage('success', 'Student added successfully.');
            } catch (Exception $e) {
                setMessage('error', 'Error adding student: ' . $e->getMessage());
            }
        }
        redirect('/CLASS_CARD_DROPPING_SYSTEM/admin/students.php');
    } elseif ($_POST['action'] === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $student_id = trim($_POST['student_id'] ?? '');
        $lastname = trim($_POST['lastname'] ?? '');
        $firstname = trim($_POST['firstname'] ?? '');
        $middlename = trim($_POST['middlename'] ?? '');
        $name = $lastname . ', ' . $firstname . ', ' . $middlename;
        $address = trim($_POST['address'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $guardian_name = trim($_POST['guardian_name'] ?? '');
        $course = trim($_POST['course'] ?? '');
        $year = intval($_POST['year'] ?? 0);
        $status = trim($_POST['status'] ?? '');

        // Validation checks
        $errors = [];

        if (!$id) {
            $errors[] = 'Invalid student ID.';
        }

        if (empty($student_id)) {
            $errors[] = 'Student ID is required.';
        } elseif (strlen($student_id) !== 8) {
            $errors[] = 'Student ID must be exactly 8 digits.';
        } elseif (!preg_match('/^[0-9]{8}$/', $student_id)) {
            $errors[] = 'Student ID can only contain numbers (0-9).';
        }

        if (empty($lastname)) {
            $errors[] = 'Last name is required.';
        } elseif (strlen($lastname) < 2) {
            $errors[] = 'Last name must be at least 2 characters.';
        } elseif (strlen($lastname) > 100) {
            $errors[] = 'Last name must not exceed 100 characters.';
        }

        if (empty($firstname)) {
            $errors[] = 'First name is required.';
        } elseif (strlen($firstname) < 2) {
            $errors[] = 'First name must be at least 2 characters.';
        } elseif (strlen($firstname) > 100) {
            $errors[] = 'First name must not exceed 100 characters.';
        }

        if (empty($middlename)) {
            $errors[] = 'Middle name is required.';
        } elseif (strlen($middlename) < 2) {
            $errors[] = 'Middle name must be at least 2 characters.';
        } elseif (strlen($middlename) > 100) {
            $errors[] = 'Middle name must not exceed 100 characters.';
        }

        if (empty($address)) {
            $errors[] = 'Complete address is required.';
        } elseif (strlen($address) < 5) {
            $errors[] = 'Complete address must be at least 5 characters.';
        } elseif (strlen($address) > 255) {
            $errors[] = 'Complete address must not exceed 255 characters.';
        }

        if (empty($email)) {
            $errors[] = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } elseif (strlen($email) > 100) {
            $errors[] = 'Email address must not exceed 100 characters.';
        }

        if (empty($guardian_name)) {
            $errors[] = 'Guardian name is required.';
        } elseif (strlen($guardian_name) < 2) {
            $errors[] = 'Guardian name must be at least 2 characters.';
        } elseif (strlen($guardian_name) > 100) {
            $errors[] = 'Guardian name must not exceed 100 characters.';
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

        if (!in_array($status, ['active', 'inactive'])) {
            $errors[] = 'Invalid status.';
        }

        // Check for duplicate student ID (excluding current record)
        if (empty($errors)) {
            $stmt = $pdo->prepare('SELECT id FROM students WHERE student_id = ? AND id != ?');
            $stmt->execute([$student_id, $id]);
            if ($stmt->fetch()) {
                $errors[] = 'This student ID is already registered by another student. Please use a different ID.';
            }
        }

        // Check for duplicate email (excluding current record)
        if (empty($errors)) {
            $stmt = $pdo->prepare('SELECT id FROM students WHERE email = ? AND id != ?');
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                $errors[] = 'This email is already registered by another student. Please use a different email.';
            }
        }

        if (!empty($errors)) {
            setMessage('error', implode('<br>', $errors));
        } else {
            try {
                $stmt = $pdo->prepare('UPDATE students SET student_id = ?, name = ?, email = ?, address = ?, guardian_name = ?, course = ?, year = ?, status = ? WHERE id = ?');
                $stmt->execute([$student_id, $name, $email, $address, $guardian_name, $course, $year, $status, $id]);
                setMessage('success', 'Student updated successfully.');
            } catch (Exception $e) {
                setMessage('error', 'Error updating student: ' . $e->getMessage());
            }
        }
        redirect('/CLASS_CARD_DROPPING_SYSTEM/admin/students.php');
    } elseif ($_POST['action'] === 'update_status') {
        $student_id = intval($_POST['student_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');

        if (!$student_id) {
            setMessage('error', 'Invalid student ID.');
        } elseif (!in_array($status, ['active', 'inactive'])) {
            setMessage('error', 'Invalid status.');
        } else {
            try {
                $stmt = $pdo->prepare('UPDATE students SET status = ? WHERE id = ?');
                $stmt->execute([$status, $student_id]);
                setMessage('success', 'Student status updated successfully.');
            } catch (Exception $e) {
                setMessage('error', 'Error updating student: ' . $e->getMessage());
            }
        }
        redirect('/CLASS_CARD_DROPPING_SYSTEM/admin/students.php');
    } elseif ($_POST['action'] === 'import_students') {
        $csv_data = $_POST['csv_data'] ?? '';
        if (empty($csv_data)) {
            setMessage('error', 'No data to import. Please select a file first.');
            redirect('/CLASS_CARD_DROPPING_SYSTEM/admin/students.php');
        }

        $lines = array_filter(explode("\n", $csv_data), function($line) {
            return trim($line) !== '';
        });
        $lines = array_values($lines);

        if (count($lines) < 2) {
            setMessage('error', 'The file contains no data rows.');
            redirect('/CLASS_CARD_DROPPING_SYSTEM/admin/students.php');
        }

        // Remove header row
        array_shift($lines);

        $imported = 0;
        $skipped = 0;
        $import_errors = [];

        foreach ($lines as $index => $line) {
            $row = str_getcsv($line);
            if (count($row) < 9) {
                $skipped++;
                $import_errors[] = "Row " . ($index + 2) . ": Incomplete data (expected 9 columns, got " . count($row) . ").";
                continue;
            }

            $student_id = trim($row[0]);
            $lastname = trim($row[1]);
            $firstname = trim($row[2]);
            $middlename = trim($row[3]);
            $name = $lastname . ', ' . $firstname . ', ' . $middlename;
            $address = trim($row[4]);
            $email = trim($row[5]);
            $guardian_name = trim($row[6]);
            $course = trim($row[7]);
            $year = intval($row[8]);

            // Basic validation
            if (empty($student_id) || empty($lastname) || empty($firstname) || empty($email)) {
                $skipped++;
                $import_errors[] = "Row " . ($index + 2) . ": Missing required fields (student_id, lastname, firstname, or email).";
                continue;
            }

            if (!preg_match('/^[0-9]{8}$/', $student_id)) {
                $skipped++;
                $import_errors[] = "Row " . ($index + 2) . ": Student ID '$student_id' must be exactly 8 digits.";
                continue;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                $import_errors[] = "Row " . ($index + 2) . ": Invalid email format '$email'.";
                continue;
            }

            if ($year < 1 || $year > 4) {
                $skipped++;
                $import_errors[] = "Row " . ($index + 2) . ": Year must be between 1 and 4.";
                continue;
            }

            // Check duplicate student_id or email
            $stmt = $pdo->prepare('SELECT id FROM students WHERE student_id = ? OR email = ?');
            $stmt->execute([$student_id, $email]);
            if ($stmt->fetch()) {
                $skipped++;
                $import_errors[] = "Row " . ($index + 2) . ": Student ID '$student_id' or email '$email' already exists.";
                continue;
            }

            try {
                $stmt = $pdo->prepare('INSERT INTO students (student_id, name, email, address, guardian_name, course, year) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$student_id, $name, $email, $address, $guardian_name, $course, $year]);
                $imported++;
            } catch (Exception $e) {
                $skipped++;
                $import_errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }

        $msg = "Import complete: <strong>$imported</strong> student(s) imported successfully";
        if ($skipped > 0) {
            $msg .= ", <strong>$skipped</strong> skipped";
        }
        $msg .= ".";
        if (!empty($import_errors)) {
            $msg .= '<br><br><strong>Details:</strong><br>' . implode('<br>', array_slice($import_errors, 0, 15));
            if (count($import_errors) > 15) {
                $msg .= '<br>...and ' . (count($import_errors) - 15) . ' more issues.';
            }
        }
        setMessage($imported > 0 ? 'success' : 'error', $msg);
        redirect('/CLASS_CARD_DROPPING_SYSTEM/admin/students.php');
    }
}

// Get course and year filters from query parameters
$course_filter = isset($_GET['course']) ? trim($_GET['course']) : null;
$year_filter = isset($_GET['year']) ? intval($_GET['year']) : null;

// Get total count and pagination data
$count_query = 'SELECT COUNT(*) as total FROM students';
$count_params = [];

if ($course_filter || $year_filter) {
    $count_query .= ' WHERE 1=1';
    if ($course_filter) {
        $count_query .= ' AND course = ?';
        $count_params[] = $course_filter;
    }
    if ($year_filter) {
        $count_query .= ' AND year = ?';
        $count_params[] = $year_filter;
    }
}

$stmt = $pdo->prepare($count_query);
$stmt->execute($count_params);
$total_students = $stmt->fetch()['total'];

$pagination = getPaginationData($total_students, 15); // 15 items per page

// Fetch paginated students
$query = 'SELECT * FROM students';
$query_params = [];

if ($course_filter || $year_filter) {
    $query .= ' WHERE 1=1';
    if ($course_filter) {
        $query .= ' AND course = ?';
        $query_params[] = $course_filter;
    }
    if ($year_filter) {
        $query .= ' AND year = ?';
        $query_params[] = $year_filter;
    }
}

$query .= ' ORDER BY name LIMIT ' . intval($pagination['limit']) . ' OFFSET ' . intval($pagination['offset']);

$stmt = $pdo->prepare($query);
$stmt->execute($query_params);
$students = $stmt->fetchAll();

// Get distinct courses for submenu
$course_stmt = $pdo->prepare('SELECT DISTINCT course FROM students WHERE course IS NOT NULL AND course != "" ORDER BY course');
$course_stmt->execute();
$courses = $course_stmt->fetchAll();

$message = getMessage();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - PhilCST</title>
    <link rel="stylesheet" href="/CLASS_CARD_DROPPING_SYSTEM/css/style.css">
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="/CLASS_CARD_DROPPING_SYSTEM/Philcst Logo (2).png" alt="PhilCST Logo" class="sidebar-logo">
                <h2>PhilCST</h2>
                <p>Admin Portal</p>
            </div>

            <nav class="sidebar-nav">
                <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/dashboard.php" class="nav-item">
                    <span>Dashboard</span>
                </a>
                <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/dropped_cards.php" class="nav-item">
                    <span>Dropped Cards</span>
                </a>
                <div class="nav-item submenu-trigger active" onclick="toggleSubmenu(this)">
                    <span>Manage Students</span>
                </div>
                <div class="submenu active" id="studentSubmenu">
                    <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/students.php" class="submenu-item <?php echo !$course_filter && !$year_filter ? 'active' : ''; ?>">All Students</a>
                    <div style="padding: 8px 16px; color: rgba(255, 255, 255, 0.6); font-size: 0.75em; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; border-top: 1px solid rgba(255, 255, 255, 0.1);">Courses</div>
                    <?php foreach ($courses as $course): ?>
                    <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/students.php?course=<?php echo urlencode($course['course']); ?>" class="submenu-item" style="padding-left: 40px; <?php echo $course_filter === $course['course'] && !$year_filter ? 'background-color: rgba(167, 139, 250, 0.25); color: #c4b5fd;' : ''; ?>"><?php echo htmlspecialchars($course['course']); ?></a>
                    <?php endforeach; ?>
                    <div style="padding: 8px 16px; color: rgba(255, 255, 255, 0.6); font-size: 0.75em; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; border-top: 1px solid rgba(255, 255, 255, 0.1);">Year Level</div>
                    <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/students.php?year=1" class="submenu-item" style="padding-left: 40px; <?php echo $year_filter === 1 && !$course_filter ? 'background-color: rgba(167, 139, 250, 0.25); color: #c4b5fd;' : ''; ?>">1st Year</a>
                    <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/students.php?year=2" class="submenu-item" style="padding-left: 40px; <?php echo $year_filter === 2 && !$course_filter ? 'background-color: rgba(167, 139, 250, 0.25); color: #c4b5fd;' : ''; ?>">2nd Year</a>
                    <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/students.php?year=3" class="submenu-item" style="padding-left: 40px; <?php echo $year_filter === 3 && !$course_filter ? 'background-color: rgba(167, 139, 250, 0.25); color: #c4b5fd;' : ''; ?>">3rd Year</a>
                    <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/students.php?year=4" class="submenu-item" style="padding-left: 40px; <?php echo $year_filter === 4 && !$course_filter ? 'background-color: rgba(167, 139, 250, 0.25); color: #c4b5fd;' : ''; ?>">4th Year</a>
                </div>
                <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/teachers.php" class="nav-item">
                    <span>Manage Teachers</span>
                </a>
                <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/drop_history.php" class="nav-item">
                    <span>Drop History</span>
                </a>
                <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/settings.php" class="nav-item">
                    <span>Settings</span>
                </a>
                <a href="#" class="nav-item logout-item" onclick="showLogoutModal(); return false;">
                    <span>Logout</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <p class="sidebar-footer-name"><?php echo htmlspecialchars($user_info['name']); ?></p>
                <p class="sidebar-footer-dept"><?php echo htmlspecialchars($user_info['department'] ?: 'Administrator'); ?></p>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-bar">
                <button class="sidebar-toggle" onclick="toggleSidebar()" id="sidebarToggleBtn">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <h1>
                    Manage Students
                    <?php 
                    if ($course_filter || $year_filter) {
                        echo ' - ';
                        if ($course_filter) echo htmlspecialchars($course_filter);
                        if ($course_filter && $year_filter) echo ' / ';
                        if ($year_filter) {
                            $year_labels = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year'];
                            echo $year_labels[$year_filter];
                        }
                    }
                    ?>
                </h1>
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

                <!-- Action Buttons -->
                <div style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
                    <button type="button" class="btn btn-primary" onclick="openRegisterModal()">Register Student</button>
                    <button type="button" class="btn btn-success" onclick="openImportModal()">Import from CSV/Excel</button>
                </div>

                <!-- Register Modal -->
                <div id="registerModal" class="modal" style="display: none;">
                    <div class="modal-content" style="max-width: 800px; max-height: 90vh; overflow-y: auto;">
                        <div class="modal-header">
                            <h2>Register New Student</h2>
                            <button type="button" class="modal-close" onclick="closeRegisterModal()">&times;</button>
                        </div>
                        <form method="POST" class="student-form">
                            <input type="hidden" name="action" value="add">
                            <div class="modal-body" style="padding: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div class="form-group">
                                    <label for="student_id">Student ID</label>
                                    <input type="text" id="student_id" name="student_id" required
                                        placeholder="ex: 000*****" maxlength="8" inputmode="numeric">
                                </div>

                                <div class="form-group">
                                    <label for="lastname">Last Name</label>
                                    <input type="text" id="lastname" name="lastname" required
                                        placeholder="Enter last name" oninput="this.value = this.value.toUpperCase()">
                                </div>

                                <div class="form-group">
                                    <label for="firstname">First Name</label>
                                    <input type="text" id="firstname" name="firstname" required
                                        placeholder="Enter first name" oninput="this.value = this.value.toUpperCase()">
                                </div>

                                <div class="form-group">
                                    <label for="middlename">Middle Name</label>
                                    <input type="text" id="middlename" name="middlename" required
                                        placeholder="Enter middle name" oninput="this.value = this.value.toUpperCase()">
                                </div>

                                <div class="form-group" style="grid-column: 1 / -1;">
                                    <label for="address">Complete Address</label>
                                    <textarea id="address" name="address" required placeholder="Enter complete address"
                                        rows="3"></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="email">Email Address</label>
                                    <input type="email" id="email" name="email" placeholder="example@gmail.com"
                                        required>
                                </div>

                                <div class="form-group">
                                    <label for="guardian_name">Guardian Name</label>
                                    <input type="text" id="guardian_name" name="guardian_name" required placeholder="Enter guardian name" oninput="this.value = this.value.toUpperCase()">
                                </div>

                                <div class="form-group" style="grid-column: 1 / -1;">
                                    <label for="course">Course</label>
                                    <select id="course" name="course" required>
                                        <option value="">-- Select Course --</option>
                                        <optgroup label="College of Engineering and Architecture">
                                            <option value="BS in Civil Engineering (BSCE)">BS in Civil Engineering
                                                (BSCE)</option>
                                            <option value="BS in Electrical Engineering (BSEE)">BS in Electrical
                                                Engineering (BSEE)</option>
                                            <option value="BS in Mechanical Engineering (BSME)">BS in Mechanical
                                                Engineering (BSME)</option>
                                        </optgroup>
                                        <optgroup label="College of Criminology">
                                            <option value="BS in Criminology (BSCrim)">BS in Criminology (BSCrim)
                                            </option>
                                        </optgroup>
                                        <optgroup label="College of Information Technology">
                                            <option value="BS in Information Technology (BSIT)">BS in Information
                                                Technology (BSIT)</option>
                                            <option value="BS in Computer Science (BSCS)">BS in Computer Science (BSCS)
                                            </option>
                                        </optgroup>
                                        <optgroup label="College of Education">
                                            <option value="Bachelor of Elementary Education (BEEd)">Bachelor of
                                                Elementary Education (BEEd)</option>
                                            <option value="Bachelor of Secondary Education (BSEd)">Bachelor of Secondary
                                                Education (BSEd)</option>
                                        </optgroup>
                                        <optgroup label="College of Business and Management">
                                            <option value="BS in Business Administration (BSBA)">BS in Business
                                                Administration (BSBA)</option>
                                            <option value="BS in Hospitality Management (BSHM)">BS in Hospitality
                                                Management (BSHM)</option>
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
                            </div>
                            <div class="modal-footer"
                                style="padding: 15px 20px; border-top: 1px solid #ddd; display: flex; gap: 10px; justify-content: flex-end;">
                                <button type="button" class="btn btn-secondary"
                                    onclick="closeRegisterModal()">Cancel</button>
                                <button type="submit" class="btn btn-primary">Register Student</button>
                            </div>
                        </form>
                    </div>
                </div>

                <style>
                    .modal {
                        position: fixed;
                        z-index: 1000;
                        left: 0;
                        top: 0;
                        width: 100%;
                        height: 100%;
                        background-color: rgba(0, 0, 0, 0.5);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }

                    .modal-content {
                        background-color: white;
                        border-radius: 8px;
                        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
                    }

                    .modal-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        padding: 20px;
                        border-bottom: 1px solid #ddd;
                    }

                    .modal-header h2 {
                        margin: 0;
                    }

                    .modal-close {
                        background: none;
                        border: none;
                        font-size: 28px;
                        cursor: pointer;
                        color: #999;
                    }

                    .modal-close:hover {
                        color: #333;
                    }
                </style>

                <script>
                    function openRegisterModal() {
                        document.getElementById('registerModal').style.display = 'flex';
                    }

                    function closeRegisterModal() {
                        document.getElementById('registerModal').style.display = 'none';
                    }

                    // Close modal when clicking outside of it
                    window.onclick = function (event) {
                        var modal = document.getElementById('registerModal');
                        if (event.target == modal) {
                            modal.style.display = 'none';
                        }
                    }
                </script>

                <!-- Update Modal -->
                <div id="updateModal" class="modal" style="display: none;">
                    <div class="modal-content" style="max-width: 600px; max-height: 90vh; overflow-y: auto;">
                        <div class="modal-header">
                            <h2>Update Student</h2>
                            <button type="button" class="modal-close" onclick="closeUpdateModal()">&times;</button>
                        </div>
                        <form method="POST" id="updateForm">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" id="updateStudentId" value="">
                            <div class="modal-body" style="padding: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div class="form-group">
                                    <label for="updateStudentIdField">Student ID</label>
                                    <input type="text" id="updateStudentIdField" name="student_id" required
                                        placeholder="ex: 000*****" maxlength="8" inputmode="numeric">
                                </div>

                                <div class="form-group">
                                    <label for="updateLastName">Last Name</label>
                                    <input type="text" id="updateLastName" name="lastname" required
                                        placeholder="Enter last name" oninput="this.value = this.value.toUpperCase()">
                                </div>

                                <div class="form-group">
                                    <label for="updateFirstName">First Name</label>
                                    <input type="text" id="updateFirstName" name="firstname" required
                                        placeholder="Enter first name" oninput="this.value = this.value.toUpperCase()">
                                </div>

                                <div class="form-group">
                                    <label for="updateMiddleName">Middle Name</label>
                                    <input type="text" id="updateMiddleName" name="middlename" required
                                        placeholder="Enter middle name" oninput="this.value = this.value.toUpperCase()">
                                </div>

                                <div class="form-group" style="grid-column: 1 / -1;">
                                    <label for="updateAddress">Complete Address</label>
                                    <textarea id="updateAddress" name="address" required
                                        placeholder="Enter complete address" rows="3"></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="updateEmail">Email Address</label>
                                    <input type="email" id="updateEmail" name="email" required
                                        placeholder="example@gmail.com">
                                </div>

                                <div class="form-group">
                                    <label for="updateGuardianName">Guardian Name</label>
                                    <input type="text" id="updateGuardianName" name="guardian_name" required
                                        placeholder="Enter guardian name" oninput="this.value = this.value.toUpperCase()">
                                </div>

                                <div class="form-group" style="grid-column: 1 / -1;">
                                    <label for="updateCourse">Course</label>
                                    <select id="updateCourse" name="course" required>
                                        <option value="">-- Select Course --</option>
                                        <optgroup label="College of Engineering and Architecture">
                                            <option value="BS in Civil Engineering (BSCE)">BS in Civil Engineering
                                                (BSCE)</option>
                                            <option value="BS in Electrical Engineering (BSEE)">BS in Electrical
                                                Engineering (BSEE)</option>
                                            <option value="BS in Mechanical Engineering (BSME)">BS in Mechanical
                                                Engineering (BSME)</option>
                                        </optgroup>
                                        <optgroup label="College of Criminology">
                                            <option value="BS in Criminology (BSCrim)">BS in Criminology (BSCrim)
                                            </option>
                                        </optgroup>
                                        <optgroup label="College of Information Technology">
                                            <option value="BS in Information Technology (BSIT)">BS in Information
                                                Technology (BSIT)</option>
                                            <option value="BS in Computer Science (BSCS)">BS in Computer Science (BSCS)
                                            </option>
                                        </optgroup>
                                        <optgroup label="College of Education">
                                            <option value="Bachelor of Elementary Education (BEEd)">Bachelor of
                                                Elementary Education (BEEd)</option>
                                            <option value="Bachelor of Secondary Education (BSEd)">Bachelor of Secondary
                                                Education (BSEd)</option>
                                        </optgroup>
                                        <optgroup label="College of Business and Management">
                                            <option value="BS in Business Administration (BSBA)">BS in Business
                                                Administration (BSBA)</option>
                                            <option value="BS in Hospitality Management (BSHM)">BS in Hospitality
                                                Management (BSHM)</option>
                                        </optgroup>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="updateYear">Year Level</label>
                                    <select id="updateYear" name="year" required>
                                        <option value="">-- Select Year --</option>
                                        <option value="1">1st Year</option>
                                        <option value="2">2nd Year</option>
                                        <option value="3">3rd Year</option>
                                        <option value="4">4th Year</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="updateStatus">Status</label>
                                    <select id="updateStatus" name="status" required>
                                        <option value="">-- Select Status --</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer"
                                style="padding: 15px 20px; border-top: 1px solid #ddd; display: flex; gap: 10px; justify-content: flex-end;">
                                <button type="button" class="btn btn-secondary"
                                    onclick="closeUpdateModal()">Cancel</button>
                                <button type="submit" class="btn btn-primary">Update</button>
                            </div>
                        </form>
                    </div>
                </div>

                <script>
                    function openUpdateModal(id, studentId, lastName, firstName, middleName, address, email, guardianName, course, year, status) {
                        document.getElementById('updateStudentId').value = id;
                        document.getElementById('updateStudentIdField').value = studentId;
                        document.getElementById('updateLastName').value = lastName;
                        document.getElementById('updateFirstName').value = firstName;
                        document.getElementById('updateMiddleName').value = middleName;
                        document.getElementById('updateAddress').value = address;
                        document.getElementById('updateEmail').value = email;
                        document.getElementById('updateGuardianName').value = guardianName;
                        document.getElementById('updateCourse').value = course;
                        document.getElementById('updateYear').value = year;
                        document.getElementById('updateStatus').value = status;
                        document.getElementById('updateModal').style.display = 'flex';
                    }

                    function closeUpdateModal() {
                        document.getElementById('updateModal').style.display = 'none';
                    }

                    // Close modal when clicking outside of it
                    window.onclick = function (event) {
                        var registerModal = document.getElementById('registerModal');
                        var updateModal = document.getElementById('updateModal');
                        var importModal = document.getElementById('importModal');
                        if (event.target == registerModal) registerModal.style.display = 'none';
                        if (event.target == updateModal) updateModal.style.display = 'none';
                        if (event.target == importModal) closeImportModal();
                    }
                </script>

                <!-- Import Students Modal -->
                <div id="importModal" class="modal" style="display: none;">
                    <div class="modal-content" style="max-width: 950px; max-height: 90vh; overflow-y: auto;">
                        <div class="modal-header">
                            <h2>Import Students from CSV / Excel</h2>
                            <button type="button" class="modal-close" onclick="closeImportModal()">&times;</button>
                        </div>
                        <div class="modal-body" style="padding: 20px;">
                            <div class="import-info-box">
                                <p><strong>Instructions:</strong></p>
                                <ol style="margin: 8px 0 0 20px; line-height: 1.8;">
                                    <li>Download the CSV template and fill in the student data</li>
                                    <li>Save as <strong>.csv</strong> or <strong>.xlsx</strong> format</li>
                                    <li>Upload the file below and review the preview</li>
                                    <li>Click <strong>Import</strong> to add the students</li>
                                </ol>
                                <p style="margin-top: 10px; font-size: 0.9em; color: #666;">
                                    <strong>Required columns:</strong> Student ID (8 digits), Last Name, First Name, Middle Name, Address, Email, Guardian Name, Guardian Email, Course, Year (1-4)
                                </p>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="downloadStudentTemplate()" style="margin-top: 10px;">Download CSV Template</button>
                            </div>

                            <div class="import-dropzone" id="studentDropzone" onclick="document.getElementById('importFileStudent').click()">
                                <div class="dropzone-content">
                                    <div class="dropzone-icon"></div>
                                    <p><strong>Click to browse</strong> or drag & drop your file here</p>
                                    <p class="dropzone-hint">Accepts .csv, .xlsx, .xls files</p>
                                </div>
                                <input type="file" id="importFileStudent" accept=".csv,.xlsx,.xls" style="display:none" onchange="handleStudentFile(this)">
                            </div>

                            <div id="importPreviewStudent" style="display: none;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin: 15px 0 10px;">
                                    <h3>Data Preview <span id="previewCountStudent" style="font-weight: normal; font-size: 0.85em; color: #666;"></span></h3>
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="clearImportStudent()">✕ Clear</button>
                                </div>
                                <div class="table-responsive" style="max-height: 350px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px;">
                                    <table class="table" id="previewTableStudent">
                                        <thead></thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <form method="POST" id="importFormStudent">
                            <input type="hidden" name="action" value="import_students">
                            <textarea name="csv_data" id="csvDataStudent" style="display:none"></textarea>
                            <div class="modal-footer" style="padding: 15px 20px; border-top: 1px solid #ddd; display: flex; gap: 10px; justify-content: flex-end;">
                                <button type="button" class="btn btn-secondary" onclick="closeImportModal()">Cancel</button>
                                <button type="submit" class="btn btn-success" id="importBtnStudent" style="display: none;">Import Students</button>
                            </div>
                        </form>
                    </div>
                </div>

                <script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
                <script>
                    function openImportModal() {
                        document.getElementById('importModal').style.display = 'flex';
                    }

                    function closeImportModal() {
                        document.getElementById('importModal').style.display = 'none';
                        clearImportStudent();
                    }

                    function clearImportStudent() {
                        document.getElementById('importPreviewStudent').style.display = 'none';
                        document.getElementById('importBtnStudent').style.display = 'none';
                        document.getElementById('importFileStudent').value = '';
                        document.getElementById('csvDataStudent').value = '';
                    }

                    function downloadStudentTemplate() {
                        var csv = 'student_id,lastname,firstname,middlename,address,email,guardian_name,course,year\n';
                        csv += '00012345,Dela Cruz,Juan,Santos,"123 Main St, Manila",juan@gmail.com,Maria Dela Cruz,BS in Information Technology (BSIT),1\n';
                        csv += '00067890,Garcia,Jose,Reyes,"456 Oak Ave, Quezon City",jose@gmail.com,Rosa Garcia,BS in Computer Science (BSCS),2\n';
                        downloadCSV(csv, 'student_import_template.csv');
                    }

                    function handleStudentFile(input) {
                        var file = input.files[0];
                        if (!file) return;

                        var reader = new FileReader();
                        reader.onload = function(e) {
                            try {
                                var workbook = XLSX.read(e.target.result, { type: 'array' });
                                var sheet = workbook.Sheets[workbook.SheetNames[0]];
                                var jsonData = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: '' });

                                if (jsonData.length < 2) {
                                    alert('The file appears to be empty or contains only a header row.');
                                    return;
                                }

                                var rows = jsonData.slice(1).filter(function(row) {
                                    return row.some(function(cell) { return String(cell).trim() !== ''; });
                                });

                                if (rows.length === 0) {
                                    alert('No data rows found in the file.');
                                    return;
                                }

                                showStudentPreview(rows);
                            } catch (err) {
                                alert('Error reading file: ' + err.message);
                            }
                        };
                        reader.readAsArrayBuffer(file);
                    }

                    function showStudentPreview(rows) {
                        var displayHeaders = ['Student ID', 'Last Name', 'First Name', 'Middle Name', 'Address', 'Email', 'Guardian Name', 'Course', 'Year'];
                        var thead = document.querySelector('#previewTableStudent thead');
                        var tbody = document.querySelector('#previewTableStudent tbody');

                        thead.innerHTML = '<tr>' + displayHeaders.map(function(h) { return '<th style="white-space:nowrap;">' + h + '</th>'; }).join('') + '</tr>';

                        var maxPreview = Math.min(rows.length, 10);
                        var tbodyHtml = '';
                        for (var i = 0; i < maxPreview; i++) {
                            tbodyHtml += '<tr>';
                            for (var j = 0; j < 9; j++) {
                                var val = rows[i][j] !== undefined ? String(rows[i][j]) : '';
                                if (val.length > 40) val = val.substring(0, 40) + '...';
                                tbodyHtml += '<td>' + val.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</td>';
                            }
                            tbodyHtml += '</tr>';
                        }
                        tbody.innerHTML = tbodyHtml;

                        document.getElementById('previewCountStudent').textContent = '— ' + rows.length + ' record(s)' + (rows.length > 10 ? ' (showing first 10)' : '');
                        document.getElementById('importPreviewStudent').style.display = 'block';
                        document.getElementById('importBtnStudent').style.display = 'inline-block';
                        document.getElementById('importBtnStudent').textContent = 'Import ' + rows.length + ' Student(s)';

                        // Build CSV data for form submission
                        var headers = ['student_id','lastname','firstname','middlename','address','email','guardian_name','course','year'];
                        var csvLines = [headers.join(',')];
                        rows.forEach(function(row) {
                            var csvRow = [];
                            for (var j = 0; j < 9; j++) {
                                var val = row[j] !== undefined ? String(row[j]) : '';
                                if (val.indexOf(',') !== -1 || val.indexOf('"') !== -1 || val.indexOf('\n') !== -1) {
                                    val = '"' + val.replace(/"/g, '""') + '"';
                                }
                                csvRow.push(val);
                            }
                            csvLines.push(csvRow.join(','));
                        });
                        document.getElementById('csvDataStudent').value = csvLines.join('\n');
                    }

                    // Drag and drop support for student import
                    (function() {
                        var dz = document.getElementById('studentDropzone');
                        if (!dz) return;
                        ['dragenter','dragover','dragleave','drop'].forEach(function(evt) {
                            dz.addEventListener(evt, function(e) { e.preventDefault(); e.stopPropagation(); });
                        });
                        ['dragenter','dragover'].forEach(function(evt) {
                            dz.addEventListener(evt, function() { dz.classList.add('dropzone-active'); });
                        });
                        ['dragleave','drop'].forEach(function(evt) {
                            dz.addEventListener(evt, function() { dz.classList.remove('dropzone-active'); });
                        });
                        dz.addEventListener('drop', function(e) {
                            var files = e.dataTransfer.files;
                            if (files.length > 0) {
                                document.getElementById('importFileStudent').files = files;
                                handleStudentFile(document.getElementById('importFileStudent'));
                            }
                        });
                    })();
                </script>

                <!-- Students List -->
                <section class="section">
                    <h2>All Students <span style="font-weight: normal; font-size: 0.9em; color: #666;">(<span id="studentsTable-count"><?php echo $pagination['total_items']; ?></span> total, page <?php echo $pagination['current_page']; ?> of <?php echo max(1, $pagination['total_pages']); ?>)</span></h2>
                    <div class="form-group" style="max-width: 400px; margin-bottom: 15px;">
                        <input type="text" id="liveSearchStudents" data-live-filter="studentsTable" placeholder="Search by ID, name, email, course..." style="width: 100%;">
                    </div>
                    <?php if (count($students) > 0): ?>
                        <div class="table-responsive">
                            <table class="table" id="studentsTable">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Last Name</th>
                                        <th>First Name</th>
                                        <th>Middle Name</th>
                                        <th>Email</th>
                                        <th>Address</th>
                                        <th>Guardian Name</th>
                                        <th>Course</th>
                                        <th>Year</th>
                                        <th>Student Status</th>
                                        <th>Registered</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($student['student_id'] ?? ''); ?></td>
                                            <td><?php
                                            $nameParts = explode(', ', $student['name'] ?? '');
                                            echo htmlspecialchars(trim($nameParts[0] ?? ''));
                                            ?></td>
                                            <td><?php
                                            $nameParts = explode(', ', $student['name'] ?? '');
                                            echo htmlspecialchars(trim($nameParts[1] ?? ''));
                                            ?></td>
                                            <td><?php
                                            $nameParts = explode(', ', $student['name'] ?? '');
                                            echo htmlspecialchars(trim($nameParts[2] ?? ''));
                                            ?></td>
                                            <td><?php echo htmlspecialchars($student['email'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($student['address'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($student['guardian_name'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($student['course'] ?? ''); ?></td>
                                            <td><?php echo $student['year'] ?? ''; ?></td>
                                            <td><span
                                                    class="status status-<?php echo ($student['status'] === 'active') ? 'active' : 'inactive'; ?>"><?php echo ucfirst($student['status'] ?? 'inactive'); ?></span>
                                            </td>
                                            <td><?php echo formatDate($student['created_at'] ?? ''); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary"
                                                    onclick="openUpdateModal(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['student_id']); ?>', '<?php $nameParts = explode(', ', $student['name']);
                                                          echo htmlspecialchars(trim($nameParts[0] ?? '')); ?>', '<?php echo htmlspecialchars(trim($nameParts[1] ?? '')); ?>', '<?php echo htmlspecialchars(trim($nameParts[2] ?? '')); ?>', '<?php echo htmlspecialchars($student['address'] ?? ''); ?>', '<?php echo htmlspecialchars($student['email']); ?>', '<?php echo htmlspecialchars($student['guardian_name'] ?? ''); ?>', '<?php echo htmlspecialchars($student['course']); ?>', <?php echo $student['year']; ?>, '<?php echo htmlspecialchars($student['status'] ?? 'inactive'); ?>')">Update</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php echo renderPaginationControls($pagination, '/CLASS_CARD_DROPPING_SYSTEM/admin/students.php'); ?>
                    <?php else: ?>
                        <p class="no-data">No students registered yet.</p>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>

    <script>
        function toggleSubmenu(trigger) {
            const submenu = trigger.nextElementSibling;
            if (submenu && submenu.classList.contains('submenu')) {
                submenu.classList.toggle('active');
                trigger.classList.toggle('active');
            }
        }

        // Prevent scroll to top on pagination click
        document.addEventListener('DOMContentLoaded', function() {
            const paginationLinks = document.querySelectorAll('.pagination-link');
            paginationLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    const scrollPosition = window.scrollY || window.pageYOffset;
                    sessionStorage.setItem('scrollPosition', scrollPosition);
                });
            });

            // Restore scroll position if coming from pagination
            const savedScrollPosition = sessionStorage.getItem('scrollPosition');
            if (savedScrollPosition !== null) {
                setTimeout(() => {
                    window.scrollTo(0, parseInt(savedScrollPosition));
                    sessionStorage.removeItem('scrollPosition');
                }, 100);
            }
        });
    </script>

    <script src="/CLASS_CARD_DROPPING_SYSTEM/js/functions.js"></script>
</body>

</html>