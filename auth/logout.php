<?php
// auth/logout.php
session_start();

// Destroy semua data session
$_SESSION = array();

// Hapus session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy session
session_destroy();

// Redirect ke landing page dengan pesan
header("Location: ../index.php?logout=success");
exit();
?>