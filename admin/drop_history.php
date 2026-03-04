<?php
// admin/drop_history.php - View Drop History per Student

require_once '../includes/session_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

if ($_SESSION['user_role'] !== 'admin') {
    redirect('/CLASS_CARD_DROPPING_SYSTEM/index.php');
}

$admin_name = getUserName($pdo, $_SESSION['user_id']);

// Fetch all drop history
$stmt = $pdo->prepare('
    SELECT ccd.*, s.student_id, s.name as student_name, u.name as teacher_name
    FROM class_card_drops ccd
    JOIN students s ON ccd.student_id = s.id
    JOIN users u ON ccd.teacher_id = u.id
    ORDER BY s.name, ccd.drop_date DESC
');
$stmt->execute();
$all_history = $stmt->fetchAll();

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
                <a href="#" class="nav-item logout-item" onclick="showLogoutModal(); return false;">
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
                
                <!-- Live Search -->
                <section class="section">
                    <div class="form-group" style="max-width: 400px; margin-bottom: 0;">
                        <label for="liveSearch">Search by Student ID, Name, Subject, or Teacher</label>
                        <input type="text" id="liveSearch" data-live-filter="historyTable" placeholder="Type to filter..." style="width: 100%;">
                    </div>
                </section>
                
                <!-- History Display -->
                <section class="section">
                    <h2>Drop History (<span id="historyTable-count"><?php echo count($all_history); ?></span> records)</h2>
                    
                    <?php if (count($all_history) > 0): ?>
                        <div class="table-responsive">
                                <table class="table" id="historyTable">
                                    <thead>
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Student Name</th>
                                            <th>Subject</th>
                                            <th>Teacher</th>
                                            <th>Remarks</th>
                                            <th>Drop Date</th>
                                            <th>Retrieve Date</th>
                                            <th>Undrop Remarks</th>
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
                                                <td><?php echo !empty($record['undrop_remarks']) ? htmlspecialchars($record['undrop_remarks']) : '-'; ?></td>
                                                <td><span class="status status-<?php echo strtolower($record['status']); ?>"><?php echo htmlspecialchars($record['status']); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                        </div>
                    <?php else: ?>
                        <p class="no-data">No drop history found.</p>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>

    <script src="/CLASS_CARD_DROPPING_SYSTEM/js/functions.js"></script>
</body>
</html>
