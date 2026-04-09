<?php
// Database Health Check Script
require_once 'backend/config/db.php';
require_once 'backend/includes/functions.php';

$checks = [];

// Check all tables exist
$tables = ['users', 'students', 'subjects', 'departments', 'department_courses', 'class_card_drops', 'philcst_undrop_records'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll();
        $checks[$table] = [
            'exists' => true,
            'columns' => count($columns),
            'details' => array_column($columns, 'Field')
        ];
    } catch (Exception $e) {
        $checks[$table] = ['exists' => false, 'error' => $e->getMessage()];
    }
}

// Check critical columns
$column_checks = [
    'users' => ['id', 'name', 'email', 'password', 'role', 'department_id'],
    'students' => ['id', 'student_id', 'name', 'email', 'course', 'year', 'status', 'guardian_name', 'address'],
    'subjects' => ['id', 'subject_code', 'subject_name', 'department_id'],
    'class_card_drops' => ['id', 'teacher_id', 'student_id', 'subject_no', 'status', 'cancelled_date', 'deadline'],
    'philcst_undrop_records' => ['id', 'drop_id', 'student_id', 'subject_no', 'retrieve_date']
];

$critical_issues = [];
foreach ($column_checks as $table => $required_columns) {
    foreach ($required_columns as $col) {
        if (!in_array($col, $checks[$table]['details'] ?? [])) {
            $critical_issues[] = "Missing column '$col' in table '$table'";
        }
    }
}

// Check test data
try {
    $dept_count = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
    $subj_count = $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
    $course_count = $pdo->query("SELECT COUNT(*) FROM department_courses")->fetchColumn();
    $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    
    $checks['data'] = [
        'departments' => $dept_count,
        'subjects' => $subj_count,
        'courses' => $course_count,
        'users' => $user_count
    ];
} catch (Exception $e) {
    $checks['data'] = ['error' => $e->getMessage()];
}

// Output results
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Health Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .check { margin: 10px 0; padding: 10px; border: 1px solid #ccc; }
        .pass { background: #d4edda; border-color: #28a745; }
        .fail { background: #f8d7da; border-color: #dc3545; }
        .warning { background: #fff3cd; border-color: #ffc107; }
        h2 { color: #333; }
        .issue { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Database Health Check</h1>
    
    <?php if (!empty($critical_issues)): ?>
        <div class="check fail">
            <h3>CRITICAL ISSUES FOUND</h3>
            <?php foreach ($critical_issues as $issue): ?>
                <p class="issue">❌ <?php echo htmlspecialchars($issue); ?></p>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="check pass">
            <h3>✓ All critical columns present</h3>
        </div>
    <?php endif; ?>
    
    <h2>Table Status</h2>
    <?php foreach ($checks as $table => $info): ?>
        <?php if ($table !== 'data'): ?>
            <div class="check <?php echo isset($info['error']) ? 'fail' : 'pass'; ?>">
                <strong><?php echo htmlspecialchars($table); ?></strong>
                <?php if (isset($info['error'])): ?>
                    <p>Error: <?php echo htmlspecialchars($info['error']); ?></p>
                <?php else: ?>
                    <p>✓ Exists | Columns: <?php echo $info['columns']; ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
    
    <h2>Data Status</h2>
    <div class="check pass">
        <p>Departments: <strong><?php echo $checks['data']['departments'] ?? 'N/A'; ?></strong> (Expected: 7)</p>
        <p>Subjects: <strong><?php echo $checks['data']['subjects'] ?? 'N/A'; ?></strong> (Expected: 70)</p>
        <p>Courses: <strong><?php echo $checks['data']['courses'] ?? 'N/A'; ?></strong> (Expected: 14)</p>
        <p>Users: <strong><?php echo $checks['data']['users'] ?? 'N/A'; ?></strong> (Expected: ≥1)</p>
    </div>
    
    <h2>Summary</h2>
    <?php if (empty($critical_issues)): ?>
        <div class="check pass">
            <h3>✓ Database is properly configured!</h3>
        </div>
    <?php else: ?>
        <div class="check fail">
            <h3>❌ Database has issues that need to be fixed</h3>
        </div>
    <?php endif; ?>
</body>
</html>
