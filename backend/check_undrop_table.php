<?php
require 'config/db.php';
$stmt = $pdo->query('DESCRIBE philcst_undrop_records');
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo $c['Field'] . ' (' . $c['Type'] . ")\n";
}
