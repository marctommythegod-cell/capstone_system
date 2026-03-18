<?php
// Add columns for deadline tracking to class_card_drops table
require_once 'config/db.php';

try {
    // Check if columns already exist
    $checkStmt = $pdo->query("SHOW COLUMNS FROM class_card_drops LIKE 'deadline'");
    if ($checkStmt->rowCount() === 0) {
        $sql = "ALTER TABLE class_card_drops 
                ADD COLUMN deadline DATETIME NULL AFTER drop_date,
                ADD COLUMN cancelled_date DATETIME NULL AFTER retrieve_date,
                ADD COLUMN cancellation_reason VARCHAR(255) NULL AFTER cancelled_date;";
        $pdo->exec($sql);
        echo "Columns added successfully!";
    } else {
        echo "Columns already exist!";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
