<?php
// admin/cancelled_class_card.php - Cancelled Class Card Management

require_once '../includes/session_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

// Check if user is admin
if ($_SESSION['user_role'] !== 'admin') {
    redirect('/CLASS_CARD_DROPPING_SYSTEM/index.php');
}

$admin_name = getUserName($pdo, $_SESSION['user_id']);
$user_info = getUserInfo($pdo, $_SESSION['user_id']);

// Auto-cancel logic: Mark as Cancelled if approved within 24 hours
$auto_cancel_query = '
    UPDATE class_card_drops
    SET status = "Cancelled"
    WHERE status = "Approved" 
    AND approved_date IS NOT NULL 
    AND TIMESTAMPDIFF(HOUR, drop_date, approved_date) <= 24
    AND status != "Cancelled"
';
try {
    $pdo->exec($auto_cancel_query);
} catch (Exception $e) {
    // Log error silently
}

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_course = isset($_GET['course']) ? trim($_GET['course']) : '';
$filter_teacher = isset($_GET['teacher']) ? trim($_GET['teacher']) : '';

// Build WHERE clause
$where_clauses = array('ccd.status = "Cancelled"');
$params = array();

if (!empty($search)) {
    $where_clauses[] = '(s.name LIKE ? OR s.student_id LIKE ? OR ccd.subject_no LIKE ? OR ccd.subject_name LIKE ?)';
    $search_param = '%' . $search . '%';
    $params = array_merge($params, array($search_param, $search_param, $search_param, $search_param));
}

if (!empty($filter_course)) {
    $where_clauses[] = 's.course LIKE ?';
    $params[] = '%' . $filter_course . '%';
}

if (!empty($filter_teacher)) {
    $where_clauses[] = 'u.name LIKE ?';
    $params[] = '%' . $filter_teacher . '%';
}

$where_sql = implode(' AND ', $where_clauses);

// Get total count
$count_query = "SELECT COUNT(*) as total FROM class_card_drops ccd JOIN students s ON ccd.student_id = s.id JOIN users u ON ccd.teacher_id = u.id WHERE " . $where_sql;
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_records = $stmt->fetch()['total'];

$pagination = getPaginationData($total_records, 10);

// Get cancelled records
$limit = intval($pagination['limit']);
$offset = intval($pagination['offset']);
$query = "SELECT ccd.*, s.name as student_name, s.student_id, s.course, s.year, u.name as teacher_name FROM class_card_drops ccd JOIN students s ON ccd.student_id = s.id JOIN users u ON ccd.teacher_id = u.id WHERE " . $where_sql . " ORDER BY ccd.cancelled_date DESC LIMIT " . $limit . " OFFSET " . $offset;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$cancelled_records = $stmt->fetchAll();

// Get unique courses for filter
$courses_query = "SELECT DISTINCT course FROM students ORDER BY course";
$courses_stmt = $pdo->prepare($courses_query);
$courses_stmt->execute();
$courses = $courses_stmt->fetchAll();

// Get unique teachers for filter
$teachers_query = "SELECT DISTINCT u.name FROM users u JOIN class_card_drops ccd ON u.id = ccd.teacher_id WHERE ccd.status = 'Cancelled' ORDER BY u.name";
$teachers_stmt = $pdo->prepare($teachers_query);
$teachers_stmt->execute();
$teachers = $teachers_stmt->fetchAll();

