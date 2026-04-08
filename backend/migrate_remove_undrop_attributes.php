<?php
// Migration: Remove undrop attributes from class_card_drops table

require_once 'config/db.php';

try {
    // Remove undrop-related columns from class_card_drops
    $columnsToRemove = ['retrieve_date', 'undrop_remarks', 'undrop_certificates'];
    
    foreach ($columnsToRemove as $column) {
        // Check if column exists before dropping
        $checkColumn = $pdo->query("SHOW COLUMNS FROM `class_card_drops` LIKE '$column'")->fetch();
        
        if ($checkColumn) {
            $pdo->exec("ALTER TABLE `class_card_drops` DROP COLUMN `$column`");
            echo "✓ Removed column `$column` from class_card_drops<br>";
        } else {
            echo "⚠ Column `$column` does not exist (skipped)<br>";
        }
    }

    echo "<br><strong>✓ Migration completed successfully!</strong><br><br>";
    echo "<strong>Updated Database Structure:</strong><br>";
    echo "📊 <strong>class_card_drops</strong> - Drop records only<br>";
    echo "   Columns: id, teacher_id, student_id, subject_no, subject_name, remarks, status, drop_date, deadline, drop_month, drop_year, admin_remarks, cancelled_date, cancellation_reason, approved_by, approved_date, created_at, updated_at<br><br>";
    echo "📋 <strong>philcst_undrop_records</strong> - Undrop records only<br>";
    echo "   Columns: id, drop_id, student_id, subject_no, subject_name, teacher_id, retrieve_date, undrop_remarks, undrop_certificates, created_at, updated_at<br>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    echo "<br>Trace: " . $e->getTraceAsString();
}
?>
