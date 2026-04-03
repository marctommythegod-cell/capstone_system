<?php
// teacher/drop_history.php - Class Card Drop History

require_once '../includes/session_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

// Check if user is teacher
if ($_SESSION['user_role'] !== 'teacher') {
    redirect('/CLASS_CARD_DROPPING_SYSTEM/index.php');
}

$user_id = $_SESSION['user_id'];
$teacher_name = getUserName($pdo, user_id: $user_id);
$user_info = getUserInfo($pdo, $user_id);

// Get year filter from query parameter
$year_filter = isset($_GET['year']) ? intval($_GET['year']) : null;

// Fetch all teacher's drops with pagination and year filter
$count_query = '
    SELECT COUNT(*) as total FROM class_card_drops ccd
    JOIN students s ON ccd.student_id = s.id
    JOIN users u ON ccd.teacher_id = u.id
    WHERE ccd.teacher_id = ?
';
$count_params = [$user_id];

if ($year_filter) {
    $count_query .= ' AND s.year = ?';
    $count_params[] = $year_filter;
}

$stmt = $pdo->prepare($count_query);
$stmt->execute($count_params);
$total_drops_count = $stmt->fetch()['total'];

$pagination = getPaginationData($total_drops_count, 20); // 20 items per page

$query = '
    SELECT ccd.*, s.name as student_name, s.guardian_name, s.student_id as student_id_number, s.course as student_course, s.status as student_status, s.year as student_year, s.address as student_address, u.name as teacher_name
    FROM class_card_drops ccd
    JOIN students s ON ccd.student_id = s.id
    JOIN users u ON ccd.teacher_id = u.id
    WHERE ccd.teacher_id = ?
';
$query_params = [$user_id];

if ($year_filter) {
    $query .= ' AND s.year = ?';
    $query_params[] = $year_filter;
}

$query .= ' ORDER BY s.year, ccd.drop_date DESC
    LIMIT ' . intval($pagination['limit']) . ' OFFSET ' . intval($pagination['offset']) . '
';

$stmt = $pdo->prepare($query);
$stmt->execute($query_params);
$drops = $stmt->fetchAll();

// Group drops by year level
$dropsByYear = [1 => [], 2 => [], 3 => [], 4 => []];
foreach ($drops as $drop) {
    $year = $drop['student_year'] ?: 1;
    if (!isset($dropsByYear[$year])) {
        $dropsByYear[$year] = [];
    }
    $dropsByYear[$year][] = $drop;
}

// Get statistics
$stats_query = 'SELECT COUNT(*) as total_drops FROM class_card_drops WHERE teacher_id = ?';
$stmt = $pdo->prepare($stats_query);
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

