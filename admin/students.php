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
        $lastname = trim($_POST['lastname'] ?? '');
        $firstname = trim($_POST['firstname'] ?? '');
        $middlename = trim($_POST['middlename'] ?? '');
        $name = $lastname . ', ' . $firstname . ', ' . $middlename;
        $address = trim($_POST['address'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $guardian_name = trim($_POST['guardian_name'] ?? '');
        $guardian_email = trim($_POST['guardian_email'] ?? '');
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

        if (empty($guardian_email)) {
            $errors[] = 'Guardian email is required.';
        } elseif (!filter_var($guardian_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid guardian email address.';
        } elseif (strlen($guardian_email) > 100) {
            $errors[] = 'Guardian email must not exceed 100 characters.';
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
                $stmt = $pdo->prepare('INSERT INTO students (student_id, name, email, address, guardian_name, guardian_email, course, year) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$student_id, $name, $email, $address, $guardian_name, $guardian_email, $course, $year]);
                setMessage('success', 'Student added successfully.');
            } catch (Exception $e) {
                setMessage('error', 'Error adding student: ' . $e->getMessage());
            }
        }
        redirect('/SYSTEM/admin/students.php');
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
        $guardian_email = trim($_POST['guardian_email'] ?? '');
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

        if (empty($guardian_email)) {
            $errors[] = 'Guardian email is required.';
        } elseif (!filter_var($guardian_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid guardian email address.';
        } elseif (strlen($guardian_email) > 100) {
            $errors[] = 'Guardian email must not exceed 100 characters.';
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

        if (!empty($errors)) {
            setMessage('error', implode('<br>', $errors));
        } else {
            try {
                $stmt = $pdo->prepare('UPDATE students SET student_id = ?, name = ?, email = ?, address = ?, guardian_name = ?, guardian_email = ?, course = ?, year = ?, status = ? WHERE id = ?');
                $stmt->execute([$student_id, $name, $email, $address, $guardian_name, $guardian_email, $course, $year, $status, $id]);
                setMessage('success', 'Student updated successfully.');
            } catch (Exception $e) {
                setMessage('error', 'Error updating student: ' . $e->getMessage());
            }
        }
        redirect('/SYSTEM/admin/students.php');
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
                <img src="/SYSTEM/Philcst Logo (2).png" alt="PhilCST Logo" class="sidebar-logo">
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
                <a href="#" class="nav-item logout-item" onclick="showLogoutModal(); return false;">
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

                <!-- Register Button -->
                <div style="margin-bottom: 20px;">
                    <button type="button" class="btn btn-primary" onclick="openRegisterModal()">Register
                        Student</button>
                </div>

                <!-- Register Modal -->
                <div id="registerModal" class="modal" style="display: none;">
                    <div class="modal-content" style="max-width: 600px; max-height: 90vh; overflow-y: auto;">
                        <div class="modal-header">
                            <h2>Register New Student</h2>
                            <button type="button" class="modal-close" onclick="closeRegisterModal()">&times;</button>
                        </div>
                        <form method="POST" class="student-form">
                            <input type="hidden" name="action" value="add">
                            <div class="modal-body" style="padding: 20px;">
                                <div class="form-group">
                                    <label for="student_id">Student ID</label>
                                    <input type="text" id="student_id" name="student_id" required
                                        placeholder="ex: 000*****" maxlength="8" inputmode="numeric">
                                </div>

                                <div class="form-group">
                                    <label for="lastname">Last Name</label>
                                    <input type="text" id="lastname" name="lastname" required
                                        placeholder="Enter last name">
                                </div>

                                <div class="form-group">
                                    <label for="firstname">First Name</label>
                                    <input type="text" id="firstname" name="firstname" required
                                        placeholder="Enter first name">
                                </div>

                                <div class="form-group">
                                    <label for="middlename">Middle Name</label>
                                    <input type="text" id="middlename" name="middlename" required
                                        placeholder="Enter middle name">
                                </div>

                                <div class="form-group">
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
                                    <input type="text" id="guardian_name" name="guardian_name" required
                                        placeholder="Enter guardian name">
                                </div>

                                <div class="form-group">
                                    <label for="guardian_email">Guardian Email</label>
                                    <input type="email" id="guardian_email" name="guardian_email" required
                                        placeholder="example@gmail.com">
                                </div>

                                <div class="form-group">
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
                            <div class="modal-body" style="padding: 20px;">
                                <div class="form-group">
                                    <label for="updateStudentIdField">Student ID</label>
                                    <input type="text" id="updateStudentIdField" name="student_id" required
                                        placeholder="ex: 000*****" maxlength="8" inputmode="numeric">
                                </div>

                                <div class="form-group">
                                    <label for="updateLastName">Last Name</label>
                                    <input type="text" id="updateLastName" name="lastname" required
                                        placeholder="Enter last name">
                                </div>

                                <div class="form-group">
                                    <label for="updateFirstName">First Name</label>
                                    <input type="text" id="updateFirstName" name="firstname" required
                                        placeholder="Enter first name">
                                </div>

                                <div class="form-group">
                                    <label for="updateMiddleName">Middle Name</label>
                                    <input type="text" id="updateMiddleName" name="middlename" required
                                        placeholder="Enter middle name">
                                </div>

                                <div class="form-group">
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
                                        placeholder="Enter guardian name">
                                </div>

                                <div class="form-group">
                                    <label for="updateGuardianEmail">Guardian Email</label>
                                    <input type="email" id="updateGuardianEmail" name="guardian_email" required
                                        placeholder="example@gmail.com">
                                </div>

                                <div class="form-group">
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
                    function openUpdateModal(id, studentId, lastName, firstName, middleName, address, email, guardianName, guardianEmail, course, year, status) {
                        document.getElementById('updateStudentId').value = id;
                        document.getElementById('updateStudentIdField').value = studentId;
                        document.getElementById('updateLastName').value = lastName;
                        document.getElementById('updateFirstName').value = firstName;
                        document.getElementById('updateMiddleName').value = middleName;
                        document.getElementById('updateAddress').value = address;
                        document.getElementById('updateEmail').value = email;
                        document.getElementById('updateGuardianName').value = guardianName;
                        document.getElementById('updateGuardianEmail').value = guardianEmail;
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
                        var updateModal = document.getElementById('updateModal');
                        if (event.target == updateModal) {
                            updateModal.style.display = 'none';
                        }
                    }
                </script>

                <!-- Students List -->
                <section class="section">
                    <h2>All Students (<span id="studentsTable-count"><?php echo count($students); ?></span>)</h2>
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
                                        <th>Guardian Email</th>
                                        <th>Course</th>
                                        <th>Year</th>
                                        <th>Status</th>
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
                                            <td><?php echo htmlspecialchars($student['guardian_email'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($student['course'] ?? ''); ?></td>
                                            <td><?php echo $student['year'] ?? ''; ?></td>
                                            <td><span
                                                    class="badge badge-<?php echo ($student['status'] === 'active') ? 'success' : 'danger'; ?>"><?php echo ucfirst($student['status'] ?? 'inactive'); ?></span>
                                            </td>
                                            <td><?php echo formatDate($student['created_at'] ?? ''); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary"
                                                    onclick="openUpdateModal(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['student_id']); ?>', '<?php $nameParts = explode(', ', $student['name']);
                                                          echo htmlspecialchars(trim($nameParts[0] ?? '')); ?>', '<?php echo htmlspecialchars(trim($nameParts[1] ?? '')); ?>', '<?php echo htmlspecialchars(trim($nameParts[2] ?? '')); ?>', '<?php echo htmlspecialchars($student['address'] ?? ''); ?>', '<?php echo htmlspecialchars($student['email']); ?>', '<?php echo htmlspecialchars($student['guardian_name'] ?? ''); ?>', '<?php echo htmlspecialchars($student['guardian_email'] ?? ''); ?>', '<?php echo htmlspecialchars($student['course']); ?>', <?php echo $student['year']; ?>, '<?php echo htmlspecialchars($student['status'] ?? 'inactive'); ?>')">Update</button>
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

    <script src="/SYSTEM/js/functions.js"></script>
</body>

</html>