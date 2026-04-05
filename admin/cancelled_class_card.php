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
$query = "SELECT ccd.*, s.name as student_name, s.student_id, s.course, s.year, s.guardian_name, s.address, s.email, u.name as teacher_name FROM class_card_drops ccd JOIN students s ON ccd.student_id = s.id JOIN users u ON ccd.teacher_id = u.id WHERE " . $where_sql . " ORDER BY ccd.cancelled_date DESC LIMIT " . $limit . " OFFSET " . $offset;

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
                                            <th>Detail</th>
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
                                                <td style="text-align: center;"><button class="detail-btn" onclick="showStudentDetailModal(<?php echo htmlspecialchars(json_encode($record)); ?>)" title="View Details"><span style="font-weight: 700; color: #a78bfa;">i</span></button></td>
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
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Requested Date & Time</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${new Date(recordData.drop_date).toLocaleString()}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Cancelled Date & Time</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${recordData.cancelled_date ? new Date(recordData.cancelled_date).toLocaleString() : 'N/A'}</p>
                                    </div>
                                    <div>
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Status</label>
                                        <p style="margin: 0; color: #1f2937;">
                                            <span style="padding: 6px 12px; border-radius: 6px; display: inline-block; font-weight: 600; font-size: 0.95em; background-color: #fee2e2; color: #991b1b;">${recordData.status}</span>
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

        setTimeout(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>
