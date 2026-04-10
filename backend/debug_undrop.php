<?php
/**
 * Debug script to test the undrop validation
 */

require 'config/db.php';

echo "Testing undrop validation...\n\n";

// Get a sample drop record
$stmt = $pdo->prepare('
    SELECT ccd.id, ccd.student_id, ccd.teacher_id, ccd.subject_no, ccd.status
    FROM class_card_drops ccd
    WHERE ccd.status = "Dropped"
    LIMIT 1
');
$stmt->execute();
$drop = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$drop) {
    echo "No dropped class cards found to test.\n";
    exit;
}

echo "Testing with Drop ID: " . $drop['id'] . "\n";
echo "Student ID: " . $drop['student_id'] . "\n";
echo "Teacher ID: " . $drop['teacher_id'] . "\n\n";

// Test LEFT JOIN query (what dropped_cards.php uses)
echo "[Test 1] Testing LEFT JOIN query...\n";
$stmt = $pdo->prepare('
    SELECT ccd.id,
           s.student_id, s.name as student_name, s.id as student_exists,
           u.name as teacher_name, u.email as teacher_email, u.id as teacher_exists
    FROM class_card_drops ccd
    LEFT JOIN students s ON ccd.student_id = s.id
    LEFT JOIN users u ON ccd.teacher_id = u.id
    WHERE ccd.id = ?
');
$stmt->execute([$drop['id']]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    echo "ERROR: Drop record not found!\n";
} else {
    echo "Drop found: YES\n";
    echo "  student_exists (s.id): " . ($result['student_exists'] ?? 'NULL') . "\n";
    echo "  teacher_exists (u.id): " . ($result['teacher_exists'] ?? 'NULL') . "\n";
    echo "  student_name: " . ($result['student_name'] ?? 'NULL') . "\n";
    echo "  teacher_name: " . ($result['teacher_name'] ?? 'NULL') . "\n";
}

echo "\n[Test 2] Direct student lookup...\n";
$stmt = $pdo->prepare('SELECT id, student_id, name FROM students WHERE id = ?');
$stmt->execute([$drop['student_id']]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if ($student) {
    echo "Student found: " . $student['student_id'] . " - " . $student['name'] . "\n";
} else {
    echo "Student NOT found!\n";
}

echo "\n[Test 3] Direct teacher lookup...\n";
$stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE id = ?');
$stmt->execute([$drop['teacher_id']]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if ($teacher) {
    echo "Teacher found: " . $teacher['name'] . " (" . $teacher['email'] . ")\n";
} else {
    echo "Teacher NOT found!\n";
}

echo "\nDEBUG COMPLETE\n";
?>
