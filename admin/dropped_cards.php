<?php
// admin/dropped_cards.php - View All Dropped Cards

require_once '../includes/session_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

if ($_SESSION['user_role'] !== 'admin') {
    redirect('/SYSTEM/index.php');
}

$admin_name = getUserName($pdo, $_SESSION['user_id']);

// Handle undrop action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'undrop') {
    $drop_id = intval($_POST['drop_id']);
    try {
        $stmt = $pdo->prepare('UPDATE class_card_drops SET status = ?, retrieve_date = NOW() WHERE id = ?');
        $stmt->execute(['Undropped', $drop_id]);
        setMessage('success', 'Class card has been undropped. The record remains in drop history.');
    } catch (Exception $e) {
        setMessage('error', 'Error undropping class card: ' . $e->getMessage());
    }
    redirect('/SYSTEM/admin/dropped_cards.php');
}

// Get filters
$search_student = $_GET['search'] ?? '';

// Fetch pending drop requests
$pending_query = '
    SELECT ccd.*, s.name as student_name, s.student_id, u.name as teacher_name
    FROM class_card_drops ccd
    JOIN students s ON ccd.student_id = s.id
    JOIN users u ON ccd.teacher_id = u.id
    WHERE ccd.status = "Pending"
';
$pending_params = [];

if ($search_student) {
    $pending_query .= ' AND (s.name LIKE ? OR s.student_id LIKE ?)';
    $pending_params[] = '%' . $search_student . '%';
    $pending_params[] = '%' . $search_student . '%';
}

$pending_query .= ' ORDER BY ccd.drop_date ASC';

$stmt = $pdo->prepare($pending_query);
$stmt->execute($pending_params);
$pending_drops = $stmt->fetchAll();

// Build query for approved/undropped cards
$query = '
    SELECT ccd.*, s.name as student_name, s.student_id, u.name as teacher_name
    FROM class_card_drops ccd
    JOIN students s ON ccd.student_id = s.id
    JOIN users u ON ccd.teacher_id = u.id
    WHERE ccd.status IN ("Dropped", "Undropped")
';
$params = [];

if ($search_student) {
    $query .= ' AND (s.name LIKE ? OR s.student_id LIKE ?)';
    $params[] = '%' . $search_student . '%';
    $params[] = '%' . $search_student . '%';
}

$query .= ' ORDER BY ccd.drop_date DESC';

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
    <title>Dropped Cards - PhilCST</title>
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
                <a href="/SYSTEM/admin/dropped_cards.php" class="nav-item active">
                    <span>Dropped Cards</span>
                </a>
                <a href="/SYSTEM/admin/students.php" class="nav-item">
                    <span>Manage Students</span>
                </a>
                <a href="/SYSTEM/admin/teachers.php" class="nav-item">
                    <span>Manage Teachers</span>
                </a>
                <a href="/SYSTEM/admin/drop_history.php" class="nav-item">
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

                <!-- Filters Section -->
                <section class="section">
                    <h2>Filter Cards</h2>
                    <form method="GET" class="filter-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="search">Search by Student Name/ID</label>
                                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search_student); ?>" placeholder="Student name or ID...">
                            </div>
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary">Search</button>
                                <a href="/SYSTEM/admin/dropped_cards.php" class="btn btn-secondary">Clear</a>
                            </div>
                        </div>
                    </form>
                </section>

                <!-- Pending Drop Requests Section -->
                <section class="section">
                    <h2>Pending Drop Requests (<?php echo count($pending_drops); ?> awaiting approval)</h2>
                    <?php if (count($pending_drops) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Subject</th>
                                        <th>Teacher</th>
                                        <th>Remarks</th>
                                        <th>Request Date & Time</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_drops as $drop): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($drop['student_id']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['subject_no'] . ' - ' . $drop['subject_name']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['teacher_name']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($drop['remarks'], 0, 50)); ?></td>
                                            <td><?php echo formatDate($drop['drop_date']); ?></td>
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
                    <h2>Approved Dropped Cards (<?php echo count($drops); ?> records)</h2>
                    <?php if (count($drops) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Subject</th>
                                        <th>Teacher</th>
                                        <th>Remarks</th>
                                        <th>Drop Date & Time</th>
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
                                            <td><?php echo htmlspecialchars($drop['teacher_name']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($drop['remarks'], 0, 30)); ?></td>
                                            <td><?php echo formatDate($drop['drop_date']); ?></td>
                                            <td>
                                                <span class="status status-<?php echo strtolower($drop['status']); ?>">
                                                    <?php echo htmlspecialchars($drop['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($drop['status'] === 'Dropped'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="undrop">
                                                        <input type="hidden" name="drop_id" value="<?php echo $drop['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to undrop this class card?')">Undrop</button>
                                                    </form>
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

    <script src="/SYSTEM/js/functions.js"></script>
</body>
</html>