$message = getMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Card Drop History - PhilCST</title>
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
                <a href="/CLASS_CARD_DROPPING_SYSTEM/teacher/dashboard.php" class="nav-item">
                    <span>Overview</span>
                </a>
                <a href="/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_class_card.php" class="nav-item">
                    <span>Drop Class Card</span>
                </a>
                <div class="nav-item submenu-trigger active" onclick="toggleSubmenu(this)">
                    <span>Drop History</span>
                </div>
                <div class="submenu active" id="historySubmenu">
                    <a href="/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_history.php" class="submenu-item <?php echo !$year_filter ? 'active' : ''; ?>">All Records</a>
                    <a href="/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_history.php?year=1" class="submenu-item <?php echo $year_filter === 1 ? 'active' : ''; ?>">1st Year</a>
                    <a href="/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_history.php?year=2" class="submenu-item <?php echo $year_filter === 2 ? 'active' : ''; ?>">2nd Year</a>
                    <a href="/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_history.php?year=3" class="submenu-item <?php echo $year_filter === 3 ? 'active' : ''; ?>">3rd Year</a>
                    <a href="/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_history.php?year=4" class="submenu-item <?php echo $year_filter === 4 ? 'active' : ''; ?>">4th Year</a>
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
        <main class="main-content">
            <header class="top-bar">
                <button class="sidebar-toggle" onclick="toggleSidebar()" id="sidebarToggleBtn">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <h1>
                    Class Card Drop History
                    <?php 
                    if ($year_filter) {
                        $year_labels = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year'];
                        echo ' - ' . $year_labels[$year_filter];
                    }
                    ?>
                </h1>
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
                
                <!-- Advanced Filter Section -->
                <section class="section">
                    <h3 style="margin-top: 0; margin-bottom: 15px; font-size: 1.1em;">Filter Drops</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: flex-end;">
                        <!-- Search by Student -->
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 0.9em; color: #333;">Search Student</label>
                            <input type="text" id="liveSearch" data-live-filter="dropHistoryTable" placeholder="Name or ID..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.95em;">
                        </div>
                        
                        <!-- Filter by Course -->
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 0.9em; color: #333;">Course</label>
                            <select id="courseFilter" onchange="filterDropHistoryTable()" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.95em;">
                                <option value="">All Courses</option>
                                <option value="BS Information Technology">BS Information Technology</option>
                                <option value="BS Computer Science">BS Computer Science</option>
                                <option value="BS Information Systems">BS Information Systems</option>
                                <option value="BS Computer Engineering">BS Computer Engineering</option>
                            </select>
                        </div>
                        
                        <!-- Filter by Date Range -->
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 0.9em; color: #333;">From Date</label>
                            <input type="date" id="filterFromDate" onchange="filterDropHistoryTable()" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.95em;">
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 0.9em; color: #333;">To Date</label>
                            <input type="date" id="filterToDate" onchange="filterDropHistoryTable()" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.95em;">
                        </div>
                        
                        <!-- Clear Filters Button -->
                        <div style="display: flex; gap: 8px;">
                            <button type="button" class="btn btn-secondary" onclick="clearAllFilters()" style="flex: 1; padding: 8px 12px; font-size: 0.95em;">Clear All</button>
                            <button type="button" class="btn btn-primary" onclick="filterDropHistoryTable()" style="flex: 1; padding: 8px 12px; font-size: 0.95em;">Apply</button>
                        </div>
                    </div>
                </section>
                
                <!-- Drop History Section -->
                <section class="section">
                    <h2>My Class Card Drops <span style="font-weight: normal; font-size: 0.9em; color: #666;">(<span id="dropHistoryTable-count"><?php echo $pagination['total_items']; ?></span> total, page <?php echo $pagination['current_page']; ?> of <?php echo max(1, $pagination['total_pages']); ?>)</span></h2>
                    <?php if (count($drops) > 0): ?>
                        <div class="table-responsive">
                            <table class="table" id="dropHistoryTable">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Course</th>
                                        <th>Year</th>
                                        <th>Subject</th>
                                        <th>Class Card Status</th>
                                        <th>Student Status</th>
                                        <th>Teacher Remarks</th>
                                        <th>Admin Remarks</th>
                                        <th>Detail</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($drops as $drop): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($drop['student_id_number']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['student_course']); ?></td>
                                            <td><?php echo $drop['student_year']; ?></td>
                                            <td><?php echo htmlspecialchars($drop['subject_no'] . ' - ' . $drop['subject_name']); ?></td>
                                            <td><span class="status status-<?php echo strtolower($drop['status']); ?>"><?php echo htmlspecialchars($drop['status']); ?></span></td>
                                            <td><span class="status status-<?php echo strtolower($drop['student_status']); ?>"><?php echo ucfirst(htmlspecialchars($drop['student_status'])); ?></span></td>
                                            <td><span class="remarks-cell" style="word-break: break-word;"><?php $remarks_text = htmlspecialchars($drop['remarks']); echo strlen($remarks_text) > 50 ? substr($remarks_text, 0, 50) . '... <a href="javascript:void(0)" onclick="showRemarksModal(\'' . addslashes($remarks_text) . '\', \'Teacher Remarks\')" style="color: #a78bfa; font-weight: 600;">See More</a>' : $remarks_text; ?></span></td>
                                            <td><span class="remarks-cell" style="word-break: break-word;"><?php $admin_remarks_text = htmlspecialchars($drop['undrop_remarks'] ?? ''); echo $admin_remarks_text ? (strlen($admin_remarks_text) > 50 ? substr($admin_remarks_text, 0, 50) . '... <a href="javascript:void(0)" onclick="showRemarksModal(\'' . addslashes($admin_remarks_text) . '\', \'Admin Remarks\')" style="color: #a78bfa; font-weight: 600;">See More</a>' : $admin_remarks_text) : '-'; ?></span></td>
                                            <td style="text-align: center;"><button class="detail-btn" onclick="showStudentDetailModal(<?php echo htmlspecialchars(json_encode($drop)); ?>)" title="View Details"><span style="font-weight: 700; color: #a78bfa;">i</span></button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php echo renderPaginationControls($pagination, '/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_history.php'); ?>
                    <?php else: ?>
                        <p class="no-data">No class cards dropped yet.</p>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>

    <script src="/CLASS_CARD_DROPPING_SYSTEM/js/functions.js"></script>
    <script>
        function filterDropHistoryTable() {
            var search = document.getElementById('liveSearch').value.toLowerCase().trim();
            var statusFilter = document.getElementById('statusFilter').value.toLowerCase().trim();
            var fromDate = document.getElementById('filterFromDate').value;
            var toDate = document.getElementById('filterToDate').value;
            var table = document.getElementById('dropHistoryTable');
            if (!table) return;
            var rows = table.querySelector('tbody').querySelectorAll('tr');
            var visibleCount = 0;

            rows.forEach(function(row) {
                var cells = row.querySelectorAll('td');
                
                // Text search (Student ID, Name, Subject)
                var textMatch = !search;
                if (search) {
                    for (let i = 0; i < Math.min(4, cells.length); i++) {
                        if (cells[i].textContent.toLowerCase().includes(search)) {
                            textMatch = true;
                            break;
                        }
                    }
                }

                // Course filter (column 2 is "Course")
                var courseMatch = true;
                var courseFilter = document.getElementById('courseFilter').value.toLowerCase().trim();
                if (courseFilter) {
                    var courseCell = cells[2] ? cells[2].textContent.toLowerCase().trim() : '';
                    courseMatch = courseCell.includes(courseFilter);
                }

                // Date filter (column 0 is used, checking drop date)
                var dateMatch = true;
                if (fromDate || toDate) {
                    // Try to find date in various columns
                    var dateStr = '';
                    for (let i = 0; i < cells.length; i++) {
                        var content = cells[i].textContent.trim();
                        if (content.match(/\d{1,2}\/\d{1,2}\/\d{4}/)) {
                            dateStr = content.split(',')[0];
                            break;
                        }
                    }
                    
                    if (dateStr) {
                        var parts = dateStr.split('/');
                        if (parts.length === 3) {
                            var rowDate = new Date(parts[2], parts[0] - 1, parts[1]);
                            var rowDateStr = rowDate.toISOString().split('T')[0];
                            if (fromDate && rowDateStr < fromDate) dateMatch = false;
                            if (toDate && rowDateStr > toDate) dateMatch = false;
                        }
                    }
                }

                var show = textMatch && courseMatch && dateMatch;
                row.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });
            
            // Update count
            var countSpan = document.getElementById('dropHistoryTable-count');
            if (countSpan) {
                countSpan.textContent = visibleCount;
            }
        }

        function clearAllFilters() {
            document.getElementById('liveSearch').value = '';
            document.getElementById('courseFilter').value = '';
            document.getElementById('filterFromDate').value = '';
            document.getElementById('filterToDate').value = '';
            filterDropHistoryTable();
        }

        function toggleSubmenu(trigger) {
            const submenu = trigger.nextElementSibling;
            if (submenu && submenu.classList.contains('submenu')) {
                submenu.classList.toggle('active');
                trigger.classList.toggle('active');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('liveSearch').addEventListener('input', filterDropHistoryTable);
            document.getElementById('filterFromDate').addEventListener('input', filterDropHistoryTable);
            document.getElementById('filterToDate').addEventListener('input', filterDropHistoryTable);

            // Prevent scroll to top on pagination click
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

        function showStudentDetailModal(dropData) {
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

            const retrieveDate = dropData.retrieve_date && dropData.retrieve_date !== '0000-00-00 00:00:00' ? dropData.retrieve_date : 'N/A';
            
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
                        <span>Student Information & Drop Details</span>
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
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${dropData.student_id_number}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Full Name</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${dropData.student_name}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Course</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${dropData.student_course}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Year Level</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${dropData.student_year || 'N/A'}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Guardian Name</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${dropData.guardian_name || 'N/A'}</p>
                                    </div>
                                    <div>
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Address</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600; word-break: break-word; line-height: 1.5;">${dropData.student_address || 'N/A'}</p>
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
                                        Drop Information
                                    </h3>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Subject</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${dropData.subject_no} - ${dropData.subject_name}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Dropped Date & Time</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${new Date(dropData.drop_date).toLocaleString()}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Retrieved Date & Time</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${retrieveDate !== 'N/A' ? new Date(retrieveDate).toLocaleString() : 'N/A'}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Class Card Status</label>
                                        <p style="margin: 0; color: #1f2937;">
                                            <span class="status status-${dropData.status.toLowerCase()}" style="padding: 6px 12px; border-radius: 6px; display: inline-block; font-weight: 600; font-size: 0.95em;">${dropData.status}</span>
                                        </p>
                                    </div>
                                    <div>
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Student Status</label>
                                        <p style="margin: 0; color: #1f2937;">
                                            <span class="status status-${dropData.student_status.toLowerCase()}" style="padding: 6px 12px; border-radius: 6px; display: inline-block; font-weight: 600; font-size: 0.95em;">${dropData.student_status.charAt(0).toUpperCase() + dropData.student_status.slice(1)}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="padding: 24px 32px; border-top: 2px solid #e9d5ff; display: flex; gap: 12px; justify-content: flex-end; background: white;">
                        <button onclick="closeStudentDetailModal()" style="
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
    </script>
</body>
</html>
