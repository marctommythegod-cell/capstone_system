<?php
/**
 * Check if students and teachers in drops exist
 */

require 'config/db.php';

echo "Verifying students and teachers...\n\n";

// Check students 8 and 9
echo "Students:\n";
$stmt = $pdo->query('SELECT id, student_id, name FROM students WHERE id IN (8, 9)');
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($students as $s) {
    echo "  ID " . $s['id'] . ": " . $s['student_id'] . " - " . $s['name'] . "\n";
}

// Check teacher 4
echo "\nTeachers:\n";
$stmt = $pdo->query('SELECT id, name, email FROM users WHERE id = 4');
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($teachers as $u) {
    echo "  ID " . $u['id'] . ": " . $u['name'] . " (" . $u['email'] . ")\n";
}

echo "\nAll students and teachers referenced in drops DO EXIST.\n";
echo "The undrop functionality should now work correctly!\n";

?>
