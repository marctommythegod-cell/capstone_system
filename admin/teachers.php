<?php
// admin/teachers.php - Manage Teachers

require_once '../includes/session_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

if ($_SESSION['user_role'] !== 'admin') {
    redirect('/CLASS_CARD_DROPPING_SYSTEM/index.php');
}

$admin_name = getUserName($pdo, $_SESSION['user_id']);
$user_info = getUserInfo($pdo, $_SESSION['user_id']);

// Handle teacher registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $teacher_id = trim($_POST['teacher_id'] ?? '');
        $lastname = trim($_POST['lastname'] ?? '');
        $firstname = trim($_POST['firstname'] ?? '');
        $middlename = trim($_POST['middlename'] ?? '');
        $name = $lastname . ', ' . $firstname . ', ' . $middlename;
        $address = trim($_POST['address'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');
        
        // Validation checks
        $errors = [];
        
        // Check for empty fields
        if (empty($teacher_id)) {
            $errors[] = 'Teacher ID is required.';
        } elseif (!preg_match('/^\d+$/', $teacher_id)) {
            $errors[] = 'Teacher ID must contain only numbers.';
        } elseif (strlen($teacher_id) < 2) {
            $errors[] = 'Teacher ID must be at least 2 characters.';
        } elseif (strlen($teacher_id) > 50) {
            $errors[] = 'Teacher ID must not exceed 50 characters.';
        }
        
        if (empty($lastname)) {
            $errors[] = 'Last name is required.';
        } elseif (strlen($lastname) < 2) {
            $errors[] = 'Last name must be at least 2 characters.';
        } elseif (strlen($lastname) > 100) {
            $errors[] = 'Last name must not exceed 100 characters.';
        } elseif (!preg_match("/^[a-zA-Z\s\-']+$/", $lastname)) {
            $errors[] = 'Last name must contain only letters, spaces, hyphens, and apostrophes.';
        }
        
        if (empty($firstname)) {
            $errors[] = 'First name is required.';
        } elseif (strlen($firstname) < 2) {
            $errors[] = 'First name must be at least 2 characters.';
        } elseif (strlen($firstname) > 100) {
            $errors[] = 'First name must not exceed 100 characters.';
        } elseif (!preg_match("/^[a-zA-Z\s\-']+$/", $firstname)) {
            $errors[] = 'First name must contain only letters, spaces, hyphens, and apostrophes.';
        }
        
        if (empty($middlename)) {
            $errors[] = 'Middle name is required.';
        } elseif (strlen($middlename) < 2) {
            $errors[] = 'Middle name must be at least 2 characters.';
        } elseif (strlen($middlename) > 100) {
            $errors[] = 'Middle name must not exceed 100 characters.';
        } elseif (!preg_match("/^[a-zA-Z\s\-']+$/", $middlename)) {
            $errors[] = 'Middle name must contain only letters, spaces, hyphens, and apostrophes.';
        }
        
        if (empty($address)) {
            $errors[] = 'Complete address is required.';
        } elseif (strlen($address) < 5) {
            $errors[] = 'Complete address must be at least 5 characters.';
        } elseif (strlen($address) > 255) {
            $errors[] = 'Complete address must not exceed 255 characters.';
        }
        
        if (empty($department)) {
            $errors[] = 'Department is required.';
        } else {
            // Valid departments (courses) for teachers
            $valid_departments = [
                'BS in Civil Engineering (BSCE)',
                'BS in Electrical Engineering (BSEE)',
                'BS in Mechanical Engineering (BSME)',
                'BS in Criminology (BSCrim)',
                'BS in Information Technology (BSIT)',
                'BS in Computer Science (BSCS)',
                'Bachelor of Elementary Education (BEEd)',
                'Bachelor of Secondary Education (BSEd)',
                'BS in Business Administration (BSBA)',
                'BS in Hospitality Management (BSHM)'
            ];
            
            if (!in_array($department, $valid_departments)) {
                $errors[] = 'Department must be a valid course.';
            }
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
                $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, teacher_id, address, department, password_changed) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$name, $email, $hashed_password, 'teacher', $teacher_id, $address, $department, 0]);
                setMessage('success', 'Teacher added successfully.');
            } catch (Exception $e) {
                setMessage('error', 'Error adding teacher: ' . $e->getMessage());
            }
        }
        redirect('/CLASS_CARD_DROPPING_SYSTEM/admin/teachers.php');
    } elseif ($_POST['action'] === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $teacher_id = trim($_POST['teacher_id'] ?? '');
        $lastname = trim($_POST['lastname'] ?? '');
        $firstname = trim($_POST['firstname'] ?? '');
        $middlename = trim($_POST['middlename'] ?? '');
        $name = $lastname . ', ' . $firstname . ', ' . $middlename;
        $address = trim($_POST['address'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $status = trim($_POST['status'] ?? '');

        $errors = [];

        if (!$id) {
            $errors[] = 'Invalid teacher record.';
        }
        if (empty($teacher_id)) {
            $errors[] = 'Teacher ID is required.';
        }
        if (empty($lastname)) {
            $errors[] = 'Last name is required.';
        }
        if (empty($firstname)) {
            $errors[] = 'First name is required.';
        }
        if (empty($email)) {
            $errors[] = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        if (empty($department)) {
            $errors[] = 'Department is required.';
        } else {
            // Valid departments (courses) for teachers
            $valid_departments = [
                'BS in Civil Engineering (BSCE)',
                'BS in Electrical Engineering (BSEE)',
                'BS in Mechanical Engineering (BSME)',
                'BS in Criminology (BSCrim)',
                'BS in Information Technology (BSIT)',
                'BS in Computer Science (BSCS)',
                'Bachelor of Elementary Education (BEEd)',
                'Bachelor of Secondary Education (BSEd)',
                'BS in Business Administration (BSBA)',
                'BS in Hospitality Management (BSHM)'
            ];
            
            if (!in_array($department, $valid_departments)) {
                $errors[] = 'Department must be a valid course.';
            }
        }
        
        if (!in_array($status, ['active', 'inactive'])) {
            $errors[] = 'Invalid status.';
        }

        // Check for duplicate email (excluding current teacher)
        if (empty($errors)) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                $errors[] = 'This email is already registered to another user.';
            }
        }

        if (!empty($errors)) {
            setMessage('error', implode('<br>', $errors));
        } else {
            try {
                $stmt = $pdo->prepare('UPDATE users SET teacher_id = ?, name = ?, email = ?, address = ?, department = ?, status = ? WHERE id = ? AND role = "teacher"');
                $stmt->execute([$teacher_id, $name, $email, $address, $department, $status, $id]);
                setMessage('success', 'Teacher updated successfully.');
            } catch (Exception $e) {
                setMessage('error', 'Error updating teacher: ' . $e->getMessage());
            }
        }
        redirect('/CLASS_CARD_DROPPING_SYSTEM/admin/teachers.php');
    } elseif ($_POST['action'] === 'import_teachers') {
        $csv_data = $_POST['csv_data'] ?? '';
        if (empty($csv_data)) {
            setMessage('error', 'No data to import. Please select a file first.');
            redirect('/CLASS_CARD_DROPPING_SYSTEM/admin/teachers.php');
        }

        $lines = array_filter(explode("\n", $csv_data), function($line) {
            return trim($line) !== '';
        });
        $lines = array_values($lines);

        if (count($lines) < 2) {
            setMessage('error', 'The file contains no data rows.');
            redirect('/CLASS_CARD_DROPPING_SYSTEM/admin/teachers.php');
        }

        // Remove header row
        array_shift($lines);

        $imported = 0;
        $skipped = 0;
        $import_errors = [];

        foreach ($lines as $index => $line) {
            $row = str_getcsv($line);
            if (count($row) < 8) {
                $skipped++;
                $import_errors[] = "Row " . ($index + 2) . ": Incomplete data (expected 8 columns, got " . count($row) . ").";
                continue;
            }

            $teacher_id = trim($row[0]);
            $lastname = trim($row[1]);
            $firstname = trim($row[2]);
            $middlename = trim($row[3]);
            $name = $lastname . ', ' . $firstname . ', ' . $middlename;
            $address = trim($row[4]);
            $email = trim($row[5]);
            $department = trim($row[6]);
            $password = trim($row[7]);

            // Basic validation
            if (empty($teacher_id) || empty($lastname) || empty($firstname) || empty($email) || empty($password)) {
                $skipped++;
                $import_errors[] = "Row " . ($index + 2) . ": Missing required fields (teacher_id, lastname, firstname, email, or password).";
                continue;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                $import_errors[] = "Row " . ($index + 2) . ": Invalid email format '$email'.";
                continue;
            }

            // Check duplicate email
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $skipped++;
                $import_errors[] = "Row " . ($index + 2) . ": Email '$email' already exists.";
                continue;
            }

            try {
                $hashed_password = securePassword($password);
                $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, teacher_id, address, department, password_changed) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$name, $email, $hashed_password, 'teacher', $teacher_id, $address, $department, 0]);
                $imported++;
            } catch (Exception $e) {
                $skipped++;
                $import_errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }

        $msg = "Import complete: <strong>$imported</strong> teacher(s) imported successfully";
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
        redirect('/CLASS_CARD_DROPPING_SYSTEM/admin/teachers.php');
    }
}

