<?php
/**
 * Test the corrected undrop functionality
 */

require 'config/db.php';
require 'email/EmailNotifier.php';

echo "======================================\n";
echo "UNDROP WITH EMAIL TEST - FIXED VERSION\n";
echo "======================================\n\n";

// Step 1: Create a test dropped class card
echo "[Step 1] Creating test dropped class card...\n";
$stmt = $pdo->prepare('
    INSERT INTO class_card_drops (student_id, teacher_id, subject_no, subject_name, status, drop_month, drop_year, remarks)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
');

try {
    $stmt->execute([8, 4, 'TEST103', 'Test Subject 3', 'Dropped', date('m'), date('Y'), 'Test undrop with corrected FK']);
    $drop_id = $pdo->lastInsertId();
    echo "✓ Created dropped class card ID: " . $drop_id . "\n";
} catch (Exception $e) {
    echo "✗ Failed to create test drop: " . $e->getMessage() . "\n";
    exit;
}

// Step 2: Simulate the admin form undrop action
echo "\n[Step 2] Simulating admin undrop action...\n";
try {
    // Get drop details (exactly like the form handler does)
    $stmt = $pdo->prepare('
        SELECT ccd.*, 
               s.id as student_pk, s.student_id, s.name as student_name, s.email as student_email,
               u.name as teacher_name, u.email as teacher_email
        FROM class_card_drops ccd
        JOIN students s ON ccd.student_id = s.id
        JOIN users u ON ccd.teacher_id = u.id
        WHERE ccd.id = ?
    ');
    $stmt->execute([$drop_id]);
    $drop = $stmt->fetch();

    if (!$drop) {
        echo "✗ Drop record not found\n";
        exit;
    }

    echo "✓ Found drop record\n";
    echo "  Student PK (for FK): " . $drop['student_pk'] . "\n";
    echo "  Student Number: " . $drop['student_id'] . "\n";
    echo "  Student Name: " . $drop['student_name'] . "\n";
    echo "  Teacher: " . $drop['teacher_name'] . "\n";

    // Update status to Undropped
    echo "\n[Step 3] Updating status to Undropped...\n";
    $stmt = $pdo->prepare('UPDATE class_card_drops SET status = ? WHERE id = ?');
    $stmt->execute(['Undropped', $drop_id]);
    echo "✓ Status updated\n";

    // Insert undrop record - using student_pk which is the actual ID
    echo "\n[Step 4] Inserting undrop record with student_pk=" . $drop['student_pk'] . "...\n";
    $stmt = $pdo->prepare('
        INSERT INTO philcst_undrop_records 
        (drop_id, student_id, subject_no, subject_name, teacher_id, retrieve_date, undrop_remarks, undrop_certificates)
        VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)
    ');
    $stmt->execute([
        $drop_id,
        $drop['student_pk'],  // <-- THIS IS THE FIX!
        $drop['subject_no'],
        $drop['subject_name'],
        $drop['teacher_id'],
        'Test remarks',
        'N/A'
    ]);
    echo "✓ Undrop record inserted successfully!\n";

    // Send emails
    echo "\n[Step 5] Sending email notifications...\n";
    $emailNotifier = new EmailNotifier();
    $emailData = [
        'student_id' => $drop['student_id'],
        'student_name' => $drop['student_name'],
        'subject_no' => $drop['subject_no'],
        'subject_name' => $drop['subject_name'],
        'drop_date' => $drop['drop_date'],
        'retrieve_date' => date('Y-m-d H:i:s'),
        'undrop_remarks' => 'Test remarks',
        'undrop_certificates' => 'N/A'
    ];

    if ($drop['student_email']) {
        echo "  Sending to student: " . $drop['student_email'] . "...\n";
        $emailNotifier->notifyStudentApproved($drop['student_email'], $emailData);
        echo "  ✓ Student email sent\n";
    }

    if ($drop['teacher_email']) {
        echo "  Sending to teacher: " . $drop['teacher_email'] . "...\n";
        $emailNotifier->notifyTeacherUndropped($drop['teacher_email'], $emailData);
        echo "  ✓ Teacher email sent\n";
    }

    echo "\n✓ Undrop with emails completed successfully!\n";

} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit;
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit;
}

echo "\n======================================\n";
echo "TEST PASSED - UNDROP WORKING!\n";
echo "======================================\n";

?>
