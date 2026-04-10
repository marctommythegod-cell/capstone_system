<?php
require 'config/db.php';

echo "Checking philcst_undrop_records structure...\n";
$stmt = $pdo->query('SHOW CREATE TABLE philcst_undrop_records');
$table = $stmt->fetch(PDO::FETCH_ASSOC);
echo $table['Create Table'] . "\n";
