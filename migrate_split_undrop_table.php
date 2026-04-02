<?php
// Migration: Split class_card_drops into separate drop and undrop tables

require_once 'config/db.php';

try {
    // Create new philcst_class_undrop table
    $createUndropTable = "
    CREATE TABLE IF NOT EXISTS `philcst_class_undrop` (
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
        INDEX idx_drop_id (drop_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($createUndropTable);
    echo "✓ Created philcst_class_undrop table<br>";

    // Migrate existing undrop data
    $migrateData = "
    INSERT INTO `philcst_class_undrop` 
    (drop_id, student_id, subject_no, subject_name, teacher_id, retrieve_date, undrop_remarks, undrop_certificates)
    SELECT 
        id, student_id, subject_no, subject_name, teacher_id, 
        retrieve_date, undrop_remarks, undrop_certificates
    FROM `class_card_drops`
    WHERE status = 'Undropped' AND retrieve_date IS NOT NULL AND retrieve_date != '0000-00-00 00:00:00'
    ";

    $pdo->exec($migrateData);
    $count = $pdo->query("SELECT COUNT(*) as cnt FROM philcst_class_undrop")->fetch()['cnt'];
    echo "✓ Migrated $count undrop records to philcst_class_undrop<br>";

    // Add drop_id column to class_card_drops if not exists (for reference)
    $checkColumn = $pdo->query("SHOW COLUMNS FROM `class_card_drops` LIKE 'drop_id'")->fetch();
    if (!$checkColumn) {
        $pdo->exec("ALTER TABLE `class_card_drops` ADD COLUMN `drop_id` INT PRIMARY KEY AUTO_INCREMENT FIRST");
        echo "✓ Added drop_id to class_card_drops<br>";
    }

    echo "<br><strong>Migration completed successfully!</strong><br>";
    echo "Now you have:<br>";
    echo "- <strong>philcst_class_drop</strong>: Main drop records<br>";
    echo "- <strong>philcst_class_undrop</strong>: Separate undrop records<br>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
