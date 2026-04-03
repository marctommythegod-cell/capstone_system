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

// This month's drops
$current_month = date('m');
$current_year = date('Y');
$stmt = $pdo->prepare('
    SELECT COUNT(*) as total FROM class_card_drops
    WHERE MONTH(drop_date) = ? AND YEAR(drop_date) = ?
');
$stmt->execute([$current_month, $current_year]);
$this_month_drops = $stmt->fetch()['total'];

// This week's drops
$stmt = $pdo->prepare('
    SELECT COUNT(*) as total FROM class_card_drops
    WHERE WEEK(drop_date) = WEEK(NOW()) AND YEAR(drop_date) = YEAR(NOW())
');
$stmt->execute();
$this_week_drops = $stmt->fetch()['total'];

// Approved dropped cards with pagination
$stmt = $pdo->prepare('
    SELECT COUNT(*) as total FROM class_card_drops
    WHERE status IN ("Dropped", "Undropped")
');
$stmt->execute();
$total_approved_drops = $stmt->fetch()['total'];

$pagination = getPaginationData($total_approved_drops, 10); // 10 items per page

$stmt = $pdo->prepare('
    SELECT ccd.*, s.name as student_name, s.guardian_name, s.student_id, u.name as teacher_name
    FROM class_card_drops ccd
    JOIN students s ON ccd.student_id = s.id
    JOIN users u ON ccd.teacher_id = u.id
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
                    <h2>System Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card clickable-stat" onclick="showDropsModal('total', 'Total Class Card Drops')">
                            <h3><?php echo $total_drops; ?></h3>
                            <p>Total Class Cards Dropped</p>
                            <small>Click to view records</small>
                        </div>
                        <div class="stat-card clickable-stat" onclick="showDropsModal('month', 'Class Card Drops - This Month')">
                            <h3><?php echo $this_month_drops; ?></h3>
                            <p>This Month's Drops</p>
                            <small>Click to view records</small>
                        </div>
                        <div class="stat-card clickable-stat" onclick="showDropsModal('week', 'Class Card Drops - This Week')">
                            <h3><?php echo $this_week_drops; ?></h3>
                            <p>This Week's Drops</p>
                            <small>Click to view records</small>
                        </div>
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
                    </div>
                </section>
                
                <!-- Approved Dropped Cards Section -->
                <section class="section">
                    <h2>Approved Dropped Cards <span style="font-weight: normal; font-size: 0.9em; color: #666;">(<span id="dropsTable-count"><?php echo $pagination['total_items']; ?></span> total, page <?php echo $pagination['current_page']; ?> of <?php echo max(1, $pagination['total_pages']); ?>)</span></h2>
                    <?php if (count($approved_drops) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Guardian Name</th>
                                        <th>Subject</th>
                                        <th>Teacher</th>
                                        <th>Dropped Date & Time</th>
                                        <th>Approved Date & Time</th>
                                        <th>Class Card Status</th>
                                        <th>Teacher Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($approved_drops as $drop): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($drop['student_id']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['guardian_name'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($drop['subject_no'] . ' - ' . $drop['subject_name']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['teacher_name']); ?></td>
                                            <td><?php echo formatDate($drop['drop_date']); ?></td>
                                            <td><?php echo $drop['approved_date'] ? formatDate($drop['approved_date']) : '-'; ?></td>
                                            <td>
                                                <span class="status status-<?php echo strtolower($drop['status']); ?>">
                                                    <?php echo htmlspecialchars($drop['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars(substr($drop['remarks'], 0, 50)); ?></td>
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
