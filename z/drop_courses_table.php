<?php
require_once __DIR__ . '/../config/db.php';

try {
    $pdo->exec("DROP TABLE IF EXISTS courses");
    echo "Courses table dropped successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
