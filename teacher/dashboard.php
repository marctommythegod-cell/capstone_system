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

// Get statistics
$stmt = $pdo->prepare('SELECT COUNT(*) as total_drops FROM class_card_drops WHERE teacher_id = ?');
$stmt->execute([$user_id]);
$total_drops = $stmt->fetch()['total_drops'];

$stmt = $pdo->prepare('SELECT COUNT(*) as this_month FROM class_card_drops WHERE teacher_id = ? AND MONTH(drop_date) = MONTH(NOW()) AND YEAR(drop_date) = YEAR(NOW())');
$stmt->execute([$user_id]);
$this_month = $stmt->fetch()['this_month'];

$stmt = $pdo->prepare('SELECT COUNT(*) as this_week FROM class_card_drops WHERE teacher_id = ? AND WEEK(drop_date) = WEEK(NOW()) AND YEAR(drop_date) = YEAR(NOW())');
$stmt->execute([$user_id]);
$this_week = $stmt->fetch()['this_week'];

// Get recent drops (last 5)
$stmt = $pdo->prepare('
    SELECT ccd.*, s.name as student_name
    FROM class_card_drops ccd
    JOIN students s ON ccd.student_id = s.id
    WHERE ccd.teacher_id = ?
    ORDER BY ccd.drop_date DESC
    LIMIT 5
');
$stmt->execute([$user_id]);
$recent_drops = $stmt->fetchAll();

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
                    <span>Overview</span>
                </a>
                <a href="/SYSTEM/teacher/drop_class_card.php" class="nav-item">
                    <span>Drop Class Card</span>
                </a>
                <a href="/SYSTEM/teacher/drop_history.php" class="nav-item">
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
                <h1>Dashboard Overview</h1>
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
                
                <!-- Statistics Section -->
                <section class="section">
                    <h2>Statistics</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $total_drops; ?></div>
                            <div class="stat-label">Total Class Card Drops</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $this_month; ?></div>
                            <div class="stat-label">This Month</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $this_week; ?></div>
                            <div class="stat-label">This Week</div>
                        </div>
                    </div>
                </section>
                
                <!-- Quick Actions Section -->
                <section class="section">
                    <h2>Quick Actions</h2>
                    <div class="action-buttons">
                        <a href="/SYSTEM/teacher/drop_class_card.php" class="btn btn-primary btn-large">
                            Drop Student Class Card
                        </a>
                        <a href="/SYSTEM/teacher/drop_history.php" class="btn btn-secondary btn-large">
                            View Drop History
                        </a>
                    </div>
                </section>
                
                <!-- Recent Drops Section -->
                <section class="section">
                    <h2>Recent Class Card Drops</h2>
                    <?php if (count($recent_drops) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Subject</th>
                                        <th>Date & Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_drops as $drop): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($drop['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['subject_no'] . ' - ' . $drop['subject_name']); ?></td>
                                            <td><?php echo formatDate($drop['drop_date']); ?></td>
                                            <td><span class="status status-<?php echo strtolower($drop['status']); ?>"><?php echo htmlspecialchars($drop['status']); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div style="text-align: center; margin-top: 15px;">
                            <a href="/SYSTEM/teacher/drop_history.php" class="btn btn-secondary">View All Drops</a>
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
