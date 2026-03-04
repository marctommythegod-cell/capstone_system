<?php
// includes/logout.php - Logout Handler

session_start();
session_destroy();
header('Location: /CLASS_CARD_DROPPING_SYSTEM/index.php');
exit;
?>
