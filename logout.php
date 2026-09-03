<?php
session_start();

// Hapus semua variabel session
session_unset();

// Hancurkan session secara menyeluruh
session_destroy();

// Redirect ke halaman login
header('Location: login.php');
exit;
?>
