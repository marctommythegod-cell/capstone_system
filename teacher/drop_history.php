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

// Fetch all teacher's drops
$query = '
    SELECT ccd.*, s.name as student_name, s.student_id as student_id_number, u.name as teacher_name
    FROM class_card_drops ccd
    JOIN students s ON ccd.student_id = s.id
    JOIN users u ON ccd.teacher_id = u.id
    WHERE ccd.teacher_id = ?
    ORDER BY ccd.drop_date DESC
';

$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
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
                <a href="/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_history.php" class="nav-item active">
                    <span>Drop History</span>
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
                <h1>Class Card Drop History</h1>
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
                    <h2>My Class Card Drops</h2>
                    <?php if (count($drops) > 0): ?>
                        <div class="table-responsive">
                            <table class="table" id="dropHistoryTable">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Subject</th>
                                        <th>Drop Date & Time</th>
                                        <th>Status</th>
                                        <th>Remarks</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($drops as $drop): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($drop['student_id_number']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['subject_no'] . ' - ' . $drop['subject_name']); ?></td>
                                            <td><?php echo formatDate($drop['drop_date']); ?></td>
                                            <td><span class="status status-<?php echo strtolower($drop['status']); ?>"><?php echo htmlspecialchars($drop['status']); ?></span></td>
                                            <td><?php echo htmlspecialchars(substr($drop['remarks'], 0, 50)); ?></td>
                                            <td>
                                                <form method="POST" action="/CLASS_CARD_DROPPING_SYSTEM/includes/api.php?action=undo_drop" style="display: inline;" id="cancelDropForm<?php echo $drop['id']; ?>">
                                                    <input type="hidden" name="drop_id" value="<?php echo $drop['id']; ?>">
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="showConfirmModal('Are you sure you want to cancel this drop? The student will not be notified.', function(){ document.getElementById('cancelDropForm<?php echo $drop['id']; ?>').submit(); })">Cancel</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
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

                // Date filter: column index 3 is "Drop Date & Time"
                var dateMatch = true;
                if (fromDate || toDate) {
                    var dateCell = cells[3] ? cells[3].textContent.trim() : '';
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

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('liveSearch').addEventListener('input', filterDropHistoryTable);
            document.getElementById('filterFromDate').addEventListener('input', filterDropHistoryTable);
            document.getElementById('filterToDate').addEventListener('input', filterDropHistoryTable);
        });
    </script>
</body>
</html>