// Get department filter from query parameter
$dept_filter = isset($_GET['department']) ? trim($_GET['department']) : null;

// Get total count and pagination data
$count_query = 'SELECT COUNT(*) as total FROM users WHERE role = "teacher"';
$count_params = [];

if ($dept_filter) {
    $count_query .= ' AND department = ?';
    $count_params[] = $dept_filter;
}

$stmt = $pdo->prepare($count_query);
$stmt->execute($count_params);
$total_teachers_count = $stmt->fetch()['total'];

$pagination = getPaginationData($total_teachers_count, 15); // 15 items per page

// Fetch paginated teachers
$query = 'SELECT id, teacher_id, name, email, address, department, status, created_at FROM users WHERE role = "teacher"';
$query_params = [];

if ($dept_filter) {
    $query .= ' AND department = ?';
    $query_params[] = $dept_filter;
}

$query .= ' ORDER BY name LIMIT ' . intval($pagination['limit']) . ' OFFSET ' . intval($pagination['offset']);

$stmt = $pdo->prepare($query);
$stmt->execute($query_params);
$teachers = $stmt->fetchAll();

// Get distinct departments for submenu
$dept_stmt = $pdo->prepare('SELECT DISTINCT department FROM users WHERE role = "teacher" AND department IS NOT NULL AND department != "" ORDER BY department');
$dept_stmt->execute();
$departments = $dept_stmt->fetchAll();

