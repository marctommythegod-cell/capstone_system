<?php
// teacher/drop_class_card.php - Drop Class Card

require_once '../includes/session_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

// Check if user is teacher
if ($_SESSION['user_role'] !== 'teacher') {
    redirect('/CLASS_CARD_DROPPING_SYSTEM/index.php');
}

$user_id = $_SESSION['user_id'];
$teacher_name = getUserName($pdo, $user_id);
$user_info = getUserInfo($pdo, $user_id);

// Fetch all students
$stmt = $pdo->prepare('SELECT id, student_id, name, course, year FROM students ORDER BY name');
$stmt->execute();
$students = $stmt->fetchAll();

// Fetch all subjects
$stmt = $pdo->prepare('SELECT id, subject_no, subject_name FROM subjects ORDER BY subject_name');
$stmt->execute();
$subjects = $stmt->fetchAll();

// Fetch recent drops (last 5)
$stmt = $pdo->prepare('
    SELECT ccd.*, s.name as student_name, s.student_id as student_id_number, s.course as student_course, s.status as student_status
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
    <title>Drop Class Card - PhilCST</title>
    <link rel="stylesheet" href="/CLASS_CARD_DROPPING_SYSTEM/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="/CLASS_CARD_DROPPING_SYSTEM/Philcst Logo (2).png" alt="PhilCST Logo" class="sidebar-logo">
                <h2>PhilCST</h2>
                <p>Teacher Portal</p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="/CLASS_CARD_DROPPING_SYSTEM/teacher/dashboard.php" class="nav-item">
                    <span>Overview</span>
                </a>
                <a href="/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_class_card.php" class="nav-item active">
                    <span>Drop Class Card</span>
                </a>
                <a href="/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_history.php" class="nav-item">
                    <span>Drop History</span>
                </a>
                <a href="#" class="nav-item logout-item" onclick="showLogoutModal(); return false;">
                    <span>Logout</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <p class="sidebar-footer-name"><?php echo htmlspecialchars($user_info['name']); ?></p>
                <p class="sidebar-footer-dept"><?php echo htmlspecialchars($user_info['department'] ?: 'Teacher'); ?></p>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="top-bar">
                <h1>Drop Class Card</h1>
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
                    <p style="margin-bottom: 15px; color: #666;">Click the button below to submit a class card drop request. All requests require admin approval.</p>
                    <button type="button" class="btn btn-primary btn-large" onclick="openDropModal()">Drop Class Card</button>
                </section>

                <!-- Drop Class Card Modal -->
                <div id="dropModal" class="drop-modal" style="display: none;">
                    <div class="drop-modal-box">
                        <div class="drop-modal-header">
                            <h3>Drop Student Class Card</h3>
                            <button type="button" class="drop-modal-close" onclick="closeDropModal()">&times;</button>
                        </div>
                        <div class="drop-modal-body">
                            <form method="POST" action="/CLASS_CARD_DROPPING_SYSTEM/includes/api.php?action=drop_class_card" id="dropForm">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="student_id">Select Student</label>
                                        <select id="student_id" name="student_id" required onchange="updateStudentInfo()">
                                            <option value="">-- Select a Student --</option>
                                            <?php foreach ($students as $student): ?>
                                                <?php
                                                    // Fetch status for each student
                                                    $stmt_status = $pdo->prepare('SELECT status FROM students WHERE id = ?');
                                                    $stmt_status->execute([$student['id']]);
                                                    $status = $stmt_status->fetchColumn();
                                                ?>
                                                <option value="<?php echo $student['id']; ?>" data-course="<?php echo htmlspecialchars($student['course']); ?>" data-year="<?php echo $student['year']; ?>" <?php echo ($status === 'inactive') ? 'disabled style="color:#aaa;"' : ''; ?>>
                                                    <?php echo htmlspecialchars($student['student_id'] . ' - ' . $student['name']); ?><?php if ($status === 'inactive') echo ' (Inactive)'; ?>
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
                                        <label for="subject_search">Search Subject (Code or Name)</label>
                                        <input type="text" id="subject_search" name="subject_search" placeholder="Search by subject code or name..." onkeyup="filterSubjects()">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="subject_id">Select Subject</label>
                                        <select id="subject_id" name="subject_id" required onchange="updateSubjectInfo()">
                                            <option value="">-- Select a Subject --</option>
                                            <?php foreach ($subjects as $subject): ?>
                                                <option value="<?php echo htmlspecialchars($subject['subject_no']); ?>" data-name="<?php echo htmlspecialchars($subject['subject_name']); ?>" data-code="<?php echo htmlspecialchars($subject['subject_no']); ?>" data-full="<?php echo htmlspecialchars($subject['subject_no'] . ' - ' . $subject['subject_name']); ?>">
                                                    <?php echo htmlspecialchars($subject['subject_no'] . ' - ' . $subject['subject_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="remarks">Remarks</label>
                                    <textarea id="remarks" name="remarks" rows="4" placeholder="Enter reason for dropping class card..."></textarea>
                                </div>

                                <div class="alert alert-info" style="margin: 15px 0 0;">
                                    <strong>Note:</strong> All class card drop requests require approval from the admin before they are officially processed.
                                </div>
                            </form>
                        </div>
                        <div class="drop-modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeDropModal()">Cancel</button>
                            <button type="button" class="btn btn-primary" onclick="document.getElementById('dropForm').submit()">Submit Drop Request</button>
                        </div>
                    </div>
                </div>

                <!-- Recent Class Card Drops Section -->
                <section class="section">
                    <h2>Recent Class Card Drops</h2>
                    <?php if (count($recent_drops) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Course</th>
                                        <th>Subject</th>
                                        <th>Drop Date & Time</th>
                                        <th>Class Card Status</th>
                                        <th>Student Status</th>
                                        <th>Teacher Remarks</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_drops as $drop): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($drop['student_id_number']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['student_course']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['subject_no'] . ' - ' . $drop['subject_name']); ?></td>
                                            <td><?php echo formatDate($drop['drop_date']); ?></td>
                                            <td><span class="status status-<?php echo strtolower($drop['status']); ?>"><?php echo htmlspecialchars($drop['status']); ?></span></td>
                                            <td><span class="status status-<?php echo strtolower($drop['student_status']); ?>"><?php echo ucfirst(htmlspecialchars($drop['student_status'])); ?></span></td>
                                            <td><?php echo htmlspecialchars(substr($drop['remarks'], 0, 50)); ?></td>
                                            <td>
                                                <?php if ($drop['status'] === 'Pending'): ?>
                                                    <form method="POST" action="/CLASS_CARD_DROPPING_SYSTEM/includes/api.php?action=cancel_drop" style="display:inline;">
                                                        <input type="hidden" name="drop_id" value="<?php echo $drop['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-cancel" onclick="return confirm('Are you sure you want to cancel this drop request?');">Cancel</button>
                                                    </form>
                                                <?php elseif ($drop['status'] === 'Dropped' || $drop['status'] === 'Undropped'): ?>
                                                    <button class="btn btn-sm btn-cancel" style="opacity:0.6; cursor:not-allowed;" disabled>Cancel</button>
                                                <?php else: ?>
                                                    <span style="color: #aaa; font-style: italic;">—</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div style="text-align: center; margin-top: 15px;">
                            <a href="/CLASS_CARD_DROPPING_SYSTEM/teacher/drop_history.php" class="btn btn-secondary">View All Drops</a>
                        </div>
                    <?php else: ?>
                        <p class="no-data">No class cards dropped yet.</p>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>

    <script src="/CLASS_CARD_DROPPING_SYSTEM/js/functions.js"></script>
    <script>
        function filterSubjects() {
            const searchInput = document.getElementById('subject_search').value.toLowerCase();
            const subjectSelect = document.getElementById('subject_id');
            const options = subjectSelect.querySelectorAll('option');
            
            options.forEach(option => {
                if (option.value === '') {
                    option.style.display = '';
                } else {
                    const fullText = option.getAttribute('data-full').toLowerCase();
                    const code = option.getAttribute('data-code').toLowerCase();
                    const name = option.getAttribute('data-name').toLowerCase();
                    
                    if (code.includes(searchInput) || name.includes(searchInput) || fullText.includes(searchInput)) {
                        option.style.display = '';
                    } else {
                        option.style.display = 'none';
                    }
                }
            });
        }

        function openDropModal() {
            document.getElementById('dropModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeDropModal() {
            document.getElementById('dropModal').style.display = 'none';
            document.body.style.overflow = '';
        }

        // Close modal when clicking outside
        document.getElementById('dropModal').addEventListener('click', function(e) {
            if (e.target === this) closeDropModal();
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeDropModal();
        });
    </script>
</body>
</html>
