<?php
require 'config/db.php';

echo "Checking what student_id is in drop records...\n\n";

$stmt = $pdo->prepare('
    SELECT ccd.student_id as drop_student_id, s.id as student_table_id, s.student_id as student_number
    FROM class_card_drops ccd
    JOIN students s ON ccd.student_id = s.id
    LIMIT 3
');
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $r) {
    echo "Drop student_id: " . $r['drop_student_id'] . 
         " → Student table id: " . $r['student_table_id'] . 
         " (student_id in table: " . $r['student_number'] . ")\n";
}

echo "\nSo ccd.student_id = s.id, which is the PK in students table.\n";
echo "This should work for the foreign key...\n";

// Check if student 8 exists
echo "\n\nChecking student 8...\n";
$stmt = $pdo->prepare('SELECT id, student_id, name FROM students WHERE id = 8');
$stmt->execute();
$s = $stmt->fetch(PDO::FETCH_ASSOC);
if ($s) {
    echo "✓ Student found: id=" . $s['id'] . ", student_id=" . $s['student_id'] . ", name=" . $s['name'] . "\n";
} else {
    echo "✗ Student 8 not found!\n";
}

// Check what's in undrop records already
echo "\n\nChecking existing undrop records...\n";
$stmt = $pdo->query('SELECT id, drop_id, student_id, teacher_id FROM philcst_undrop_records');
$undrop_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Total undrop records: " . count($undrop_records) . "\n";
if ($undrop_records) {
    foreach ($undrop_records as $u) {
        echo "  Undrop ID " . $u['id'] . ": drop_id=" . $u['drop_id'] . ", student_id=" . $u['student_id'] . ", teacher_id=" . $u['teacher_id'] . "\n";
    }
}

?>
