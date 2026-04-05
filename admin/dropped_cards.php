<?php
// admin/dropped_cards.php - View All Dropped Cards

require_once '../includes/session_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/check_overdue_requests.php';
require_once '../email/EmailNotifier.php';

if ($_SESSION['user_role'] !== 'admin') {
    redirect('/CLASS_CARD_DROPPING_SYSTEM/index.php');
}

// Check and cancel any overdue requests
checkAndCancelOverdueRequests($pdo);

$admin_name = getUserName($pdo, $_SESSION['user_id']);
$user_info = getUserInfo($pdo, $_SESSION['user_id']);

// Handle undrop action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'undrop') {
    $drop_id = intval($_POST['drop_id']);
    $undrop_remarks = trim($_POST['undrop_remarks'] ?? '');
    $undrop_certificates = trim($_POST['undrop_certificates'] ?? '');
    try {
        // Get drop details before updating
        $stmt = $pdo->prepare('SELECT * FROM class_card_drops WHERE id = ?');
        $stmt->execute([$drop_id]);
        $drop = $stmt->fetch();

        if (!$drop) {
            setMessage('error', 'Drop record not found.');
            redirect('/CLASS_CARD_DROPPING_SYSTEM/admin/dropped_cards.php');
        }

        // Update status to Undropped in class_card_drops (without retrieve_date as it's now in separate table)
        $stmt = $pdo->prepare('UPDATE class_card_drops SET status = ? WHERE id = ?');
        $stmt->execute(['Undropped', $drop_id]);

        // Insert undrop record into separate philcst_undrop_records table
        $stmt = $pdo->prepare('
            INSERT INTO philcst_undrop_records 
            (drop_id, student_id, subject_no, subject_name, teacher_id, retrieve_date, undrop_remarks, undrop_certificates)
            VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)
        ');
        $stmt->execute([
            $drop_id,
            $drop['student_id'],
            $drop['subject_no'],
            $drop['subject_name'],
            $drop['teacher_id'],
            $undrop_remarks,
            $undrop_certificates
        ]);

        // Get student and teacher info for email notification
        $stmt = $pdo->prepare('SELECT student_id, name, email FROM students WHERE id = ?');
        $stmt->execute([$drop['student_id']]);
        $student = $stmt->fetch();

        $stmt = $pdo->prepare('SELECT name, email FROM users WHERE id = ?');
        $stmt->execute([$drop['teacher_id']]);
        $teacher = $stmt->fetch();

        // Send email notification to teacher
        if ($teacher && $teacher['email']) {
            error_log("Attempting to send undrop email to teacher: " . $teacher['email']);
            $emailNotifier = new EmailNotifier();
            $emailData = [
                'student_id' => $student['student_id'],
                'student_name' => $student['name'],
                'subject_no' => $drop['subject_no'],
                'subject_name' => $drop['subject_name'],
                'drop_date' => $drop['drop_date'],
                'retrieve_date' => date('Y-m-d H:i:s'),
                'undrop_remarks' => $undrop_remarks,
                'undrop_certificates' => $undrop_certificates
            ];
            $emailNotifier->notifyTeacherUndropped($teacher['email'], $emailData);
        } else {
            error_log("Teacher has no email address for undrop notification");
        }

        setMessage('success', 'Class card has been undropped. The teacher has been notified.');
    } catch (Exception $e) {
        error_log("Exception in undrop action: " . $e->getMessage());
        setMessage('error', 'Error undropping class card: ' . $e->getMessage());
    }
    redirect('/CLASS_CARD_DROPPING_SYSTEM/admin/dropped_cards.php');
}

// Fetch pending drop requests with deadline
$pending_query = '
    SELECT ccd.*, s.name as student_name, s.guardian_name, s.student_id, s.course as student_course, s.year, u.name as teacher_name
    FROM class_card_drops ccd
    JOIN students s ON ccd.student_id = s.id
    JOIN users u ON ccd.teacher_id = u.id
    WHERE ccd.status = "Pending" AND ccd.cancelled_date IS NULL
    ORDER BY ccd.deadline ASC
';

