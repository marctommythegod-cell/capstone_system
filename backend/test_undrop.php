<?php
/**
 * Test the complete undrop flow
 */

require 'config/db.php';
require 'email/EmailNotifier.php';

echo "======================================\n";
echo "UNDROP FUNCTIONALITY TEST\n";
echo "======================================\n\n";

// Step 1: Create a test dropped class card
echo "[Step 1] Creating test dropped class card...\n";
$stmt = $pdo->prepare('
    INSERT INTO class_card_drops (student_id, teacher_id, subject_no, subject_name, status, drop_month, drop_year, remarks)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
');

try {
    $stmt->execute([8, 4, 'TEST101', 'Test Subject', 'Dropped', date('m'), date('Y'), 'Test undrop functionality']);
    $drop_id = $pdo->lastInsertId();
    echo "✓ Created dropped class card ID: " . $drop_id . "\n";
} catch (Exception $e) {
    echo "✗ Failed to create test drop: " . $e->getMessage() . "\n";
    exit;
}

// Step 2: Verify the drop was created
echo "\n[Step 2] Verifying dropped class card exists...\n";
$stmt = $pdo->prepare('SELECT * FROM class_card_drops WHERE id = ?');
$stmt->execute([$drop_id]);
$drop = $stmt->fetch(PDO::FETCH_ASSOC);

if ($drop && $drop['status'] === 'Dropped') {
    echo "✓ Class card is in 'Dropped' status\n";
} else {
    echo "✗ Class card not found or not in Dropped status\n";
    exit;
}

// Step 3: Test the undrop operation (simulating what dropped_cards.php does)
echo "\n[Step 3] Testing undrop operation...\n";

// Get the drop with LEFT JOINs (like dropped_cards.php does)
$stmt = $pdo->prepare('
    SELECT ccd.id, ccd.student_id, ccd.teacher_id, ccd.subject_no,
           s.id as student_exists,
           u.id as teacher_exists,
           s.name as student_name,
           u.name as teacher_name,
           u.email as teacher_email
    FROM class_card_drops ccd
    LEFT JOIN students s ON ccd.student_id = s.id
    LEFT JOIN users u ON ccd.teacher_id = u.id
    WHERE ccd.id = ?
');
$stmt->execute([$drop_id]);
$drop_data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$drop_data) {
    echo "✗ Drop record not found\n";
    exit;
}

echo "  Student exists: " . ($drop_data['student_exists'] ? 'YES (ID ' . $drop_data['student_exists'] . ')' : 'NO') . "\n";
echo "  Teacher exists: " . ($drop_data['teacher_exists'] ? 'YES (ID ' . $drop_data['teacher_exists'] . ')' : 'NO') . "\n";

// Check validation
if (!$drop_data['student_exists']) {
    echo "✗ ERROR: Student record no longer exists. Cannot undrop this class card.\n";
    exit;
}

if (!$drop_data['teacher_exists']) {
    echo "✗ ERROR: Teacher record no longer exists. Cannot undrop this class card.\n";
    exit;
}

echo "✓ Validation passed - both student and teacher exist\n";

// Step 4: Perform the undrop
echo "\n[Step 4] Performing undrop...\n";

try {
    // Insert undrop record
    $stmt = $pdo->prepare('
        INSERT INTO philcst_undrop_records (drop_id, student_id, teacher_id, subject_no, subject_name, undrop_remarks)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([$drop_id, $drop_data['student_id'], $drop_data['teacher_id'], $drop_data['subject_no'], $drop_data['subject_name'] ?? 'N/A', 'Test undrop']);
    
    // Update the drop status
    $stmt = $pdo->prepare('UPDATE class_card_drops SET status = ? WHERE id = ?');
    $stmt->execute(['Undropped', $drop_id]);
    
    echo "✓ Undrop record inserted successfully\n";
    echo "✓ Class card status updated to 'Undropped'\n";
} catch (PDOException $exception) {
    if (strpos($exception->getMessage(), '1452') !== false) {
        echo "✗ FOREIGN KEY CONSTRAINT VIOLATION\n";
        echo "Message: " . $exception->getMessage() . "\n";
        
        // Re-validate
        $student_exists = $pdo->prepare('SELECT id FROM students WHERE id = ?');
        $student_exists->execute([$drop_data['student_id']]);
        $has_student = $student_exists->fetch();
        
        $teacher_exists = $pdo->prepare('SELECT id FROM users WHERE id = ?');
        $teacher_exists->execute([$drop_data['teacher_id']]);
        $has_teacher = $teacher_exists->fetch();
        
        echo "Re-validation at error time:\n";
        echo "  Student exists: " . ($has_student ? 'YES' : 'NO') . "\n";
        echo "  Teacher exists: " . ($has_teacher ? 'YES' : 'NO') . "\n";
        exit;
    } else {
        echo "✗ Database error: " . $exception->getMessage() . "\n";
        exit;
    }
}

// Step 5: Verify the undrop was successful
echo "\n[Step 5] Verifying undrop success...\n";
$stmt = $pdo->prepare('
    SELECT status FROM class_card_drops WHERE id = ?
');
$stmt->execute([$drop_id]);
$final_status = $stmt->fetch(PDO::FETCH_ASSOC)['status'];

if ($final_status === 'Undropped') {
    echo "✓ Class card status is now 'Undropped'\n";
} else {
    echo "✗ Class card status is " . $final_status . " (expected 'Undropped')\n";
    exit;
}

// Verify undrop record exists
$stmt = $pdo->prepare('SELECT id FROM philcst_undrop_records WHERE drop_id = ?');
$stmt->execute([$drop_id]);
$undrop_record = $stmt->fetch(PDO::FETCH_ASSOC);

if ($undrop_record) {
    echo "✓ Undrop record created (ID: " . $undrop_record['id'] . ")\n";
} else {
    echo "✗ Undrop record not found\n";
    exit;
}

echo "\n======================================\n";
echo "TEST COMPLETE - UNDROP WORKING!\n";
echo "======================================\n";

?>
