<?php
// admin/dropped_cards.php - View All Dropped Cards

require_once '../../backend/includes/session_check.php';
require_once '../../backend/config/db.php';
require_once '../../backend/includes/functions.php';
require_once '../../backend/includes/check_overdue_requests.php';
require_once '../../backend/email/EmailNotifier.php';

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
        // Get drop details with student and teacher info in one optimized query
        $stmt = $pdo->prepare('
            SELECT ccd.*, 
                   s.student_id, s.name as student_name,
                   u.name as teacher_name, u.email as teacher_email
            FROM class_card_drops ccd
            JOIN students s ON ccd.student_id = s.id
            JOIN users u ON ccd.teacher_id = u.id
            WHERE ccd.id = ?
        ');
        $stmt->execute([$drop_id]);
        $drop = $stmt->fetch();

        if (!$drop) {
            setMessage('error', 'Drop record not found.');
            redirect('/CLASS_CARD_DROPPING_SYSTEM/frontend/admin/dropped_cards.php');
        }

        // Update status to Undropped
        $stmt = $pdo->prepare('UPDATE class_card_drops SET status = ? WHERE id = ?');
        $stmt->execute(['Undropped', $drop_id]);

        // Insert undrop record
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

        // Allow script to continue after headers are sent (async email)
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        // Send email notification to teacher asynchronously
        if ($drop['teacher_email']) {
            error_log("Sending undrop email to teacher: " . $drop['teacher_email']);
            $emailNotifier = new EmailNotifier();
            $emailData = [
                'student_id' => $drop['student_id'],
                'student_name' => $drop['student_name'],
                'subject_no' => $drop['subject_no'],
                'subject_name' => $drop['subject_name'],
                'drop_date' => $drop['drop_date'],
                'retrieve_date' => date('Y-m-d H:i:s'),
                'undrop_remarks' => $undrop_remarks,
                'undrop_certificates' => $undrop_certificates
            ];
            $emailNotifier->notifyTeacherUndropped($drop['teacher_email'], $emailData);
        }

        setMessage('success', 'Class card has been undropped. The teacher is being notified.');
    } catch (Exception $e) {
        error_log("Exception in undrop action: " . $e->getMessage());
        setMessage('error', 'Error undropping class card: ' . $e->getMessage());
    }
    redirect('/CLASS_CARD_DROPPING_SYSTEM/frontend/admin/dropped_cards.php');
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

