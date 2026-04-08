<?php
require_once 'config/db.php';

try {
    $sql = "ALTER TABLE class_card_drops ADD COLUMN undrop_certificates VARCHAR(255) NULL AFTER undrop_remarks;";
    $pdo->exec($sql);
    echo "Column added successfully!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
