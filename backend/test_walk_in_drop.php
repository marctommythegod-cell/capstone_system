<?php
/**
 * Test the walk-in drop API endpoint
 */

require 'config/db.php';
require 'email/EmailNotifier.php';

echo "======================================\n";
echo "WALK-IN DROP TEST\n";
echo "======================================\n\n";

// Get test data
$student_id = 8;
$subject_no = 'CS301';
$teacher_id = 4;
$remarks = 'Walk-in test';

echo "[Step 1] Checking prerequisites...\n";

// Check student
$stmt = $pdo->prepare('SELECT id, name, email FROM students WHERE id = ?');
$stmt->execute([$student_id]);
$student = $stmt->fetch();
if (!$student) {
    echo "✗ Student not found\n";
    exit;
}
echo "✓ Student found: " . $student['name'] . "\n";

// Check subject
$stmt = $pdo->prepare('SELECT subject_code, subject_name FROM subjects WHERE subject_code = ?');
$stmt->execute([$subject_no]);
$subject = $stmt->fetch();
if (!$subject) {
    echo "✗ Subject not found\n";
    exit;
}
echo "✓ Subject found: " . $subject['subject_name'] . "\n";

// Check teacher
$stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE id = ? AND role = "teacher"');
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch();
if (!$teacher) {
    echo "✗ Teacher not found\n";
    exit;
}
echo "✓ Teacher found: " . $teacher['name'] . "\n";

// Check for existing drop
echo "\n[Step 2] Checking for existing drops...\n";
$stmt = $pdo->prepare('
    SELECT id, status FROM class_card_drops 
    WHERE student_id = ? AND subject_no = ? 
    AND status IN ("Pending", "Dropped")
    AND cancelled_date IS NULL
    LIMIT 1
');
$stmt->execute([$student_id, $subject_no]);
$existing_drop = $stmt->fetch();

if ($existing_drop) {
    echo "✗ Drop already exists with status: " . $existing_drop['status'] . "\n";
    exit;
}
echo "✓ No existing drop found\n";

// Insert the drop
echo "\n[Step 3] Inserting drop record...\n";
try {
    $drop_date = date('Y-m-d H:i:s');
    $deadline = date('Y-m-d 23:59:59');
    $drop_month = date('F Y');
    $drop_year = date('Y');
    
    $stmt = $pdo->prepare('
        INSERT INTO class_card_drops 
        (teacher_id, student_id, subject_no, subject_name, remarks, status, drop_date, deadline, drop_month, drop_year, approved_by, approved_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ');
    $stmt->execute([
        $teacher_id,
        $student_id,
        $subject_no,
        $subject['subject_name'],
        $remarks,
        'Dropped',
        $drop_date,
        $deadline,
        $drop_month,
        $drop_year,
        1  // admin_id
    ]);
    
    $drop_id = $pdo->lastInsertId();
    echo "✓ Drop record inserted with ID: " . $drop_id . "\n";
} catch (Exception $e) {
    echo "✗ Insert failed: " . $e->getMessage() . "\n";
    exit;
}

// Send emails
echo "\n[Step 4] Sending emails...\n";
try {
    $emailNotifier = new EmailNotifier();
    
    $emailData = [
        'student_id' => $student['id'],
        'student_name' => $student['name'],
        'subject_no' => $subject_no,
        'subject_name' => $subject['subject_name'],
        'remarks' => $remarks,
        'teacher_name' => $teacher['name'],
        'drop_date' => $drop_date,
        'approved_date' => date('Y-m-d H:i:s')
    ];
    
    if ($student && $student['email']) {
        echo "  Sending to student: " . $student['email'] . "...\n";
        $emailNotifier->notifyStudentApproved($student['email'], $emailData);
        echo "  ✓ Student email sent\n";
    }
    
    if ($teacher && $teacher['email']) {
        echo "  Sending to teacher: " . $teacher['email'] . "...\n";
        $emailNotifier->notifyTeacherApproved($teacher['email'], $emailData);
        echo "  ✓ Teacher email sent\n";
    }
} catch (Exception $e) {
    echo "✗ Email error: " . $e->getMessage() . "\n";
}

echo "\n======================================\n";
echo "WALK-IN DROP TEST COMPLETED\n";
echo "======================================\n";

?>
