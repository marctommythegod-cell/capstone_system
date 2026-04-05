<?php
// admin/drop_history.php - View Drop History per Student

require_once '../includes/session_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

if ($_SESSION['user_role'] !== 'admin') {
    redirect('/CLASS_CARD_DROPPING_SYSTEM/index.php');
}

$admin_name = getUserName($pdo, $_SESSION['user_id']);
$user_info = getUserInfo($pdo, $_SESSION['user_id']);

// Get year filter from query parameter
$year_filter = isset($_GET['year']) ? intval($_GET['year']) : null;

// Fetch all drop history with pagination and year filter
$count_query = '
    SELECT COUNT(*) as total FROM class_card_drops ccd
    JOIN students s ON ccd.student_id = s.id
    JOIN users u ON ccd.teacher_id = u.id
';
$count_params = [];

if ($year_filter) {
    $count_query .= ' WHERE s.year = ?';
    $count_params[] = $year_filter;
}

$stmt = $pdo->prepare($count_query);
$stmt->execute($count_params);
$total_history = $stmt->fetch()['total'];

$pagination = getPaginationData($total_history, 10); // 10 items per page

$query = '
    SELECT ccd.*, s.student_id, s.name as student_name, s.guardian_name, s.course as student_course, s.status as student_status, s.year as student_year, s.address, s.email, u.name as teacher_name, pur.undrop_remarks, pur.retrieve_date as undrop_retrieve_date
    FROM class_card_drops ccd
    JOIN students s ON ccd.student_id = s.id
    JOIN users u ON ccd.teacher_id = u.id
    LEFT JOIN philcst_undrop_records pur ON ccd.id = pur.drop_id
';
$query_params = [];

if ($year_filter) {
    $query .= ' WHERE s.year = ?';
    $query_params[] = $year_filter;
}

$query .= ' ORDER BY s.year, s.name, ccd.drop_date DESC
    LIMIT ' . intval($pagination['limit']) . ' OFFSET ' . intval($pagination['offset']) . '
';

$stmt = $pdo->prepare($query);
$stmt->execute($query_params);
$all_history = $stmt->fetchAll();

// Group drops by year level
$historyByYear = [1 => [], 2 => [], 3 => [], 4 => []];
foreach ($all_history as $record) {
    $year = $record['student_year'] ?: 1;
    if (!isset($historyByYear[$year])) {
        $historyByYear[$year] = [];
    }
    $historyByYear[$year][] = $record;
}

