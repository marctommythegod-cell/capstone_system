<?php
require 'config/db.php';

echo "Checking the drop records and undrop attempts...\n\n";

// Check drop ID 7 and 9
echo "Drop ID 7:\n";
$stmt = $pdo->prepare('SELECT id, status, student_id, teacher_id FROM class_card_drops WHERE id = 7');
$stmt->execute();
$d7 = $stmt->fetch(PDO::FETCH_ASSOC);
var_dump($d7);

echo "\nDrop ID 9:\n";
$stmt = $pdo->prepare('SELECT id, status, student_id, teacher_id FROM class_card_drops WHERE id = 9');
$stmt->execute();
$d9 = $stmt->fetch(PDO::FETCH_ASSOC);
var_dump($d9);

// Check if drop 9 already has undrop record
echo "\n\nUndrop records:\n";
$stmt = $pdo->prepare('SELECT * FROM philcst_undrop_records WHERE drop_id = 9');
$stmt->execute();
$undrop = $stmt->fetch(PDO::FETCH_ASSOC);
if ($undrop) {
    echo "Drop 9 already has undrop record!\n";
    var_dump($undrop);
} else {
    echo "Drop 9 has NO undrop record yet\n";
}

// Check student 8 in both tables
echo "\n\nVerifying foreign keys...\n";
$stmt = $pdo->query('SELECT id FROM students WHERE id = 8');
if ($stmt->fetch()) {
    echo "✓ students.id = 8 exists\n";
} else {
    echo "✗ students.id = 8 DOES NOT exist!\n";
}

$stmt = $pdo->query('SELECT id FROM users WHERE id = 4');
if ($stmt->fetch()) {
    echo "✓ users.id = 4 exists\n";
} else {
    echo "✗ users.id = 4 DOES NOT exist!\n";
}

?>
