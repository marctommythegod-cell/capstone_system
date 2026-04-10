<?php
require 'config/db.php';

echo "Checking recently dropped cards...\n\n";

$stmt = $pdo->query('
    SELECT ccd.id, s.name as student_name, s.email as student_email, ccd.subject_no, ccd.subject_name, u.name as teacher_name, ccd.status, ccd.drop_date
    FROM class_card_drops ccd
    JOIN students s ON ccd.student_id = s.id
    JOIN users u ON ccd.teacher_id = u.id
    ORDER BY ccd.id DESC
    LIMIT 5
');

$drops = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Recent drops:\n";
echo str_repeat("-", 100) . "\n";
foreach ($drops as $drop) {
    echo "ID: " . $drop['id'] . "\n";
    echo "  Student: " . $drop['student_name'] . " (" . $drop['student_email'] . ")\n";
    echo "  Subject: " . $drop['subject_no'] . " - " . $drop['subject_name'] . "\n";
    echo "  Teacher: " . $drop['teacher_name'] . "\n";
    echo "  Status: " . $drop['status'] . "\n";
    echo "  Drop Date: " . $drop['drop_date'] . "\n";
    echo "\n";
}

?>
