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

// Fetch all drop history
$stmt = $pdo->prepare('
    SELECT ccd.*, s.student_id, s.name as student_name, s.guardian_name, s.course as student_course, s.status as student_status, s.year as student_year, u.name as teacher_name
    FROM class_card_drops ccd
    JOIN students s ON ccd.student_id = s.id
    JOIN users u ON ccd.teacher_id = u.id
    ORDER BY s.year, s.name, ccd.drop_date DESC
');
$stmt->execute();
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
                <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/drop_history.php" class="nav-item active">
                    <span>Drop History</span>
                </a>
                <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/settings.php" class="nav-item">
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
                <h1>Student Drop History</h1>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($admin_name); ?> (Administrator)</span>
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
                            <label for="liveSearch">Search by Student ID, Name, Subject, or Teacher</label>
                            <input type="text" id="liveSearch" data-live-filter="historyTable" placeholder="Type to filter..." style="width: 100%;">
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
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('liveSearch').value=''; document.getElementById('filterFromDate').value=''; document.getElementById('filterToDate').value=''; filterHistoryTable();">Clear</button>
                        </div>
                    </div>
                </section>
                
                <!-- History Display -->
                <section class="section">
                    <h2>Drop History (<span id="historyTable-count"><?php echo count($all_history); ?></span> records)</h2>
                    
                    <?php if (count($all_history) > 0): ?>
                        <?php 
                        $yearLabels = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year'];
                        foreach ([1, 2, 3, 4] as $year):
                            if (empty($historyByYear[$year])) continue;
                        ?>
                            <div style="margin-bottom: 30px;">
                                <h3 style="color: #333; padding: 15px; background-color: #f0f0f0; border-left: 4px solid #7f3fc6; margin-bottom: 15px;">
                                    <?php echo $yearLabels[$year]; ?> (<?php echo count($historyByYear[$year]); ?> drop<?php echo count($historyByYear[$year]) !== 1 ? 's' : ''; ?>)
                                </h3>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Student ID</th>
                                                <th>Student Name</th>
                                                <th>Guardian Name</th>
                                                <th>Course</th>
                                                <th>Subject</th>
                                                <th>Teacher</th>
                                                <th>Drop Date & Time</th>
                                                <th>Retrieve Date & Time</th>
                                                <th>Class Card Status</th>
                                                <th>Student Status</th>
                                                <th>Teacher Remarks</th>
                                                <th>Admin Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($historyByYear[$year] as $record): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($record['student_id']); ?></td>
                                                    <td><?php echo htmlspecialchars($record['student_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($record['guardian_name'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($record['student_course']); ?></td>
                                                    <td><?php echo htmlspecialchars($record['subject_no'] . ' - ' . $record['subject_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($record['teacher_name']); ?></td>
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
                            </div>
                        <?php endforeach; ?>
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
            var fromDate = document.getElementById('filterFromDate').value;
            var toDate = document.getElementById('filterToDate').value;
            var table = document.getElementById('historyTable');
            if (!table) return;
            var rows = table.querySelector('tbody').querySelectorAll('tr');
            var visibleCount = 0;

            rows.forEach(function(row) {
                var cells = row.querySelectorAll('td');
                var textMatch = false;
                cells.forEach(function(cell) {
                    if (cell.textContent.toLowerCase().includes(search)) textMatch = true;
                });

                // Date filter: column index 5 is "Drop Date & Time"
                var dateMatch = true;
                if (fromDate || toDate) {
                    var dateCell = cells[5] ? cells[5].textContent.trim() : '';
                    var rowDate = new Date(dateCell);
                    if (isNaN(rowDate.getTime())) {
                        dateMatch = false;
                    } else {
                        var rowDateStr = rowDate.toISOString().split('T')[0];
                        if (fromDate && rowDateStr < fromDate) dateMatch = false;
                        if (toDate && rowDateStr > toDate) dateMatch = false;
                    }
                }

                var show = textMatch && dateMatch;
                row.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });

            var countEl = document.getElementById('historyTable-count');
            if (countEl) countEl.textContent = visibleCount;
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('liveSearch').addEventListener('input', filterHistoryTable);
            document.getElementById('filterFromDate').addEventListener('input', filterHistoryTable);
            document.getElementById('filterToDate').addEventListener('input', filterHistoryTable);
        });
    </script>
</body>
</html>
