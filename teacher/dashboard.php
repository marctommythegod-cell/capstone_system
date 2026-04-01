<?php
// teacher/dashboard.php - Teacher Dashboard

require_once '../includes/session_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

// Check if user is teacher
if ($_SESSION['user_role'] !== 'teacher') {
    redirect('/CLASS_CARD_DROPPING_SYSTEM/index.php');
}

$user_id = $_SESSION['user_id'];
$teacher_name = getUserName($pdo, $user_id);
$user_info = getUserInfo($pdo, $user_id);

// Check if password has been changed
$stmt = $pdo->prepare('SELECT password_changed FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user_data = $stmt->fetch();
$password_changed = $user_data ? $user_data['password_changed'] : true;
$show_password_modal = !$password_changed;

// Get statistics
$stmt = $pdo->prepare('SELECT COUNT(*) as total_drops FROM class_card_drops WHERE teacher_id = ?');
$stmt->execute([$user_id]);
$total_drops = $stmt->fetch()['total_drops'];

$stmt = $pdo->prepare('SELECT COUNT(*) as this_month FROM class_card_drops WHERE teacher_id = ? AND MONTH(drop_date) = MONTH(NOW()) AND YEAR(drop_date) = YEAR(NOW())');
$stmt->execute([$user_id]);
$this_month = $stmt->fetch()['this_month'];

$stmt = $pdo->prepare('SELECT COUNT(*) as this_week FROM class_card_drops WHERE teacher_id = ? AND WEEK(drop_date) = WEEK(NOW()) AND YEAR(drop_date) = YEAR(NOW())');
$stmt->execute([$user_id]);
$this_week = $stmt->fetch()['this_week'];

// Get recent drops (last 5)
$stmt = $pdo->prepare('
    SELECT ccd.*, s.name as student_name, s.student_id as student_id_number, s.course as student_course, s.status as student_status
    FROM class_card_drops ccd
    JOIN students s ON ccd.student_id = s.id
    WHERE ccd.teacher_id = ?
    ORDER BY ccd.drop_date DESC
    LIMIT 5
');
$stmt->execute([$user_id]);
$recent_drops = $stmt->fetchAll();

$message = getMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - PhilCST</title>
    <link rel="stylesheet" href="/CLASS_CARD_DROPPING_SYSTEM/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="/CLASS_CARD_DROPPING_SYSTEM/Philcst Logo (2).png" alt="PhilCST Logo" class="sidebar-logo">
                <h2>PhilCST</h2>
                <p>Teacher Portal</p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="/CLASS_CARD_DROPPING_SYSTEM/teacher/dashboard.php" class="nav-item active">
                    <span>Overview</span>
                </a>
                <a href="/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_class_card.php" class="nav-item">
                    <span>Drop Class Card</span>
                </a>
                <a href="/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_history.php" class="nav-item">
                    <span>Drop History</span>
                </a>
                <a href="/CLASS_CARD_DROPPING_SYSTEM/teacher/settings.php" class="nav-item">
                    <span>Settings</span>
                </a>
                <a href="#" class="nav-item logout-item" onclick="showLogoutModal(); return false;">
                    <span>Logout</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <p class="sidebar-footer-name"><?php echo htmlspecialchars($user_info['name']); ?></p>
                <p class="sidebar-footer-dept"><?php echo htmlspecialchars($user_info['department'] ?: 'Teacher'); ?></p>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="top-bar">
                <h1>Dashboard Overview</h1>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($teacher_name); ?> (Teacher)</span>
                </div>
            </header>
            
            <div class="content-wrapper">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message['type']; ?>">
                        <?php echo htmlspecialchars($message['text']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Statistics Section -->
                <section class="section">
                    <h2>Statistics</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $total_drops; ?></div>
                            <div class="stat-label">Total Class Card Drops</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $this_month; ?></div>
                            <div class="stat-label">This Month</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $this_week; ?></div>
                            <div class="stat-label">This Week</div>
                        </div>
                    </div>
                </section>
                
                <!-- Quick Actions Section -->
                <section class="section">
                    <h2>Quick Actions</h2>
                    <div class="action-buttons">
                        <a href="/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_class_card.php" class="btn btn-primary btn-large">
                            Drop Student Class Card
                        </a>
                        <a href="/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_history.php" class="btn btn-secondary btn-large">
                            View Drop History
                        </a>
                    </div>
                </section>
                
                <!-- Recent Drops Section -->
                <section class="section">
                    <h2>Recent Class Card Drops</h2>
                    <?php if (count($recent_drops) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Course</th>
                                        <th>Subject</th>
                                        <th>Drop Date & Time</th>
                                        <th>Class Card Status</th>
                                        <th>Teacher Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_drops as $drop): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($drop['student_id_number']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['student_course']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['subject_no'] . ' - ' . $drop['subject_name']); ?></td>
                                            <td><?php echo formatDate($drop['drop_date']); ?></td>
                                            <td><span class="status status-<?php echo strtolower($drop['status']); ?>"><?php echo htmlspecialchars($drop['status']); ?></span></td>
                                            <td><?php echo htmlspecialchars(substr($drop['remarks'], 0, 50)); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div style="text-align: center; margin-top: 15px;">
                            <a href="/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_history.php" class="btn btn-secondary">View All Drops</a>
                        </div>
                    <?php else: ?>
                        <p class="no-data">No class cards dropped yet.</p>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>

    <!-- Change Password Modal (First Time Login) -->
    <div id="changePasswordModal" class="modal" style="display: <?php echo $show_password_modal ? 'flex' : 'none'; ?>;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header" style="background-color: #ff9800;">
                <h2 style="color: white; margin: 0;">Change Your Default Password</h2>
            </div>
            <div class="modal-body" style="padding: 25px;">
                <div style="margin-bottom: 20px; padding: 15px; background-color: #fff3cd; border-left: 4px solid #ff9800; border-radius: 4px;">
                    <p style="margin: 0; color: #856404; line-height: 1.6;">
                        <strong>Welcome!</strong> For security reasons, you must change your default password on your first login. Please create a strong password below.
                    </p>
                </div>
                <form method="POST" action="/CLASS_CARD_DROPPING_SYSTEM/includes/api.php?action=change_password" id="changePasswordForm">
                    <input type="hidden" name="is_first_time" value="1">
                    
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
                        <label for="confirmPassword">Confirm Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="confirmPassword" name="confirm_password" required placeholder="Re-enter your new password">
                            <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword')">
                                <svg class="eye-icon eye-show" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                <svg class="eye-icon eye-hide" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                            </button>
                        </div>
                        <div id="confirm-match" style="margin-top: 8px; font-size: 0.85em;"></div>
                    </div>

                    <div class="modal-footer" style="padding: 0; margin-top: 20px; border-top: 1px solid #ddd; display: flex; gap: 10px; justify-content: flex-end; padding-top: 20px;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">Change Password</button>
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
            background-color: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 30px rgba(0,0,0,0.4);
        }
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #ddd;
            border-radius: 8px 8px 0 0;
        }
        .modal-body {
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-footer {
            padding: 15px 20px;
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

        document.getElementById('confirmPassword').addEventListener('input', checkConfirmMatch);

        // Prevent closing modal on first password change
        <?php if ($show_password_modal): ?>
        window.addEventListener('beforeunload', function(e) {
            // This prevents navigation away from the page
            // but doesn't work perfectly - the form submission will work fine
        });
        <?php endif; ?>
    </script>
    
    <script src="/CLASS_CARD_DROPPING_SYSTEM/js/functions.js"></script>
</body>
</html>
