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

// Fetch pending drops
$stmt = $pdo->prepare('
    SELECT ccd.*, s.name as student_name, s.guardian_name, s.student_id as student_id_number, s.course as student_course, s.status as student_status, s.year as student_year, s.address as student_address, u.name as teacher_name, pur.retrieve_date as undrop_retrieve_date, pur.undrop_remarks
    FROM class_card_drops ccd
    JOIN students s ON ccd.student_id = s.id
    JOIN users u ON ccd.teacher_id = u.id
    LEFT JOIN philcst_undrop_records pur ON ccd.id = pur.drop_id
    WHERE ccd.teacher_id = ? AND ccd.status = "Pending"
    ORDER BY ccd.drop_date DESC
    LIMIT 5
');
$stmt->execute([$user_id]);
$recent_drops = $stmt->fetchAll();

// Function to get active drop status for a student
function getActiveDropStatus($pdo, $student_id, $subject_no) {
    $stmt = $pdo->prepare('
        SELECT id, status FROM class_card_drops 
        WHERE student_id = ? AND subject_no = ? 
        AND status IN ("Pending", "Dropped")
        AND cancelled_date IS NULL
        LIMIT 1
    ');
    $stmt->execute([$student_id, $subject_no]);
    return $stmt->fetch();
}

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
                <a href="/CLASS_CARD_DROPPING_SYSTEM/teacher/profile.php" class="nav-item">
                    <span>Profile</span>
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
            <!-- Sidebar Toggle Button -->
            <button class="sidebar-toggle-btn" onclick="toggleSidebar()" title="Toggle Sidebar">≡</button>
            
            <header class="top-bar">
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
                                <div class="form-group">
                                    <label for="student_id">Select Student <span style="color: #a78bfa;">*</span></label>
                                    <div style="position: relative;">
                                        <input type="text" id="student_search_display" placeholder="Click to select a student..." readonly style="cursor: pointer; background: white;">
                                        <div id="student_search_dropdown" class="student-search-dropdown" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border: 2px solid #e5e7eb; border-top: none; border-radius: 0 0 10px 10px; max-height: 300px; overflow-y: auto; z-index: 1000; box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);">
                                            <div style="padding: 12px; border-bottom: 1px solid #e5e7eb; position: sticky; top: 0; background: white;">
                                                <input type="text" id="student_search_input" placeholder="Search by ID or name..." style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 0.9em;" onkeyup="filterStudentSelect()">
                                            </div>
                                            <div id="student_options_list"></div>
                                        </div>
                                    </div>
                                    <select id="student_id" name="student_id" required style="display: none;">
                                        <option value="">-- Choose a Student --</option>
                                        <?php foreach ($students as $student): ?>
                                            <?php
                                                // Fetch status for each student
                                                $stmt_status = $pdo->prepare('SELECT status FROM students WHERE id = ?');
                                                $stmt_status->execute([$student['id']]);
                                                $status = $stmt_status->fetchColumn();
                                            ?>
                                            <option value="<?php echo $student['id']; ?>" data-course="<?php echo htmlspecialchars($student['course']); ?>" data-year="<?php echo $student['year']; ?>" data-name="<?php echo htmlspecialchars($student['name']); ?>" data-student-id="<?php echo htmlspecialchars($student['student_id']); ?>" data-search="<?php echo htmlspecialchars(strtolower($student['student_id'] . ' ' . $student['name'])); ?>" <?php echo ($status === 'inactive') ? 'disabled' : ''; ?>>
                                                <?php echo htmlspecialchars($student['student_id'] . ' - ' . $student['name']); ?><?php if ($status === 'inactive') echo ' (Inactive)'; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="course">Course</label>
                                        <input type="text" id="course" name="course" readonly placeholder="Auto-filled from student selection...">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="year">Year Level</label>
                                        <input type="text" id="year" name="year" readonly placeholder="Auto-filled from student selection...">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="subject_id">Select Subject <span style="color: #a78bfa;">*</span></label>
                                    <div style="position: relative;">
                                        <input type="text" id="subject_search_display" placeholder="Click to select a subject..." readonly style="cursor: pointer; background: white;">
                                        <div id="subject_search_dropdown" class="subject-search-dropdown" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border: 2px solid #e5e7eb; border-top: none; border-radius: 0 0 10px 10px; max-height: 300px; overflow-y: auto; z-index: 1000; box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);">
                                            <div style="padding: 12px; border-bottom: 1px solid #e5e7eb; position: sticky; top: 0; background: white;">
                                                <input type="text" id="subject_search_input" placeholder="Search by code or name..." style="width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 0.9em;" onkeyup="filterSubjectSearch()">
                                            </div>
                                            <div id="subject_options_list"></div>
                                        </div>
                                    </div>
                                    <select id="subject_id" name="subject_id" required style="display: none;">
                                        <option value="">-- Choose a Subject --</option>
                                        <?php foreach ($subjects as $subject): ?>
                                            <option value="<?php echo htmlspecialchars($subject['subject_no']); ?>" data-name="<?php echo htmlspecialchars($subject['subject_name']); ?>" data-code="<?php echo htmlspecialchars($subject['subject_no']); ?>" data-full="<?php echo htmlspecialchars($subject['subject_no'] . ' - ' . $subject['subject_name']); ?>" data-search="<?php echo htmlspecialchars(strtolower($subject['subject_no'] . ' ' . $subject['subject_name'])); ?>">
                                                <?php echo htmlspecialchars($subject['subject_no'] . ' - ' . $subject['subject_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="remarks">Reason for Drop</label>
                                    <textarea id="remarks" name="remarks" rows="3" placeholder="Enter reason (optional)..."></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="drop-modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeDropModal()">Cancel</button>
                            <button type="button" class="btn btn-primary" onclick="document.getElementById('dropForm').submit()">Submit Request</button>
                        </div>
                    </div>
                </div>

                <!-- Pending Request Class Card Section -->
                <section class="section">
                    <h2>Pending Request Class Card</h2>
                    <?php if (count($recent_drops) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Course</th>
                                        <th>Year</th>
                                        <th>Subject</th>
                                        <th>Class Card Status</th>
                                        <th>Student Status</th>
                                        <th>Teacher Remarks</th>
                                        <th>Detail</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_drops as $drop): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($drop['student_id_number']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['student_course']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['student_year'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($drop['subject_no'] . ' - ' . $drop['subject_name']); ?></td>
                                            <td><span class="status status-<?php echo strtolower($drop['status']); ?>"><?php echo htmlspecialchars($drop['status']); ?></span></td>
                                            <td><span class="status status-<?php echo strtolower($drop['student_status']); ?>"><?php echo ucfirst(htmlspecialchars($drop['student_status'])); ?></span></td>
                                            <td><span class="remarks-cell" style="word-break: break-word;"><?php $remarks_text = htmlspecialchars($drop['remarks']); echo strlen($remarks_text) > 50 ? substr($remarks_text, 0, 50) . '... <a href="javascript:void(0)" onclick="showRemarksModal(\'' . addslashes($remarks_text) . '\', \'Teacher Remarks\')" style="color: #a78bfa; font-weight: 600;">See More</a>' : $remarks_text; ?></span></td>
                                            <td style="text-align: center;"><button class="detail-btn" onclick="showStudentDetailModal(<?php echo htmlspecialchars(json_encode($drop)); ?>)" title="View Details"><span style="font-weight: 700; color: #a78bfa;">i</span></button></td>
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

                <!-- Approved Dropped Class Card Section -->
                <section class="section">
                    <h2>Approved Dropped Class Card</h2>
                    <?php 
                        $approved_drops = array_filter($recent_drops, function($drop) {
                            return $drop['status'] === 'Dropped' || $drop['status'] === 'Undropped';
                        });
                    ?>
                    <?php if (count($approved_drops) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Course</th>
                                        <th>Year</th>
                                        <th>Subject</th>
                                        <th>Class Card Status</th>
                                        <th>Student Status</th>
                                        <th>Teacher Remarks</th>
                                        <th>Detail</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($approved_drops as $drop): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($drop['student_id_number']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['student_course']); ?></td>
                                            <td><?php echo htmlspecialchars($drop['student_year'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($drop['subject_no'] . ' - ' . $drop['subject_name']); ?></td>
                                            <td><span class="status status-<?php echo strtolower($drop['status']); ?>"><?php echo htmlspecialchars($drop['status']); ?></span></td>
                                            <td><span class="status status-<?php echo strtolower($drop['student_status']); ?>"><?php echo ucfirst(htmlspecialchars($drop['student_status'])); ?></span></td>
                                            <td><span class="remarks-cell" style="word-break: break-word;"><?php $remarks_text = htmlspecialchars($drop['remarks']); echo strlen($remarks_text) > 50 ? substr($remarks_text, 0, 50) . '... <a href="javascript:void(0)" onclick="showRemarksModal(\'' . addslashes($remarks_text) . '\', \'Teacher Remarks\')" style="color: #a78bfa; font-weight: 600;">See More</a>' : $remarks_text; ?></span></td>
                                            <td style="text-align: center;"><button class="detail-btn" onclick="showStudentDetailModal(<?php echo htmlspecialchars(json_encode($drop)); ?>)" title="View Details"><span style="font-weight: 700; color: #a78bfa;">i</span></button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="no-data">No approved dropped class cards yet.</p>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>

    <script src="/CLASS_CARD_DROPPING_SYSTEM/js/functions.js"></script>
    <script>
        // Populate student options on page load
        function initStudentSearch() {
            const studentSelect = document.getElementById('student_id');
            const optionsList = document.getElementById('student_options_list');
            
            optionsList.innerHTML = '';
            
            const options = studentSelect.querySelectorAll('option');
            options.forEach(option => {
                if (option.value !== '') {
                    const div = document.createElement('div');
                    div.className = 'student-option';
                    div.style.cssText = 'padding: 12px 14px; cursor: pointer; color: #374151; font-size: 0.95em; transition: all 0.2s; border-bottom: 1px solid #f3f4f6;';
                    div.innerHTML = `<strong style="color: #7f3fc6; font-weight: 700;">${option.getAttribute('data-student-id')}</strong> - ${option.getAttribute('data-name')}`;
                    div.onclick = () => selectStudent(option.value, option.getAttribute('data-student-id'), option.getAttribute('data-name'), option.getAttribute('data-course'), option.getAttribute('data-year'));
                    
                    // Add hover effect
                    div.onmouseover = function() {
                        this.style.backgroundColor = '#f3f4f6';
                        this.style.paddingLeft = '18px';
                    };
                    div.onmouseout = function() {
                        this.style.backgroundColor = 'transparent';
                        this.style.paddingLeft = '14px';
                    };
                    
                    optionsList.appendChild(div);
                }
            });
        }

        function filterStudentSelect() {
            const searchInput = document.getElementById('student_search_input').value.toLowerCase();
            const studentSelect = document.getElementById('student_id');
            const optionsList = document.getElementById('student_options_list');
            
            optionsList.innerHTML = '';
            
            const options = studentSelect.querySelectorAll('option');
            options.forEach(option => {
                if (option.value === '') return;
                
                const searchText = option.getAttribute('data-search');
                if (searchText && searchText.includes(searchInput)) {
                    const div = document.createElement('div');
                    div.className = 'student-option';
                    div.style.cssText = 'padding: 12px 14px; cursor: pointer; color: #374151; font-size: 0.95em; transition: all 0.2s; border-bottom: 1px solid #f3f4f6;';
                    div.innerHTML = `<strong style="color: #7f3fc6; font-weight: 700;">${option.getAttribute('data-student-id')}</strong> - ${option.getAttribute('data-name')}`;
                    div.onclick = () => selectStudent(option.value, option.getAttribute('data-student-id'), option.getAttribute('data-name'), option.getAttribute('data-course'), option.getAttribute('data-year'));
                    
                    // Add hover effect
                    div.onmouseover = function() {
                        this.style.backgroundColor = '#f3f4f6';
                        this.style.paddingLeft = '18px';
                    };
                    div.onmouseout = function() {
                        this.style.backgroundColor = 'transparent';
                        this.style.paddingLeft = '14px';
                    };
                    
                    optionsList.appendChild(div);
                }
            });
        }

        function selectStudent(studentId, studentIdNum, studentName, course, year) {
            document.getElementById('student_id').value = studentId;
            document.getElementById('student_search_display').value = `${studentIdNum} - ${studentName}`;
            document.getElementById('course').value = course;
            document.getElementById('year').value = year;
            document.getElementById('student_search_dropdown').style.display = 'none';
            document.getElementById('student_search_input').value = '';
        }

        // Toggle student search dropdown
        document.getElementById('student_search_display').addEventListener('click', function() {
            const dropdown = document.getElementById('student_search_dropdown');
            if (dropdown.style.display === 'none') {
                dropdown.style.display = 'block';
                document.getElementById('student_search_input').focus();
                initStudentSearch();
            } else {
                dropdown.style.display = 'none';
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('student_search_dropdown');
            const searchDisplay = document.getElementById('student_search_display');
            if (e.target !== searchDisplay && !searchDisplay?.contains(e.target) && !dropdown?.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });

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

        function updateSubjectInfo() {
            // Subject info update if needed
        }

        // Initialize subject search
        function initSubjectSearch() {
            const subjectSelect = document.getElementById('subject_id');
            const optionsList = document.getElementById('subject_options_list');
            
            optionsList.innerHTML = '';
            
            const options = subjectSelect.querySelectorAll('option');
            options.forEach(option => {
                if (option.value !== '') {
                    const div = document.createElement('div');
                    div.className = 'subject-option';
                    div.style.cssText = 'padding: 12px 14px; cursor: pointer; color: #374151; font-size: 0.95em; transition: all 0.2s; border-bottom: 1px solid #f3f4f6;';
                    div.innerHTML = `<strong style="color: #7f3fc6; font-weight: 700;">${option.getAttribute('data-code')}</strong> - ${option.getAttribute('data-name')}`;
                    div.onclick = () => selectSubject(option.value, option.getAttribute('data-code'), option.getAttribute('data-name'));
                    
                    // Add hover effect
                    div.onmouseover = function() {
                        this.style.backgroundColor = '#f3f4f6';
                        this.style.paddingLeft = '18px';
                    };
                    div.onmouseout = function() {
                        this.style.backgroundColor = 'transparent';
                        this.style.paddingLeft = '14px';
                    };
                    
                    optionsList.appendChild(div);
                }
            });
        }

        function filterSubjectSearch() {
            const searchInput = document.getElementById('subject_search_input').value.toLowerCase();
            const subjectSelect = document.getElementById('subject_id');
            const optionsList = document.getElementById('subject_options_list');
            
            optionsList.innerHTML = '';
            
            const options = subjectSelect.querySelectorAll('option');
            options.forEach(option => {
                if (option.value === '') return;
                
                const searchText = option.getAttribute('data-search');
                if (searchText && searchText.includes(searchInput)) {
                    const div = document.createElement('div');
                    div.className = 'subject-option';
                    div.style.cssText = 'padding: 12px 14px; cursor: pointer; color: #374151; font-size: 0.95em; transition: all 0.2s; border-bottom: 1px solid #f3f4f6;';
                    div.innerHTML = `<strong style="color: #7f3fc6; font-weight: 700;">${option.getAttribute('data-code')}</strong> - ${option.getAttribute('data-name')}`;
                    div.onclick = () => selectSubject(option.value, option.getAttribute('data-code'), option.getAttribute('data-name'));
                    
                    // Add hover effect
                    div.onmouseover = function() {
                        this.style.backgroundColor = '#f3f4f6';
                        this.style.paddingLeft = '18px';
                    };
                    div.onmouseout = function() {
                        this.style.backgroundColor = 'transparent';
                        this.style.paddingLeft = '14px';
                    };
                    
                    optionsList.appendChild(div);
                }
            });
        }

        function selectSubject(subjectCode, subjectId, subjectName) {
            document.getElementById('subject_id').value = subjectCode;
            document.getElementById('subject_search_display').value = `${subjectId} - ${subjectName}`;
            document.getElementById('subject_search_dropdown').style.display = 'none';
            document.getElementById('subject_search_input').value = '';
        }

        // Toggle subject search dropdown
        document.getElementById('subject_search_display').addEventListener('click', function() {
            const dropdown = document.getElementById('subject_search_dropdown');
            if (dropdown.style.display === 'none') {
                dropdown.style.display = 'block';
                document.getElementById('subject_search_input').focus();
                initSubjectSearch();
            } else {
                dropdown.style.display = 'none';
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const studentDropdown = document.getElementById('student_search_dropdown');
            const subjectDropdown = document.getElementById('subject_search_dropdown');
            const searchDisplay = document.getElementById('student_search_display');
            const subjectDisplay = document.getElementById('subject_search_display');
            
            if (e.target !== searchDisplay && !searchDisplay?.contains(e.target) && !studentDropdown?.contains(e.target)) {
                if (studentDropdown) studentDropdown.style.display = 'none';
            }
            if (e.target !== subjectDisplay && !subjectDisplay?.contains(e.target) && !subjectDropdown?.contains(e.target)) {
                if (subjectDropdown) subjectDropdown.style.display = 'none';
            }
        });

        function openDropModal() {
            document.getElementById('dropModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeDropModal() {
            document.getElementById('dropModal').style.display = 'none';
            document.body.style.overflow = '';
        }

        // Cancel drop confirmation modal
        function showCancelDropConfirmModal(dropId) {
            const modal = document.createElement('div');
            modal.className = 'confirm-cancel-modal';
            modal.id = 'cancelDropConfirmModal';
            
            modal.innerHTML = `
                <div class="confirm-cancel-modal-box">
                    <div class="confirm-cancel-modal-header">
                        <h3>Confirm Cancel Drop Request</h3>
                    </div>
                    <div class="confirm-cancel-modal-body">
                        <p>Are you sure you want to cancel this drop request? This action cannot be undone.</p>
                    </div>
                    <div class="confirm-cancel-modal-footer">
                        <button class="btn btn-secondary" onclick="closeCancelDropConfirmModal()">Keep Request</button>
                        <button class="btn btn-danger" onclick="confirmCancelDrop(${dropId})">Yes, Cancel Request</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            modal.addEventListener('click', function(e) {
                if (e.target === modal) closeCancelDropConfirmModal();
            });
            
            document.addEventListener('keydown', function handler(e) {
                if (e.key === 'Escape') {
                    closeCancelDropConfirmModal();
                    document.removeEventListener('keydown', handler);
                }
            });
        }

        function closeCancelDropConfirmModal() {
            const modal = document.getElementById('cancelDropConfirmModal');
            if (modal) modal.remove();
        }

        function confirmCancelDrop(dropId) {
            closeCancelDropConfirmModal();
            
            // Submit the form to cancel the drop
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/CLASS_CARD_DROPPING_SYSTEM/includes/api.php?action=cancel_drop';
            form.innerHTML = `<input type="hidden" name="drop_id" value="${dropId}">`;
            document.body.appendChild(form);
            form.submit();
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

        function showStudentDetailModal(dropData) {
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

            const undropDate = dropData.undrop_retrieve_date && dropData.undrop_retrieve_date !== '0000-00-00 00:00:00' ? dropData.undrop_retrieve_date : 'N/A';
            
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
                                    padding: 24px;
                                    border-radius: 14px;
                                    border-left: 5px solid var(--primary-color);
                                ">
                                    <h3 style="
                                        color: var(--primary-color);
                                        margin: 0 0 24px 0;
                                        font-size: 1.25em;
                                        font-weight: 700;
                                        display: flex;
                                        align-items: center;
                                        gap: 10px;
                                    ">
                                        Student Information
                                    </h3>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Student ID</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${dropData.student_id_number}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Full Name</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${dropData.student_name}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Course</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${dropData.student_course}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Year Level</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${dropData.student_year || 'N/A'}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Guardian Name</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${dropData.guardian_name || 'N/A'}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Address</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600; word-break: break-word; line-height: 1.5;">${dropData.student_address || 'N/A'}</p>
                                    </div>
                                    <div>
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Student Status</label>
                                        <p style="margin: 0; color: #1f2937;">
                                            <span class="status status-${dropData.student_status.toLowerCase()}" style="padding: 6px 12px; border-radius: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.95em;">${dropData.student_status.charAt(0).toUpperCase() + dropData.student_status.slice(1)}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div style="
                                    background: linear-gradient(135deg, rgba(167, 139, 250, 0.1), rgba(155, 89, 182, 0.05));
                                    padding: 24px;
                                    border-radius: 14px;
                                    border-left: 5px solid #9b59b6;
                                ">
                                    <h3 style="
                                        color: #9b59b6;
                                        margin: 0 0 24px 0;
                                        font-size: 1.25em;
                                        font-weight: 700;
                                        display: flex;
                                        align-items: center;
                                        gap: 10px;
                                    ">
                                        Class Card Dropping Information
                                    </h3>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Teacher Name</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${dropData.teacher_name || 'N/A'}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Subject</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${dropData.subject_no} - ${dropData.subject_name}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Class Card Status</label>
                                        <p style="margin: 0; color: #1f2937;">
                                            <span class="status status-${dropData.status.toLowerCase()}">${dropData.status}</span>
                                        </p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Dropped Date & Time</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${new Date(dropData.drop_date).toLocaleString()}</p>
                                    </div>
                                    <div style="margin-bottom: 22px;">
                                        <label style="font-weight: 700; color: #6b7280; font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Undropped Date & Time</label>
                                        <p style="margin: 0; color: #1f2937; font-size: 1.05em; font-weight: 600;">${undropDate !== 'N/A' ? new Date(undropDate).toLocaleString() : 'N/A'}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="padding: 24px 32px; border-top: 2px solid #e9d5ff; display: flex; gap: 12px; justify-content: flex-end; background: white;">
                        <button onclick="closeStudentDetailModal()" style="
                            padding: 12px 28px;
                            background-color: #e9d5ff;
                            color: var(--primary-color);
                            border: none;
                            border-radius: 10px;
                            cursor: pointer;
                            font-weight: 700;
                            transition: all 0.3s;
                            font-size: 1em;
                        " onmouseover="this.style.backgroundColor='#ddd6fe'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 16px rgba(167, 139, 250, 0.3)'" onmouseout="this.style.backgroundColor='#e9d5ff'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">Close</button>
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

        // Check availability functionality removed - now allows re-dropping of class cards

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