$stmt = $pdo->prepare($pending_query);
$stmt->execute();
$pending_drops = $stmt->fetchAll();

// Fetch approved/undropped cards with pagination
$stmt = $pdo->prepare('
    SELECT COUNT(*) as total FROM class_card_drops
    WHERE status IN ("Dropped", "Undropped")
');
$stmt->execute();
$total_approved_drops = $stmt->fetch()['total'];

$pagination = getPaginationData($total_approved_drops, 15); // 15 items per page

$query = '
    SELECT ccd.*, s.name as student_name, s.guardian_name, s.student_id, s.address, s.email, s.course, s.year, s.status as student_status, u.name as teacher_name, pur.retrieve_date as undrop_retrieve_date, pur.undrop_remarks
    FROM class_card_drops ccd
    JOIN students s ON ccd.student_id = s.id
    JOIN users u ON ccd.teacher_id = u.id
    LEFT JOIN philcst_undrop_records pur ON ccd.id = pur.drop_id
    WHERE ccd.status IN ("Dropped", "Undropped")
    ORDER BY ccd.drop_date DESC
    LIMIT ' . intval($pagination['limit']) . ' OFFSET ' . intval($pagination['offset']) . '
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
                <a href="/CLASS_CARD_DROPPING_SYSTEM/admin/cancelled_class_card.php" class="nav-item">
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
            <header class="top-bar">
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
                                        <th>Year</th>
                                        <th>Subject</th>
                                        <th>Teacher</th>
                                        <th>Request Date & Time</th>
                                        <th>Time Remaining</th>
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
                                            <td><?php echo isset($drop['year']) ? htmlspecialchars($drop['year']) : 'N/A'; ?></td>
                                            <td><?php echo htmlspecialchars($drop['subject_no'] . ' - ' . $drop['subject_name']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['teacher_name']); ?></td>
                                            <td><?php echo formatDate($drop['drop_date']); ?></td>
                                            <td>
                                                <span class="countdown" data-deadline="<?php echo htmlspecialchars($drop['deadline']); ?>" style="color: inherit;">
                                                    <?php 
                                                    $now = new DateTime('now');
                                                    $deadline = new DateTime($drop['deadline']);
                                                    $diff = $deadline->diff($now);
                                                    if ($deadline > $now) {
                                                        if ($diff->h > 0) {
                                                            echo $diff->h . 'h ' . $diff->i . 'm';
                                                        } else {
                                                            echo $diff->i . 'm ' . $diff->s . 's';
                                                        }
                                                    } else {
                                                        echo '<span style="color: #dc3545; font-weight: bold;">OVERDUE</span>';
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                            <td><span class="remarks-cell" style="word-break: break-word;"><?php $remarks_text = htmlspecialchars($drop['remarks']); echo strlen($remarks_text) > 50 ? substr($remarks_text, 0, 50) . '... <a href="javascript:void(0)" onclick="showRemarksModal(\'' . addslashes($remarks_text) . '\', \'Teacher Remarks\')" style="color: #a78bfa; font-weight: 600;">See More</a>' : $remarks_text; ?></span></td>
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

                <!-- Approved Dropped Cards Table -->
                <section class="section">
                    <h2>Approved Dropped Class Card <span style="font-weight: normal; font-size: 0.9em; color: #666;">(<span id="approvedTable-count"><?php echo $pagination['total_items']; ?></span> total, page <?php echo $pagination['current_page']; ?> of <?php echo max(1, $pagination['total_pages']); ?>)</span></h2>
                    <?php if (count($drops) > 0): ?>
                        <div class="table-responsive">
                            <table class="table" id="approvedTable">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Course</th>
                                        <th>Year</th>
                                        <th>Subject</th>
                                        <th>Teacher</th>
                                        <th>Dropped Date & Time</th>
                                        <th>Approved Date & Time</th>
                                        <th>Class Card Status</th>
                                        <th>Teacher Remarks</th>
                                        <th>Action</th>
                                        <th>Detail</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($drops as $drop): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($drop['student_id']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['course'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($drop['year'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($drop['subject_no'] . ' - ' . $drop['subject_name']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['teacher_name']); ?></td>
                                            <td><?php echo formatDate($drop['drop_date']); ?></td>
                                            <td><?php echo $drop['approved_date'] ? formatDate($drop['approved_date']) : '-'; ?></td>
                                            <td>
                                                <span class="status status-<?php echo strtolower($drop['status']); ?>">
                                                    <?php echo htmlspecialchars($drop['status']); ?>
                                                </span>
                                            </td>
                                            <td><span class="remarks-cell" style="word-break: break-word;"><?php $remarks_text = htmlspecialchars($drop['remarks']); echo strlen($remarks_text) > 50 ? substr($remarks_text, 0, 50) . '... <a href="javascript:void(0)" onclick="showRemarksModal(\'' . addslashes($remarks_text) . '\', \'Teacher Remarks\')" style="color: #a78bfa; font-weight: 600;">See More</a>' : $remarks_text; ?></span></td>
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
                                            <td style="text-align: center;"><button class="detail-btn" onclick="showStudentDetailModal(<?php echo htmlspecialchars(json_encode($drop)); ?>)" title="View Details"><span style="font-weight: 700; color: #a78bfa;">i</span></button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php echo renderPaginationControls($pagination, '/CLASS_CARD_DROPPING_SYSTEM/admin/dropped_cards.php'); ?>
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
            startCountdownTimers();
        });

        // Update countdown timers every second
        function startCountdownTimers() {
            updateAllCountdowns();
            setInterval(updateAllCountdowns, 1000);
        }

        function updateAllCountdowns() {
            const countdowns = document.querySelectorAll('.countdown');
            countdowns.forEach(function(el) {
                const deadline = el.getAttribute('data-deadline');
                if (!deadline) return;

                const now = new Date();
                const deadlineDate = new Date(deadline);
                const diff = Math.floor((deadlineDate - now) / 1000); // seconds

                if (diff <= 0) {
                    el.innerHTML = '<span style="color: #dc3545; font-weight: bold;">OVERDUE</span>';
                    el.style.color = '#dc3545';
                } else {
                    const hours = Math.floor(diff / 3600);
                    const minutes = Math.floor((diff % 3600) / 60);
                    const seconds = diff % 60;

                    let timeStr = '';
                    if (hours > 0) {
                        timeStr = hours + 'h ' + minutes + 'm ' + seconds + 's';
                    } else {
                        timeStr = minutes + 'm ' + seconds + 's';
                    }

                    // Change color based on remaining time
                    if (diff < 3600) { // Less than 1 hour
                        el.style.color = '#dc3545';
                        el.style.fontWeight = 'bold';
                    } else if (diff < 7200) { // Less than 2 hours
                        el.style.color = '#ff9800';
                    } else {
                        el.style.color = 'inherit';
                    }

                    el.innerHTML = timeStr;
                }
            });
        }

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

            const retrieveDate = (recordData.undrop_retrieve_date && recordData.undrop_retrieve_date !== '0000-00-00 00:00:00') ? recordData.undrop_retrieve_date : 'N/A';
            
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
                                    padding: 32px;
                                    border-radius: 14px;
                                    border-left: 5px solid var(--primary-color);
                                ">
                                    <h3 style="
                                        color: var(--primary-color);
                                        margin: 0 0 28px 0;
                                        font-size: 1.35em;
                                        font-weight: 700;
                                    ">
                                        Student Information
                                    </h3>
                                    <div style="margin-bottom: 26px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Student ID</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.1em; font-weight: 600;">${recordData.student_id}</p>
                                    </div>
                                    <div style="margin-bottom: 26px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Full Name</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.1em; font-weight: 600;">${recordData.student_name}</p>
                                    </div>
                                    <div style="margin-bottom: 26px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Course</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.1em; font-weight: 600;">${recordData.course || 'N/A'}</p>
                                    </div>
                                    <div style="margin-bottom: 26px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Year Level</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.1em; font-weight: 600;">${recordData.year || 'N/A'}</p>
                                    </div>
                                    <div style="margin-bottom: 26px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Guardian Name</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.1em; font-weight: 600;">${recordData.guardian_name || 'N/A'}</p>
                                    </div>
                                    <div style="margin-bottom: 26px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Address</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.1em; font-weight: 600; word-break: break-word; line-height: 1.5;">${recordData.address || 'N/A'}</p>
                                    </div>
                                    <div>
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Email Address</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.1em; font-weight: 600; word-break: break-word;">${recordData.email || 'N/A'}</p>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div style="
                                    background: linear-gradient(135deg, rgba(167, 139, 250, 0.1), rgba(155, 89, 182, 0.05));
                                    padding: 32px;
                                    border-radius: 14px;
                                    border-left: 5px solid #9b59b6;
                                ">
                                    <h3 style="
                                        color: #9b59b6;
                                        margin: 0 0 28px 0;
                                        font-size: 1.35em;
                                        font-weight: 700;
                                    ">
                                        Drop Information
                                    </h3>
                                    <div style="margin-bottom: 26px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Teacher</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.1em; font-weight: 600;">${recordData.teacher_name || 'N/A'}</p>
                                    </div>
                                    <div style="margin-bottom: 26px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Subject</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.1em; font-weight: 600;">${recordData.subject_no} - ${recordData.subject_name}</p>
                                    </div>
                                    <div style="margin-bottom: 26px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Class Card Status</label>
                                        <p style="margin: 0; color: #1f2937;">
                                            <span style="padding: 6px 12px; border-radius: 6px; display: inline-block; font-weight: 600; font-size: 0.95em; background-color: #dbeafe; color: #1e40af;">${recordData.status}</span>
                                        </p>
                                    </div>
                                    <div style="margin-bottom: 26px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Dropped Date & Time</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.1em; font-weight: 600;">${new Date(recordData.drop_date).toLocaleString()}</p>
                                    </div>
                                    <div style="margin-bottom: 26px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Retrieved Date & Time</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.1em; font-weight: 600;">${retrieveDate !== 'N/A' ? new Date(retrieveDate).toLocaleString() : 'N/A'}</p>
                                    </div>
                                    <div>
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Student Status</label>
                                        <p style="margin: 0; color: #1f2937;">
                                            <span style="padding: 6px 12px; border-radius: 6px; display: inline-block; font-weight: 600; font-size: 0.95em; background-color: #e0e7ff; color: #3730a3;">${recordData.student_status ? recordData.student_status.charAt(0).toUpperCase() + recordData.student_status.slice(1) : 'N/A'}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
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

        // Remarks modal function
        function showRemarksModal(remarksText, remarksTitle) {
            const modal = document.createElement('div');
            modal.className = 'remarks-modal';
            modal.id = 'remarksModal';
            
            modal.innerHTML = `
                <div class="remarks-modal-box">
                    <div class="remarks-modal-header">
                        <h3>${remarksTitle}</h3>
                        <button class="remarks-modal-close" onclick="closeRemarksModal()">×</button>
                    </div>
                    <div class="remarks-modal-body">
                        <p>${remarksText}</p>
                    </div>
                    <div class="remarks-modal-footer">
                        <button class="btn-close-remarks-modal" onclick="closeRemarksModal()">Close</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            modal.addEventListener('click', function(e) {
                if (e.target === modal) closeRemarksModal();
            });
            
            document.addEventListener('keydown', function handler(e) {
                if (e.key === 'Escape') {
                    closeRemarksModal();
                    document.removeEventListener('keydown', handler);
                }
            });
        }

        function closeRemarksModal() {
            const modal = document.getElementById('remarksModal');
            if (modal) modal.remove();
        }

        // Prevent scroll to top on pagination click
        document.addEventListener('DOMContentLoaded', function() {
            const paginationLinks = document.querySelectorAll('.pagination-link');
            paginationLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    const scrollPosition = window.scrollY || window.pageYOffset;
                    sessionStorage.setItem('scrollPosition', scrollPosition);
                });
            });

            // Restore scroll position if coming from pagination
            const savedScrollPosition = sessionStorage.getItem('scrollPosition');
            if (savedScrollPosition !== null) {
                setTimeout(() => {
                    window.scrollTo(0, parseInt(savedScrollPosition));
                    sessionStorage.removeItem('scrollPosition');
                }, 100);
            }
        });
    </script>
</body>
</html>