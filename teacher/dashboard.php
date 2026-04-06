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
$stmt = $pdo->prepare('SELECT COUNT(*) as this_month FROM class_card_drops WHERE teacher_id = ? AND MONTH(drop_date) = MONTH(NOW()) AND YEAR(drop_date) = YEAR(NOW())');
$stmt->execute([$user_id]);
$this_month = $stmt->fetch()['this_month'];

$stmt = $pdo->prepare('SELECT COUNT(*) as this_week FROM class_card_drops WHERE teacher_id = ? AND WEEK(drop_date) = WEEK(NOW()) AND YEAR(drop_date) = YEAR(NOW())');
$stmt->execute([$user_id]);
$this_week = $stmt->fetch()['this_week'];

$stmt = $pdo->prepare('SELECT COUNT(*) as this_day FROM class_card_drops WHERE teacher_id = ? AND DATE(drop_date) = DATE(NOW())');
$stmt->execute([$user_id]);
$this_day = $stmt->fetch()['this_day'];

// Get recent drops with pagination
$stmt = $pdo->prepare('
    SELECT COUNT(*) as total FROM class_card_drops WHERE teacher_id = ?
');
$stmt->execute([$user_id]);
$total_recent_drops = $stmt->fetch()['total'];

$pagination = getPaginationData($total_recent_drops, 10); // 10 items per page

$stmt = $pdo->prepare('
    SELECT ccd.*, s.name as student_name, s.student_id as student_id_number, s.course as student_course, s.status as student_status, s.year as student_year, s.guardian_name, s.address as student_address, u.name as teacher_name, pur.retrieve_date as undrop_retrieve_date, pur.undrop_remarks
    FROM class_card_drops ccd
    JOIN students s ON ccd.student_id = s.id
    JOIN users u ON ccd.teacher_id = u.id
    LEFT JOIN philcst_undrop_records pur ON ccd.id = pur.drop_id
    WHERE ccd.teacher_id = ?
    ORDER BY ccd.drop_date DESC
    LIMIT ' . intval($pagination['limit']) . ' OFFSET ' . intval($pagination['offset']) . '
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
                <div class="nav-item submenu-trigger" onclick="toggleSubmenu(this)">
                    <span>Drop History</span>
                </div>
                <div class="submenu" id="historySubmenu">
                    <a href="/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_history.php" class="submenu-item">All Records</a>
                    <a href="/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_history.php?year=1" class="submenu-item">1st Year</a>
                    <a href="/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_history.php?year=2" class="submenu-item">2nd Year</a>
                    <a href="/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_history.php?year=3" class="submenu-item">3rd Year</a>
                    <a href="/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_history.php?year=4" class="submenu-item">4th Year</a>
                </div>
                <a href="/CLASS_CARD_DROPPING_SYSTEM/teacher/profile.php" class="nav-item">
                    <span>Profile</span>
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
        <!-- Sidebar Toggle Button -->
        <button class="sidebar-toggle-btn" onclick="toggleSidebar()" title="Toggle Sidebar">≡</button>
        
        <main class="main-content">
            <header class="top-bar">
            </header>
            
            <div class="content-wrapper">
                <!-- Global Header - Above Everything -->
                <div class="global-header">
                    <div class="header-left">
                        <button class="sidebar-toggle" onclick="toggleSidebar()" id="sidebarToggleBtn" title="Toggle Sidebar">
                            <span></span>
                            <span></span>
                            <span></span>
                        </button>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message['type']; ?>">
                        <?php echo htmlspecialchars($message['text']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Statistics Section -->
                <section class="section">
                    <h2>Statistics</h2>
                    <div class="stats-grid">
                        <div class="stat-card clickable-stat" onclick="showTeacherDropsModal('day', 'Your Drops - Today')">
                            <div class="stat-value"><?php echo $this_day; ?></div>
                            <div class="stat-label">This Day</div>
                            <small>Click to view records</small>
                        </div>
                        <div class="stat-card clickable-stat" onclick="showTeacherDropsModal('week', 'Your Drops - This Week')">
                            <div class="stat-value"><?php echo $this_week; ?></div>
                            <div class="stat-label">This Week</div>
                            <small>Click to view records</small>
                        </div>
                        <div class="stat-card clickable-stat" onclick="showTeacherDropsModal('month', 'Your Drops - This Month')">
                            <div class="stat-value"><?php echo $this_month; ?></div>
                            <div class="stat-label">This Month</div>
                            <small>Click to view records</small>
                        </div>
                    </div>
                </section>
                
                <!-- Recent Drops Section -->
                <section class="section">
                    <h2>Recent Class Card Drops <span style="font-weight: normal; font-size: 0.9em; color: #666;">(<span id="recentDropsTable-count"><?php echo $pagination['total_items']; ?></span> total, page <?php echo $pagination['current_page']; ?> of <?php echo max(1, $pagination['total_pages']); ?>)</span></h2>
                    <?php if (count($recent_drops) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Course</th>
                                        <th>Year</th>
                                        <th>Subject</th>
                                        <th>Drop Date & Time</th>
                                        <th>Class Card Status</th>
                                        <th>Teacher Remarks</th>
                                        <th>Detail</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_drops as $drop): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($drop['student_id_number']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['student_course']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['student_year'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($drop['subject_no'] . ' - ' . $drop['subject_name']); ?></td>
                                            <td><?php echo formatDate($drop['drop_date']); ?></td>
                                            <td><span class="status status-<?php echo strtolower($drop['status']); ?>"><?php echo htmlspecialchars($drop['status']); ?></span></td>
                                            <td><span class="remarks-cell" style="word-break: break-word;"><?php $remarks_text = htmlspecialchars($drop['remarks']); echo strlen($remarks_text) > 50 ? substr($remarks_text, 0, 50) . '... <a href="javascript:void(0)" onclick="showRemarksModal(\'' . addslashes($remarks_text) . '\', \'Teacher Remarks\')" style="color: #a78bfa; font-weight: 600;">See More</a>' : $remarks_text; ?></span></td>
                                            <td style="text-align: center;"><button class="detail-btn" onclick="showDropDetailModal(<?php echo htmlspecialchars(json_encode($drop)); ?>)" title="View Details"><span style="font-weight: 700; color: #a78bfa;">i</span></button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php echo renderPaginationControls($pagination, '/CLASS_CARD_DROPPING_SYSTEM/teacher/dashboard.php'); ?>
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

        // Toggle submenu function
        function toggleSubmenu(trigger) {
            const submenu = trigger.nextElementSibling;
            if (submenu && submenu.classList.contains('submenu')) {
                submenu.classList.toggle('active');
                trigger.classList.toggle('active');
            }
        }

        // Teacher drops modal functions
        function showTeacherDropsModal(type, title) {
            fetch('/CLASS_CARD_DROPPING_SYSTEM/includes/api.php?action=get_teacher_drops&type=' + encodeURIComponent(type))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayTeacherDropsModal(data.drops, title, type);
                    } else {
                        alert('Error loading data: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading data');
                });
        }

        function displayTeacherDropsModal(drops, title, type) {
            const existing = document.getElementById('dropsModal');
            if (existing) existing.remove();

            const modal = document.createElement('div');
            modal.id = 'dropsModal';
            modal.className = 'drops-modal';
            
            let dropsTableHTML = '';
            if (drops.length > 0) {
                dropsTableHTML = '<table class="drops-modal-table"><thead><tr><th>Student ID</th><th>Student Name</th><th>Subject</th><th>Drop Date</th><th>Status</th></tr></thead><tbody>';
                drops.forEach(drop => {
                    dropsTableHTML += `<tr>
                        <td>${escapeHtml(drop.student_id)}</td>
                        <td>${escapeHtml(drop.student_name)}</td>
                        <td>${escapeHtml(drop.subject_no)} - ${escapeHtml(drop.subject_name)}</td>
                        <td>${escapeHtml(drop.drop_date_formatted)}</td>
                        <td><span class="status status-${drop.status.toLowerCase()}">${escapeHtml(drop.status)}</span></td>
                    </tr>`;
                });
                dropsTableHTML += '</tbody></table>';
            } else {
                dropsTableHTML = '<p class="no-drops-message">No records found.</p>';
            }

            modal.innerHTML = `
                <div class="drops-modal-box">
                    <div class="drops-modal-header">
                        <h3>${escapeHtml(title)}</h3>
                        <button class="drops-modal-close" onclick="closeTeacherDropsModal()">×</button>
                    </div>
                    <div class="drops-modal-body">
                        <div class="drops-modal-count">Total: <strong>${drops.length}</strong></div>
                        ${dropsTableHTML}
                    </div>
                    <div class="drops-modal-footer">
                        <button class="btn-close-drops-modal" onclick="closeTeacherDropsModal()">Close</button>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);

            modal.addEventListener('click', function(e) {
                if (e.target === modal) closeTeacherDropsModal();
            });

            document.addEventListener('keydown', function handler(e) {
                if (e.key === 'Escape') {
                    closeTeacherDropsModal();
                    document.removeEventListener('keydown', handler);
                }
            });
        }

        function closeTeacherDropsModal() {
            const modal = document.getElementById('dropsModal');
            if (modal) modal.remove();
        }

        // Detail modal for individual drop record
        function showDropDetailModal(drop) {
            const modal = document.createElement('div');
            modal.id = 'dropDetailModal';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.6);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 1000;
                backdrop-filter: blur(5px);
                padding: 20px;
            `;

            const yearLabels = {1: '1st Year', 2: '2nd Year', 3: '3rd Year', 4: '4th Year'};
            const yearDisplay = yearLabels[drop.student_year] || drop.student_year;
            
            modal.innerHTML = `
                <div style="
                    background: white;
                    border-radius: 16px;
                    width: 100%;
                    max-width: 850px;
                    max-height: 85vh;
                    overflow-y: auto;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
                ">
                    <div style="
                        background: linear-gradient(135deg, var(--primary-color), #9b59b6);
                        color: white;
                        padding: 28px 32px;
                        border-radius: 16px 16px 0 0;
                        font-size: 1.4em;
                        font-weight: 700;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        letter-spacing: 0.3px;
                    ">
                        <span>Student Information & Class Card Dropping Information</span>
                        <button onclick="closeDropDetailModal()" style="
                            background: rgba(255, 255, 255, 0.25);
                            border: none;
                            color: white;
                            font-size: 28px;
                            cursor: pointer;
                            width: 40px;
                            height: 40px;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            transition: all 0.3s;
                            line-height: 1;
                        " onmouseover="this.style.backgroundColor='rgba(255, 255, 255, 0.35); this.style.transform='scale(1.1)'" onmouseout="this.style.backgroundColor='rgba(255, 255, 255, 0.25); this.style.transform='scale(1)'">×</button>
                    </div>
                    <div style="padding: 40px 32px; background: #f8f6ff;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
                            <div>
                                <div style="
                                    background: linear-gradient(135deg, rgba(167, 139, 250, 0.1), rgba(155, 89, 182, 0.05));
                                    padding: 24px;
                                    border-radius: 14px;
                                    border-left: 5px solid var(--primary-color);
                                ">
                                    <h3 style="
                                        color: var(--primary-color);
                                        margin: 0 0 24px 0;
                                        font-size: 1.25em;
                                        font-weight: 700;
                                    ">
                                        Student Information
                                    </h3>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Student ID</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${escapeHtml(drop.student_id_number)}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Full Name</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${escapeHtml(drop.student_name)}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Course</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${escapeHtml(drop.student_course)}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Year Level</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${escapeHtml(yearDisplay)}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Guardian Name</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${escapeHtml(drop.guardian_name || 'N/A')}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Address</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600; word-break: break-word; line-height: 1.5;">${escapeHtml(drop.student_address || 'N/A')}</p>
                                    </div>
                                    <div>
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Student Status</label>
                                        <p style="margin: 0; color: #1f2937;">
                                            <span class="status status-${drop.student_status.toLowerCase()}" style="padding: 6px 12px; border-radius: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.95em;">${escapeHtml(drop.student_status.charAt(0).toUpperCase() + drop.student_status.slice(1))}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div style="
                                    background: linear-gradient(135deg, rgba(167, 139, 250, 0.1), rgba(155, 89, 182, 0.05));
                                    padding: 24px;
                                    border-radius: 14px;
                                    border-left: 5px solid #9b59b6;
                                ">
                                    <h3 style="
                                        color: #9b59b6;
                                        margin: 0 0 24px 0;
                                        font-size: 1.25em;
                                        font-weight: 700;
                                    ">
                                        Class Card Dropping Information
                                    </h3>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Teacher Name</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${escapeHtml(drop.teacher_name || 'N/A')}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Subject</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${escapeHtml(drop.subject_no)} - ${escapeHtml(drop.subject_name)}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Class Card Status</label>
                                        <p style="margin: 0; color: #1f2937;">
                                            <span class="status status-${drop.status.toLowerCase()}">${escapeHtml(drop.status)}</span>
                                        </p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Dropped Date & Time</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${new Date(drop.drop_date).toLocaleString()}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Undropped Date & Time</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${drop.undrop_retrieve_date && drop.undrop_retrieve_date !== '0000-00-00 00:00:00' ? new Date(drop.undrop_retrieve_date).toLocaleString() : 'N/A'}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="padding: 24px 32px; border-top: 2px solid #e9d5ff; display: flex; gap: 12px; justify-content: flex-end; background: white;">
                        <button onclick="closeDropDetailModal()" style="
                            padding: 12px 28px;
                            background-color: #e9d5ff;
                            color: var(--primary-color);
                            border: none;
                            border-radius: 10px;
                            cursor: pointer;
                            font-weight: 700;
                            transition: all 0.3s;
                            font-size: 1em;
                        " onmouseover="this.style.backgroundColor='#ddd6fe'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 16px rgba(167, 139, 250, 0.3)'" onmouseout="this.style.backgroundColor='#e9d5ff'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">Close</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);

            modal.addEventListener('click', function(e) {
                if (e.target === modal) closeDropDetailModal();
            });

            document.addEventListener('keydown', function handler(e) {
                if (e.key === 'Escape') {
                    closeDropDetailModal();
                    document.removeEventListener('keydown', handler);
                }
            });
        }

        function closeDropDetailModal() {
            const modal = document.getElementById('dropDetailModal');
            if (modal) modal.remove();
        }

        // Remarks modal function
        function showRemarksModal(remarksText, remarksTitle) {
            const modal = document.createElement('div');
            modal.className = 'remarks-modal';
            modal.id = 'remarksModal';
            
            modal.innerHTML = `
                <div class="remarks-modal-box">
                    <div class="remarks-modal-header">
                        <h3>${remarksTitle}</h3>
                        <button class="remarks-modal-close" onclick="closeRemarksModal()">×</button>
                    </div>
                    <div class="remarks-modal-body">
                        <p>${remarksText}</p>
                    </div>
                    <div class="remarks-modal-footer">
                        <button class="btn-close-remarks-modal" onclick="closeRemarksModal()">Close</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            modal.addEventListener('click', function(e) {
                if (e.target === modal) closeRemarksModal();
            });
            
            document.addEventListener('keydown', function handler(e) {
                if (e.key === 'Escape') {
                    closeRemarksModal();
                    document.removeEventListener('keydown', handler);
                }
            });
        }

        function closeRemarksModal() {
            const modal = document.getElementById('remarksModal');
            if (modal) modal.remove();
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
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
