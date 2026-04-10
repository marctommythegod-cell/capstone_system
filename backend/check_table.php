<?php
/**
 * Check table structure
 */

require 'config/db.php';

echo "Checking class_card_drops table structure...\n\n";

$stmt = $pdo->query("DESCRIBE class_card_drops");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($columns as $col) {
    echo $col['Field'] . " (" . $col['Type'] . ")" . 
         ($col['Null'] === 'NO' ? ' NOT NULL' : ' NULL') .
         ($col['Key'] ? ' [' . $col['Key'] . ']' : '') .
         ($col['Default'] ? ' DEFAULT ' . $col['Default'] : '') . "\n";
}

?>
