<?php
require 'config/db.php';

echo "Checking class_card_drops columns...\n\n";

$stmt = $pdo->query('DESCRIBE class_card_drops');
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

$found_subject_no = false;
foreach ($columns as $col) {
    echo $col['Field'] . " (" . $col['Type'] . ")\n";
    if ($col['Field'] === 'subject_no') {
        $found_subject_no = true;
    }
}

echo "\n\nsubject_no column exists: " . ($found_subject_no ? "YES" : "NO") . "\n";

// Now test inserting with the columns from api.php
echo "\n\nTesting INSERT statement from api.php...\n";

try {
    $stmt = $pdo->prepare('
        INSERT INTO class_card_drops 
        (teacher_id, student_id, subject_no, subject_name, remarks, status, drop_date, deadline, drop_month, drop_year, approved_by, approved_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ');
    
    $stmt->execute([
        4, 8, 'TEST999', 'Test Subject', 'Walk-in test', 'Dropped',
        date('Y-m-d H:i:s'), date('Y-m-d 23:59:59'), 'April 2026', 2026, 1
    ]);
    
    echo "✓ Insert successful!\n";
} catch (PDOException $e) {
    echo "✗ Insert failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
}

?>