// Fetch dropped cards (to undrop) with pagination
$stmt = $pdo->prepare('
    SELECT COUNT(*) as total FROM class_card_drops
    WHERE status IN ("Dropped")
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
    WHERE ccd.status IN ("Dropped")
    ORDER BY ccd.drop_date DESC
    LIMIT ' . intval($pagination['limit']) . ' OFFSET ' . intval($pagination['offset']) . '
';

$stmt = $pdo->prepare($query);
$stmt->execute();
$drops = $stmt->fetchAll();

$message = getMessage();

// Fetch all students, subjects, and teachers for walk-in modal
$stmt = $pdo->prepare('SELECT id, student_id, name FROM students WHERE status = "active" ORDER BY name ASC');
$stmt->execute();
$all_students = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT subject_no, subject_name FROM subjects ORDER BY subject_name ASC');
$stmt->execute();
$all_subjects = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT id, name FROM users WHERE role = "teacher" ORDER BY name ASC');
$stmt->execute();
$all_teachers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Class Cards - PhilCST</title>
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        @keyframes slideUp {
            from {
                transform: translateY(0);
                opacity: 1;
            }
            to {
                transform: translateY(-20px);
                opacity: 0;
            }
        }
    </style>
    <script>
        // Embed data for walk-in modal
        window.walkInStudents = <?php echo json_encode($all_students); ?>;
        window.walkInSubjects = <?php echo json_encode($all_subjects); ?>;
        window.walkInTeachers = <?php echo json_encode($all_teachers); ?>;
    </script>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar Toggle Button -->
        <button class="sidebar-toggle-btn" onclick="toggleSidebar()" title="Toggle Sidebar">≡</button>
        
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../images/Philcst Logo (2).png" alt="PhilCST Logo" class="sidebar-logo">
                <h2>PhilCST</h2>
                <p>Admin Portal</p>
            </div>

            <nav class="sidebar-nav">
                <a href="/CLASS_CARD_DROPPING_SYSTEM/frontend/admin/dashboard.php" class="nav-item">
                    <span>Dashboard</span>
                </a>
                <a href="/CLASS_CARD_DROPPING_SYSTEM/frontend/admin/dropped_cards.php" class="nav-item active">
                    <span>Manage Class Cards</span>
                </a>
                <a href="/CLASS_CARD_DROPPING_SYSTEM/frontend/admin/students.php" class="nav-item">
                    <span>Manage Students</span>
                </a>
                <a href="/CLASS_CARD_DROPPING_SYSTEM/frontend/admin/teachers.php" class="nav-item">
                    <span>Manage Teachers</span>
                </a>
                <a href="/CLASS_CARD_DROPPING_SYSTEM/frontend/admin/drop_history.php" class="nav-item">
                    <span>Class Cards History</span>
                </a>
                <a href="/CLASS_CARD_DROPPING_SYSTEM/frontend/admin/cancelled_class_card.php" class="nav-item">
                    <span>Cancelled Class Cards</span>
                </a>
                <a href="/CLASS_CARD_DROPPING_SYSTEM/frontend/admin/profile.php" class="nav-item">
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
                        <div style="margin-bottom: 16px; display: flex; align-items: center; gap: 16px;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px;">
                                <input type="checkbox" id="pendingSelectAllCheckbox" onchange="toggleSelectAllPending()" style="width: 16px; height: 16px; cursor: pointer; accent-color: #7f3fc6;">
                                <span style="font-weight: 500; color: #374151; font-size: 0.9em;">Select All</span>
                            </label>
                            <span id="pendingSelectedCount" style="color: #7f3fc6; font-weight: 600; font-size: 0.85em; display: none;">0 selected</span>
                            <button type="button" id="bulkApprovePendingBtn" onclick="bulkApprovePending()" style="
                                padding: 8px 16px;
                                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                                color: white;
                                border: none;
                                border-radius: 6px;
                                cursor: pointer;
                                font-weight: 600;
                                font-size: 0.9em;
                                transition: all 0.3s;
                                display: none;
                                box-shadow: 0 2px 8px rgba(16, 185, 129, 0.25);
                            " onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(16, 185, 129, 0.35)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(16, 185, 129, 0.25)'">Approve Selected</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table" id="pendingTable">
                                <thead>
                                    <tr>
                                        <th style="width: 40px; text-align: center;"></th>
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
                                        <tr class="pending-drop-row" data-drop-id="<?php echo $drop['id']; ?>" style="transition: all 0.2s;">
                                            <td style="width: 40px; text-align: center;"><input type="checkbox" class="pending-drop-checkbox" data-drop-id="<?php echo $drop['id']; ?>" onchange="updatePendingSelection()" style="width: 18px; height: 18px; cursor: pointer; accent-color: #7f3fc6;"></td>
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

                <!-- Undrop Class Card Table -->
                <section class="section">
                    <h2>Undrop Class Card <span style="font-weight: normal; font-size: 0.9em; color: #666;">(<span id="approvedTable-count"><?php echo $pagination['total_items']; ?></span> total, page <?php echo $pagination['current_page']; ?> of <?php echo max(1, $pagination['total_pages']); ?>)</span></h2>
                    
                    <!-- Walk-in Drop Button -->
                    <div style="margin-bottom: 20px;">
                        <button type="button" onclick="showWalkInDropModal()" style="
                            padding: 12px 24px;
                            background: linear-gradient(135deg, #a78bfa 0%, #7f3fc6 100%);
                            color: white;
                            border: none;
                            border-radius: 6px;
                            cursor: pointer;
                            font-weight: 600;
                            font-size: 0.95em;
                            transition: all 0.3s;
                            box-shadow: 0 4px 12px rgba(167, 139, 250, 0.3);
                        " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(167, 139, 250, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(167, 139, 250, 0.3)'">Drop Class Card</button>
                    </div>
                    
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
                                        <tr class="approved-drop-row" data-drop-id="<?php echo $drop['id']; ?>" style="transition: all 0.2s;">
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
                                                        <button type="button" class="btn btn-sm btn-danger" onclick="showUndropModal(<?php echo $drop['id']; ?>, '<?php echo addslashes(htmlspecialchars($drop['remarks'])); ?>')">Undrop</button>
                                                    </form>
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
                        <?php echo renderPaginationControls($pagination, '/CLASS_CARD_DROPPING_SYSTEM/frontend/admin/dropped_cards.php'); ?>
                    <?php else: ?>
                        <p class="no-data">No dropped cards found.</p>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>

    <script src="../js/functions.js"></script>
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
                        <span>Student Information & Class Card Dropping Information</span>
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
                                    <div style="margin-bottom: 26px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Email Address</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.1em; font-weight: 600; word-break: break-word;">${recordData.email || 'N/A'}</p>
                                    </div>
                                    <div>
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Student Status</label>
                                        <p style="margin: 0; color: #1f2937;">
                                            <span class="status status-${recordData.student_status.toLowerCase()}" style="padding: 6px 12px; border-radius: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.95em;">${recordData.student_status ? recordData.student_status.charAt(0).toUpperCase() + recordData.student_status.slice(1) : 'N/A'}</span>
                                        </p>
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
                                        Class Card Dropping Information
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
                                            <span class="status status-${recordData.status.toLowerCase()}">${recordData.status}</span>
                                        </p>
                                    </div>
                                    <div style="margin-bottom: 26px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Dropped Date & Time</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.1em; font-weight: 600;">${new Date(recordData.drop_date).toLocaleString()}</p>
                                    </div>
                                    <div>
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Retrieved Date & Time</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.1em; font-weight: 600;">${retrieveDate !== 'N/A' ? new Date(retrieveDate).toLocaleString() : 'N/A'}</p>
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

        // Bulk approval functions
        function toggleSelectAllPending() {
            const checkboxes = document.querySelectorAll('.pending-drop-checkbox');
            const selectAllCheckbox = document.getElementById('pendingSelectAllCheckbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            updatePendingSelection();
        }

        function updatePendingSelection() {
            const checkboxes = document.querySelectorAll('.pending-drop-checkbox');
            const selectedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
            const selectAllCheckbox = document.getElementById('pendingSelectAllCheckbox');
            const bulkBtn = document.getElementById('bulkApprovePendingBtn');
            const countSpan = document.getElementById('pendingSelectedCount');

            // Update row highlighting
            document.querySelectorAll('.pending-drop-row').forEach(row => {
                const checkbox = row.querySelector('.pending-drop-checkbox');
                if (checkbox && checkbox.checked) {
                    row.style.background = 'rgba(16, 185, 129, 0.08)';
                    row.style.borderLeft = '4px solid #10b981';
                } else {
                    row.style.background = '';
                    row.style.borderLeft = '';
                }
            });

            // Update select all checkbox state
            selectAllCheckbox.checked = selectedCount > 0 && selectedCount === checkboxes.length;
            selectAllCheckbox.indeterminate = selectedCount > 0 && selectedCount < checkboxes.length;

            // Show/hide bulk action button and count
            if (selectedCount > 0) {
                bulkBtn.style.display = 'inline-block';
                countSpan.style.display = 'inline-block';
                countSpan.textContent = selectedCount + ' selected';
            } else {
                bulkBtn.style.display = 'none';
                countSpan.style.display = 'none';
            }
        }

        function bulkApprovePending() {
            const checkboxes = document.querySelectorAll('.pending-drop-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('Please select at least one drop request to approve.');
                return;
            }

            const dropIds = Array.from(checkboxes).map(cb => cb.getAttribute('data-drop-id'));
            const message = 'Are you sure you want to approve ' + dropIds.length + ' class card drop request(s)?\n\nNotification emails will be sent to all students and teachers.';
            
            showConfirmModal(message, function() {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../../backend/includes/api.php?action=bulk_approve_drops';

                dropIds.forEach(dropId => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'drop_ids[]';
                    input.value = dropId;
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();
            });
        }

        // Bulk undrop functions
        function toggleSelectAllApproved() {
            const checkboxes = document.querySelectorAll('.approved-drop-checkbox');
            const selectAllCheckbox = document.getElementById('approvedSelectAllCheckbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            updateApprovedSelection();
        }

        function updateApprovedSelection() {
            const checkboxes = document.querySelectorAll('.approved-drop-checkbox');
            const selectedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
            const selectAllCheckbox = document.getElementById('approvedSelectAllCheckbox');
            const countSpan = document.getElementById('approvedSelectedCount');

            // Update row highlighting
            document.querySelectorAll('.approved-drop-row').forEach(row => {
                const checkbox = row.querySelector('.approved-drop-checkbox');
                if (checkbox && checkbox.checked) {
                    row.style.background = 'rgba(220, 38, 38, 0.08)';
                    row.style.borderLeft = '4px solid #dc2626';
                } else {
                    row.style.background = '';
                    row.style.borderLeft = '';
                }
            });

            // Update select all checkbox state
            selectAllCheckbox.checked = selectedCount > 0 && selectedCount === checkboxes.length;
            selectAllCheckbox.indeterminate = selectedCount > 0 && selectedCount < checkboxes.length;

            // Show/hide count
            if (selectedCount > 0) {
                countSpan.style.display = 'inline-block';
                countSpan.textContent = selectedCount + ' selected';
            } else {
                countSpan.style.display = 'none';
            }
        }

        // Walk-in Drop Functions
        function showWalkInDropModal() {
            const modal = document.createElement('div');
            modal.id = 'walkInDropModal';
            modal.style.cssText = `
                display: flex;
                position: fixed;
                z-index: 1000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.6);
                align-items: center;
                justify-content: center;
                backdrop-filter: blur(4px);
            `;
            modal.innerHTML = `
                <div style="
                    background: linear-gradient(135deg, #ffffff 0%, #f9f7ff 100%);
                    padding: 0;
                    border-radius: 12px;
                    width: 90%;
                    max-width: 650px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    max-height: 90vh;
                    overflow: hidden;
                    display: flex;
                    flex-direction: column;
                ">
                    <div style="
                        background: linear-gradient(135deg, #a78bfa 0%, #7f3fc6 100%);
                        padding: 24px 30px;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        border-bottom: 2px solid rgba(167, 139, 250, 0.2);
                    ">
                        <h2 style="margin: 0; font-size: 1.6em; font-weight: 700; color: white; letter-spacing: 0.5px;">Drop Class Card</h2>
                        <button onclick="document.getElementById('walkInDropModal').remove()" style="
                            background: rgba(255,255,255,0.2);
                            border: none;
                            font-size: 28px;
                            cursor: pointer;
                            color: white;
                            width: 40px;
                            height: 40px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            border-radius: 6px;
                            transition: all 0.3s;
                        " onmouseover="this.style.backgroundColor='rgba(255,255,255,0.3)'" onmouseout="this.style.backgroundColor='rgba(255,255,255,0.2)'">&times;</button>
                    </div>
                    <form id="walkInDropForm" method="POST" style="
                        display: flex;
                        flex-direction: column;
                        gap: 20px;
                        padding: 30px;
                        overflow-y: auto;
                        flex: 1;
                    ">
                        <div>
                            <label style="
                                display: block;
                                margin-bottom: 10px;
                                font-weight: 600;
                                color: #1f2937;
                                font-size: 0.95em;
                                letter-spacing: 0.3px;
                            ">Student <span style="color: #ef4444;">*</span></label>
                            <div style="position: relative;">
                                <input type="text" id="walkInStudentDisplay" placeholder="Click to search student..." readonly style="
                                    cursor: pointer; 
                                    background: white;
                                    width: 100%;
                                    padding: 12px 16px;
                                    border: 2px solid #e5e7eb;
                                    border-radius: 8px;
                                    font-size: 0.95em;
                                    transition: all 0.3s;
                                    color: #374151;
                                " onmouseover="this.style.borderColor='#a78bfa'" onmouseout="this.style.borderColor='#e5e7eb'">
                                <div id="walkInStudentDropdown" style="
                                    display: none; 
                                    position: absolute; 
                                    top: 100%; 
                                    left: 0; 
                                    right: 0; 
                                    background: white; 
                                    border: 2px solid #a78bfa; 
                                    border-top: none; 
                                    border-radius: 0 0 8px 8px; 
                                    max-height: 300px; 
                                    overflow-y: auto; 
                                    z-index: 1001; 
                                    box-shadow: 0 10px 25px rgba(167, 139, 250, 0.15);
                                ">
                                    <div style="padding: 14px; border-bottom: 1px solid #e5e7eb; position: sticky; top: 0; background: #fafafa;">
                                        <input type="text" id="walkInStudentSearch" placeholder="Search by ID or name..." style="
                                            width: 100%; 
                                            padding: 10px 12px; 
                                            border: 2px solid #e5e7eb; 
                                            border-radius: 6px; 
                                            font-size: 0.9em;
                                            transition: all 0.3s;
                                        " onfocus="this.style.borderColor='#a78bfa'" onblur="this.style.borderColor='#e5e7eb'" onkeyup="filterWalkInStudents()">
                                    </div>
                                    <div id="walkInStudentList"></div>
                                </div>
                            </div>
                            <input type="hidden" id="walkInStudentId" name="student_id">
                        </div>
                        <div>
                            <label style="
                                display: block;
                                margin-bottom: 10px;
                                font-weight: 600;
                                color: #1f2937;
                                font-size: 0.95em;
                                letter-spacing: 0.3px;
                            ">Subject <span style="color: #ef4444;">*</span></label>
                            <div style="position: relative;">
                                <input type="text" id="walkInSubjectDisplay" placeholder="Click to search subject..." readonly style="
                                    cursor: pointer; 
                                    background: white;
                                    width: 100%;
                                    padding: 12px 16px;
                                    border: 2px solid #e5e7eb;
                                    border-radius: 8px;
                                    font-size: 0.95em;
                                    transition: all 0.3s;
                                    color: #374151;
                                " onmouseover="this.style.borderColor='#a78bfa'" onmouseout="this.style.borderColor='#e5e7eb'">
                                <div id="walkInSubjectDropdown" style="
                                    display: none; 
                                    position: absolute; 
                                    top: 100%; 
                                    left: 0; 
                                    right: 0; 
                                    background: white; 
                                    border: 2px solid #a78bfa; 
                                    border-top: none; 
                                    border-radius: 0 0 8px 8px; 
                                    max-height: 300px; 
                                    overflow-y: auto; 
                                    z-index: 1001; 
                                    box-shadow: 0 10px 25px rgba(167, 139, 250, 0.15);
                                ">
                                    <div style="padding: 14px; border-bottom: 1px solid #e5e7eb; position: sticky; top: 0; background: #fafafa;">
                                        <input type="text" id="walkInSubjectSearch" placeholder="Search by code or name..." style="
                                            width: 100%; 
                                            padding: 10px 12px; 
                                            border: 2px solid #e5e7eb; 
                                            border-radius: 6px; 
                                            font-size: 0.9em;
                                            transition: all 0.3s;
                                        " onfocus="this.style.borderColor='#a78bfa'" onblur="this.style.borderColor='#e5e7eb'" onkeyup="filterWalkInSubjects()">
                                    </div>
                                    <div id="walkInSubjectList"></div>
                                </div>
                            </div>
                            <input type="hidden" id="walkInSubjectNo" name="subject_no">
                        </div>
                        <div>
                            <label style="
                                display: block;
                                margin-bottom: 10px;
                                font-weight: 600;
                                color: #1f2937;
                                font-size: 0.95em;
                                letter-spacing: 0.3px;
                            ">Teacher <span style="color: #ef4444;">*</span></label>
                            <div style="position: relative;">
                                <input type="text" id="walkInTeacherDisplay" placeholder="Click to search teacher..." readonly style="
                                    cursor: pointer; 
                                    background: white;
                                    width: 100%;
                                    padding: 12px 16px;
                                    border: 2px solid #e5e7eb;
                                    border-radius: 8px;
                                    font-size: 0.95em;
                                    transition: all 0.3s;
                                    color: #374151;
                                " onmouseover="this.style.borderColor='#a78bfa'" onmouseout="this.style.borderColor='#e5e7eb'">
                                <div id="walkInTeacherDropdown" style="
                                    display: none; 
                                    position: absolute; 
                                    top: 100%; 
                                    left: 0; 
                                    right: 0; 
                                    background: white; 
                                    border: 2px solid #a78bfa; 
                                    border-top: none; 
                                    border-radius: 0 0 8px 8px; 
                                    max-height: 300px; 
                                    overflow-y: auto; 
                                    z-index: 1001; 
                                    box-shadow: 0 10px 25px rgba(167, 139, 250, 0.15);
                                ">
                                    <div style="padding: 14px; border-bottom: 1px solid #e5e7eb; position: sticky; top: 0; background: #fafafa;">
                                        <input type="text" id="walkInTeacherSearch" placeholder="Search by name..." style="
                                            width: 100%; 
                                            padding: 10px 12px; 
                                            border: 2px solid #e5e7eb; 
                                            border-radius: 6px; 
                                            font-size: 0.9em;
                                            transition: all 0.3s;
                                        " onfocus="this.style.borderColor='#a78bfa'" onblur="this.style.borderColor='#e5e7eb'" onkeyup="filterWalkInTeachers()">
                                    </div>
                                    <div id="walkInTeacherList"></div>
                                </div>
                            </div>
                            <input type="hidden" id="walkInTeacherId" name="teacher_id">
                        </div>
                        <div>
                            <label style="
                                display: block;
                                margin-bottom: 10px;
                                font-weight: 600;
                                color: #1f2937;
                                font-size: 0.95em;
                                letter-spacing: 0.3px;
                            ">Remarks (Optional)</label>
                            <textarea id="walkInRemarks" name="remarks" placeholder="Add any additional remarks..." style="
                                width: 100%;
                                padding: 12px 16px;
                                border: 2px solid #e5e7eb;
                                border-radius: 8px;
                                font-size: 0.95em;
                                min-height: 110px;
                                font-family: inherit;
                                resize: vertical;
                                transition: all 0.3s;
                                color: #374151;
                            " onfocus="this.style.borderColor='#a78bfa'" onblur="this.style.borderColor='#e5e7eb'"></textarea>
                        </div>
                    </form>
                    <div style="
                        padding: 20px 30px;
                        background: #f9f7ff;
                        border-top: 1px solid #e5e7eb;
                        display: flex;
                        gap: 12px;
                        justify-content: flex-end;
                    ">
                        <button type="button" onclick="document.getElementById('walkInDropModal').remove()" style="
                            padding: 11px 24px;
                            background: #e5e7eb;
                            border: none;
                            border-radius: 8px;
                            cursor: pointer;
                            font-weight: 600;
                            color: #374151;
                            font-size: 0.95em;
                            transition: all 0.3s;
                        " onmouseover="this.style.backgroundColor='#d1d5db'" onmouseout="this.style.backgroundColor='#e5e7eb'">Cancel</button>
                        <button type="submit" form="walkInDropForm" style="
                            padding: 11px 28px;
                            background: linear-gradient(135deg, #a78bfa 0%, #7f3fc6 100%);
                            color: white;
                            border: none;
                            border-radius: 8px;
                            cursor: pointer;
                            font-weight: 600;
                            font-size: 0.95em;
                            box-shadow: 0 4px 12px rgba(167, 139, 250, 0.3);
                            transition: all 0.3s;
                        " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(167, 139, 250, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(167, 139, 250, 0.3)'">Confirm</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Load data and setup event listeners
            loadWalkInDropData();
            setupWalkInDropListeners();
            
            document.getElementById('walkInDropForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const studentId = document.getElementById('walkInStudentId').value;
                const subjectNo = document.getElementById('walkInSubjectNo').value;
                const teacherId = document.getElementById('walkInTeacherId').value;
                const remarks = document.getElementById('walkInRemarks').value.trim();
                
                if (!studentId || !subjectNo || !teacherId) {
                    alert('Please select student, subject, and teacher.');
                    return;
                }
                
                submitWalkInDrop(studentId, subjectNo, teacherId, remarks);
            });
        }

        function loadWalkInDropData() {
            // This will be populated by PHP data embedded in the page
            // For now, we'll fetch it via AJAX or use embedded data
            const modal = document.getElementById('walkInDropModal');
            initWalkInStudents(modal);
            initWalkInSubjects(modal);
            initWalkInTeachers(modal);
        }

        function setupWalkInDropListeners() {
            // Student dropdown toggle
            document.getElementById('walkInStudentDisplay').addEventListener('click', function() {
                const dropdown = document.getElementById('walkInStudentDropdown');
                dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
                if (dropdown.style.display === 'block') {
                    document.getElementById('walkInStudentSearch').focus();
                    initWalkInStudents();
                }
            });

            // Subject dropdown toggle
            document.getElementById('walkInSubjectDisplay').addEventListener('click', function() {
                const dropdown = document.getElementById('walkInSubjectDropdown');
                dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
                if (dropdown.style.display === 'block') {
                    document.getElementById('walkInSubjectSearch').focus();
                    initWalkInSubjects();
                }
            });

            // Teacher dropdown toggle
            document.getElementById('walkInTeacherDisplay').addEventListener('click', function() {
                const dropdown = document.getElementById('walkInTeacherDropdown');
                dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
                if (dropdown.style.display === 'block') {
                    document.getElementById('walkInTeacherSearch').focus();
                    initWalkInTeachers();
                }
            });

            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('#walkInStudentDisplay') && !e.target.closest('#walkInStudentDropdown')) {
                    document.getElementById('walkInStudentDropdown').style.display = 'none';
                }
                if (!e.target.closest('#walkInSubjectDisplay') && !e.target.closest('#walkInSubjectDropdown')) {
                    document.getElementById('walkInSubjectDropdown').style.display = 'none';
                }
                if (!e.target.closest('#walkInTeacherDisplay') && !e.target.closest('#walkInTeacherDropdown')) {
                    document.getElementById('walkInTeacherDropdown').style.display = 'none';
                }
            });
        }

        // Load and display students
        function initWalkInStudents() {
            const list = document.getElementById('walkInStudentList');
            list.innerHTML = '';
            
            const students = window.walkInStudents || [];
            students.forEach(student => {
                const div = document.createElement('div');
                div.style.cssText = 'padding: 12px 14px; cursor: pointer; color: #374151; font-size: 0.95em; transition: all 0.2s; border-bottom: 1px solid #f3f4f6;';
                div.innerHTML = `<strong style="color: #7f3fc6; font-weight: 700;">${student.student_id}</strong> - ${student.name}`;
                div.onclick = () => selectWalkInStudent(student.id, student.student_id, student.name);
                div.onmouseover = function() { this.style.backgroundColor = '#f3f4f6'; };
                div.onmouseout = function() { this.style.backgroundColor = 'transparent'; };
                list.appendChild(div);
            });
        }

        function filterWalkInStudents() {
            const search = document.getElementById('walkInStudentSearch').value.toLowerCase();
            const list = document.getElementById('walkInStudentList');
            list.innerHTML = '';
            
            const students = window.walkInStudents || [];
            students.filter(s => (s.student_id + ' ' + s.name).toLowerCase().includes(search)).forEach(student => {
                const div = document.createElement('div');
                div.style.cssText = 'padding: 12px 14px; cursor: pointer; color: #374151; font-size: 0.95em; transition: all 0.2s; border-bottom: 1px solid #f3f4f6;';
                div.innerHTML = `<strong style="color: #7f3fc6; font-weight: 700;">${student.student_id}</strong> - ${student.name}`;
                div.onclick = () => selectWalkInStudent(student.id, student.student_id, student.name);
                div.onmouseover = function() { this.style.backgroundColor = '#f3f4f6'; };
                div.onmouseout = function() { this.style.backgroundColor = 'transparent'; };
                list.appendChild(div);
            });
        }

        function selectWalkInStudent(id, studentId, name) {
            document.getElementById('walkInStudentId').value = id;
            document.getElementById('walkInStudentDisplay').value = `${studentId} - ${name}`;
            document.getElementById('walkInStudentDropdown').style.display = 'none';
        }

        // Load and display subjects
        function initWalkInSubjects() {
            const list = document.getElementById('walkInSubjectList');
            list.innerHTML = '';
            
            const subjects = window.walkInSubjects || [];
            subjects.forEach(subject => {
                const div = document.createElement('div');
                div.style.cssText = 'padding: 12px 14px; cursor: pointer; color: #374151; font-size: 0.95em; transition: all 0.2s; border-bottom: 1px solid #f3f4f6;';
                div.innerHTML = `<strong style="color: #7f3fc6; font-weight: 700;">${subject.subject_no}</strong> - ${subject.subject_name}`;
                div.onclick = () => selectWalkInSubject(subject.subject_no, subject.subject_name);
                div.onmouseover = function() { this.style.backgroundColor = '#f3f4f6'; };
                div.onmouseout = function() { this.style.backgroundColor = 'transparent'; };
                list.appendChild(div);
            });
        }

        function filterWalkInSubjects() {
            const search = document.getElementById('walkInSubjectSearch').value.toLowerCase();
            const list = document.getElementById('walkInSubjectList');
            list.innerHTML = '';
            
            const subjects = window.walkInSubjects || [];
            subjects.filter(s => (s.subject_no + ' ' + s.subject_name).toLowerCase().includes(search)).forEach(subject => {
                const div = document.createElement('div');
                div.style.cssText = 'padding: 12px 14px; cursor: pointer; color: #374151; font-size: 0.95em; transition: all 0.2s; border-bottom: 1px solid #f3f4f6;';
                div.innerHTML = `<strong style="color: #7f3fc6; font-weight: 700;">${subject.subject_no}</strong> - ${subject.subject_name}`;
                div.onclick = () => selectWalkInSubject(subject.subject_no, subject.subject_name);
                div.onmouseover = function() { this.style.backgroundColor = '#f3f4f6'; };
                div.onmouseout = function() { this.style.backgroundColor = 'transparent'; };
                list.appendChild(div);
            });
        }

        function selectWalkInSubject(subjectNo, subjectName) {
            document.getElementById('walkInSubjectNo').value = subjectNo;
            document.getElementById('walkInSubjectDisplay').value = `${subjectNo} - ${subjectName}`;
            document.getElementById('walkInSubjectDropdown').style.display = 'none';
        }

        // Load and display teachers
        function initWalkInTeachers() {
            const list = document.getElementById('walkInTeacherList');
            list.innerHTML = '';
            
            const teachers = window.walkInTeachers || [];
            teachers.forEach(teacher => {
                const div = document.createElement('div');
                div.style.cssText = 'padding: 12px 14px; cursor: pointer; color: #374151; font-size: 0.95em; transition: all 0.2s; border-bottom: 1px solid #f3f4f6;';
                div.innerHTML = `<strong style="color: #7f3fc6; font-weight: 700;">${teacher.name}</strong>`;
                div.onclick = () => selectWalkInTeacher(teacher.id, teacher.name);
                div.onmouseover = function() { this.style.backgroundColor = '#f3f4f6'; };
                div.onmouseout = function() { this.style.backgroundColor = 'transparent'; };
                list.appendChild(div);
            });
        }

        function filterWalkInTeachers() {
            const search = document.getElementById('walkInTeacherSearch').value.toLowerCase();
            const list = document.getElementById('walkInTeacherList');
            list.innerHTML = '';
            
            const teachers = window.walkInTeachers || [];
            teachers.filter(t => t.name.toLowerCase().includes(search)).forEach(teacher => {
                const div = document.createElement('div');
                div.style.cssText = 'padding: 12px 14px; cursor: pointer; color: #374151; font-size: 0.95em; transition: all 0.2s; border-bottom: 1px solid #f3f4f6;';
                div.innerHTML = `<strong style="color: #7f3fc6; font-weight: 700;">${teacher.name}</strong>`;
                div.onclick = () => selectWalkInTeacher(teacher.id, teacher.name);
                div.onmouseover = function() { this.style.backgroundColor = '#f3f4f6'; };
                div.onmouseout = function() { this.style.backgroundColor = 'transparent'; };
                list.appendChild(div);
            });
        }

        function selectWalkInTeacher(id, name) {
            document.getElementById('walkInTeacherId').value = id;
            document.getElementById('walkInTeacherDisplay').value = name;
            document.getElementById('walkInTeacherDropdown').style.display = 'none';
        }

        function submitWalkInDrop(studentId, subjectNo, teacherId, remarks) {
            const formData = new FormData();
            formData.append('action', 'walk_in_drop');
            formData.append('student_id', studentId);
            formData.append('subject_no', subjectNo);
            formData.append('teacher_id', teacherId);
            formData.append('remarks', remarks);

            fetch('../../backend/includes/api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Close the modal
                const modal = document.getElementById('walkInDropModal');
                if (modal) modal.remove();
                
                if (data.success) {
                    // Show success notification
                    showSuccessNotification('Class card has been dropped successfully! Email notifications have been sent to both student and teacher.');
                    
                    // Reload page after 2 seconds
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    // Show error notification
                    showErrorNotification(data.message || 'Error processing walk-in drop');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorNotification('Error processing walk-in drop: ' + error.message);
            });
        }

        function showSuccessNotification(message) {
            // Find content wrapper
            const contentWrapper = document.querySelector('.content-wrapper');
            if (!contentWrapper) {
                console.error('Content wrapper not found');
                return;
            }

            // Create alert div
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success';
            alertDiv.style.cssText = 'animation: slideDown 0.3s ease-out;';
            alertDiv.innerHTML = `
                <svg style="width: 20px; height: 20px; margin-right: 12px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>${message}</span>
            `;

            // Insert at the beginning of content wrapper
            contentWrapper.insertBefore(alertDiv, contentWrapper.firstChild);

            // Auto-remove after 4 seconds
            setTimeout(() => {
                alertDiv.style.animation = 'slideUp 0.3s ease-out';
                setTimeout(() => alertDiv.remove(), 300);
            }, 4000);
        }

        function showErrorNotification(message) {
            // Find content wrapper
            const contentWrapper = document.querySelector('.content-wrapper');
            if (!contentWrapper) {
                console.error('Content wrapper not found');
                return;
            }

            // Create alert div
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-error';
            alertDiv.style.cssText = 'animation: slideDown 0.3s ease-out;';
            alertDiv.innerHTML = `
                <svg style="width: 20px; height: 20px; margin-right: 12px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 0v2m0-2v-2m0 0v-2m0 2h2m-2 0h-2"></path>
                </svg>
                <span>${message}</span>
            `;

            // Insert at the beginning of content wrapper
            contentWrapper.insertBefore(alertDiv, contentWrapper.firstChild);

            // Auto-remove after 4 seconds
            setTimeout(() => {
                alertDiv.style.animation = 'slideUp 0.3s ease-out';
                setTimeout(() => alertDiv.remove(), 300);
            }, 4000);
        }
    </script>
</body>
</html>
