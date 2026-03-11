<?php
// admin/dashboard.php - Admin Dashboard

require_once '../includes/session_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

// Check if user is admin
if ($_SESSION['user_role'] !== 'admin') {
    redirect('/CLASS_CARD_DROPPING_SYSTEM/index.php');
}

$admin_name = getUserName($pdo, $_SESSION['user_id']);
$user_info = getUserInfo($pdo, $_SESSION['user_id']);

// Get statistics
$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM class_card_drops');
$stmt->execute();
$total_drops = $stmt->fetch()['total'];

$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM students');
$stmt->execute();
$total_students = $stmt->fetch()['total'];

$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM users WHERE role = "teacher"');
$stmt->execute();
$total_teachers = $stmt->fetch()['total'];

// Approved dropped cards
$stmt = $pdo->prepare('
    SELECT ccd.*, s.name as student_name, s.student_id, u.name as teacher_name
    FROM class_card_drops ccd
    JOIN students s ON ccd.student_id = s.id
    JOIN users u ON ccd.teacher_id = u.id
    WHERE ccd.status IN ("Dropped", "Undropped")
    ORDER BY ccd.drop_date DESC
    LIMIT 10
');
$stmt->execute();
$approved_drops = $stmt->fetchAll();

$message = getMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PhilCST</title>
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
                <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/dashboard.php" class="nav-item active">
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
                <h1>Admin Dashboard</h1>
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
                
                <!-- Statistics Section -->
                <section class="section">
                    <h2>System Overview</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <h3><?php echo $total_drops; ?></h3>
                            <p>Total Class Cards Dropped</p>
                        </div>
                        <div class="stat-card">
                            <h3><?php echo $total_students; ?></h3>
                            <p>Total Students</p>
                        </div>
                        <div class="stat-card">
                            <h3><?php echo $total_teachers; ?></h3>
                            <p>Total Teachers</p>
                        </div>
                    </div>
                </section>
                
                <!-- Approved Dropped Cards Section -->
                <section class="section">
                    <h2>Approved Dropped Cards</h2>
                    <?php if (count($approved_drops) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Subject</th>
                                        <th>Teacher</th>
                                        <th>Dropped Date & Time</th>
                                        <th>Approved Date & Time</th>
                                        <th>Class Card Status</th>
                                        <th>Teacher Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($approved_drops as $drop): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($drop['student_id']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['subject_no'] . ' - ' . $drop['subject_name']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['teacher_name']); ?></td>
                                            <td><?php echo formatDate($drop['drop_date']); ?></td>
                                            <td><?php echo $drop['approved_date'] ? formatDate($drop['approved_date']) : '-'; ?></td>
                                            <td>
                                                <span class="status status-<?php echo strtolower($drop['status']); ?>">
                                                    <?php echo htmlspecialchars($drop['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars(substr($drop['remarks'], 0, 50)); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="no-data">No approved dropped cards yet.</p>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>
    <script src="/CLASS_CARD_DROPPING_SYSTEM/js/functions.js"></script>
</body>
</html>
