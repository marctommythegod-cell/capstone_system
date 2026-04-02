<?php
// Migration: Create separate undrop table (keeping existing class_card_drops table intact)

require_once 'config/db.php';

try {
    // Create the undrop table
    $createUndropTable = "
    CREATE TABLE IF NOT EXISTS `philcst_undrop_records` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `drop_id` INT NOT NULL COMMENT 'Reference to class_card_drops',
        `student_id` INT NOT NULL,
        `subject_no` VARCHAR(50) NOT NULL,
        `subject_name` VARCHAR(255) NOT NULL,
        `teacher_id` INT NOT NULL,
        `retrieve_date` DATETIME NOT NULL COMMENT 'When class card was retrieved',
        `undrop_remarks` TEXT COMMENT 'Admin remarks for undrop',
        `undrop_certificates` VARCHAR(255) COMMENT 'Certificate information',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`drop_id`) REFERENCES `class_card_drops`(`id`) ON DELETE CASCADE,
        INDEX idx_student_id (student_id),
        INDEX idx_subject_no (subject_no),
        INDEX idx_drop_id (drop_id),
        UNIQUE KEY unique_drop_record (drop_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($createUndropTable);
    echo "✓ Created philcst_undrop_records table<br>";

    // Migrate existing undrop data
    $migrateData = "
    INSERT IGNORE INTO `philcst_undrop_records` 
    (drop_id, student_id, subject_no, subject_name, teacher_id, retrieve_date, undrop_remarks, undrop_certificates)
    SELECT 
        id, student_id, subject_no, subject_name, teacher_id, 
        retrieve_date, undrop_remarks, undrop_certificates
    FROM `class_card_drops`
    WHERE status = 'Undropped' AND retrieve_date IS NOT NULL AND retrieve_date != '0000-00-00 00:00:00'
    ";

    $pdo->exec($migrateData);
    $count = $pdo->query("SELECT COUNT(*) as cnt FROM philcst_undrop_records")->fetch()['cnt'];
    echo "✓ Migrated $count undrop records to philcst_undrop_records<br>";

    echo "<br><strong>✓ Migration completed successfully!</strong><br><br>";
    echo "<strong>Database Structure:</strong><br>";
    echo "📊 <strong>class_card_drops</strong> - Main class card drop records (unchanged)<br>";
    echo "   Keeps all existing data and functionality<br><br>";
    echo "📋 <strong>philcst_undrop_records</strong> - Separate undrop records<br>";
    echo "   Columns: id, drop_id (FK), student_id, subject_no, subject_name, teacher_id, retrieve_date, undrop_remarks, undrop_certificates, created_at, updated_at<br>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    echo "<br>Trace: " . $e->getTraceAsString();
}
?>