$message = getMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drop History - PhilCST</title>
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
                <div class="nav-item submenu-trigger active" onclick="toggleSubmenu(this)">
                    <span>Drop History</span>
                </div>
                <div class="submenu active" id="historySubmenu">
                    <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/drop_history.php" class="submenu-item <?php echo !$year_filter ? 'active' : ''; ?>">All Records</a>
                    <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/drop_history.php?year=1" class="submenu-item <?php echo $year_filter === 1 ? 'active' : ''; ?>">1st Year</a>
                    <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/drop_history.php?year=2" class="submenu-item <?php echo $year_filter === 2 ? 'active' : ''; ?>">2nd Year</a>
                    <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/drop_history.php?year=3" class="submenu-item <?php echo $year_filter === 3 ? 'active' : ''; ?>">3rd Year</a>
                    <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/drop_history.php?year=4" class="submenu-item <?php echo $year_filter === 4 ? 'active' : ''; ?>">4th Year</a>
                </div>
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
                        <?php echo htmlspecialchars($message['text']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Live Search -->
                <section class="section">
                    <h3 style="margin-top: 0; margin-bottom: 15px; font-size: 1.1em;">Filter History</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: flex-end;">
                        <!-- Search by Student/Teacher -->
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 0.9em; color: #333;">Search</label>
                            <input type="text" id="liveSearch" data-live-filter="historyTable" placeholder="Student or Teacher..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.95em;">
                        </div>
                        
                        <!-- Filter by Status -->
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 0.9em; color: #333;">Status</label>
                            <select id="statusFilter" onchange="filterHistoryTable()" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.95em;">
                                <option value="">All Status</option>
                                <option value="Pending">Pending</option>
                                <option value="Dropped">Approved</option>
                                <option value="Undropped">Undropped</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                        
                        <!-- Filter by Date Range -->
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 0.9em; color: #333;">From Date</label>
                            <input type="date" id="filterFromDate" onchange="filterHistoryTable()" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.95em;">
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 0.9em; color: #333;">To Date</label>
                            <input type="date" id="filterToDate" onchange="filterHistoryTable()" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.95em;">
                        </div>
                        
                        <!-- Clear Filters Button -->
                        <div style="display: flex; gap: 8px;">
                            <button type="button" class="btn btn-secondary" onclick="clearAllFiltersHistory()" style="flex: 1; padding: 8px 12px; font-size: 0.95em;">Clear All</button>
                            <button type="button" class="btn btn-primary" onclick="filterHistoryTable()" style="flex: 1; padding: 8px 12px; font-size: 0.95em;">Apply</button>
                        </div>
                    </div>
                </section>
                
                <!-- History Display -->
                <section class="section">
                    <h2>Drop History <span style="font-weight: normal; font-size: 0.9em; color: #666;">(<span id="historyTable-count"><?php echo $pagination['total_items']; ?></span> total, page <?php echo $pagination['current_page']; ?> of <?php echo max(1, $pagination['total_pages']); ?>)</span></h2>
                    
                    <?php if (count($all_history) > 0): ?>
                        <div class="table-responsive">
                            <table class="table" id="historyTable">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Course</th>
                                        <th>Year</th>
                                        <th>Subject</th>
                                        <th>Teacher</th>
                                        <th>Drop Date & Time</th>
                                        <th>Retrieve Date & Time</th>
                                        <th>Class Card Status</th>
                                        <th>Teacher Remarks</th>
                                        <th>Admin Remarks</th>
                                        <th>Detail</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_history as $record): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($record['student_id']); ?></td>
                                            <td><?php echo htmlspecialchars($record['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($record['student_course']); ?></td>
                                            <td><?php echo $record['student_year']; ?></td>
                                            <td><?php echo htmlspecialchars($record['subject_no'] . ' - ' . $record['subject_name']); ?></td>
                                            <td><?php echo htmlspecialchars($record['teacher_name']); ?></td>
                                            <td><?php echo formatDate($record['drop_date']); ?></td>
                                            <td><?php echo ($record['undrop_retrieve_date'] ?? null) ? formatDate($record['undrop_retrieve_date']) : '-'; ?></td>
                                            <td><span class="status status-<?php echo strtolower($record['status']); ?>"><?php echo htmlspecialchars($record['status']); ?></span></td>
                                            <td><span class="remarks-cell" style="word-break: break-word;"><?php $remarks_text = htmlspecialchars($record['remarks']); echo strlen($remarks_text) > 50 ? substr($remarks_text, 0, 50) . '... <a href="javascript:void(0)" onclick="showRemarksModal(\'' . addslashes($remarks_text) . '\', \'Teacher Remarks\')" style="color: #a78bfa; font-weight: 600;">See More</a>' : $remarks_text; ?></span></td>
                                            <td><span class="remarks-cell" style="word-break: break-word;"><?php $admin_remarks_text = htmlspecialchars($record['undrop_remarks'] ?? ''); echo $admin_remarks_text ? (strlen($admin_remarks_text) > 50 ? substr($admin_remarks_text, 0, 50) . '... <a href="javascript:void(0)" onclick="showRemarksModal(\'' . addslashes($admin_remarks_text) . '\', \'Admin Remarks\')" style="color: #a78bfa; font-weight: 600;">See More</a>' : $admin_remarks_text) : '-'; ?></span></td>
                                            <td style="text-align: center;"><button class="detail-btn" onclick="showStudentDetailModal(<?php echo htmlspecialchars(json_encode($record)); ?>)" title="View Details"><span style="font-weight: 700; color: #a78bfa;">i</span></button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php 
                        $pagination_url = '/CLASS_CARD_DROPPING_SYSTEM/admin/drop_history.php';
                        if ($year_filter) {
                            $pagination_url .= '?year=' . $year_filter;
                        }
                        echo renderPaginationControls($pagination, $pagination_url); 
                        ?>
                    <?php else: ?>
                        <p class="no-data">No drop history found.</p>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>

    <script src="/CLASS_CARD_DROPPING_SYSTEM/js/functions.js"></script>
    <script>
        function filterHistoryTable() {
            var search = document.getElementById('liveSearch').value.toLowerCase().trim();
            var statusFilter = document.getElementById('statusFilter').value.toLowerCase().trim();
            var fromDate = document.getElementById('filterFromDate').value;
            var toDate = document.getElementById('filterToDate').value;
            var table = document.getElementById('historyTable');
            if (!table) return;
            var rows = table.querySelector('tbody').querySelectorAll('tr');
            var visibleCount = 0;

            rows.forEach(function(row) {
                var cells = row.querySelectorAll('td');
                
                // Text search (Student ID, Name, Subject, Teacher)
                var textMatch = !search;
                if (search) {
                    for (let i = 0; i < Math.min(5, cells.length); i++) {
                        if (cells[i].textContent.toLowerCase().includes(search)) {
                            textMatch = true;
                            break;
                        }
                    }
                }

                // Status filter (column 9 is "Class Card Status")
                var statusMatch = true;
                if (statusFilter) {
                    var statusCell = cells[9] ? cells[9].textContent.toLowerCase().trim() : '';
                    statusMatch = statusCell.includes(statusFilter);
                }

                // Date filter (column 7 is "Drop Date & Time")
                var dateMatch = true;
                if (fromDate || toDate) {
                    var dateCell = cells[7] ? cells[7].textContent.trim() : '';
                    var rowDate = new Date(dateCell);
                    if (isNaN(rowDate.getTime())) {
                        dateMatch = false;
                    } else {
                        var rowDateStr = rowDate.toISOString().split('T')[0];
                        if (fromDate && rowDateStr < fromDate) dateMatch = false;
                        if (toDate && rowDateStr > toDate) dateMatch = false;
                    }
                }

                var show = textMatch && statusMatch && dateMatch;
                row.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });

            var countEl = document.getElementById('historyTable-count');
            if (countEl) countEl.textContent = visibleCount;
        }

        function clearAllFiltersHistory() {
            document.getElementById('liveSearch').value = '';
            document.getElementById('statusFilter').value = '';
            document.getElementById('filterFromDate').value = '';
            document.getElementById('filterToDate').value = '';
            filterHistoryTable();
        }

        function toggleSubmenu(trigger) {
            const submenu = trigger.nextElementSibling;
            if (submenu && submenu.classList.contains('submenu')) {
                submenu.classList.toggle('active');
                trigger.classList.toggle('active');
            }
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

            const retrieveDate = (recordData.retrieve_date && recordData.retrieve_date !== '0000-00-00 00:00:00') ? recordData.retrieve_date : 'N/A';
            
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
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${recordData.student_id}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Full Name</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${recordData.student_name}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Guardian Name</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${recordData.guardian_name || 'N/A'}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Course</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${recordData.student_course || 'N/A'}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Year Level</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${recordData.student_year || 'N/A'}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Address</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600; word-break: break-word; line-height: 1.5;">${recordData.address || 'N/A'}</p>
                                    </div>
                                    <div>
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Email Address</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600; word-break: break-word;">${recordData.email || 'N/A'}</p>
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
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${recordData.subject_no} - ${recordData.subject_name}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Drop Date & Time</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${new Date(recordData.drop_date).toLocaleString()}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Retrieved Date & Time</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${retrieveDate !== 'N/A' ? new Date(retrieveDate).toLocaleString() : 'N/A'}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Class Card Status</label>
                                        <p style="margin: 0; color: #1f2937;">
                                            <span style="padding: 6px 12px; border-radius: 6px; display: inline-block; font-weight: 600; font-size: 0.95em; background-color: #dbeafe; color: #1e40af;">${recordData.status}</span>
                                        </p>
                                    </div>
                                    <div>
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Student Status</label>
                                        <p style="margin: 0; color: #1f2937;">
                                            <span style="padding: 6px 12px; border-radius: 6px; display: inline-block; font-weight: 600; font-size: 0.95em; background-color: #e0e7ff; color: #3730a3;">${recordData.student_status ? recordData.student_status.charAt(0).toUpperCase() + recordData.student_status.slice(1) : 'N/A'}</span>
                                        </p>
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

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('liveSearch').addEventListener('input', filterHistoryTable);
            document.getElementById('filterFromDate').addEventListener('input', filterHistoryTable);
            document.getElementById('filterToDate').addEventListener('input', filterHistoryTable);

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
    </script>
</body>
</html>
