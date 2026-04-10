<?php
require 'config/db.php';

echo "Checking class_card_drops for orphaned references...\n\n";

// Check drops with missing students
$stmt = $pdo->prepare('SELECT ccd.id, ccd.student_id, ccd.subject_no, ccd.status FROM class_card_drops ccd WHERE ccd.student_id NOT IN (SELECT id FROM students)');
$stmt->execute();
$orphaned_students = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($orphaned_students) > 0) {
    echo "FOUND " . count($orphaned_students) . " DROP RECORDS WITH MISSING STUDENTS:\n";
    foreach ($orphaned_students as $drop) {
        echo "  - Drop ID: " . $drop['id'] . ", Student ID: " . $drop['student_id'] . ", Subject: " . $drop['subject_no'] . ", Status: " . $drop['status'] . "\n";
    }
} else {
    echo "OK: No drop records with missing students\n";
}

echo "\n";

// Check drops with missing teachers
$stmt = $pdo->prepare('SELECT ccd.id, ccd.teacher_id, ccd.subject_no, ccd.status FROM class_card_drops ccd WHERE ccd.teacher_id NOT IN (SELECT id FROM users)');
$stmt->execute();
$orphaned_teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($orphaned_teachers) > 0) {
    echo "FOUND " . count($orphaned_teachers) . " DROP RECORDS WITH MISSING TEACHERS:\n";
    foreach ($orphaned_teachers as $drop) {
        echo "  - Drop ID: " . $drop['id'] . ", Teacher ID: " . $drop['teacher_id'] . ", Subject: " . $drop['subject_no'] . ", Status: " . $drop['status'] . "\n";
    }
} else {
    echo "OK: No drop records with missing teachers\n";
}
?>
