<?php
/**
 * Complete walk-in drop test simulating the admin portal flow
 */

require 'config/db.php';
require 'email/EmailNotifier.php';

// Simulate admin POST request
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';

$action = 'walk_in_drop';
$admin_id = $_SESSION['user_id'];
$student_id = intval(8);
$subject_no = trim('CS202');
$teacher_id = intval(4);
$remarks = trim('Walk-in drop - corrected');

echo "======================================\n";
echo "WALK-IN DROP COMPLETE FLOW TEST\n";
echo "======================================\n\n";

echo "[Step 1] Validating inputs...\n";
if (!$student_id || !$subject_no || !$teacher_id) {
    echo "✗ Missing required fields\n";
    exit;
}
echo "✓ All required fields provided\n";

try {
    // Fetch student
    echo "\n[Step 2] Fetching student...\n";
    $stmt = $pdo->prepare('SELECT id, name, email, student_id FROM students WHERE id = ?');
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        echo "✗ Student not found\n";
        exit;
    }
    echo "✓ Student: " . $student['name'] . " (" . $student['email'] . ")\n";
    
    // Fetch subject (using correct column: subject_code)
    echo "\n[Step 3] Fetching subject...\n";
    $stmt = $pdo->prepare('SELECT subject_code, subject_name FROM subjects WHERE subject_code = ?');
    $stmt->execute([$subject_no]);
    $subject = $stmt->fetch();
    
    if (!$subject) {
        echo "✗ Subject not found\n";
        exit;
    }
    echo "✓ Subject: " . $subject['subject_code'] . " - " . $subject['subject_name'] . "\n";
    
    $subject_name = $subject['subject_name'];
    
    // Fetch teacher
    echo "\n[Step 4] Fetching teacher...\n";
    $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE id = ? AND role = "teacher"');
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch();
    
    if (!$teacher) {
        echo "✗ Teacher not found\n";
        exit;
    }
    echo "✓ Teacher: " . $teacher['name'] . " (" . $teacher['email'] . ")\n";
    
    // Check for existing drop
    echo "\n[Step 5] Checking for duplicate drops...\n";
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
        echo "✗ Student already has " . $existing_drop['status'] . " drop for this subject\n";
        exit;
    }
    echo "✓ No duplicate drops found\n";
    
    // Prepare drop record
    echo "\n[Step 6] Preparing drop record...\n";
    $deadline = date('Y-m-d 23:59:59');
    $drop_date = date('Y-m-d H:i:s');
    $drop_month = date('F Y');
    $drop_year = date('Y');
    
    echo "  Drop Date: " . $drop_date . "\n";
    echo "  Deadline: " . $deadline . "\n";
    
    // Insert drop record with Dropped status (already approved by admin for walk-in)
    echo "\n[Step 7] Inserting drop record...\n";
    $stmt = $pdo->prepare('
        INSERT INTO class_card_drops 
        (teacher_id, student_id, subject_no, subject_name, remarks, status, drop_date, deadline, drop_month, drop_year, approved_by, approved_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ');
    $stmt->execute([
        $teacher_id,
        $student_id,
        $subject_no,
        $subject_name,
        $remarks,
        'Dropped',
        $drop_date,
        $deadline,
        $drop_month,
        $drop_year,
        $admin_id
    ]);
    
    $drop_id = $pdo->lastInsertId();
    echo "✓ Drop record inserted (ID: " . $drop_id . ")\n";
    
    // Send email to student
    echo "\n[Step 8] Sending student notification...\n";
    if ($student && $student['email']) {
        $emailNotifier = new EmailNotifier();
        $emailData = [
            'student_id' => $student['student_id'],
            'student_name' => $student['name'],
            'subject_no' => $subject_no,
            'subject_name' => $subject_name,
            'remarks' => $remarks,
            'teacher_name' => $teacher['name'],
            'drop_date' => $drop_date,
            'approved_date' => date('Y-m-d H:i:s')
        ];
        $emailNotifier->notifyStudentApproved($student['email'], $emailData);
        echo "✓ Student email sent to: " . $student['email'] . "\n";
    }
    
    // Send email to teacher
    echo "\n[Step 9] Sending teacher notification...\n";
    if ($teacher && $teacher['email']) {
        $emailNotifier = new EmailNotifier();
        $emailData = [
            'student_id' => $student['student_id'],
            'student_name' => $student['name'],
            'subject_no' => $subject_no,
            'subject_name' => $subject_name,
            'drop_date' => $drop_date,
            'approved_date' => date('Y-m-d H:i:s'),
            'remarks' => $remarks
        ];
        $emailNotifier->notifyTeacherApproved($teacher['email'], $emailData);
        echo "✓ Teacher email sent to: " . $teacher['email'] . "\n";
    }
    
    // Verify in database
    echo "\n[Step 10] Verifying in database...\n";
    $stmt = $pdo->prepare('
        SELECT ccd.*, s.name as student_name, u.name as teacher_name
        FROM class_card_drops ccd
        JOIN students s ON ccd.student_id = s.id
        JOIN users u ON ccd.teacher_id = u.id
        WHERE ccd.id = ?
    ');
    $stmt->execute([$drop_id]);
    $verified = $stmt->fetch();
    
    if ($verified && $verified['status'] === 'Dropped') {
        echo "✓ Drop record verified in database\n";
        echo "  Status: " . $verified['status'] . "\n";
        echo "  Student: " . $verified['student_name'] . "\n";
        echo "  Teacher: " . $verified['teacher_name'] . "\n";
    }
    
    echo "\n======================================\n";
    echo "✓ WALK-IN DROP COMPLETED SUCCESSFULLY!\n";
    echo "======================================\n";
    
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    exit;
}

?>
