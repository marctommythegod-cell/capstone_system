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
                <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/teachers.php" class="nav-item">
                    <span>Manage Teachers</span>
                </a>
                <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/drop_history.php" class="nav-item">
                    <span>Drop History</span>
                </a>
                <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/settings.php" class="nav-item active">
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

                <!-- Profile Header Container -->
                <div style="background: linear-gradient(135deg, #7f3fc6 0%, #a78bfa 100%); border-radius: 12px; padding: 25px 20px; margin-bottom: 30px; box-shadow: 0 6px 20px rgba(127, 63, 198, 0.2);">
                    <h1 style="margin: 0 0 5px 0; font-size: 1.5em; color: white; font-weight: 700;">My Profile</h1>
                    <p style="margin: 0; color: rgba(255, 255, 255, 0.9); font-size: 0.9em; font-weight: 500;">Manage your profile information and password</p>
                </div>

                <!-- Profile Information Card -->
                <div style="background: white; border: 1px solid #e5e5e5; border-radius: 10px; padding: 30px; margin-bottom: 25px; max-width: 600px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                        <div>
                            <h3 style="margin: 0 0 5px 0; font-size: 1.1em; color: #333;">Profile Information</h3>
                            <p style="margin: 0; color: #999; font-size: 0.9em;">Your personal details</p>
                        </div>
                        <button type="button" onclick="openEditProfileModal()" style="background: #8b5cf6; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-size: 0.9em; font-weight: 500; cursor: pointer; transition: all 0.2s;">
                            Edit
                        </button>
                    </div>

                    <div style="display: grid; gap: 20px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div>
                                <p style="margin: 0; color: #999; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">First Name</p>
                                <p style="margin: 8px 0 0 0; color: #333; font-size: 0.95em;"><?php echo htmlspecialchars($firstname); ?></p>
                            </div>
                            <div>
                                <p style="margin: 0; color: #999; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Last Name</p>
                                <p style="margin: 8px 0 0 0; color: #333; font-size: 0.95em;"><?php echo htmlspecialchars($lastname); ?></p>
                            </div>
                        </div>

                        <div>
                            <p style="margin: 0; color: #999; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Middle Name</p>
                            <p style="margin: 8px 0 0 0; color: #333; font-size: 0.95em;"><?php echo htmlspecialchars($middlename); ?></p>
                        </div>

                        <div>
                            <p style="margin: 0; color: #999; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Email</p>
                            <p style="margin: 8px 0 0 0; color: #333; font-size: 0.95em;"><?php echo htmlspecialchars($admin_info['email']); ?></p>
                        </div>

                        <div>
                            <p style="margin: 0; color: #999; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Position</p>
                            <p style="margin: 8px 0 0 0; color: #333; font-size: 0.95em;"><?php echo htmlspecialchars($admin_info['department'] ?: 'Administrator'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Security Card -->
                <div style="background: white; border: 1px solid #e5e5e5; border-radius: 10px; padding: 30px; max-width: 600px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h3 style="margin: 0 0 5px 0; font-size: 1.1em; color: #333;">Security</h3>
                            <p style="margin: 0; color: #999; font-size: 0.9em;">Update your password</p>
                        </div>
                        <button type="button" onclick="openChangePasswordModal()" style="background: #8b5cf6; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-size: 0.9em; font-weight: 500; cursor: pointer; transition: all 0.2s;">
                            Change Password
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Profile</h2>
                <button type="button" class="modal-close" onclick="closeEditProfileModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="/CLASS_CARD_DROPPING_SYSTEM/includes/api.php?action=update_admin_profile" id="editProfileForm">
                    <div class="form-group">
                        <label for="modal_firstname">First Name</label>
                        <input type="text" id="modal_firstname" name="firstname" required value="<?php echo htmlspecialchars($firstname); ?>" placeholder="First name" pattern="[a-zA-Z\s\-']+" oninput="validateNameInput(this); this.value = this.value.toUpperCase()" title="Letters, spaces, hyphens, and apostrophes only">
                    </div>
                    <div class="form-group">
                        <label for="modal_lastname">Last Name</label>
                        <input type="text" id="modal_lastname" name="lastname" required value="<?php echo htmlspecialchars($lastname); ?>" placeholder="Last name" pattern="[a-zA-Z\s\-']+" oninput="validateNameInput(this); this.value = this.value.toUpperCase()" title="Letters, spaces, hyphens, and apostrophes only">
                    </div>
                    <div class="form-group">
                        <label for="modal_middlename">Middle Name</label>
                        <input type="text" id="modal_middlename" name="middlename" required value="<?php echo htmlspecialchars($middlename); ?>" placeholder="Middle name" pattern="[a-zA-Z\s\-']+" oninput="validateNameInput(this); this.value = this.value.toUpperCase()" title="Letters, spaces, hyphens, and apostrophes only">
                    </div>
                    <div class="form-group">
                        <label for="modal_position">Position</label>
                        <input type="text" id="modal_position" name="position" required value="<?php echo htmlspecialchars($admin_info['department'] ?: ''); ?>" placeholder="Your position or title">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeEditProfileModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="changePasswordModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Change Password</h2>
                <button type="button" class="modal-close" onclick="closeChangePasswordModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="/CLASS_CARD_DROPPING_SYSTEM/includes/api.php?action=update_admin_password" id="changePasswordForm">
                    <div class="form-group">
                        <label for="currentPassword">Current Password</label>
                        <input type="password" id="currentPassword" name="current_password" required placeholder="Enter your current password">
                    </div>

                    <div class="form-group">
                        <label for="newPassword">New Password</label>
                        <input type="password" id="newPassword" name="new_password" required placeholder="Enter your new password" oninput="checkPasswordStrength(this.value)">
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
                        <input type="password" id="confirmPassword" name="confirm_password" required placeholder="Re-enter your new password" oninput="checkConfirmMatch()">
                        <div id="confirm-match" style="margin-top: 8px; font-size: 0.85em;"></div>
                    </div>

                    <div class="modal-footer">
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
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            width: 95%;
            max-width: 500px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            padding: 25px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.2em;
            color: #333;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #999;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .modal-close:hover {
            background: #f5f5f5;
            color: #333;
        }

        .modal-body {
            padding: 25px;
        }

        .modal-footer {
            padding: 20px 25px;
            border-top: 1px solid #f0f0f0;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
            font-size: 0.95em;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.95em;
            font-family: inherit;
            box-sizing: border-box;
            transition: all 0.2s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #8b5cf6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }

        #password-requirements {
            background: #f5f5f5;
            padding: 12px;
            border-radius: 6px;
            font-size: 0.85em;
            line-height: 1.6;
            color: #666;
        }

        #password-strength {
            font-size: 0.9em;
            font-weight: 500;
        }

        #confirm-match {
            font-size: 0.9em;
            font-weight: 500;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 0.95em;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background-color: #8b5cf6;
            color: white;
        }

        .btn-primary:hover {
            background-color: #7c3aed;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
        }

        .btn-secondary {
            background-color: #f0f0f0;
            color: #333;
        }

        .btn-secondary:hover {
            background-color: #e0e0e0;
        }
    </style>

    <script>
        function openEditProfileModal() {
            document.getElementById('editProfileModal').style.display = 'flex';
        }

        function closeEditProfileModal() {
            document.getElementById('editProfileModal').style.display = 'none';
        }

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
            const validValue = input.value.replace(/[^a-zA-Z\s\-']/g, '');
            if (validValue !== input.value) {
                input.value = validValue;
            }
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const editModal = document.getElementById('editProfileModal');
            const passwordModal = document.getElementById('changePasswordModal');
            if (event.target == editModal) {
                editModal.style.display = 'none';
            }
            if (event.target == passwordModal) {
                passwordModal.style.display = 'none';
            }
        }
    </script>

    <script src="/CLASS_CARD_DROPPING_SYSTEM/js/functions.js"></script>
</body>
</html>
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
