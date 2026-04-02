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
    SELECT ccd.*, s.name as student_name, s.guardian_name, s.student_id as student_id_number, s.course as student_course, s.status as student_status, s.year as student_year, u.name as teacher_name
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
                
                <!-- Live Search -->
                <section class="section">
                    <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                        <div class="form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                            <label for="liveSearch">Search by Student ID, Name, or Subject</label>
                            <input type="text" id="liveSearch" data-live-filter="dropHistoryTable" placeholder="Type to filter..." style="width: 100%;">
                        </div>
                        <div class="form-group" style="min-width: 160px; margin-bottom: 0;">
                            <label for="filterFromDate">From Date</label>
                            <input type="date" id="filterFromDate" style="width: 100%;">
                        </div>
                        <div class="form-group" style="min-width: 160px; margin-bottom: 0;">
                            <label for="filterToDate">To Date</label>
                            <input type="date" id="filterToDate" style="width: 100%;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('liveSearch').value=''; document.getElementById('filterFromDate').value=''; document.getElementById('filterToDate').value=''; filterDropHistoryTable();">Clear</button>
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
                                        <th>Guardian Name</th>
                                        <th>Course</th>
                                        <th>Year</th>
                                        <th>Subject</th>
                                        <th>Drop Date & Time</th>
                                        <th>Retrieve Date & Time</th>
                                        <th>Class Card Status</th>
                                        <th>Student Status</th>
                                        <th>Teacher Remarks</th>
                                        <th>Admin Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($drops as $drop): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($drop['student_id_number']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['guardian_name'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($drop['student_course']); ?></td>
                                            <td><?php echo $drop['student_year']; ?></td>
                                            <td><?php echo htmlspecialchars($drop['subject_no'] . ' - ' . $drop['subject_name']); ?></td>
                                            <td><?php echo formatDate($drop['drop_date']); ?></td>
                                            <td><?php echo $drop['retrieve_date'] ? formatDate($drop['retrieve_date']) : '-'; ?></td>
                                            <td><span class="status status-<?php echo strtolower($drop['status']); ?>"><?php echo htmlspecialchars($drop['status']); ?></span></td>
                                            <td><span class="status status-<?php echo strtolower($drop['student_status']); ?>"><?php echo ucfirst(htmlspecialchars($drop['student_status'])); ?></span></td>
                                            <td><?php echo htmlspecialchars(substr($drop['remarks'], 0, 50)); ?></td>
                                            <td><?php echo !empty($drop['undrop_remarks']) ? htmlspecialchars($drop['undrop_remarks']) : '-'; ?></td>
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
            var fromDate = document.getElementById('filterFromDate').value;
            var toDate = document.getElementById('filterToDate').value;
            var table = document.getElementById('dropHistoryTable');
            if (!table) return;
            var rows = table.querySelector('tbody').querySelectorAll('tr');

            rows.forEach(function(row) {
                var cells = row.querySelectorAll('td');
                var textMatch = false;
                cells.forEach(function(cell) {
                    if (cell.textContent.toLowerCase().includes(search)) textMatch = true;
                });

                // Date filter: column index 4 is "Drop Date & Time"
                var dateMatch = true;
                if (fromDate || toDate) {
                    var dateCell = cells[4] ? cells[4].textContent.trim() : '';
                    var rowDate = new Date(dateCell);
                    if (isNaN(rowDate.getTime())) {
                        dateMatch = false;
                    } else {
                        var rowDateStr = rowDate.toISOString().split('T')[0];
                        if (fromDate && rowDateStr < fromDate) dateMatch = false;
                        if (toDate && rowDateStr > toDate) dateMatch = false;
                    }
                }

                row.style.display = (textMatch && dateMatch) ? '' : 'none';
            });
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
    </script>
</body>
</html>
