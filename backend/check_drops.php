<?php
/**
 * Check all drop records in database
 */

require 'config/db.php';

echo "Checking database for drop records...\n\n";

// Check all class_card_drops
$stmt = $pdo->query('
    SELECT id, student_id, teacher_id, subject_no, status
    FROM class_card_drops
    ORDER BY id DESC
    LIMIT 20
');
$drops = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total drops found: " . count($drops) . "\n";
if ($drops) {
    echo "\nFirst 20 records:\n";
    echo str_pad("ID", 5) . str_pad("Student", 10) . str_pad("Teacher", 10) . str_pad("Subject", 15) . "Status\n";
    echo str_repeat("-", 55) . "\n";
    
    foreach ($drops as $drop) {
        echo str_pad($drop['id'], 5) . 
             str_pad($drop['student_id'], 10) . 
             str_pad($drop['teacher_id'], 10) . 
             str_pad($drop['subject_no'], 15) . 
             $drop['status'] . "\n";
    }
}

// Check student count
$stmt = $pdo->query('SELECT COUNT(*) as count FROM students');
$student_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
echo "\n\nTotal students: " . $student_count . "\n";

// Check user/teacher count
$stmt = $pdo->query('SELECT COUNT(*) as count FROM users');
$user_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
echo "Total users/teachers: " . $user_count . "\n";

// Check undrop records
$stmt = $pdo->query('SELECT COUNT(*) as count FROM philcst_undrop_records');
$undrop_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
echo "Total undrop records: " . $undrop_count . "\n";

?>
