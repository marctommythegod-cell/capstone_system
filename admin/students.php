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

// Get all courses organized by category from courses table
$courses_by_category = [];
$all_courses_stmt = $pdo->prepare('SELECT category, course_name FROM courses ORDER BY category, course_name');
$all_courses_stmt->execute();
$all_courses = $all_courses_stmt->fetchAll();

foreach ($all_courses as $course) {
    $category = $course['category'];
    if (!isset($courses_by_category[$category])) {
        $courses_by_category[$category] = [];
    }
    $courses_by_category[$category][] = $course['course_name'];
}

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
        <!-- Sidebar Toggle Button -->
        <button class="sidebar-toggle-btn" onclick="toggleSidebar()" title="Toggle Sidebar">≡</button>
        
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
                <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/cancelled_class_card.php" class="nav-item">
                    <span>Cancelled Class Cards</span>
                </a>
                <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/profile.php" class="nav-item">
                    <span>Profile</span>
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
                <div id="registerModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.6); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
                    <div class="modal-content" style="background: white; border-radius: 16px; width: 100%; max-width: 900px; max-height: 85vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);">
                        <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color), #9b59b6); color: white; padding: 28px 32px; border-radius: 16px 16px 0 0; font-size: 1.4em; font-weight: 700; display: flex; justify-content: space-between; align-items: center; letter-spacing: 0.3px;">
                            <span>Register New Student</span>
                            <button type="button" class="modal-close" onclick="closeRegisterModal()" style="background: rgba(255, 255, 255, 0.25); border: none; color: white; font-size: 28px; cursor: pointer; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.3s; line-height: 1;" onmouseover="this.style.backgroundColor='rgba(255, 255, 255, 0.35); this.style.transform='scale(1.1)'" onmouseout="this.style.backgroundColor='rgba(255, 255, 255, 0.25); this.style.transform='scale(1)'">&times;</button>
                        </div>
                        <form method="POST" class="student-form" onsubmit="return validateStudentForm()">
                            <input type="hidden" name="action" value="add">
                            <div class="modal-body" style="padding: 40px 32px; background: #f8f6ff; display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                                <div style="grid-column: 1 / 2;">
                                    <div class="form-group" style="margin-bottom: 24px;">
                                        <label for="student_id" style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Student ID</label>
                                        <input type="text" id="student_id" name="student_id" required placeholder="ex: 00000000" maxlength="8" inputmode="numeric" style="width: 100%; padding: 12px 16px; border: 2px solid #e9d5ff; border-radius: 10px; font-size: 1em; transition: all 0.3s;" onfocus="this.style.borderColor='var(--primary-color)'" onblur="this.style.borderColor='#e9d5ff'; validateStudentId()" oninput="validateStudentId()">
                                        <small id="student_id_error" style="color: #ef4444; font-size: 0.85em; margin-top: 5px; display: none;"></small>
                                    </div>

                                    <div class="form-group" style="margin-bottom: 24px;">
                                        <label for="firstname" style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">First Name</label>
                                        <input type="text" id="firstname" name="firstname" required placeholder="Enter first name" oninput="validateLettersOnly(this, 'firstname_error'); this.value = this.value.toUpperCase()" onblur="validateLettersOnly(this, 'firstname_error')" style="width: 100%; padding: 12px 16px; border: 2px solid #e9d5ff; border-radius: 10px; font-size: 1em; transition: all 0.3s;" onfocus="this.style.borderColor='var(--primary-color)'" onblur="this.style.borderColor='#e9d5ff'">
                                        <small id="firstname_error" style="color: #ef4444; font-size: 0.85em; margin-top: 5px; display: none;"></small>
                                    </div>

                                    <div class="form-group" style="margin-bottom: 24px;">
                                        <label for="middlename" style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Middle Name</label>
                                        <input type="text" id="middlename" name="middlename" required placeholder="Enter middle name" oninput="validateLettersOnly(this, 'middlename_error'); this.value = this.value.toUpperCase()" onblur="validateLettersOnly(this, 'middlename_error')" style="width: 100%; padding: 12px 16px; border: 2px solid #e9d5ff; border-radius: 10px; font-size: 1em; transition: all 0.3s;" onfocus="this.style.borderColor='var(--primary-color)'" onblur="this.style.borderColor='#e9d5ff'">
                                        <small id="middlename_error" style="color: #ef4444; font-size: 0.85em; margin-top: 5px; display: none;"></small>
                                    </div>

                                    <div class="form-group">
                                        <label for="email" style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Email Address</label>
                                        <input type="email" id="email" name="email" placeholder="example@gmail.com" required oninput="validateGmailOnly()" onblur="validateGmailOnly()" style="width: 100%; padding: 12px 16px; border: 2px solid #e9d5ff; border-radius: 10px; font-size: 1em; transition: all 0.3s;" onfocus="this.style.borderColor='var(--primary-color)'" onblur="this.style.borderColor='#e9d5ff'">
                                        <small id="email_error" style="color: #ef4444; font-size: 0.85em; margin-top: 5px; display: none;"></small>
                                    </div>
                                </div>

                                <div style="grid-column: 2 / 3;">
                                    <div class="form-group" style="margin-bottom: 24px;">
                                        <label for="lastname" style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Last Name</label>
                                        <input type="text" id="lastname" name="lastname" required placeholder="Enter last name" oninput="validateLettersOnly(this, 'lastname_error'); this.value = this.value.toUpperCase()" onblur="validateLettersOnly(this, 'lastname_error')" style="width: 100%; padding: 12px 16px; border: 2px solid #e9d5ff; border-radius: 10px; font-size: 1em; transition: all 0.3s;" onfocus="this.style.borderColor='var(--primary-color)'" onblur="this.style.borderColor='#e9d5ff'">
                                        <small id="lastname_error" style="color: #ef4444; font-size: 0.85em; margin-top: 5px; display: none;"></small>
                                    </div>

                                    <div class="form-group" style="margin-bottom: 24px;">
                                        <label for="address" style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Complete Address</label>
                                        <textarea id="address" name="address" required placeholder="Enter complete address" rows="2" style="width: 100%; padding: 12px 16px; border: 2px solid #e9d5ff; border-radius: 10px; font-size: 1em; transition: all 0.3s; font-family: inherit; resize: vertical;" onfocus="this.style.borderColor='var(--primary-color)'" onblur="this.style.borderColor='#e9d5ff'"></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="guardian_name" style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Guardian Name</label>
                                        <input type="text" id="guardian_name" name="guardian_name" required placeholder="Enter guardian name" oninput="validateLettersOnly(this, 'guardian_name_error'); this.value = this.value.toUpperCase()" onblur="validateLettersOnly(this, 'guardian_name_error')" style="width: 100%; padding: 12px 16px; border: 2px solid #e9d5ff; border-radius: 10px; font-size: 1em; transition: all 0.3s;" onfocus="this.style.borderColor='var(--primary-color)'" onblur="this.style.borderColor='#e9d5ff'">
                                        <small id="guardian_name_error" style="color: #ef4444; font-size: 0.85em; margin-top: 5px; display: none;"></small>
                                    </div>
                                </div>

                                <div style="grid-column: 1 / -1;">
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                                        <div>
                                            <label for="course" style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Course</label>
                                            <select id="course" name="course" required style="width: 100%; padding: 12px 16px; border: 2px solid #e9d5ff; border-radius: 10px; font-size: 1em; transition: all 0.3s;" onfocus="this.style.borderColor='var(--primary-color)'" onblur="this.style.borderColor='#e9d5ff'">
                                                <option value="">-- Select Course --</option>
                                                <?php foreach ($courses_by_category as $category => $course_list): ?>
                                                    <optgroup label="<?php echo htmlspecialchars($category); ?>">
                                                        <?php foreach ($course_list as $course_name): ?>
                                                            <option value="<?php echo htmlspecialchars($course_name); ?>">
                                                                <?php echo htmlspecialchars($course_name); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </optgroup>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div>
                                            <label for="year" style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Year Level</label>
                                            <select id="year" name="year" required style="width: 100%; padding: 12px 16px; border: 2px solid #e9d5ff; border-radius: 10px; font-size: 1em; transition: all 0.3s;" onfocus="this.style.borderColor='var(--primary-color)'" onblur="this.style.borderColor='#e9d5ff'">
                                                <option value="">-- Select Year --</option>
                                                <option value="1">1st Year</option>
                                                <option value="2">2nd Year</option>
                                                <option value="3">3rd Year</option>
                                                <option value="4">4th Year</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer" style="padding: 24px 32px; border-top: 2px solid #e9d5ff; display: flex; gap: 12px; justify-content: flex-end; background: white; border-radius: 0 0 16px 16px;">
                                <button type="button" class="btn btn-secondary" onclick="closeRegisterModal()" style="padding: 12px 28px; background-color: #e9d5ff; color: var(--primary-color); border: none; border-radius: 10px; cursor: pointer; font-weight: 700; transition: all 0.3s; font-size: 1em;" onmouseover="this.style.backgroundColor='#ddd6fe'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 16px rgba(167, 139, 250, 0.3)'" onmouseout="this.style.backgroundColor='#e9d5ff'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">Cancel</button>
                                <button type="submit" class="btn btn-primary" style="padding: 12px 28px; background-color: var(--primary-color); color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 700; transition: all 0.3s; font-size: 1em;" onmouseover="this.style.backgroundColor='#9b59b6'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 16px rgba(167, 139, 250, 0.4)'" onmouseout="this.style.backgroundColor='var(--primary-color)'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">Register Student</button>
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
                        // Clear all error messages when closing
                        document.getElementById('student_id_error').style.display = 'none';
                        document.getElementById('firstname_error').style.display = 'none';
                        document.getElementById('lastname_error').style.display = 'none';
                        document.getElementById('middlename_error').style.display = 'none';
                        document.getElementById('email_error').style.display = 'none';
                        document.getElementById('guardian_name_error').style.display = 'none';
                    }

                    // Validate Student ID - numbers only, 8 digits
                    function validateStudentId() {
                        const input = document.getElementById('student_id');
                        const errorMsg = document.getElementById('student_id_error');
                        const value = input.value;

                        // Only allow numbers
                        input.value = value.replace(/[^0-9]/g, '');

                        if (input.value.length > 0 && input.value.length !== 8) {
                            errorMsg.textContent = 'Student ID must be exactly 8 digits';
                            errorMsg.style.display = 'block';
                            input.style.borderColor = '#ef4444';
                        } else if (input.value.length === 8) {
                            errorMsg.style.display = 'none';
                            input.style.borderColor = '#10b981';
                        } else {
                            errorMsg.style.display = 'none';
                            input.style.borderColor = '#e9d5ff';
                        }
                    }

                    // Validate letters only - no numbers or special characters
                    function validateLettersOnly(input, errorId) {
                        const errorMsg = document.getElementById(errorId);
                        const value = input.value;
                        const lettersOnlyPattern = /^[a-zA-Z\s\-']*$/;

                        if (value.length > 0 && !lettersOnlyPattern.test(value)) {
                            // Remove invalid characters
                            input.value = value.replace(/[^a-zA-Z\s\-']/g, '').toUpperCase();
                            errorMsg.textContent = 'Letters only (no numbers or special characters allowed)';
                            errorMsg.style.display = 'block';
                            input.style.borderColor = '#ef4444';
                        } else if (value.length > 0) {
                            errorMsg.style.display = 'none';
                            input.style.borderColor = '#10b981';
                        } else {
                            errorMsg.style.display = 'none';
                            input.style.borderColor = '#e9d5ff';
                        }
                    }

                    // Validate Email - must be @gmail.com only
                    function validateGmailOnly() {
                        const input = document.getElementById('email');
                        const errorMsg = document.getElementById('email_error');
                        const value = input.value;

                        if (value.length > 0) {
                            if (!value.endsWith('@gmail.com')) {
                                errorMsg.textContent = 'Email must be a @gmail.com address';
                                errorMsg.style.display = 'block';
                                input.style.borderColor = '#ef4444';
                            } else if (!value.match(/^[a-zA-Z0-9._%-]+@gmail\.com$/)) {
                                errorMsg.textContent = 'Invalid email format';
                                errorMsg.style.display = 'block';
                                input.style.borderColor = '#ef4444';
                            } else {
                                errorMsg.style.display = 'none';
                                input.style.borderColor = '#10b981';
                            }
                        } else {
                            errorMsg.style.display = 'none';
                            input.style.borderColor = '#e9d5ff';
                        }
                    }

                    // Validate entire form before submission
                    function validateStudentForm() {
                        const studentId = document.getElementById('student_id').value.trim();
                        const firstName = document.getElementById('firstname').value.trim();
                        const lastName = document.getElementById('lastname').value.trim();
                        const middleName = document.getElementById('middlename').value.trim();
                        const guardianName = document.getElementById('guardian_name').value.trim();
                        const email = document.getElementById('email').value.trim();
                        const course = document.getElementById('course').value.trim();
                        const year = document.getElementById('year').value.trim();
                        const address = document.getElementById('address').value.trim();

                        let isValid = true;
                        let errorMessages = [];

                        // Validate Student ID
                        if (!studentId) {
                            errorMessages.push('Student ID is required');
                            isValid = false;
                        } else if (!/^\d{8}$/.test(studentId)) {
                            errorMessages.push('Student ID must be exactly 8 digits');
                            isValid = false;
                        }

                        // Validate First Name
                        if (!firstName) {
                            errorMessages.push('First Name is required');
                            isValid = false;
                        } else if (!/^[a-zA-Z\s\-']+$/.test(firstName)) {
                            errorMessages.push('First Name must contain letters only');
                            isValid = false;
                        }

                        // Validate Last Name
                        if (!lastName) {
                            errorMessages.push('Last Name is required');
                            isValid = false;
                        } else if (!/^[a-zA-Z\s\-']+$/.test(lastName)) {
                            errorMessages.push('Last Name must contain letters only');
                            isValid = false;
                        }

                        // Validate Middle Name
                        if (!middleName) {
                            errorMessages.push('Middle Name is required');
                            isValid = false;
                        } else if (!/^[a-zA-Z\s\-']+$/.test(middleName)) {
                            errorMessages.push('Middle Name must contain letters only');
                            isValid = false;
                        }

                        // Validate Guardian Name
                        if (!guardianName) {
                            errorMessages.push('Guardian Name is required');
                            isValid = false;
                        } else if (!/^[a-zA-Z\s\-']+$/.test(guardianName)) {
                            errorMessages.push('Guardian Name must contain letters only');
                            isValid = false;
                        }

                        // Validate Email
                        if (!email) {
                            errorMessages.push('Email Address is required');
                            isValid = false;
                        } else if (!email.endsWith('@gmail.com')) {
                            errorMessages.push('Email must be a @gmail.com address');
                            isValid = false;
                        } else if (!/^[a-zA-Z0-9._%-]+@gmail\.com$/.test(email)) {
                            errorMessages.push('Invalid email format');
                            isValid = false;
                        }

                        // Validate Address
                        if (!address) {
                            errorMessages.push('Address is required');
                            isValid = false;
                        }

                        // Validate Course
                        if (!course) {
                            errorMessages.push('Course is required');
                            isValid = false;
                        }

                        // Validate Year
                        if (!year) {
                            errorMessages.push('Year Level is required');
                            isValid = false;
                        }

                        if (!isValid) {
                            alert('Please fix the following errors:\n\n' + errorMessages.join('\n'));
                            return false;
                        }

                        return true;
                    }

                    // Validate entire form before submission
                    function validateStudentForm() {
                        const studentId = document.getElementById('student_id').value.trim();
                        const firstName = document.getElementById('firstname').value.trim();
                        const lastName = document.getElementById('lastname').value.trim();
                        const middleName = document.getElementById('middlename').value.trim();
                        const guardianName = document.getElementById('guardian_name').value.trim();
                        const email = document.getElementById('email').value.trim();
                        const course = document.getElementById('course').value.trim();
                        const year = document.getElementById('year').value.trim();
                        const address = document.getElementById('address').value.trim();

                        let isValid = true;
                        let errorMessages = [];

                        // Validate Student ID
                        if (!studentId) {
                            errorMessages.push('• Student ID is required');
                            isValid = false;
                        } else if (!/^\d{8}$/.test(studentId)) {
                            errorMessages.push('• Student ID must be exactly 8 digits');
                            isValid = false;
                        }

                        // Validate First Name
                        if (!firstName) {
                            errorMessages.push('• First Name is required');
                            isValid = false;
                        } else if (!/^[a-zA-Z\s\-']+$/.test(firstName)) {
                            errorMessages.push('• First Name must contain letters only');
                            isValid = false;
                        }

                        // Validate Last Name
                        if (!lastName) {
                            errorMessages.push('• Last Name is required');
                            isValid = false;
                        } else if (!/^[a-zA-Z\s\-']+$/.test(lastName)) {
                            errorMessages.push('• Last Name must contain letters only');
                            isValid = false;
                        }

                        // Validate Middle Name
                        if (!middleName) {
                            errorMessages.push('• Middle Name is required');
                            isValid = false;
                        } else if (!/^[a-zA-Z\s\-']+$/.test(middleName)) {
                            errorMessages.push('• Middle Name must contain letters only');
                            isValid = false;
                        }

                        // Validate Guardian Name
                        if (!guardianName) {
                            errorMessages.push('• Guardian Name is required');
                            isValid = false;
                        } else if (!/^[a-zA-Z\s\-']+$/.test(guardianName)) {
                            errorMessages.push('• Guardian Name must contain letters only');
                            isValid = false;
                        }

                        // Validate Email
                        if (!email) {
                            errorMessages.push('• Email Address is required');
                            isValid = false;
                        } else if (!email.endsWith('@gmail.com')) {
                            errorMessages.push('• Email must be a @gmail.com address');
                            isValid = false;
                        } else if (!/^[a-zA-Z0-9._%-]+@gmail\.com$/.test(email)) {
                            errorMessages.push('• Invalid email format');
                            isValid = false;
                        }

                        // Validate Address
                        if (!address) {
                            errorMessages.push('• Address is required');
                            isValid = false;
                        }

                        // Validate Course
                        if (!course) {
                            errorMessages.push('• Course is required');
                            isValid = false;
                        }

                        // Validate Year
                        if (!year) {
                            errorMessages.push('• Year Level is required');
                            isValid = false;
                        }

                        if (!isValid) {
                            showValidationError(errorMessages);
                            return false;
                        }

                        return true;
                    }

                    // Show custom validation error modal
                    function showValidationError(errorMessages) {
                        const errorModal = document.createElement('div');
                        errorModal.id = 'validationErrorModal';
                        errorModal.style.cssText = `
                            position: fixed;
                            top: 0;
                            left: 0;
                            right: 0;
                            bottom: 0;
                            background: rgba(0, 0, 0, 0.5);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            z-index: 2000;
                            backdrop-filter: blur(8px);
                            animation: fadeIn 0.3s ease-out;
                        `;

                        const modalContent = document.createElement('div');
                        modalContent.style.cssText = `
                            background: white;
                            border-radius: 20px;
                            width: 100%;
                            max-width: 520px;
                            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.3);
                            overflow: hidden;
                            animation: slideUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
                        `;

                        modalContent.innerHTML = `
                            <div style="
                                position: relative;
                                padding: 0;
                                background: linear-gradient(135deg, #f3e8ff, #ede9fe);
                                border-bottom: 3px solid #e9d5ff;
                            ">
                                <div style="
                                    padding: 36px 40px;
                                    display: flex;
                                    align-items: center;
                                    justify-content: space-between;
                                ">
                                    <div style="flex: 1;">
                                        <h2 style="
                                            margin: 0 0 8px 0;
                                            color: #6b21a8;
                                            font-size: 1.4em;
                                            font-weight: 800;
                                            letter-spacing: -0.5px;
                                        ">Validation Error</h2>
                                        <p style="
                                            margin: 0;
                                            color: #7e22ce;
                                            font-size: 0.95em;
                                            font-weight: 500;
                                        ">Please review and correct the errors below</p>
                                    </div>
                                    <button onclick="document.getElementById('validationErrorModal').remove()" style="
                                        background: rgba(167, 139, 250, 0.15);
                                        border: none;
                                        color: #7e22ce;
                                        font-size: 24px;
                                        cursor: pointer;
                                        width: 38px;
                                        height: 38px;
                                        border-radius: 50%;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        transition: all 0.3s;
                                        line-height: 1;
                                        flex-shrink: 0;
                                        margin-left: 20px;
                                    " onmouseover="this.style.backgroundColor='rgba(167, 139, 250, 0.25); this.style.transform='rotate(90deg)'" onmouseout="this.style.backgroundColor='rgba(167, 139, 250, 0.15); this.style.transform='rotate(0deg)'">×</button>
                                </div>
                            </div>

                            <div style="padding: 32px 40px; background: white;">
                                <ul style="
                                    margin: 0;
                                    padding: 0;
                                    list-style: none;
                                ">
                                    ${errorMessages.map((msg, idx) => `
                                        <li style="
                                            color: #6b21a8;
                                            font-size: 1.02em;
                                            margin-bottom: ${idx === errorMessages.length - 1 ? '0' : '14px'};
                                            padding-left: 28px;
                                            position: relative;
                                            line-height: 1.6;
                                            font-weight: 500;
                                            animation: slideInLeft 0.4s ease-out ${0.1 * (idx + 1)}s backwards;
                                        ">
                                            <span style="
                                                position: absolute;
                                                left: 0;
                                                top: 5px;
                                                width: 8px;
                                                height: 8px;
                                                background: linear-gradient(135deg, #c084fc, #a78bfa);
                                                border-radius: 50%;
                                            "></span>
                                            ${msg.replace('• ', '')}
                                        </li>
                                    `).join('')}
                                </ul>
                            </div>

                            <div style="
                                padding: 24px 40px;
                                background: linear-gradient(135deg, #f3e8ff, #ede9fe);
                                border-top: 2px solid #e9d5ff;
                                display: flex;
                                gap: 12px;
                                justify-content: flex-end;
                            ">
                                <button onclick="document.getElementById('validationErrorModal').remove()" style="
                                    padding: 14px 36px;
                                    background: linear-gradient(135deg, #a78bfa, #9b59b6);
                                    color: white;
                                    border: none;
                                    border-radius: 12px;
                                    cursor: pointer;
                                    font-weight: 700;
                                    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
                                    font-size: 1.05em;
                                    letter-spacing: 0.3px;
                                    box-shadow: 0 6px 16px rgba(167, 139, 250, 0.3);
                                " onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 12px 28px rgba(167, 139, 250, 0.45)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 6px 16px rgba(167, 139, 250, 0.3)'">Got it</button>
                            </div>
                        `;

                        errorModal.appendChild(modalContent);
                        document.body.appendChild(errorModal);

                        // Add animations
                        const style = document.createElement('style');
                        style.textContent = `
                            @keyframes fadeIn {
                                from {
                                    opacity: 0;
                                }
                                to {
                                    opacity: 1;
                                }
                            }
                            @keyframes slideUp {
                                from {
                                    transform: translateY(30px);
                                    opacity: 0;
                                }
                                to {
                                    transform: translateY(0);
                                    opacity: 1;
                                }
                            }
                            @keyframes slideInLeft {
                                from {
                                    transform: translateX(-20px);
                                    opacity: 0;
                                }
                                to {
                                    transform: translateX(0);
                                    opacity: 1;
                                }
                            }
                        `;
                        document.head.appendChild(style);

                        errorModal.addEventListener('click', function(e) {
                            if (e.target === errorModal) {
                                errorModal.remove();
                            }
                        });

                        document.addEventListener('keydown', function handler(e) {
                            if (e.key === 'Escape') {
                                const modal = document.getElementById('validationErrorModal');
                                if (modal) modal.remove();
                                document.removeEventListener('keydown', handler);
                            }
                        });
                    }

                    // Close modal when clicking outside of it
                    window.onclick = function (event) {
                        var modal = document.getElementById('registerModal');
                        if (event.target == modal) {
                            modal.style.display = 'none';
                        }
                    }
                </script>
                <div id="updateModal" class="modal" style="display: none;">
                    <div class="modal-content" style="max-width: 600px; max-height: 90vh; overflow-y: auto; background: white; border-radius: 16px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);">
                        <div style="background: linear-gradient(135deg, var(--primary-color), #9b59b6); color: white; padding: 28px 32px; border-radius: 16px 16px 0 0; font-size: 1.4em; font-weight: 700; display: flex; justify-content: space-between; align-items: center; letter-spacing: 0.3px;">
                            <span>Update Student Information</span>
                            <button type="button" class="modal-close" onclick="closeUpdateModal()" style="background: rgba(255, 255, 255, 0.25); border: none; color: white; font-size: 28px; cursor: pointer; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.3s; line-height: 1;" onmouseover="this.style.backgroundColor='rgba(255, 255, 255, 0.35); this.style.transform='scale(1.1)'" onmouseout="this.style.backgroundColor='rgba(255, 255, 255, 0.25); this.style.transform='scale(1)'">&times;</button>
                        </div>
                        <form method="POST" id="updateForm">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" id="updateStudentId" value="">
                            <div style="padding: 40px 32px; background: #f8f6ff; display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                                <div class="form-group">
                                    <label for="updateStudentIdField" style="display: block; font-weight: 600; font-size: 0.9em; color: #374151; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Student ID</label>
                                    <input type="text" id="updateStudentIdField" name="student_id" required placeholder="ex: 000*****" maxlength="8" inputmode="numeric" style="width: 100%; padding: 12px 14px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1em; font-family: inherit; transition: all 0.3s; background: white;" onfocus="this.style.borderColor='var(--primary-color)'; this.style.boxShadow='0 0 0 3px rgba(127, 63, 198, 0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                                </div>

                                <div class="form-group">
                                    <label for="updateLastName" style="display: block; font-weight: 600; font-size: 0.9em; color: #374151; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Last Name</label>
                                    <input type="text" id="updateLastName" name="lastname" required placeholder="Enter last name" oninput="this.value = this.value.toUpperCase()" style="width: 100%; padding: 12px 14px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1em; font-family: inherit; transition: all 0.3s; background: white;" onfocus="this.style.borderColor='var(--primary-color)'; this.style.boxShadow='0 0 0 3px rgba(127, 63, 198, 0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                                </div>

                                <div class="form-group">
                                    <label for="updateFirstName" style="display: block; font-weight: 600; font-size: 0.9em; color: #374151; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">First Name</label>
                                    <input type="text" id="updateFirstName" name="firstname" required placeholder="Enter first name" oninput="this.value = this.value.toUpperCase()" style="width: 100%; padding: 12px 14px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1em; font-family: inherit; transition: all 0.3s; background: white;" onfocus="this.style.borderColor='var(--primary-color)'; this.style.boxShadow='0 0 0 3px rgba(127, 63, 198, 0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                                </div>

                                <div class="form-group">
                                    <label for="updateMiddleName" style="display: block; font-weight: 600; font-size: 0.9em; color: #374151; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Middle Name</label>
                                    <input type="text" id="updateMiddleName" name="middlename" required placeholder="Enter middle name" oninput="this.value = this.value.toUpperCase()" style="width: 100%; padding: 12px 14px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1em; font-family: inherit; transition: all 0.3s; background: white;" onfocus="this.style.borderColor='var(--primary-color)'; this.style.boxShadow='0 0 0 3px rgba(127, 63, 198, 0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                                </div>

                                <div class="form-group" style="grid-column: 1 / -1;">
                                    <label for="updateAddress" style="display: block; font-weight: 600; font-size: 0.9em; color: #374151; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Complete Address</label>
                                    <textarea id="updateAddress" name="address" required placeholder="Enter complete address" rows="3" style="width: 100%; padding: 12px 14px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1em; font-family: inherit; transition: all 0.3s; background: white; resize: vertical; min-height: 100px;" onfocus="this.style.borderColor='var(--primary-color)'; this.style.boxShadow='0 0 0 3px rgba(127, 63, 198, 0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'"></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="updateEmail" style="display: block; font-weight: 600; font-size: 0.9em; color: #374151; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Email Address</label>
                                    <input type="email" id="updateEmail" name="email" required placeholder="example@gmail.com" style="width: 100%; padding: 12px 14px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1em; font-family: inherit; transition: all 0.3s; background: white;" onfocus="this.style.borderColor='var(--primary-color)'; this.style.boxShadow='0 0 0 3px rgba(127, 63, 198, 0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                                </div>

                                <div class="form-group">
                                    <label for="updateGuardianName" style="display: block; font-weight: 600; font-size: 0.9em; color: #374151; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Guardian Name</label>
                                    <input type="text" id="updateGuardianName" name="guardian_name" required placeholder="Enter guardian name" oninput="this.value = this.value.toUpperCase()" style="width: 100%; padding: 12px 14px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1em; font-family: inherit; transition: all 0.3s; background: white;" onfocus="this.style.borderColor='var(--primary-color)'; this.style.boxShadow='0 0 0 3px rgba(127, 63, 198, 0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                                </div>

                                <div class="form-group" style="grid-column: 1 / -1;">
                                    <label for="updateCourse" style="display: block; font-weight: 600; font-size: 0.9em; color: #374151; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Course</label>
                                    <select id="updateCourse" name="course" required style="width: 100%; padding: 12px 14px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1em; font-family: inherit; transition: all 0.3s; background: white;" onfocus="this.style.borderColor='var(--primary-color)'; this.style.boxShadow='0 0 0 3px rgba(127, 63, 198, 0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                                        <option value="">-- Select Course --</option>
                                        <?php foreach ($courses_by_category as $category => $course_list): ?>
                                            <optgroup label="<?php echo htmlspecialchars($category); ?>">
                                                <?php foreach ($course_list as $course_name): ?>
                                                    <option value="<?php echo htmlspecialchars($course_name); ?>">
                                                        <?php echo htmlspecialchars($course_name); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="updateYear" style="display: block; font-weight: 600; font-size: 0.9em; color: #374151; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Year Level</label>
                                    <select id="updateYear" name="year" required style="width: 100%; padding: 12px 14px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1em; font-family: inherit; transition: all 0.3s; background: white;" onfocus="this.style.borderColor='var(--primary-color)'; this.style.boxShadow='0 0 0 3px rgba(127, 63, 198, 0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                                        <option value="">-- Select Year --</option>
                                        <option value="1">1st Year</option>
                                        <option value="2">2nd Year</option>
                                        <option value="3">3rd Year</option>
                                        <option value="4">4th Year</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="updateStatus" style="display: block; font-weight: 600; font-size: 0.9em; color: #374151; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Status</label>
                                    <select id="updateStatus" name="status" required style="width: 100%; padding: 12px 14px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1em; font-family: inherit; transition: all 0.3s; background: white;" onfocus="this.style.borderColor='var(--primary-color)'; this.style.boxShadow='0 0 0 3px rgba(127, 63, 198, 0.1)'" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                                        <option value="">-- Select Status --</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div style="padding: 24px 32px; border-top: 2px solid #e9d5ff; display: flex; gap: 12px; justify-content: flex-end; background: white;">
                                <button type="button" class="btn btn-secondary" onclick="closeUpdateModal()" style="padding: 12px 28px; background-color: #e9d5ff; color: var(--primary-color); border: none; border-radius: 10px; cursor: pointer; font-weight: 700; transition: all 0.3s; font-size: 1em;" onmouseover="this.style.backgroundColor='#ddd6fe'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 16px rgba(167, 139, 250, 0.3)'" onmouseout="this.style.backgroundColor='#e9d5ff'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">Cancel</button>
                                <button type="submit" class="btn btn-primary" style="padding: 12px 28px; background: linear-gradient(135deg, var(--primary-color), #9b59b6); color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 700; transition: all 0.3s; font-size: 1em;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 16px rgba(127, 63, 198, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">Update Student</button>
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
                            <table class="table" id="studentsTable" style="font-size: 0.9em;">
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