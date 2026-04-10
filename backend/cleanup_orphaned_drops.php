<?php
/**
 * Clean up orphaned drop records
 * Removes drop records that reference non-existent students or teachers
 */

require 'config/db.php';

echo "========================================\n";
echo "ORPHANED DROP RECORDS CLEANUP\n";
echo "========================================\n\n";

// Find orphaned drop records (student doesn't exist)
echo "[1/3] Finding drop records with missing students...\n";
$stmt = $pdo->prepare('
    SELECT ccd.id, ccd.student_id, ccd.subject_no, ccd.status, ccd.drop_date
    FROM class_card_drops ccd
    WHERE ccd.student_id NOT IN (SELECT id FROM students)
');
$stmt->execute();
$orphaned_students = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($orphaned_students) > 0) {
    echo "Found " . count($orphaned_students) . " drop records with missing students:\n";
    foreach ($orphaned_students as $drop) {
        echo "  - Drop ID: " . $drop['id'] . ", Student ID: " . $drop['student_id'] . 
             ", Subject: " . $drop['subject_no'] . ", Status: " . $drop['status'] . 
             ", Date: " . $drop['drop_date'] . "\n";
    }
    
    echo "\nDeleting these orphaned drop records...\n";
    $stmt = $pdo->prepare('DELETE FROM class_card_drops WHERE student_id NOT IN (SELECT id FROM students)');
    $stmt->execute();
    echo "✓ Deleted " . count($orphaned_students) . " drop records\n\n";
} else {
    echo "✓ No orphaned drop records found (by student)\n\n";
}

// Find orphaned drop records (teacher doesn't exist)
echo "[2/3] Finding drop records with missing teachers...\n";
$stmt = $pdo->prepare('
    SELECT ccd.id, ccd.teacher_id, ccd.subject_no, ccd.status
    FROM class_card_drops ccd
    WHERE ccd.teacher_id NOT IN (SELECT id FROM users)
');
$stmt->execute();
$orphaned_teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($orphaned_teachers) > 0) {
    echo "Found " . count($orphaned_teachers) . " drop records with missing teachers:\n";
    foreach ($orphaned_teachers as $drop) {
        echo "  - Drop ID: " . $drop['id'] . ", Teacher ID: " . $drop['teacher_id'] . 
             ", Subject: " . $drop['subject_no'] . ", Status: " . $drop['status'] . "\n";
    }
    
    echo "\nDeleting these orphaned drop records...\n";
    $stmt = $pdo->prepare('DELETE FROM class_card_drops WHERE teacher_id NOT IN (SELECT id FROM users)');
    $stmt->execute();
    echo "✓ Deleted " . count($orphaned_teachers) . " drop records\n\n";
} else {
    echo "✓ No orphaned drop records found (by teacher)\n\n";
}

// Verify cleanup
echo "[3/3] Verifying cleanup...\n";
$stmt = $pdo->prepare('
    SELECT COUNT(*) as orphaned FROM class_card_drops ccd
    WHERE ccd.student_id NOT IN (SELECT id FROM students)
    OR ccd.teacher_id NOT IN (SELECT id FROM users)
');
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result['orphaned'] == 0) {
    echo "✓ All drop records now reference valid students and teachers\n\n";
    echo "========================================\n";
    echo "CLEANUP COMPLETE\n";
    echo "========================================\n";
    echo "You can now undrop class cards without errors!\n";
} else {
    echo "⚠ WARNING: Still " . $result['orphaned'] . " orphaned records found\n";
}
?>