$message = getMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers - PhilCST</title>
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
                <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/students.php" class="nav-item">
                    <span>Manage Students</span>
                </a>
                <div class="nav-item submenu-trigger active" onclick="toggleSubmenu(this)">
                    <span>Manage Teachers</span>
                </div>
                <div class="submenu active" id="teacherSubmenu">
                    <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/teachers.php" class="submenu-item <?php echo !$dept_filter ? 'active' : ''; ?>">All Teachers</a>
                    <div style="padding: 8px 16px; color: rgba(255, 255, 255, 0.6); font-size: 0.75em; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; border-top: 1px solid rgba(255, 255, 255, 0.1);">Departments</div>
                    <?php foreach ($departments as $dept): ?>
                    <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/teachers.php?department=<?php echo urlencode($dept['department']); ?>" class="submenu-item" style="padding-left: 40px; <?php echo $dept_filter === $dept['department'] ? 'background-color: rgba(167, 139, 250, 0.25); color: #c4b5fd;' : ''; ?>"><?php echo htmlspecialchars($dept['department']); ?></a>
                    <?php endforeach; ?>
                </div>
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
                    <button type="button" class="btn btn-primary" onclick="openRegisterModal()">Register Teacher</button>
                    <button type="button" class="btn btn-success" onclick="openImportModal()">Import from CSV/Excel</button>
                </div>
                
                <!-- Register Modal -->
                <div id="registerModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.6); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
                    <div class="modal-content" style="background: white; border-radius: 16px; width: 100%; max-width: 900px; max-height: 85vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);">
                        <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color), #9b59b6); color: white; padding: 28px 32px; border-radius: 16px 16px 0 0; font-size: 1.4em; font-weight: 700; display: flex; justify-content: space-between; align-items: center; letter-spacing: 0.3px;">
                            <span>Register New Teacher</span>
                            <button type="button" class="modal-close" onclick="closeRegisterModal()" style="background: rgba(255, 255, 255, 0.25); border: none; color: white; font-size: 28px; cursor: pointer; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.3s; line-height: 1;" onmouseover="this.style.backgroundColor='rgba(255, 255, 255, 0.35); this.style.transform='scale(1.1)'" onmouseout="this.style.backgroundColor='rgba(255, 255, 255, 0.25); this.style.transform='scale(1)'">&times;</button>
                        </div>
                        <form method="POST" class="teacher-form" id="teacherForm" onsubmit="return validateTeacherForm()">
                            <input type="hidden" name="action" value="add">
                            <div class="modal-body" style="padding: 40px 32px; background: #f8f6ff; display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                                <div style="grid-column: 1 / 2;">
                                    <div class="form-group" style="margin-bottom: 24px;">
                                        <label for="teacher_id" style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Teacher ID</label>
                                        <input type="text" id="teacher_id" name="teacher_id" required placeholder="Enter teacher ID (8 digits)" oninput="validateTeacherId()" style="width: 100%; padding: 12px 16px; border: 2px solid #e9d5ff; border-radius: 10px; font-size: 1em; transition: all 0.3s;" onfocus="this.style.borderColor='var(--primary-color)'" onblur="this.style.borderColor='#e9d5ff'">
                                        <div id="teacher_id_error" style="color: #ef4444; font-size: 0.9em; margin-top: 8px; display: none;"></div>
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 24px;">
                                        <label for="firstname" style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">First Name</label>
                                        <input type="text" id="firstname" name="firstname" required placeholder="Letters only" oninput="validateLettersOnly(this, 'firstname_error'); this.value = this.value.toUpperCase()" style="width: 100%; padding: 12px 16px; border: 2px solid #e9d5ff; border-radius: 10px; font-size: 1em; transition: all 0.3s;" onfocus="this.style.borderColor='var(--primary-color)'" onblur="this.style.borderColor='#e9d5ff'">
                                        <div id="firstname_error" style="color: #ef4444; font-size: 0.9em; margin-top: 8px; display: none;"></div>
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 24px;">
                                        <label for="middlename" style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Middle Name</label>
                                        <input type="text" id="middlename" name="middlename" required placeholder="Letters only" oninput="validateLettersOnly(this, 'middlename_error'); this.value = this.value.toUpperCase()" style="width: 100%; padding: 12px 16px; border: 2px solid #e9d5ff; border-radius: 10px; font-size: 1em; transition: all 0.3s;" onfocus="this.style.borderColor='var(--primary-color)'" onblur="this.style.borderColor='#e9d5ff'">
                                        <div id="middlename_error" style="color: #ef4444; font-size: 0.9em; margin-top: 8px; display: none;"></div>
                                    </div>

                                    <div class="form-group">
                                        <label for="email" style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Email Address</label>
                                        <input type="text" id="email" name="email" required placeholder="example@gmail.com" oninput="validateGmailOnly()" style="width: 100%; padding: 12px 16px; border: 2px solid #e9d5ff; border-radius: 10px; font-size: 1em; transition: all 0.3s;" onfocus="this.style.borderColor='var(--primary-color)'" onblur="this.style.borderColor='#e9d5ff'">
                                        <div id="email_error" style="color: #ef4444; font-size: 0.9em; margin-top: 8px; display: none;"></div>
                                    </div>
                                </div>

                                <div style="grid-column: 2 / 3;">
                                    <div class="form-group" style="margin-bottom: 24px;">
                                        <label for="lastname" style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Last Name</label>
                                        <input type="text" id="lastname" name="lastname" required placeholder="Letters only" oninput="validateLettersOnly(this, 'lastname_error'); this.value = this.value.toUpperCase()" style="width: 100%; padding: 12px 16px; border: 2px solid #e9d5ff; border-radius: 10px; font-size: 1em; transition: all 0.3s;" onfocus="this.style.borderColor='var(--primary-color)'" onblur="this.style.borderColor='#e9d5ff'">
                                        <div id="lastname_error" style="color: #ef4444; font-size: 0.9em; margin-top: 8px; display: none;"></div>
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 24px;">
                                        <label for="address" style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Complete Address</label>
                                        <textarea id="address" name="address" required placeholder="Enter complete address" rows="2" style="width: 100%; padding: 12px 16px; border: 2px solid #e9d5ff; border-radius: 10px; font-size: 1em; transition: all 0.3s; font-family: inherit; resize: vertical;" onfocus="this.style.borderColor='var(--primary-color)'" onblur="this.style.borderColor='#e9d5ff'"></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="department" style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Department</label>
                                        <select id="department" name="department" required style="width: 100%; padding: 12px 16px; border: 2px solid #e9d5ff; border-radius: 10px; font-size: 1em; transition: all 0.3s;" onfocus="this.style.borderColor='var(--primary-color)'" onblur="this.style.borderColor='#e9d5ff'">
                                            <option value="">-- Select Department --</option>
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
                                </div>

                                <div style="grid-column: 1 / -1;">
                                    <div style="background: linear-gradient(135deg, rgba(167, 139, 250, 0.1), rgba(155, 89, 182, 0.05)); padding: 24px; border-radius: 14px; border-left: 5px solid var(--primary-color);">
                                        <h3 style="color: var(--primary-color); margin: 0 0 20px 0; font-size: 1.2em; font-weight: 700;">Security Credentials</h3>
                                        <div class="form-group" style="margin-bottom: 20px;">
                                            <label for="password" style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Password</label>
                                            <div style="display: flex; gap: 10px; align-items: flex-start;">
                                                <div class="password-input-wrapper" style="flex: 1;">
                                                    <input type="password" id="password" name="password" required minlength="6" placeholder="Put a strong password here" oninput="checkPasswordStrength(this.value)" style="width: 100%; padding: 12px 16px; border: 2px solid #e9d5ff; border-radius: 10px; font-size: 1em; transition: all 0.3s;" onfocus="this.style.borderColor='var(--primary-color)'" onblur="this.style.borderColor='#e9d5ff'">
                                                    <button type="button" class="password-toggle" onclick="togglePassword('password')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #6b7280;">
                                                        <svg class="eye-icon eye-show" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                                        <svg class="eye-icon eye-hide" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                                                    </button>
                                                </div>
                                                <button type="button" class="btn btn-secondary" onclick="generatePassword()" style="padding: 12px 20px; font-size: 0.9em; background-color: #e9d5ff; color: var(--primary-color); border: none; border-radius: 10px; cursor: pointer; font-weight: 700; transition: all 0.3s;" onmouseover="this.style.backgroundColor='#ddd6fe'" onmouseout="this.style.backgroundColor='#e9d5ff'">Generate</button>
                                            </div>
                                            <small id="password-requirements" style="display: block; margin-top: 10px; color: #666; font-size: 0.9em;">
                                                <strong>Password Requirements:</strong><br>
                                                • At least 6–8 characters long<br>
                                                • At least one uppercase letter (A–Z)<br>
                                                • At least one lowercase letter (a–z)<br>
                                                • At least one number (0–9)<br>
                                                • At least one special character (!, @, #, $, %)
                                            </small>
                                            <div id="password-strength" style="margin-top: 10px; font-size: 0.85em;"></div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="confirm_password" style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Confirm Password</label>
                                            <div class="password-input-wrapper" style="position: relative;">
                                                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Re-enter your password" style="width: 100%; padding: 12px 16px; border: 2px solid #e9d5ff; border-radius: 10px; font-size: 1em; transition: all 0.3s;" onfocus="this.style.borderColor='var(--primary-color)'" onblur="this.style.borderColor='#e9d5ff'">
                                                <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #6b7280;">
                                                    <svg class="eye-icon eye-show" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                                    <svg class="eye-icon eye-hide" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                                                </button>
                                            </div>
                                            <div id="confirm-match" style="margin-top: 10px; font-size: 0.85em;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer" style="padding: 24px 32px; border-top: 2px solid #e9d5ff; display: flex; gap: 12px; justify-content: flex-end; background: white; border-radius: 0 0 16px 16px;">
                                <button type="button" class="btn btn-secondary" onclick="closeRegisterModal()" style="padding: 12px 28px; background-color: #e9d5ff; color: var(--primary-color); border: none; border-radius: 10px; cursor: pointer; font-weight: 700; transition: all 0.3s; font-size: 1em;" onmouseover="this.style.backgroundColor='#ddd6fe'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 16px rgba(167, 139, 250, 0.3)'" onmouseout="this.style.backgroundColor='#e9d5ff'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">Cancel</button>
                                <button type="submit" class="btn btn-primary" style="padding: 12px 28px; background-color: var(--primary-color); color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 700; transition: all 0.3s; font-size: 1em;" onmouseover="this.style.backgroundColor='#9b59b6'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 16px rgba(167, 139, 250, 0.4)'" onmouseout="this.style.backgroundColor='var(--primary-color)'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">Register Teacher</button>
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
                        background-color: rgba(0,0,0,0.5);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    .modal-content {
                        background-color: white;
                        border-radius: 8px;
                        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
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
                        // Clear all error messages
                        document.getElementById('teacher_id_error').style.display = 'none';
                        document.getElementById('firstname_error').style.display = 'none';
                        document.getElementById('lastname_error').style.display = 'none';
                        document.getElementById('middlename_error').style.display = 'none';
                        document.getElementById('email_error').style.display = 'none';
                        // Reset borders
                        document.getElementById('teacher_id').style.borderColor = '#e9d5ff';
                        document.getElementById('firstname').style.borderColor = '#e9d5ff';
                        document.getElementById('lastname').style.borderColor = '#e9d5ff';
                        document.getElementById('middlename').style.borderColor = '#e9d5ff';
                        document.getElementById('email').style.borderColor = '#e9d5ff';
                    }
                </script>
                
                <!-- Update Teacher Modal -->
                <div id="updateModal" class="modal" style="display: none;">
                    <div class="modal-content" style="max-width: 700px; max-height: 90vh; overflow-y: auto; border-radius: 16px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);">
                        <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color), #9b59b6); color: white; padding: 28px 32px; border-radius: 16px 16px 0 0; display: flex; justify-content: space-between; align-items: center;">
                            <h2 style="margin: 0; font-size: 1.4em; font-weight: 600;">Update Teacher</h2>
                            <button type="button" class="modal-close" onclick="closeUpdateModal()" style="background: none; border: none; color: white; font-size: 28px; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">&times;</button>
                        </div>
                        <form method="POST" id="updateForm">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" id="updateId" value="">
                            <div class="modal-body" style="padding: 40px 32px; background: #f8f6ff; display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                                <div class="form-group">
                                    <label for="updateTeacherId" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 0.95em;">Teacher ID</label>
                                    <input type="text" id="updateTeacherId" name="teacher_id" required placeholder="Enter teacher ID" style="width: 100%; padding: 12px 14px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1em; font-family: inherit; transition: all 0.3s ease;" onfocus="this.style.borderColor = 'var(--primary-color)'; this.style.boxShadow = '0 0 0 3px rgba(127, 63, 198, 0.1)';" onblur="this.style.borderColor = '#e5e7eb'; this.style.boxShadow = 'none';">
                                </div>
                                <div class="form-group">
                                    <label for="updateLastname" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 0.95em;">Last Name</label>
                                    <input type="text" id="updateLastname" name="lastname" required placeholder="Enter last name" oninput="this.value = this.value.toUpperCase()" style="width: 100%; padding: 12px 14px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1em; font-family: inherit; transition: all 0.3s ease;" onfocus="this.style.borderColor = 'var(--primary-color)'; this.style.boxShadow = '0 0 0 3px rgba(127, 63, 198, 0.1)';" onblur="this.style.borderColor = '#e5e7eb'; this.style.boxShadow = 'none';">
                                </div>
                                <div class="form-group">
                                    <label for="updateFirstname" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 0.95em;">First Name</label>
                                    <input type="text" id="updateFirstname" name="firstname" required placeholder="Enter first name" oninput="this.value = this.value.toUpperCase()" style="width: 100%; padding: 12px 14px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1em; font-family: inherit; transition: all 0.3s ease;" onfocus="this.style.borderColor = 'var(--primary-color)'; this.style.boxShadow = '0 0 0 3px rgba(127, 63, 198, 0.1)';" onblur="this.style.borderColor = '#e5e7eb'; this.style.boxShadow = 'none';">
                                </div>
                                <div class="form-group">
                                    <label for="updateMiddlename" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 0.95em;">Middle Name</label>
                                    <input type="text" id="updateMiddlename" name="middlename" required placeholder="Enter middle name" oninput="this.value = this.value.toUpperCase()" style="width: 100%; padding: 12px 14px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1em; font-family: inherit; transition: all 0.3s ease;" onfocus="this.style.borderColor = 'var(--primary-color)'; this.style.boxShadow = '0 0 0 3px rgba(127, 63, 198, 0.1)';" onblur="this.style.borderColor = '#e5e7eb'; this.style.boxShadow = 'none';">
                                </div>
                                <div class="form-group" style="grid-column: 1 / -1;">
                                    <label for="updateAddress" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 0.95em;">Complete Address</label>
                                    <textarea id="updateAddress" name="address" required placeholder="Enter complete address" rows="3" style="width: 100%; padding: 12px 14px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1em; font-family: inherit; transition: all 0.3s ease; resize: vertical;" onfocus="this.style.borderColor = 'var(--primary-color)'; this.style.boxShadow = '0 0 0 3px rgba(127, 63, 198, 0.1)';" onblur="this.style.borderColor = '#e5e7eb'; this.style.boxShadow = 'none';"></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="updateEmail" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 0.95em;">Email Address</label>
                                    <input type="email" id="updateEmail" name="email" required placeholder="example@gmail.com" style="width: 100%; padding: 12px 14px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1em; font-family: inherit; transition: all 0.3s ease;" onfocus="this.style.borderColor = 'var(--primary-color)'; this.style.boxShadow = '0 0 0 3px rgba(127, 63, 198, 0.1)';" onblur="this.style.borderColor = '#e5e7eb'; this.style.boxShadow = 'none';">
                                </div>
                                <div class="form-group">
                                    <label for="updateDepartment" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 0.95em;">Department</label>
                                    <select id="updateDepartment" name="department" required style="width: 100%; padding: 12px 14px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1em; font-family: inherit; transition: all 0.3s ease; cursor: pointer;" onfocus="this.style.borderColor = 'var(--primary-color)'; this.style.boxShadow = '0 0 0 3px rgba(127, 63, 198, 0.1)';" onblur="this.style.borderColor = '#e5e7eb'; this.style.boxShadow = 'none';">
                                        <option value="">-- Select Department --</option>
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
                                    <label for="updateStatus" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 0.95em;">Status</label>
                                    <select id="updateStatus" name="status" required style="width: 100%; padding: 12px 14px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1em; font-family: inherit; transition: all 0.3s ease; cursor: pointer;" onfocus="this.style.borderColor = 'var(--primary-color)'; this.style.boxShadow = '0 0 0 3px rgba(127, 63, 198, 0.1)';" onblur="this.style.borderColor = '#e5e7eb'; this.style.boxShadow = 'none';">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer" style="padding: 20px 32px; border-top: 1px solid #e5e7eb; display: flex; gap: 12px; justify-content: flex-end; background: white; border-radius: 0 0 16px 16px;">
                                <button type="button" class="btn btn-secondary" onclick="closeUpdateModal()" style="padding: 10px 24px; border: 2px solid #e5e7eb; border-radius: 10px; background: white; color: #666; font-weight: 600; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.background = '#f5f5f5'; this.style.borderColor = '#d0d0d0';" onmouseout="this.style.background = 'white'; this.style.borderColor = '#e5e7eb';">Cancel</button>
                                <button type="submit" class="btn btn-primary" style="padding: 10px 28px; background: linear-gradient(135deg, var(--primary-color), #9b59b6); color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.transform = 'translateY(-2px)'; this.style.boxShadow = '0 6px 20px rgba(127, 63, 198, 0.4)';" onmouseout="this.style.transform = 'translateY(0)'; this.style.boxShadow = 'none';">Update Teacher</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <script>
                    function openUpdateModal(id, teacherId, lastName, firstName, middleName, address, email, department, status) {
                        document.getElementById('updateId').value = id;
                        document.getElementById('updateTeacherId').value = teacherId;
                        document.getElementById('updateLastname').value = lastName;
                        document.getElementById('updateFirstname').value = firstName;
                        document.getElementById('updateMiddlename').value = middleName;
                        document.getElementById('updateAddress').value = address;
                        document.getElementById('updateEmail').value = email;
                        document.getElementById('updateDepartment').value = department;
                        document.getElementById('updateStatus').value = status;
                        document.getElementById('updateModal').style.display = 'flex';
                    }
                    
                    function closeUpdateModal() {
                        document.getElementById('updateModal').style.display = 'none';
                    }
                    
                    // Close modal when clicking outside of it
                    window.onclick = function(event) {
                        var registerModal = document.getElementById('registerModal');
                        var updateModal = document.getElementById('updateModal');
                        var importModal = document.getElementById('importModal');
                        if (event.target == registerModal) registerModal.style.display = 'none';
                        if (event.target == updateModal) updateModal.style.display = 'none';
                        if (event.target == importModal) closeImportModal();
                    }
                </script>

                <!-- Import Teachers Modal -->
                <div id="importModal" class="modal" style="display: none;">
                    <div class="modal-content" style="max-width: 900px; max-height: 90vh; overflow-y: auto;">
                        <div class="modal-header">
                            <h2>Import Teachers from CSV / Excel</h2>
                            <button type="button" class="modal-close" onclick="closeImportModal()">&times;</button>
                        </div>
                        <div class="modal-body" style="padding: 20px;">
                            <div class="import-info-box">
                                <p><strong>Instructions:</strong></p>
                                <ol style="margin: 8px 0 0 20px; line-height: 1.8;">
                                    <li>Download the CSV template and fill in the teacher data</li>
                                    <li>Save as <strong>.csv</strong> or <strong>.xlsx</strong> format</li>
                                    <li>Upload the file below and review the preview</li>
                                    <li>Click <strong>Import</strong> to add the teachers</li>
                                </ol>
                                <p style="margin-top: 10px; font-size: 0.9em; color: #666;">
                                    <strong>Required columns:</strong> Teacher ID, Last Name, First Name, Middle Name, Address, Email, Department, Password
                                </p>
                                <div class="alert alert-warning" style="margin-top: 10px; padding: 10px; font-size: 0.88em;">
                                    <strong>Note:</strong> Passwords in the file should meet the requirements (6+ chars, uppercase, lowercase, number, special character). Each teacher will use the password provided in the file for their initial login.
                                </div>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="downloadTeacherTemplate()" style="margin-top: 10px;">Download CSV Template</button>
                            </div>

                            <div class="import-dropzone" id="teacherDropzone" onclick="document.getElementById('importFileTeacher').click()">
                                <div class="dropzone-content">
                                    <div class="dropzone-icon"></div>
                                    <p><strong>Click to browse</strong> or drag & drop your file here</p>
                                    <p class="dropzone-hint">Accepts .csv, .xlsx, .xls files</p>
                                </div>
                                <input type="file" id="importFileTeacher" accept=".csv,.xlsx,.xls" style="display:none" onchange="handleTeacherFile(this)">
                            </div>

                            <div id="importPreviewTeacher" style="display: none;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin: 15px 0 10px;">
                                    <h3>Data Preview <span id="previewCountTeacher" style="font-weight: normal; font-size: 0.85em; color: #666;"></span></h3>
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="clearImportTeacher()">✕ Clear</button>
                                </div>
                                <div class="table-responsive" style="max-height: 350px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px;">
                                    <table class="table" id="previewTableTeacher">
                                        <thead></thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <form method="POST" id="importFormTeacher">
                            <input type="hidden" name="action" value="import_teachers">
                            <textarea name="csv_data" id="csvDataTeacher" style="display:none"></textarea>
                            <div class="modal-footer" style="padding: 15px 20px; border-top: 1px solid #ddd; display: flex; gap: 10px; justify-content: flex-end;">
                                <button type="button" class="btn btn-secondary" onclick="closeImportModal()">Cancel</button>
                                <button type="submit" class="btn btn-success" id="importBtnTeacher" style="display: none;">Import Teachers</button>
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
                        clearImportTeacher();
                    }

                    function clearImportTeacher() {
                        document.getElementById('importPreviewTeacher').style.display = 'none';
                        document.getElementById('importBtnTeacher').style.display = 'none';
                        document.getElementById('importFileTeacher').value = '';
                        document.getElementById('csvDataTeacher').value = '';
                    }

                    function downloadTeacherTemplate() {
                        var csv = 'teacher_id,lastname,firstname,middlename,address,email,department,password\n';
                        csv += 'T-001,Dela Cruz,Juan,Santos,"123 Main St, Manila",juan.teacher@gmail.com,College of Information Technology,Teacher@123\n';
                        csv += 'T-002,Garcia,Maria,Lopez,"456 Oak Ave, Quezon City",maria.teacher@gmail.com,College of Engineering,Teacher@456\n';
                        downloadCSV(csv, 'teacher_import_template.csv');
                    }

                    function handleTeacherFile(input) {
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

                                showTeacherPreview(rows);
                            } catch (err) {
                                alert('Error reading file: ' + err.message);
                            }
                        };
                        reader.readAsArrayBuffer(file);
                    }

                    function showTeacherPreview(rows) {
                        var displayHeaders = ['Teacher ID', 'Last Name', 'First Name', 'Middle Name', 'Address', 'Email', 'Department', 'Password'];
                        var thead = document.querySelector('#previewTableTeacher thead');
                        var tbody = document.querySelector('#previewTableTeacher tbody');

                        thead.innerHTML = '<tr>' + displayHeaders.map(function(h) { return '<th style="white-space:nowrap;">' + h + '</th>'; }).join('') + '</tr>';

                        var maxPreview = Math.min(rows.length, 10);
                        var tbodyHtml = '';
                        for (var i = 0; i < maxPreview; i++) {
                            tbodyHtml += '<tr>';
                            for (var j = 0; j < 8; j++) {
                                var val = rows[i][j] !== undefined ? String(rows[i][j]) : '';
                                // Mask password column
                                if (j === 7 && val.length > 0) {
                                    val = '••••••••';
                                }
                                if (val.length > 40) val = val.substring(0, 40) + '...';
                                tbodyHtml += '<td>' + val.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</td>';
                            }
                            tbodyHtml += '</tr>';
                        }
                        tbody.innerHTML = tbodyHtml;

                        document.getElementById('previewCountTeacher').textContent = '— ' + rows.length + ' record(s)' + (rows.length > 10 ? ' (showing first 10)' : '');
                        document.getElementById('importPreviewTeacher').style.display = 'block';
                        document.getElementById('importBtnTeacher').style.display = 'inline-block';
                        document.getElementById('importBtnTeacher').textContent = 'Import ' + rows.length + ' Teacher(s)';

                        // Build CSV data for form submission
                        var headers = ['teacher_id','lastname','firstname','middlename','address','email','department','password'];
                        var csvLines = [headers.join(',')];
                        rows.forEach(function(row) {
                            var csvRow = [];
                            for (var j = 0; j < 8; j++) {
                                var val = row[j] !== undefined ? String(row[j]) : '';
                                if (val.indexOf(',') !== -1 || val.indexOf('"') !== -1 || val.indexOf('\n') !== -1) {
                                    val = '"' + val.replace(/"/g, '""') + '"';
                                }
                                csvRow.push(val);
                            }
                            csvLines.push(csvRow.join(','));
                        });
                        document.getElementById('csvDataTeacher').value = csvLines.join('\n');
                    }

                    // Drag and drop support for teacher import
                    (function() {
                        var dz = document.getElementById('teacherDropzone');
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
                                document.getElementById('importFileTeacher').files = files;
                                handleTeacherFile(document.getElementById('importFileTeacher'));
                            }
                        });
                    })();
                </script>
                
                <!-- Teachers List -->
                <section class="section">
                    <h2>All Teachers <span style="font-weight: normal; font-size: 0.9em; color: #666;">(<span id="teachersTable-count"><?php echo $pagination['total_items']; ?></span> total, page <?php echo $pagination['current_page']; ?> of <?php echo max(1, $pagination['total_pages']); ?>)</span></h2>
                    <div class="form-group" style="max-width: 400px; margin-bottom: 15px;">
                        <input type="text" id="liveSearchTeachers" data-live-filter="teachersTable" placeholder="Search by ID, name, email, department..." style="width: 100%;">
                    </div>
                    <?php if (count($teachers) > 0): ?>
                        <div class="table-responsive">
                            <table class="table" id="teachersTable">
                                <thead>
                                    <tr>
                                        <th>Teacher ID</th>
                                        <th>Last Name</th>
                                        <th>First Name</th>
                                        <th>Middle Name</th>
                                        <th>Email</th>
                                        <th>Address</th>
                                        <th>Department</th>
                                        <th>Teacher Status</th>
                                        <th>Registered</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($teacher['teacher_id'] ?? ''); ?></td>
                                            <td><?php 
                                                $nameParts = explode(', ', $teacher['name'] ?? '');
                                                echo htmlspecialchars(trim($nameParts[0] ?? ''));
                                            ?></td>
                                            <td><?php 
                                                $nameParts = explode(', ', $teacher['name'] ?? '');
                                                echo htmlspecialchars(trim($nameParts[1] ?? ''));
                                            ?></td>
                                            <td><?php 
                                                $nameParts = explode(', ', $teacher['name'] ?? '');
                                                echo htmlspecialchars(trim($nameParts[2] ?? ''));
                                            ?></td>
                                            <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                            <td><?php echo htmlspecialchars($teacher['address'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($teacher['department'] ?? ''); ?></td>
                                            <td><span class="status status-<?php echo ($teacher['status'] === 'active') ? 'active' : 'inactive'; ?>"><?php echo ucfirst($teacher['status'] ?? 'inactive'); ?></span></td>
                                            <td><?php echo formatDate($teacher['created_at']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary" onclick="openUpdateModal(
                                                    <?php echo $teacher['id']; ?>,
                                                    '<?php echo htmlspecialchars($teacher['teacher_id'] ?? ''); ?>',
                                                    '<?php $nameParts = explode(', ', $teacher['name'] ?? ''); echo htmlspecialchars(trim($nameParts[0] ?? '')); ?>',
                                                    '<?php echo htmlspecialchars(trim($nameParts[1] ?? '')); ?>',
                                                    '<?php echo htmlspecialchars(trim($nameParts[2] ?? '')); ?>',
                                                    '<?php echo htmlspecialchars($teacher['address'] ?? ''); ?>',
                                                    '<?php echo htmlspecialchars($teacher['email'] ?? ''); ?>',
                                                    '<?php echo htmlspecialchars($teacher['department'] ?? ''); ?>',
                                                    '<?php echo htmlspecialchars($teacher['status'] ?? 'inactive'); ?>'
                                                )">Update</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php echo renderPaginationControls($pagination, '/CLASS_CARD_DROPPING_SYSTEM/admin/teachers.php'); ?>
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
            const toggleBtn = passwordField.closest('.password-input-wrapper').querySelector('.password-toggle');
            const eyeShow = toggleBtn.querySelector('.eye-show');
            const eyeHide = toggleBtn.querySelector('.eye-hide');
            const isPassword = passwordField.type === 'password';
            passwordField.type = isPassword ? 'text' : 'password';
            eyeShow.style.display = isPassword ? 'none' : 'block';
            eyeHide.style.display = isPassword ? 'block' : 'none';
            toggleBtn.classList.toggle('active', isPassword);
        }

        function checkPasswordStrength(value) {
            const strengthEl = document.getElementById('password-strength');
            const requirementsEl = document.getElementById('password-requirements');
            const rules = [
                { regex: /.{6,}/, label: 'At least 6 characters' },
                { regex: /[A-Z]/, label: 'Uppercase letter' },
                { regex: /[a-z]/, label: 'Lowercase letter' },
                { regex: /[0-9]/, label: 'Number' },
                { regex: /[!@#$%]/, label: 'Special character (!, @, #, $, %)' },
            ];

            if (value.length === 0) {
                strengthEl.innerHTML = '';
                requirementsEl.style.display = 'none';
                return;
            }
            
            // Show requirements when user starts typing
            requirementsEl.style.display = 'block';

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
                matchEl.style.display = 'none';
                return;
            }
            
            // Show match status when user types in confirm field
            matchEl.style.display = 'block';

            if (password === confirm) {
                matchEl.innerHTML = '<span style="color:#27ae60;">✔ Passwords match</span>';
            } else {
                matchEl.innerHTML = '<span style="color:#e74c3c;">✘ Passwords do not match</span>';
            }
        }

        document.getElementById('confirm_password').addEventListener('input', checkConfirmMatch);

        function generatePassword() {
            const uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            const lowercase = 'abcdefghijklmnopqrstuvwxyz';
            const numbers = '0123456789';
            const special = '!@#$%';
            
            // Generate random characters from each required set
            let password = '';
            password += uppercase[Math.floor(Math.random() * uppercase.length)];
            password += lowercase[Math.floor(Math.random() * lowercase.length)];
            password += numbers[Math.floor(Math.random() * numbers.length)];
            password += special[Math.floor(Math.random() * special.length)];
            
            // Add 4 more random characters to reach 8 characters
            const allChars = uppercase + lowercase + numbers + special;
            for (let i = 0; i < 4; i++) {
                password += allChars[Math.floor(Math.random() * allChars.length)];
            }
            
            // Shuffle the password
            password = password.split('').sort(() => Math.random() - 0.5).join('');
            
            // Set password field
            const passwordField = document.getElementById('password');
            passwordField.value = password;
            passwordField.type = 'text'; // Show the generated password
            
            // Update password strength indicator
            checkPasswordStrength(password);
            
            // Clear confirm password to make user type it again
            document.getElementById('confirm_password').value = '';
            document.getElementById('confirm-match').innerHTML = '';
            document.getElementById('confirm-match').style.display = 'none';
        }

        function validateTeacherId() {
            const input = document.getElementById('teacher_id');
            const errorDiv = document.getElementById('teacher_id_error');
            
            // Filter to only numbers
            input.value = input.value.replace(/[^\d]/g, '');
            
            if (input.value.length === 8) {
                input.style.borderColor = '#10b981';
                errorDiv.style.display = 'none';
            } else if (input.value.length > 0) {
                input.style.borderColor = '#ef4444';
                errorDiv.textContent = 'Teacher ID must be exactly 8 digits';
                errorDiv.style.display = 'block';
            } else {
                input.style.borderColor = '#e9d5ff';
                errorDiv.style.display = 'none';
            }
        }

        function validateLettersOnly(input, errorId) {
            const errorDiv = document.getElementById(errorId);
            const validValue = input.value.replace(/[^a-zA-Z\s\-']/g, '');
            input.value = validValue;
            
            if (input.value && !/^[a-zA-Z\s\-']+$/.test(input.value)) {
                input.style.borderColor = '#ef4444';
                errorDiv.textContent = 'Letters only (no numbers or symbols)';
                errorDiv.style.display = 'block';
            } else if (input.value) {
                input.style.borderColor = '#10b981';
                errorDiv.style.display = 'none';
            } else {
                input.style.borderColor = '#e9d5ff';
                errorDiv.style.display = 'none';
            }
        }

        function validateGmailOnly() {
            const input = document.getElementById('email');
            const errorDiv = document.getElementById('email_error');
            const email = input.value.trim();
            
            if (!email.endsWith('@gmail.com')) {
                input.style.borderColor = '#ef4444';
                errorDiv.textContent = 'Email must be @gmail.com only';
                errorDiv.style.display = 'block';
            } else if (!/^[a-zA-Z0-9._%-]+@gmail\.com$/.test(email)) {
                input.style.borderColor = '#ef4444';
                errorDiv.textContent = 'Invalid email format';
                errorDiv.style.display = 'block';
            } else if (email) {
                input.style.borderColor = '#10b981';
                errorDiv.style.display = 'none';
            } else {
                input.style.borderColor = '#e9d5ff';
                errorDiv.style.display = 'none';
            }
        }

        function validateTeacherForm() {
            const teacherId = document.getElementById('teacher_id').value.trim();
            const firstName = document.getElementById('firstname').value.trim();
            const lastName = document.getElementById('lastname').value.trim();
            const middleName = document.getElementById('middlename').value.trim();
            const email = document.getElementById('email').value.trim();
            const address = document.getElementById('address').value.trim();
            const department = document.getElementById('department').value.trim();
            const password = document.getElementById('password').value.trim();
            const confirmPassword = document.getElementById('confirm_password').value.trim();

            const errorMessages = [];

            // Validate Teacher ID
            if (!teacherId) {
                errorMessages.push('• Teacher ID is required');
            } else if (!/^\d{8}$/.test(teacherId)) {
                errorMessages.push('• Teacher ID must be exactly 8 digits');
            }

            // Validate names
            if (!firstName) {
                errorMessages.push('• First Name is required');
            } else if (!/^[a-zA-Z\s\-']+$/.test(firstName)) {
                errorMessages.push('• First Name must contain letters only');
            }

            if (!lastName) {
                errorMessages.push('• Last Name is required');
            } else if (!/^[a-zA-Z\s\-']+$/.test(lastName)) {
                errorMessages.push('• Last Name must contain letters only');
            }

            if (!middleName) {
                errorMessages.push('• Middle Name is required');
            } else if (!/^[a-zA-Z\s\-']+$/.test(middleName)) {
                errorMessages.push('• Middle Name must contain letters only');
            }

            // Validate email
            if (!email) {
                errorMessages.push('• Email Address is required');
            } else if (!email.endsWith('@gmail.com')) {
                errorMessages.push('• Email must be @gmail.com only');
            } else if (!/^[a-zA-Z0-9._%-]+@gmail\.com$/.test(email)) {
                errorMessages.push('• Email format is invalid');
            }

            // Validate address
            if (!address) {
                errorMessages.push('• Complete Address is required');
            }

            // Validate department
            if (!department) {
                errorMessages.push('• Department is required');
            }

            // Validate password
            if (!password) {
                errorMessages.push('• Password is required');
            } else if (password.length < 6) {
                errorMessages.push('• Password must be at least 6 characters long');
            }

            // Validate confirm password
            if (!confirmPassword) {
                errorMessages.push('• Confirm Password is required');
            } else if (password !== confirmPassword) {
                errorMessages.push('• Passwords do not match');
            }

            if (errorMessages.length > 0) {
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

        function validateNameInput(input) {
            // Allow only letters, spaces, hyphens, and apostrophes
            const validValue = input.value.replace(/[^a-zA-Z\s\-']/g, '');
            if (validValue !== input.value) {
                input.value = validValue;
            }
        }

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