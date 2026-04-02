<?php
// admin/settings.php - Admin Settings/Profile

require_once '../includes/session_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

// Check if user is admin
if ($_SESSION['user_role'] !== 'admin') {
    redirect('/CLASS_CARD_DROPPING_SYSTEM/index.php');
}

$user_id = $_SESSION['user_id'];
$admin_name = getUserName($pdo, $user_id);
$user_info = getUserInfo($pdo, $user_id);

// Get full admin info
$stmt = $pdo->prepare('SELECT id, name, email, department FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$admin_info = $stmt->fetch();

// Extract name parts
$nameParts = explode(', ', $admin_info['name']);
$lastname = trim($nameParts[0] ?? '');
$firstname = trim($nameParts[1] ?? '');
$middlename = trim($nameParts[2] ?? '');

$message = getMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Portal - PhilCST</title>
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
                <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/students.php" class="nav-item">
                    <span>Manage Students</span>
                </a>
                <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/teachers.php" class="nav-item">
                    <span>Manage Teachers</span>
                </a>
                <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/drop_history.php" class="nav-item">
                    <span>Drop History</span>
                </a>
                <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/settings.php" class="nav-item active">
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
                <h1>Settings</h1>
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

                <!-- Profile Section -->
                <section class="section">
                    <h2>My Profile</h2>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <!-- Profile Info Display -->
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                            <h3 style="margin-top: 0;">Profile Information</h3>
                            <div style="margin-bottom: 15px;">
                                <p style="margin: 0; color: #666; font-size: 0.9em;">EMAIL</p>
                                <p style="margin: 5px 0 0 0; font-weight: 500;"><?php echo htmlspecialchars($admin_info['email']); ?></p>
                            </div>
                            <div style="margin-bottom: 15px;">
                                <p style="margin: 0; color: #666; font-size: 0.9em;">ROLE</p>
                                <p style="margin: 5px 0 0 0; font-weight: 500;">Administrator</p>
                            </div>
                            <div style="margin-bottom: 15px;">
                                <p style="margin: 0; color: #666; font-size: 0.9em;">LAST NAME</p>
                                <p style="margin: 5px 0 0 0; font-weight: 500;"><?php echo htmlspecialchars($lastname); ?></p>
                            </div>
                            <div style="margin-bottom: 15px;">
                                <p style="margin: 0; color: #666; font-size: 0.9em;">FIRST NAME</p>
                                <p style="margin: 5px 0 0 0; font-weight: 500;"><?php echo htmlspecialchars($firstname); ?></p>
                            </div>
                            <div style="margin-bottom: 15px;">
                                <p style="margin: 0; color: #666; font-size: 0.9em;">MIDDLE NAME</p>
                                <p style="margin: 5px 0 0 0; font-weight: 500;"><?php echo htmlspecialchars($middlename); ?></p>
                            </div>
                            <div style="margin-bottom: 0;">
                                <p style="margin: 0; color: #666; font-size: 0.9em;">POSITION</p>
                                <p style="margin: 5px 0 0 0; font-weight: 500;"><?php echo htmlspecialchars($admin_info['department'] ?: 'N/A'); ?></p>
                            </div>
                        </div>

                        <!-- Profile Edit Form -->
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                            <h3 style="margin-top: 0;">Edit Profile</h3>
                            <form method="POST" action="/CLASS_CARD_DROPPING_SYSTEM/includes/api.php?action=update_admin_profile" style="display: grid; gap: 12px;">
                                <div class="form-group">
                                    <label for="edit_lastname">Last Name</label>
                                    <input type="text" id="edit_lastname" name="lastname" required value="<?php echo htmlspecialchars($lastname); ?>" placeholder="Letters only" pattern="[a-zA-Z\s\-']+" oninput="validateNameInput(this); this.value = this.value.toUpperCase()" title="Last name must contain only letters, spaces, hyphens, and apostrophes">
                                </div>
                                <div class="form-group">
                                    <label for="edit_firstname">First Name</label>
                                    <input type="text" id="edit_firstname" name="firstname" required value="<?php echo htmlspecialchars($firstname); ?>" placeholder="Letters only" pattern="[a-zA-Z\s\-']+" oninput="validateNameInput(this); this.value = this.value.toUpperCase()" title="First name must contain only letters, spaces, hyphens, and apostrophes">
                                </div>
                                <div class="form-group">
                                    <label for="edit_middlename">Middle Name</label>
                                    <input type="text" id="edit_middlename" name="middlename" required value="<?php echo htmlspecialchars($middlename); ?>" placeholder="Letters only" pattern="[a-zA-Z\s\-']+" oninput="validateNameInput(this); this.value = this.value.toUpperCase()" title="Middle name must contain only letters, spaces, hyphens, and apostrophes">
                                </div>
                                <div class="form-group">
                                    <label for="edit_position">Position</label>
                                    <input type="text" id="edit_position" name="position" required value="<?php echo htmlspecialchars($admin_info['department'] ?: ''); ?>" placeholder="Your position or title">
                                </div>
                                <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Save Changes</button>
                            </form>
                        </div>
                    </div>
                </section>

                <!-- Password Section -->
                <section class="section">
                    <h2>Security</h2>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; max-width: 500px;">
                        <h3 style="margin-top: 0;">Change Password</h3>
                        <p style="color: #666; margin-bottom: 20px;">Manage your password and keep your account secure.</p>
                        <button type="button" class="btn btn-primary" onclick="openChangePasswordModal()">Change Password</button>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <!-- Change Password Modal -->
    <div id="changePasswordModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>Change Password</h2>
                <button type="button" class="modal-close" onclick="closeChangePasswordModal()">&times;</button>
            </div>
            <div class="modal-body" style="padding: 20px;">
                <form method="POST" action="/CLASS_CARD_DROPPING_SYSTEM/includes/api.php?action=update_admin_password" id="changePasswordForm">
                    <div class="form-group">
                        <label for="currentPassword">Current Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="currentPassword" name="current_password" required placeholder="Enter your current password">
                            <button type="button" class="password-toggle" onclick="togglePassword('currentPassword')">
                                <svg class="eye-icon eye-show" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                <svg class="eye-icon eye-hide" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="newPassword">New Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="newPassword" name="new_password" required placeholder="Enter your new password" oninput="checkPasswordStrength(this.value)">
                            <button type="button" class="password-toggle" onclick="togglePassword('newPassword')">
                                <svg class="eye-icon eye-show" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                <svg class="eye-icon eye-hide" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                            </button>
                        </div>
                        <small id="password-requirements" style="display: none; display: block; margin-top: 8px; color: #666;">
                            <strong>Password Requirements:</strong><br>
                            • At least 6 characters long<br>
                            • At least one uppercase letter (A–Z)<br>
                            • At least one lowercase letter (a–z)<br>
                            • At least one number (0–9)<br>
                            • At least one special character (!, @, #, $, %)
                        </small>
                        <div id="password-strength" style="margin-top: 8px; font-size: 0.85em;"></div>
                    </div>

                    <div class="form-group">
                        <label for="confirmPassword">Confirm New Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="confirmPassword" name="confirm_password" required placeholder="Re-enter your new password" oninput="checkConfirmMatch()">
                            <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword')">
                                <svg class="eye-icon eye-show" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                <svg class="eye-icon eye-hide" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                            </button>
                        </div>
                        <div id="confirm-match" style="margin-top: 8px; font-size: 0.85em;"></div>
                    </div>

                    <div class="modal-footer" style="padding: 0; margin-top: 20px; border-top: 1px solid #ddd; display: flex; gap: 10px; justify-content: flex-end; padding-top: 20px;">
                        <button type="button" class="btn btn-secondary" onclick="closeChangePasswordModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Password</button>
                    </div>
                </form>
            </div>
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
            max-height: 90vh;
            overflow-y: auto;
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
        .password-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-input-wrapper input {
            width: 100%;
            padding: 10px 40px 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1em;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            background: none;
            border: none;
            cursor: pointer;
            color: #999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .password-toggle:hover {
            color: #333;
        }
    </style>

    <script>
        function openChangePasswordModal() {
            document.getElementById('changePasswordModal').style.display = 'flex';
        }

        function closeChangePasswordModal() {
            document.getElementById('changePasswordModal').style.display = 'none';
            document.getElementById('changePasswordForm').reset();
            document.getElementById('password-strength').innerHTML = '';
            document.getElementById('password-requirements').style.display = 'none';
            document.getElementById('confirm-match').innerHTML = '';
        }

        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const wrapper = passwordField.closest('.password-input-wrapper') || passwordField.parentElement;
            const toggleBtn = wrapper.querySelector('.password-toggle');
            if (!toggleBtn) return;
            
            const eyeShow = toggleBtn.querySelector('.eye-show');
            const eyeHide = toggleBtn.querySelector('.eye-hide');
            const isPassword = passwordField.type === 'password';
            passwordField.type = isPassword ? 'text' : 'password';
            if (eyeShow) eyeShow.style.display = isPassword ? 'none' : 'block';
            if (eyeHide) eyeHide.style.display = isPassword ? 'block' : 'none';
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

            checkConfirmMatch();
        }

        function checkConfirmMatch() {
            const password = document.getElementById('newPassword').value;
            const confirm = document.getElementById('confirmPassword').value;
            const matchEl = document.getElementById('confirm-match');

            if (confirm.length === 0) {
                matchEl.innerHTML = '';
                matchEl.style.display = 'none';
                return;
            }
            
            matchEl.style.display = 'block';

            if (password === confirm) {
                matchEl.innerHTML = '<span style="color:#27ae60;">✔ Passwords match</span>';
            } else {
                matchEl.innerHTML = '<span style="color:#e74c3c;">✘ Passwords do not match</span>';
            }
        }

        function validateNameInput(input) {
            // Allow only letters, spaces, hyphens, and apostrophes
            const validValue = input.value.replace(/[^a-zA-Z\s\-']/g, '');
            if (validValue !== input.value) {
                input.value = validValue;
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('changePasswordModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>

    <script src="/CLASS_CARD_DROPPING_SYSTEM/js/functions.js"></script>
</body>
</html>
