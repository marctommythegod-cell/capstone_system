<?php
// admin/drop_history.php - View Drop History per Student

require_once '../includes/session_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

if ($_SESSION['user_role'] !== 'admin') {
    redirect('/SYSTEM/index.php');
}

$admin_name = getUserName($pdo, $_SESSION['user_id']);

// Get search filter
$search = $_GET['search'] ?? '';

// Build query for students
$query = 'SELECT id, student_id, name FROM students WHERE 1=1';
$params = [];

if ($search) {
    $query .= ' AND (student_id LIKE ? OR name LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$query .= ' ORDER BY name';

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Get all history for the filtered students
$all_history = [];

if ($students) {
    $student_ids = array_column($students, 'id');
    $placeholders = implode(',', array_fill(0, count($student_ids), '?'));
    
    $stmt = $pdo->prepare("
        SELECT ccd.*, s.student_id, s.name as student_name, u.name as teacher_name
        FROM class_card_drops ccd
        JOIN students s ON ccd.student_id = s.id
        JOIN users u ON ccd.teacher_id = u.id
        WHERE ccd.student_id IN ($placeholders)
        ORDER BY s.name, ccd.drop_date DESC
    ");
    $stmt->execute($student_ids);
    $all_history = $stmt->fetchAll();
}

$message = getMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drop History - PhilCST</title>
    <link rel="stylesheet" href="/SYSTEM/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>PhilCST</h2>
                <p>Admin Portal</p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="/SYSTEM/admin/dashboard.php" class="nav-item">
                    <span>Dashboard</span>
                </a>
                <a href="/SYSTEM/admin/dropped_cards.php" class="nav-item">
                    <span>Dropped Cards</span>
                </a>
                <a href="/SYSTEM/admin/students.php" class="nav-item">
                    <span>Manage Students</span>
                </a>
                <a href="/SYSTEM/admin/teachers.php" class="nav-item">
                    <span>Manage Teachers</span>
                </a>
                <a href="/SYSTEM/admin/drop_history.php" class="nav-item active">
                    <span>Drop History</span>
                </a>
                <a href="/SYSTEM/includes/logout.php" class="nav-item">
                    <span>Logout</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <p>Welcome, <strong><?php echo htmlspecialchars($admin_name); ?></strong></p>
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
                
                <!-- Search Section -->
                <section class="section">
                    <h2>Search Students</h2>
                    <form method="GET" class="filter-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="search">Search by Student ID or Name</label>
                                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Student ID or name...">
                            </div>
                            
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary">Search</button>
                                <a href="/SYSTEM/admin/drop_history.php" class="btn btn-secondary">Clear</a>
                            </div>
                        </div>
                    </form>
                </section>
                
                <!-- History Display -->
                <?php if ($students): ?>
                    <section class="section">
                        <h2>Drop History Results</h2>
                        
                        <?php if (count($all_history) > 0): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Student Name</th>
                                            <th>Subject</th>
                                            <th>Teacher</th>
                                            <th>Remarks</th>
                                            <th>Drop Date</th>
                                            <th>Retrieve Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_history as $record): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($record['student_id']); ?></td>
                                                <td><?php echo htmlspecialchars($record['student_name']); ?></td>
                                                <td><?php echo htmlspecialchars($record['subject_no'] . ' - ' . $record['subject_name']); ?></td>
                                                <td><?php echo htmlspecialchars($record['teacher_name']); ?></td>
                                                <td><?php echo htmlspecialchars(substr($record['remarks'], 0, 50)); ?></td>
                                                <td><?php echo formatDate($record['drop_date']); ?></td>
                                                <td><?php echo $record['retrieve_date'] ? formatDate($record['retrieve_date']) : '-'; ?></td>
                                                <td><span class="status status-<?php echo strtolower($record['status']); ?>"><?php echo htmlspecialchars($record['status']); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="history-summary">
                                <p><strong>Total Drops:</strong> <?php echo count($all_history); ?> class card(s) from <?php echo count($students); ?> student(s)</p>
                            </div>
                        <?php else: ?>
                            <p class="no-data">No drop history found for the selected students.</p>
                        <?php endif; ?>
                    </section>
                <?php else: ?>
                    <section class="section">
                        <p class="no-data">No students found. Please refine your search.</p>
                    </section>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
