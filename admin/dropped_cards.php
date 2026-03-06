<?php
// admin/dropped_cards.php - View All Dropped Cards

require_once '../includes/session_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../email/EmailNotifier.php';

if ($_SESSION['user_role'] !== 'admin') {
    redirect('/CLASS_CARD_DROPPING_SYSTEM/index.php');
}

$admin_name = getUserName($pdo, $_SESSION['user_id']);
$user_info = getUserInfo($pdo, $_SESSION['user_id']);

// Handle undrop action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'undrop') {
    $drop_id = intval($_POST['drop_id']);
    $undrop_remarks = trim($_POST['undrop_remarks'] ?? '');
    try {
        // Get drop details before updating
        $stmt = $pdo->prepare('SELECT * FROM class_card_drops WHERE id = ?');
        $stmt->execute([$drop_id]);
        $drop = $stmt->fetch();

        if (!$drop) {
            setMessage('error', 'Drop record not found.');
            redirect('/CLASS_CARD_DROPPING_SYSTEM/admin/dropped_cards.php');
        }

        $stmt = $pdo->prepare('UPDATE class_card_drops SET status = ?, retrieve_date = NOW(), undrop_remarks = ? WHERE id = ?');
        $stmt->execute(['Undropped', $undrop_remarks, $drop_id]);

        // Get student and teacher info for email notification
        $stmt = $pdo->prepare('SELECT student_id, name, email FROM students WHERE id = ?');
        $stmt->execute([$drop['student_id']]);
        $student = $stmt->fetch();

        $stmt = $pdo->prepare('SELECT name, email FROM users WHERE id = ?');
        $stmt->execute([$drop['teacher_id']]);
        $teacher = $stmt->fetch();

        // Send email notification to teacher
        if ($teacher && $teacher['email']) {
            $emailNotifier = new EmailNotifier();
            $emailData = [
                'student_id' => $student['student_id'],
                'student_name' => $student['name'],
                'subject_no' => $drop['subject_no'],
                'subject_name' => $drop['subject_name'],
                'drop_date' => $drop['drop_date'],
                'retrieve_date' => date('Y-m-d H:i:s'),
                'undrop_remarks' => $undrop_remarks
            ];
            $emailNotifier->notifyTeacherUndropped($teacher['email'], $emailData);
        }

        setMessage('success', 'Class card has been undropped. The teacher has been notified.');
    } catch (Exception $e) {
        setMessage('error', 'Error undropping class card: ' . $e->getMessage());
    }
    redirect('/CLASS_CARD_DROPPING_SYSTEM/admin/dropped_cards.php');
}

// Fetch pending drop requests
$pending_query = '
    SELECT ccd.*, s.name as student_name, s.student_id, s.course as student_course, u.name as teacher_name
    FROM class_card_drops ccd
    JOIN students s ON ccd.student_id = s.id
    JOIN users u ON ccd.teacher_id = u.id
    WHERE ccd.status = "Pending"
    ORDER BY ccd.drop_date ASC
';

$stmt = $pdo->prepare($pending_query);
$stmt->execute();
$pending_drops = $stmt->fetchAll();

// Fetch approved/undropped cards
$query = '
    SELECT ccd.*, s.name as student_name, s.student_id, u.name as teacher_name
    FROM class_card_drops ccd
    JOIN students s ON ccd.student_id = s.id
    JOIN users u ON ccd.teacher_id = u.id
    WHERE ccd.status IN ("Dropped", "Undropped")
    ORDER BY ccd.drop_date DESC
';

$stmt = $pdo->prepare($query);
$stmt->execute();
$drops = $stmt->fetchAll();

$message = getMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dropped Cards - PhilCST</title>
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
                <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/dropped_cards.php" class="nav-item active">
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
                <h1>Dropped Class Cards</h1>
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
                        <label for="liveSearch">Search by Student Name, ID, Subject, or Teacher</label>
                        <input type="text" id="liveSearch" data-live-filter="pendingTable" placeholder="Type to filter..." style="width: 100%;">
                    </div>
                </section>

                <!-- Pending Drop Requests Section -->
                <section class="section">
                    <h2>Pending Drop Requests (<span id="pendingTable-count"><?php echo count($pending_drops); ?></span> awaiting approval)</h2>
                    <?php if (count($pending_drops) > 0): ?>
                        <div class="table-responsive">
                            <table class="table" id="pendingTable">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Course</th>
                                        <th>Subject</th>
                                        <th>Teacher</th>
                                        <th>Request Date & Time</th>
                                        <th>Class Card Status</th>
                                        <th>Teacher Remarks</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_drops as $drop): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($drop['student_id']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['student_course']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['subject_no'] . ' - ' . $drop['subject_name']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['teacher_name']); ?></td>
                                            <td><?php echo formatDate($drop['drop_date']); ?></td>
                                            <td><span class="status status-pending"><?php echo htmlspecialchars($drop['status']); ?></span></td>
                                            <td><?php echo htmlspecialchars(substr($drop['remarks'], 0, 50)); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-success" onclick="approveDrop(<?php echo $drop['id']; ?>)">Approve</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="no-data">No pending drop requests.</p>
                    <?php endif; ?>
                </section>

                <!-- Approved/Dropped Cards Table -->
                <section class="section">
                    <h2>Approved Dropped Cards (<span id="approvedTable-count"><?php echo count($drops); ?></span> records)</h2>
                    <?php if (count($drops) > 0): ?>
                        <div class="table-responsive">
                            <table class="table" id="approvedTable">
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
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($drops as $drop): ?>
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
                                            <td><?php echo htmlspecialchars(substr($drop['remarks'], 0, 30)); ?></td>
                                            <td>
                                                <?php if ($drop['status'] === 'Dropped'): ?>
                                                    <form method="POST" style="display: inline;" id="undropForm<?php echo $drop['id']; ?>">
                                                        <input type="hidden" name="action" value="undrop">
                                                        <input type="hidden" name="drop_id" value="<?php echo $drop['id']; ?>">
                                                        <button type="button" class="btn btn-sm btn-danger" onclick="showUndropModal(<?php echo $drop['id']; ?>)">Undrop</button>
                                                    </form>
                                                <?php elseif ($drop['status'] === 'Undropped'): ?>
                                                    <button class="btn btn-sm btn-danger" style="opacity:0.6; cursor:not-allowed;" disabled>Undrop</button>
                                                <?php else: ?>
                                                    <span style="color: #aaa; font-style: italic;">—</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="no-data">No dropped cards found.</p>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>

    <script src="/CLASS_CARD_DROPPING_SYSTEM/js/functions.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            liveTableFilter('liveSearch', 'approvedTable');
        });
    </script>
</body>
</html>