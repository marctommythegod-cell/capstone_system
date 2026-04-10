<?php
require 'config/db.php';

echo "Checking subjects table structure...\n\n";

$stmt = $pdo->query('DESCRIBE subjects');
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($columns as $col) {
    echo $col['Field'] . " (" . $col['Type'] . ")\n";
}

?>
