<?php
// teacher/drop_history.php - Class Card Drop History

require_once '../includes/session_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

// Check if user is teacher
if ($_SESSION['user_role'] !== 'teacher') {
    redirect('/SYSTEM/index.php');
}

$user_id = $_SESSION['user_id'];
$teacher_name = getUserName($pdo, user_id: $user_id);

// Pagination settings
$items_per_page = 10;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Get date filter parameters
$filter_from_date = $_GET['from_date'] ?? '';
$filter_to_date = $_GET['to_date'] ?? '';

// Build query with optional date filtering
$query = '
    SELECT ccd.*, s.name as student_name, u.name as teacher_name
    FROM class_card_drops ccd
    JOIN students s ON ccd.student_id = s.id
    JOIN users u ON ccd.teacher_id = u.id
    WHERE ccd.teacher_id = ?
';
$params = [$user_id];

// Add date range filter if provided
if (!empty($filter_from_date)) {
    $query .= ' AND DATE(ccd.drop_date) >= ?';
    $params[] = $filter_from_date;
}

if (!empty($filter_to_date)) {
    $query .= ' AND DATE(ccd.drop_date) <= ?';
    $params[] = $filter_to_date;
}

// Get total count for pagination
$count_query = 'SELECT COUNT(*) as total FROM class_card_drops ccd WHERE ccd.teacher_id = ?';
if (!empty($filter_from_date)) {
    $count_query .= ' AND DATE(ccd.drop_date) >= ?';
}
if (!empty($filter_to_date)) {
    $count_query .= ' AND DATE(ccd.drop_date) <= ?';
}

$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_records = $stmt->fetch()['total'];
$total_pages = ceil($total_records / $items_per_page);

// Ensure current page is within range
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
}

// Calculate offset
$offset = ($current_page - 1) * $items_per_page;

$query .= ' ORDER BY ccd.drop_date DESC LIMIT ' . intval($items_per_page) . ' OFFSET ' . intval($offset);

// Fetch teacher's drops
$stmt = $pdo->prepare($query);
$stmt->execute($params);
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
    <link rel="stylesheet" href="/SYSTEM/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>PhilCST</h2>
                <p>Teacher Portal</p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="/SYSTEM/teacher/dashboard.php" class="nav-item">
                    <span>Overview</span>
                </a>
                <a href="/SYSTEM/teacher/drop_class_card.php" class="nav-item">
                    <span>Drop Class Card</span>
                </a>
                <a href="/SYSTEM/teacher/drop_history.php" class="nav-item active">
                    <span>Drop History</span>
                </a>
                <a href="/SYSTEM/includes/logout.php" class="nav-item">
                    <span>Logout</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <p>Welcome, <strong><?php echo htmlspecialchars($teacher_name); ?></strong></p>
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
                
                <!-- Date Filter Section -->
                <section class="section">
                    <h2>Filter Class Card Drops by Date</h2>
                    <form method="GET" class="filter-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="from_date">From Date</label>
                                <input type="date" id="from_date" name="from_date" value="<?php echo htmlspecialchars($filter_from_date); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="to_date">To Date</label>
                                <input type="date" id="to_date" name="to_date" value="<?php echo htmlspecialchars($filter_to_date); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="/SYSTEM/teacher/drop_history.php" class="btn btn-secondary">Clear</a>
                            </div>
                        </div>
                    </form>
                </section>
                
                <!-- Drop History Section -->
                <section class="section">
                    <h2>My Class Card Drops
                        <?php if ($filter_from_date || $filter_to_date): ?>
                            <span style="font-size: 14px; color: #666;">
                                (Filtered: <?php echo !empty($filter_from_date) ? $filter_from_date : 'any'; ?> to <?php echo !empty($filter_to_date) ? $filter_to_date : 'any'; ?>)
                            </span>
                        <?php endif; ?>
                    </h2>
                    <?php if (count($drops) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
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
                                            <td><?php echo htmlspecialchars($drop['student_id']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['subject_no'] . ' - ' . $drop['subject_name']); ?></td>
                                            <td><?php echo formatDate($drop['drop_date']); ?></td>
                                            <td><span class="status status-<?php echo strtolower($drop['status']); ?>"><?php echo htmlspecialchars($drop['status']); ?></span></td>
                                            <td><?php echo htmlspecialchars(substr($drop['remarks'], 0, 50)); ?></td>
                                            <td>
                                                <form method="POST" action="/SYSTEM/includes/api.php?action=undo_drop" style="display: inline;">
                                                    <input type="hidden" name="drop_id" value="<?php echo $drop['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to cancel this drop? The student will not be notified.')">Cancel</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <div class="pagination-container">
                            <ul class="pagination">
                                <?php if ($current_page > 1): ?>
                                    <li><a href="?page=1&from_date=<?php echo htmlspecialchars($filter_from_date); ?>&to_date=<?php echo htmlspecialchars($filter_to_date); ?>" class="pagination-link">First</a></li>
                                    <li><a href="?page=<?php echo $current_page - 1; ?>&from_date=<?php echo htmlspecialchars($filter_from_date); ?>&to_date=<?php echo htmlspecialchars($filter_to_date); ?>" class="pagination-link">Previous</a></li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <?php if ($i >= $current_page - 2 && $i <= $current_page + 2): ?>
                                        <?php if ($i == $current_page): ?>
                                            <li class="pagination-item active"><?php echo $i; ?></li>
                                        <?php else: ?>
                                            <li><a href="?page=<?php echo $i; ?>&from_date=<?php echo htmlspecialchars($filter_from_date); ?>&to_date=<?php echo htmlspecialchars($filter_to_date); ?>" class="pagination-link"><?php echo $i; ?></a></li>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if ($current_page < $total_pages): ?>
                                    <li><a href="?page=<?php echo $current_page + 1; ?>&from_date=<?php echo htmlspecialchars($filter_from_date); ?>&to_date=<?php echo htmlspecialchars($filter_to_date); ?>" class="pagination-link">Next</a></li>
                                    <li><a href="?page=<?php echo $total_pages; ?>&from_date=<?php echo htmlspecialchars($filter_from_date); ?>&to_date=<?php echo htmlspecialchars($filter_to_date); ?>" class="pagination-link">Last</a></li>
                                <?php endif; ?>
                            </ul>
                            <div class="pagination-info">
                                Page <?php echo $current_page; ?> of <?php echo $total_pages; ?> | Showing <?php echo count($drops); ?> of <?php echo $total_records; ?> records
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="no-data">No class cards dropped yet.</p>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>

    <script src="/SYSTEM/js/functions.js"></script>
</body>
</html>
