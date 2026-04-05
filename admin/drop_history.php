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
    SELECT ccd.*, s.student_id, s.name as student_name, s.guardian_name, s.course as student_course, s.status as student_status, s.year as student_year, u.name as teacher_name
    FROM class_card_drops ccd
    JOIN students s ON ccd.student_id = s.id
    JOIN users u ON ccd.teacher_id = u.id
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
                                        <th>Guardian Name</th>
                                        <th>Course</th>
                                        <th>Subject</th>
                                        <th>Teacher</th>
                                        <th>Year</th>
                                        <th>Drop Date & Time</th>
                                        <th>Retrieve Date & Time</th>
                                        <th>Class Card Status</th>
                                        <th>Student Status</th>
                                        <th>Teacher Remarks</th>
                                        <th>Admin Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_history as $record): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($record['student_id']); ?></td>
                                            <td><?php echo htmlspecialchars($record['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($record['guardian_name'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($record['student_course']); ?></td>
                                            <td><?php echo htmlspecialchars($record['subject_no'] . ' - ' . $record['subject_name']); ?></td>
                                            <td><?php echo htmlspecialchars($record['teacher_name']); ?></td>
                                            <td><?php echo $record['student_year']; ?></td>
                                            <td><?php echo formatDate($record['drop_date']); ?></td>
                                            <td><?php echo $record['retrieve_date'] ? formatDate($record['retrieve_date']) : '-'; ?></td>
                                            <td><span class="status status-<?php echo strtolower($record['status']); ?>"><?php echo htmlspecialchars($record['status']); ?></span></td>
                                            <td><span class="status status-<?php echo strtolower($record['student_status']); ?>"><?php echo ucfirst(htmlspecialchars($record['student_status'])); ?></span></td>
                                            <td><?php echo htmlspecialchars(substr($record['remarks'], 0, 50)); ?></td>
                                            <td><?php echo !empty($record['undrop_remarks']) ? htmlspecialchars($record['undrop_remarks']) : '-'; ?></td>
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
