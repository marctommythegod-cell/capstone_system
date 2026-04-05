<?php
// admin/dashboard.php - Admin Dashboard

require_once '../includes/session_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

// Check if user is admin
if ($_SESSION['user_role'] !== 'admin') {
    redirect('/CLASS_CARD_DROPPING_SYSTEM/index.php');
}

$admin_name = getUserName($pdo, $_SESSION['user_id']);
$user_info = getUserInfo($pdo, $_SESSION['user_id']);

// Get statistics
$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM class_card_drops');
$stmt->execute();
$total_drops = $stmt->fetch()['total'];

$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM students');
$stmt->execute();
$total_students = $stmt->fetch()['total'];

$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM users WHERE role = "teacher"');
$stmt->execute();
$total_teachers = $stmt->fetch()['total'];

// Today's dropped
$stmt = $pdo->prepare('
    SELECT COUNT(*) as total FROM class_card_drops
    WHERE DATE(drop_date) = CURDATE() AND status = "Dropped"
');
$stmt->execute();
$today_dropped = $stmt->fetch()['total'];

// Today's undropped
$stmt = $pdo->prepare('
    SELECT COUNT(*) as total FROM class_card_drops
    WHERE DATE(drop_date) = CURDATE() AND status = "Undropped"
');
$stmt->execute();
$today_undropped = $stmt->fetch()['total'];

// This month's drops
$current_month = date('m');
$current_year = date('Y');
$stmt = $pdo->prepare('
    SELECT COUNT(*) as total FROM class_card_drops
    WHERE MONTH(drop_date) = ? AND YEAR(drop_date) = ? AND status = "Dropped"
');
$stmt->execute([$current_month, $current_year]);
$this_month_dropped = $stmt->fetch()['total'];

// Total cancelled cards
$stmt = $pdo->prepare('
    SELECT COUNT(*) as total FROM class_card_drops
    WHERE status = "Cancelled"
');
$stmt->execute();
$total_cancelled = $stmt->fetch()['total'];

// Approved dropped cards with pagination
$stmt = $pdo->prepare('
    SELECT COUNT(*) as total FROM class_card_drops
    WHERE status IN ("Dropped", "Undropped")
');
$stmt->execute();
$total_approved_drops = $stmt->fetch()['total'];

$pagination = getPaginationData($total_approved_drops, 10); // 10 items per page

$stmt = $pdo->prepare('
    SELECT ccd.*, s.name as student_name, s.guardian_name, s.student_id, s.course, s.year, s.status as student_status, s.address, s.email, u.name as teacher_name, pur.retrieve_date as undrop_retrieve_date, pur.undrop_remarks
    FROM class_card_drops ccd
    JOIN students s ON ccd.student_id = s.id
    JOIN users u ON ccd.teacher_id = u.id
    LEFT JOIN philcst_undrop_records pur ON ccd.id = pur.drop_id
    WHERE ccd.status IN ("Dropped", "Undropped")
    ORDER BY ccd.drop_date DESC
    LIMIT ' . intval($pagination['limit']) . ' OFFSET ' . intval($pagination['offset']) . '
');
$stmt->execute();
$approved_drops = $stmt->fetchAll();

$message = getMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PhilCST</title>
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
                <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/dashboard.php" class="nav-item active">
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
            <div class="content-wrapper">
                <!-- Global Header - Above Everything -->
                <div class="global-header">

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message['type']; ?>">
                        <?php echo htmlspecialchars($message['text']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Statistics Section -->
                <section class="section">
                    <h2>Statistics</h2>
                    <div class="stats-grid-columns">
                        <!-- Left Column: Drop Statistics -->
                        <div class="stats-column">
                            <div class="stat-card">
                                <h3><?php echo $today_dropped; ?></h3>
                                <p>Today Dropped</p>
                            </div>
                            <div class="stat-card">
                                <h3><?php echo $today_undropped; ?></h3>
                                <p>Today Undropped</p>
                            </div>
                            <div class="stat-card">
                                <h3><?php echo $this_month_dropped; ?></h3>
                                <p>This Month Dropped</p>
                            </div>
                            <div class="stat-card">
                                <h3><?php echo $total_drops; ?></h3>
                                <p>Total Class Cards Dropped</p>
                            </div>
                        </div>
                        
                        <!-- Right Column: User Statistics -->
                        <div class="stats-column">
                            <div class="stat-card clickable-stat" onclick="window.location.href='/CLASS_CARD_DROPPING_SYSTEM/admin/students.php'" style="cursor: pointer;">
                                <h3><?php echo $total_students; ?></h3>
                                <p>Total Students</p>
                                <small>Click to manage</small>
                            </div>
                            <div class="stat-card clickable-stat" onclick="window.location.href='/CLASS_CARD_DROPPING_SYSTEM/admin/teachers.php'" style="cursor: pointer;">
                                <h3><?php echo $total_teachers; ?></h3>
                                <p>Total Teachers</p>
                                <small>Click to manage</small>
                            </div>
                            <div class="stat-card clickable-stat" onclick="window.location.href='/CLASS_CARD_DROPPING_SYSTEM/admin/cancelled_class_card.php'" style="cursor: pointer;">
                                <h3><?php echo $total_cancelled; ?></h3>
                                <p>Total Cancelled Class Cards</p>
                                <small>Click to view</small>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Approved Dropped Cards Section -->
                <section class="section">
                    <h2>Approved Dropped Class Card <span style="font-weight: normal; font-size: 0.9em; color: #666;">(<span id="dropsTable-count"><?php echo $pagination['total_items']; ?></span> total, page <?php echo $pagination['current_page']; ?> of <?php echo max(1, $pagination['total_pages']); ?>)</span></h2>
                    <?php if (count($approved_drops) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Course</th>
                                        <th>Year</th>
                                        <th>Subject</th>
                                        <th>Teacher</th>
                                        <th>Dropped Date & Time</th>
                                        <th>Approved Date & Time</th>
                                        <th>Class Card Status</th>
                                        <th>Teacher Remarks</th>
                                        <th>Detail</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($approved_drops as $drop): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($drop['student_id']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['course'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($drop['year'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($drop['subject_no'] . ' - ' . $drop['subject_name']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['teacher_name']); ?></td>
                                            <td><?php echo formatDate($drop['drop_date']); ?></td>
                                            <td><?php echo $drop['approved_date'] ? formatDate($drop['approved_date']) : '-'; ?></td>
                                            <td>
                                                <span class="status status-<?php echo strtolower($drop['status']); ?>">
                                                    <?php echo htmlspecialchars($drop['status']); ?>
                                                </span>
                                            </td>
                                            <td><span class="remarks-cell" style="word-break: break-word;"><?php $remarks_text = htmlspecialchars($drop['remarks']); echo strlen($remarks_text) > 50 ? substr($remarks_text, 0, 50) . '... <a href="javascript:void(0)" onclick="showRemarksModal(\'' . addslashes($remarks_text) . '\', \'Teacher Remarks\')" style="color: #a78bfa; font-weight: 600;">See More</a>' : $remarks_text; ?></span></td>
                                            <td style="text-align: center;"><button class="detail-btn" onclick="showStudentDetailModal(<?php echo htmlspecialchars(json_encode($drop)); ?>)" title="View Details"><span style="font-weight: 700; color: #a78bfa;">i</span></button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php echo renderPaginationControls($pagination, '/CLASS_CARD_DROPPING_SYSTEM/admin/dashboard.php'); ?>
                    <?php else: ?>
                        <p class="no-data">No approved dropped cards yet.</p>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>
    <script src="/CLASS_CARD_DROPPING_SYSTEM/js/functions.js"></script>
    <script>
        // Get drops modal data via AJAX
        function showDropsModal(type, title) {
            // Fetch data from server
            fetch('/CLASS_CARD_DROPPING_SYSTEM/includes/api.php?action=get_drops&type=' + encodeURIComponent(type))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayDropsModal(data.drops, title, type);
                    } else {
                        alert('Error loading data: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading data');
                });
        }

        function displayDropsModal(drops, title, type) {
            // Remove existing modal if any
            const existing = document.getElementById('dropsModal');
            if (existing) existing.remove();

            const modal = document.createElement('div');
            modal.id = 'dropsModal';
            modal.className = 'drops-modal';
            
            let dropsTableHTML = '';
            if (drops.length > 0) {
                dropsTableHTML = '<table class="drops-modal-table"><thead><tr><th>Student ID</th><th>Student Name</th><th>Subject</th><th>Teacher</th><th>Drop Date</th><th>Status</th></tr></thead><tbody>';
                drops.forEach(drop => {
                    dropsTableHTML += `<tr>
                        <td>${escapeHtml(drop.student_id)}</td>
                        <td>${escapeHtml(drop.student_name)}</td>
                        <td>${escapeHtml(drop.subject_no)} - ${escapeHtml(drop.subject_name)}</td>
                        <td>${escapeHtml(drop.teacher_name)}</td>
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
                        <button class="drops-modal-close" onclick="closeDropsModal()">×</button>
                    </div>
                    <div class="drops-modal-body">
                        <div class="drops-modal-count">Total: <strong>${drops.length}</strong></div>
                        ${dropsTableHTML}
                    </div>
                    <div class="drops-modal-footer">
                        <button class="btn-close-drops-modal" onclick="closeDropsModal()">Close</button>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);

            // Close on backdrop click
            modal.addEventListener('click', function(e) {
                if (e.target === modal) closeDropsModal();
            });

            // Close on Escape key
            document.addEventListener('keydown', function handler(e) {
                if (e.key === 'Escape') {
                    closeDropsModal();
                    document.removeEventListener('keydown', handler);
                }
            });
        }

        function closeDropsModal() {
            const modal = document.getElementById('dropsModal');
            if (modal) modal.remove();
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showStudentDetailModal(recordData) {
            const modal = document.createElement('div');
            modal.id = 'detailModal';
            modal.className = 'modal';
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

            const retrieveDate = (recordData.undrop_retrieve_date && recordData.undrop_retrieve_date !== '0000-00-00 00:00:00') ? recordData.undrop_retrieve_date : 'N/A';
            
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
                        <button onclick="closeStudentDetailModal()" style="
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
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${recordData.student_id}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Full Name</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${recordData.student_name}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Course</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${recordData.course || 'N/A'}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Year Level</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${recordData.year || 'N/A'}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Guardian Name</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${recordData.guardian_name || 'N/A'}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Address</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600; word-break: break-word; line-height: 1.5;">${recordData.address || 'N/A'}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Email</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600; word-break: break-word;">${recordData.email || 'N/A'}</p>
                                    </div>
                                    <div>
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Student Status</label>
                                        <p style="margin: 0; color: #1f2937;">
                                            <span style="padding: 6px 12px; border-radius: 6px; display: inline-block; font-weight: 600; font-size: 0.95em; background-color: #e0e7ff; color: #3730a3;">${recordData.student_status ? recordData.student_status.charAt(0).toUpperCase() + recordData.student_status.slice(1) : 'N/A'}</span>
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
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Teacher</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${recordData.teacher_name}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Subject</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${recordData.subject_no} - ${recordData.subject_name}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Class Card Status</label>
                                        <p style="margin: 0; color: #1f2937;">
                                            <span style="padding: 6px 12px; border-radius: 6px; display: inline-block; font-weight: 600; font-size: 0.95em; background-color: #dbeafe; color: #1e40af;">${recordData.status}</span>
                                        </p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Dropped Date & Time</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${new Date(recordData.drop_date).toLocaleString()}</p>
                                    </div>
                                    <div>
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Retrieved Date & Time</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${retrieveDate !== 'N/A' ? new Date(retrieveDate).toLocaleString() : 'N/A'}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);

            modal.addEventListener('click', function(e) {
                if (e.target === modal) closeStudentDetailModal();
            });

            document.addEventListener('keydown', function handler(e) {
                if (e.key === 'Escape') {
                    closeStudentDetailModal();
                    document.removeEventListener('keydown', handler);
                }
            });
        }

        function closeStudentDetailModal() {
            const modal = document.getElementById('detailModal');
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
</body>
</html>
