<?php
// teacher/drop_class_card.php - Drop Class Card

require_once '../includes/session_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

// Check if user is teacher
if ($_SESSION['user_role'] !== 'teacher') {
    redirect('/SYSTEM/index.php');
}

$user_id = $_SESSION['user_id'];
$teacher_name = getUserName($pdo, $user_id);

// Fetch all students
$stmt = $pdo->prepare('SELECT id, student_id, name, course, year FROM students ORDER BY name');
$stmt->execute();
$students = $stmt->fetchAll();

// Fetch all subjects
$stmt = $pdo->prepare('SELECT id, subject_no, subject_name FROM subjects ORDER BY subject_name');
$stmt->execute();
$subjects = $stmt->fetchAll();

$message = getMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drop Class Card - PhilCST</title>
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
                <a href="/SYSTEM/teacher/dashboard.php" class="nav-item">
                    <span>Overview</span>
                </a>
                <a href="/SYSTEM/teacher/drop_class_card.php" class="nav-item active">
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
                    <form method="POST" action="/SYSTEM/includes/api.php?action=drop_class_card" class="drop-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="student_id">Select Student</label>
                                <select id="student_id" name="student_id" required onchange="updateStudentInfo()">
                                    <option value="">-- Select a Student --</option>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?php echo $student['id']; ?>" data-course="<?php echo htmlspecialchars($student['course']); ?>" data-year="<?php echo $student['year']; ?>">
                                            <?php echo htmlspecialchars($student['student_id'] . ' - ' . $student['name']); ?>
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
                        
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="remarks">Remarks</label>
                                <textarea id="remarks" name="remarks" rows="4" placeholder="Enter reason for dropping class card..."></textarea>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <button type="submit" class="btn btn-primary">Drop Class Card</button>
                        </div>
                    </form>
                </section>
            </div>
        </main>
    </div>

    <script src="/SYSTEM/js/functions.js"></script>
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
    </script>
</body>
</html>
