<?php
// teacher/dashboard.php - Teacher Dashboard

require_once '../includes/session_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

// Check if user is teacher
if ($_SESSION['user_role'] !== 'teacher') {
    redirect('/SYSTEM/index.php');
}

$user_id = $_SESSION['user_id'];
$teacher_name = getUserName($pdo, $user_id);

// Get date filter parameters
$filter_from_date = $_GET['from_date'] ?? '';
$filter_to_date = $_GET['to_date'] ?? '';

// Fetch all students
$stmt = $pdo->prepare('SELECT id, student_id, name, course, year FROM students ORDER BY name');
$stmt->execute();
$students = $stmt->fetchAll();

// Fetch all subjects
$stmt = $pdo->prepare('SELECT id, subject_no, subject_name FROM subjects ORDER BY subject_name');
$stmt->execute();
$subjects = $stmt->fetchAll();

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

$query .= ' ORDER BY ccd.drop_date DESC';

// Fetch teacher's drops
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$drops = $stmt->fetchAll();

$message = getMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - PhilCST</title>
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
                <a href="/SYSTEM/teacher/dashboard.php" class="nav-item active">
                    <span>Dashboard</span>
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
                <h1>Teacher Dashboard</h1>
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
                
                <!-- Drop Form Section -->
                <section class="section">
                    <h2>Drop Student Class Card</h2>
                    <form method="POST" action="/SYSTEM/includes/api.php?action=drop_class_card" class="drop-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="student_id">Select Student</label>
                                <select id="student_id" name="student_id" required onchange="updateStudentInfo()">
                                    <option value="">-- Select a Student --</option>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?php echo $student['id']; ?>" data-course="<?php echo htmlspecialchars($student['course']); ?>" data-year="<?php echo $student['year']; ?>">
                                            <?php echo htmlspecialchars($student['student_id'] . ' - ' . $student['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="course">Course</label>
                                <input type="text" id="course" name="course" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label for="year">Year</label>
                                <input type="text" id="year" name="year" readonly>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="subject_id">Select Subject</label>
                                <select id="subject_id" name="subject_id" required onchange="updateSubjectInfo()">
                                    <option value="">-- Select a Subject --</option>
                                    <?php foreach ($subjects as $subject): ?>
                                        <option value="<?php echo htmlspecialchars($subject['subject_no']); ?>" data-name="<?php echo htmlspecialchars($subject['subject_name']); ?>">
                                            <?php echo htmlspecialchars($subject['subject_no'] . ' - ' . $subject['subject_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="remarks">Remarks</label>
                                <textarea id="remarks" name="remarks" rows="4" placeholder="Enter reason for dropping class card..."></textarea>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <button type="submit" class="btn btn-primary">Drop Class Card</button>
                        </div>
                    </form>
                </section>
                
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
                                <a href="/SYSTEM/teacher/dashboard.php" class="btn btn-secondary">Clear</a>
                            </div>
                        </div>
                    </form>
                </section>
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
                                        <th>Remarks</th>
                                        <th>Date & Time</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($drops as $drop): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($drop['student_id']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['subject_no'] . ' - ' . $drop['subject_name']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($drop['remarks'], 0, 50)); ?></td>
                                            <td><?php echo formatDate($drop['drop_date']); ?></td>
                                            <td><span class="status status-<?php echo strtolower($drop['status']); ?>"><?php echo htmlspecialchars($drop['status']); ?></span></td>
                                            <td>
                                                <form method="POST" action="/SYSTEM/includes/api.php?action=undo_drop" style="display: inline;">
                                                    <input type="hidden" name="drop_id" value="<?php echo $drop['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to undo this drop? The student will not be notified.')">Undo</button>
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
    
    <script src="/SYSTEM/js/functions.js"></script>
</body>
</html>
