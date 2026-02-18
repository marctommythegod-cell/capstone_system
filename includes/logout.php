<?php
// includes/logout.php - Logout Handler

session_start();
session_destroy();
header('Location: /SYSTEM/index.php');
exit;
?>
