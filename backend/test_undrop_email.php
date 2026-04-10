<?php
/**
 * Test the fixed undrop functionality with email sending
 */

require 'config/db.php';
require 'email/EmailNotifier.php';

echo "======================================\n";
echo "UNDROP WITH EMAIL TEST\n";
echo "======================================\n\n";

// Step 1: Create a test dropped class card
echo "[Step 1] Creating test dropped class card...\n";
$stmt = $pdo->prepare('
    INSERT INTO class_card_drops (student_id, teacher_id, subject_no, subject_name, status, drop_month, drop_year, remarks)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
');

try {
    $stmt->execute([8, 4, 'TEST102', 'Test Subject 2', 'Dropped', date('m'), date('Y'), 'Test undrop with email']);
    $drop_id = $pdo->lastInsertId();
    echo "✓ Created dropped class card ID: " . $drop_id . "\n";
} catch (Exception $e) {
    echo "✗ Failed to create test drop: " . $e->getMessage() . "\n";
    exit;
}

// Step 2: Get drop details (simulating what the form handler does)
echo "\n[Step 2] Getting drop details...\n";
$stmt = $pdo->prepare('
    SELECT ccd.*, 
           s.student_id, s.name as student_name, s.email as student_email,
           u.name as teacher_name, u.email as teacher_email
    FROM class_card_drops ccd
    JOIN students s ON ccd.student_id = s.id
    JOIN users u ON ccd.teacher_id = u.id
    WHERE ccd.id = ?
');
$stmt->execute([$drop_id]);
$drop = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$drop) {
    echo "✗ Drop record not found\n";
    exit;
}

echo "✓ Found drop record\n";
echo "  Student: " . $drop['student_name'] . " (" . $drop['student_email'] . ")\n";
echo "  Teacher: " . $drop['teacher_name'] . " (" . $drop['teacher_email'] . ")\n";

// Step 3: Update status
echo "\n[Step 3] Updating status to Undropped...\n";
$stmt = $pdo->prepare('UPDATE class_card_drops SET status = ? WHERE id = ?');
$stmt->execute(['Undropped', $drop_id]);
echo "✓ Status updated\n";

// Step 4: Insert undrop record
echo "\n[Step 4] Inserting undrop record...\n";
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
    'Test undrop remarks',
    'N/A'
]);
echo "✓ Undrop record inserted\n";

// Step 5: Send emails
echo "\n[Step 5] Sending email notifications...\n";
$emailNotifier = new EmailNotifier();
$emailData = [
    'student_id' => $drop['student_id'],
    'student_name' => $drop['student_name'],
    'subject_no' => $drop['subject_no'],
    'subject_name' => $drop['subject_name'],
    'drop_date' => $drop['drop_date'],
    'retrieve_date' => date('Y-m-d H:i:s'),
    'undrop_remarks' => 'Test undrop remarks',
    'undrop_certificates' => 'N/A'
];

// Send to student
if ($drop['student_email']) {
    echo "  Sending email to student: " . $drop['student_email'] . "...\n";
    try {
        $result = $emailNotifier->notifyStudentApproved($drop['student_email'], $emailData);
        echo "  ✓ Student email " . ($result ? "sent successfully" : "queued") . "\n";
    } catch (Exception $e) {
        echo "  ✗ Failed to send student email: " . $e->getMessage() . "\n";
    }
}

// Send to teacher
if ($drop['teacher_email']) {
    echo "  Sending email to teacher: " . $drop['teacher_email'] . "...\n";
    try {
        $result = $emailNotifier->notifyTeacherUndropped($drop['teacher_email'], $emailData);
        echo "  ✓ Teacher email " . ($result ? "sent successfully" : "queued") . "\n";
    } catch (Exception $e) {
        echo "  ✗ Failed to send teacher email: " . $e->getMessage() . "\n";
    }
}

echo "\n======================================\n";
echo "TEST COMPLETE - UNDROP WITH EMAILS\n";
echo "======================================\n";

?>
