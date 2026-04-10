<?php
require 'config/db.php';

echo "Testing insert directly...\n\n";

try {
    // Prepare exact insert
    $stmt = $pdo->prepare('
        INSERT INTO philcst_undrop_records 
        (drop_id, student_id, subject_no, subject_name, teacher_id, retrieve_date, undrop_remarks, undrop_certificates)
        VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)
    ');
    
    echo "Executing with values:\n";
    echo "  drop_id: 9\n";
    echo "  student_id: 8\n";
    echo "  subject_no: TEST102\n";
    echo "  subject_name: Test Subject 2\n";
    echo "  teacher_id: 4\n";
    echo "  undrop_remarks: Test\n";
    echo "  undrop_certificates: N/A\n";
    
    $result = $stmt->execute([9, 8, 'TEST102', 'Test Subject 2', 4, 'Test', 'N/A']);
    
    if ($result) {
        echo "\n✓ Insert successful!\n";
    }
} catch (PDOException $e) {
    echo "\n✗ Insert failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
}

?>