$message = getMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancelled Class Cards - PhilCST</title>
    <link rel="stylesheet" href="/CLASS_CARD_DROPPING_SYSTEM/css/style.css">
    <style>
        .filter-section {
            background: linear-gradient(135deg, #f5f5f5 0%, #fafafa 100%);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border: 1px solid #e0e0e0;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: 600;
            font-size: 0.9em;
            margin-bottom: 8px;
            color: #333;
        }

        .filter-group input, .filter-group select {
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.95em;
            transition: border-color 0.3s ease;
        }

        .filter-group input:focus, .filter-group select:focus {
            outline: none;
            border-color: #7f3fc6;
            box-shadow: 0 0 0 3px rgba(127, 63, 198, 0.1);
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .btn-filter, .btn-reset {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95em;
        }

        .btn-filter {
            background-color: #7f3fc6;
            color: white;
        }

        .btn-filter:hover {
            background-color: #6a2fa8;
            box-shadow: 0 4px 12px rgba(127, 63, 198, 0.3);
        }

        .btn-reset {
            background-color: #e0e0e0;
            color: #333;
        }

        .btn-reset:hover {
            background-color: #d0d0d0;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-box {
            background: linear-gradient(135deg, #dc3545 0%, #ff6b6b 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.2);
        }

        .stat-box h3 {
            font-size: 2em;
            margin: 0 0 5px 0;
        }

        .stat-box p {
            font-size: 0.9em;
            opacity: 0.9;
            margin: 0;
        }

        .cancelled-table {
            margin-top: 20px;
        }

        .cancelled-table .table tbody tr {
            opacity: 0.85;
            background-color: #fafafa;
            border-left: 4px solid #dc3545;
        }

        .cancelled-table .table tbody tr:hover {
            background-color: #f0f0f0;
            opacity: 1;
        }

        .status-cancelled {
            background-color: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .no-results h3 {
            color: #333;
            margin-bottom: 10px;
        }
    </style>
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
                <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/drop_history.php" class="nav-item">
                    <span>Drop History</span>
                </a>
                <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/cancelled_class_card.php" class="nav-item active">
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
                <div class="global-header">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message['type']; ?>">
                            <?php echo htmlspecialchars($message['text']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Filter Section -->
                    <section class="section">
                        <h2>Search & Filter</h2>
                        <div class="filter-section">
                            <form method="GET" id="filterForm">
                                <div class="filter-grid">
                                    <div class="filter-group">
                                        <label for="search">Search (Student/Subject)</label>
                                        <input type="text" id="search" name="search" placeholder="Enter student name, ID, or subject..." value="<?php echo htmlspecialchars($search); ?>">
                                    </div>

                                    <div class="filter-group">
                                        <label for="course">Course</label>
                                        <select id="course" name="course">
                                            <option value="">All Courses</option>
                                            <?php foreach ($courses as $course): ?>
                                                <option value="<?php echo htmlspecialchars($course['course']); ?>" <?php echo $filter_course === $course['course'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($course['course']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="filter-group">
                                        <label for="teacher">Teacher</label>
                                        <select id="teacher" name="teacher">
                                            <option value="">All Teachers</option>
                                            <?php foreach ($teachers as $teacher): ?>
                                                <option value="<?php echo htmlspecialchars($teacher['name']); ?>" <?php echo $filter_teacher === $teacher['name'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($teacher['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="filter-buttons" style="margin-top: 15px;">
                                    <button type="submit" class="btn-filter">Search</button>
                                    <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/cancelled_class_card.php" class="btn-reset">Reset Filters</a>
                                </div>
                            </form>
                        </div>
                    </section>

                    <!-- Cancelled Records Table -->
                    <section class="section">
                        <h2>Cancelled Class Card Records <span style="font-weight: normal; font-size: 0.9em; color: #666;">(<span id="recordsCount"><?php echo $pagination['total_items']; ?></span> records, page <?php echo $pagination['current_page']; ?> of <?php echo max(1, $pagination['total_pages']); ?>)</span></h2>
                        
                        <?php if (count($cancelled_records) > 0): ?>
                            <div class="table-responsive cancelled-table">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Student Name</th>
                                            <th>Course</th>
                                            <th>Year</th>
                                            <th>Subject</th>
                                            <th>Teacher</th>
                                            <th>Date Requested</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cancelled_records as $record): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($record['student_id']); ?></td>
                                                <td><?php echo htmlspecialchars($record['student_name']); ?></td>
                                                <td><?php echo htmlspecialchars($record['course'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($record['year'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($record['subject_no'] . ' - ' . $record['subject_name']); ?></td>
                                                <td><?php echo htmlspecialchars($record['teacher_name']); ?></td>
                                                <td><?php echo formatDate($record['drop_date']); ?></td>
                                                <td><span class="status-cancelled"><?php echo htmlspecialchars($record['status']); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php 
                            $filter_params = '';
                            if (!empty($search)) $filter_params .= '?search=' . urlencode($search);
                            if (!empty($filter_course)) $filter_params .= (strpos($filter_params, '?') !== false ? '&' : '?') . 'course=' . urlencode($filter_course);
                            if (!empty($filter_teacher)) $filter_params .= (strpos($filter_params, '?') !== false ? '&' : '?') . 'teacher=' . urlencode($filter_teacher);
                            echo renderPaginationControls($pagination, '/CLASS_CARD_DROPPING_SYSTEM/admin/cancelled_class_card.php' . $filter_params); 
                            ?>
                        <?php else: ?>
                            <div class="no-results">
                                <h3>No Records Found</h3>
                                <p>There are no cancelled class card records matching your search criteria.</p>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>
            </div>
        </main>
    </div>

    <script src="/CLASS_CARD_DROPPING_SYSTEM/js/functions.js"></script>
    <script>
        setTimeout(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>
