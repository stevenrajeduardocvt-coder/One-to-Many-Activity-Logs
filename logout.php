<?php
/**
 * logout.php — Destroys the current session and redirects to login.
 */
require_once 'includes/auth.php';
session_destroy();
header("Location: /student_grades/login.php");
exit();
?>
