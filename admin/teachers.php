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
        
        if (empty($department)) {
            $errors[] = 'Department is required.';
        } elseif (strlen($department) < 2) {
            $errors[] = 'Department must be at least 2 characters.';
        } elseif (strlen($department) > 100) {
            $errors[] = 'Department must not exceed 100 characters.';
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
                $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, teacher_id, address, department) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$name, $email, $hashed_password, 'teacher', $teacher_id, $address, $department]);
                setMessage('success', 'Teacher added successfully.');
            } catch (Exception $e) {
                setMessage('error', 'Error adding teacher: ' . $e->getMessage());
            }
        }
        redirect('/SYSTEM/admin/teachers.php');
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
        redirect('/SYSTEM/admin/teachers.php');
    }
}

// Fetch all teachers
$stmt = $pdo->prepare('SELECT id, teacher_id, name, email, address, department, status, created_at FROM users WHERE role = "teacher" ORDER BY name');
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
                <a href="/SYSTEM/admin/students.php" class="nav-item">
                    <span>Manage Students</span>
                </a>
                <a href="/SYSTEM/admin/teachers.php" class="nav-item active">
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
                
                <!-- Register Button -->
                <div style="margin-bottom: 20px;">
                    <button type="button" class="btn btn-primary" onclick="openRegisterModal()">Register Teacher</button>
                </div>
                
                <!-- Register Modal -->
                <div id="registerModal" class="modal" style="display: none;">
                    <div class="modal-content" style="max-width: 600px; max-height: 90vh; overflow-y: auto;">
                        <div class="modal-header">
                            <h2>Register New Teacher</h2>
                            <button type="button" class="modal-close" onclick="closeRegisterModal()">&times;</button>
                        </div>
                        <form method="POST" class="teacher-form" id="teacherForm">
                            <input type="hidden" name="action" value="add">
                            <div class="modal-body" style="padding: 20px;">
                                <div class="form-group">
                                    <label for="teacher_id">Teacher ID</label>
                                    <input type="text" id="teacher_id" name="teacher_id" required placeholder="Enter teacher ID">
                                </div>
                                
                                <div class="form-group">
                                    <label for="lastname">Last Name</label>
                                    <input type="text" id="lastname" name="lastname" required placeholder="Enter last name">
                                </div>
                                
                                <div class="form-group">
                                    <label for="firstname">First Name</label>
                                    <input type="text" id="firstname" name="firstname" required placeholder="Enter first name">
                                </div>
                                
                                <div class="form-group">
                                    <label for="middlename">Middle Name</label>
                                    <input type="text" id="middlename" name="middlename" required placeholder="Enter middle name">
                                </div>
                                
                                <div class="form-group">
                                    <label for="address">Complete Address</label>
                                    <textarea id="address" name="address" required placeholder="Enter complete address" rows="3"></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email Address</label>
                                    <input type="email" id="email" name="email" required placeholder="example@gmail.com">
                                </div>
                                
                                <div class="form-group">
                                    <label for="department">Department</label>
                                    <input type="text" id="department" name="department" required placeholder="Enter department">
                                </div>
                                
                                <div class="form-group">
                                    <label for="password">Password</label>
                                    <div class="password-input-wrapper">
                                        <input type="password" id="password" name="password" required minlength="6" placeholder="Put a strong password here" oninput="checkPasswordStrength(this.value)">
                                        <button type="button" class="password-toggle" onclick="togglePassword('password')">👁️</button>
                                    </div>
                                    <small id="password-requirements" style="display: none; display: block; margin-top: 5px; color: #666;">
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
                                    <div id="confirm-match" style="margin-top: 5px; font-size: 0.85em; display: none;"></div>
                                </div>
                            </div>
                            <div class="modal-footer" style="padding: 15px 20px; border-top: 1px solid #ddd; display: flex; gap: 10px; justify-content: flex-end;">
                                <button type="button" class="btn btn-secondary" onclick="closeRegisterModal()">Cancel</button>
                                <button type="submit" class="btn btn-primary">Register Teacher</button>
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
                    }
                </script>
                
                <!-- Update Teacher Modal -->
                <div id="updateModal" class="modal" style="display: none;">
                    <div class="modal-content" style="max-width: 600px; max-height: 90vh; overflow-y: auto;">
                        <div class="modal-header">
                            <h2>Update Teacher</h2>
                            <button type="button" class="modal-close" onclick="closeUpdateModal()">&times;</button>
                        </div>
                        <form method="POST" id="updateForm">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" id="updateId" value="">
                            <div class="modal-body" style="padding: 20px;">
                                <div class="form-group">
                                    <label for="updateTeacherId">Teacher ID</label>
                                    <input type="text" id="updateTeacherId" name="teacher_id" required placeholder="Enter teacher ID">
                                </div>
                                <div class="form-group">
                                    <label for="updateLastname">Last Name</label>
                                    <input type="text" id="updateLastname" name="lastname" required placeholder="Enter last name">
                                </div>
                                <div class="form-group">
                                    <label for="updateFirstname">First Name</label>
                                    <input type="text" id="updateFirstname" name="firstname" required placeholder="Enter first name">
                                </div>
                                <div class="form-group">
                                    <label for="updateMiddlename">Middle Name</label>
                                    <input type="text" id="updateMiddlename" name="middlename" placeholder="Enter middle name">
                                </div>
                                <div class="form-group">
                                    <label for="updateAddress">Complete Address</label>
                                    <textarea id="updateAddress" name="address" required placeholder="Enter complete address" rows="3"></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="updateEmail">Email Address</label>
                                    <input type="email" id="updateEmail" name="email" required placeholder="example@gmail.com">
                                </div>
                                <div class="form-group">
                                    <label for="updateDepartment">Department</label>
                                    <input type="text" id="updateDepartment" name="department" required placeholder="Enter department">
                                </div>
                                <div class="form-group">
                                    <label for="updateStatus">Status</label>
                                    <select id="updateStatus" name="status" required>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer" style="padding: 15px 20px; border-top: 1px solid #ddd; display: flex; gap: 10px; justify-content: flex-end;">
                                <button type="button" class="btn btn-secondary" onclick="closeUpdateModal()">Cancel</button>
                                <button type="submit" class="btn btn-primary">Update Teacher</button>
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
                        if (event.target == registerModal) {
                            registerModal.style.display = 'none';
                        }
                        if (event.target == updateModal) {
                            updateModal.style.display = 'none';
                        }
                    }
                </script>
                
                <!-- Teachers List -->
                <section class="section">
                    <h2>All Teachers (<span id="teachersTable-count"><?php echo count($teachers); ?></span>)</h2>
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
                                        <th>Status</th>
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
                                            <td><span class="badge badge-<?php echo ($teacher['status'] === 'active') ? 'success' : 'danger'; ?>"><?php echo ucfirst($teacher['status'] ?? 'inactive'); ?></span></td>
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
    </script>

    <script src="/SYSTEM/js/functions.js"></script>
</body>
</html>