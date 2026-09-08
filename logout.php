<?php
require 'session_config.php';

// Hapus semua variabel session
$_SESSION = [];

// Hapus session cookie dari browser jika ada
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hancurkan session secara menyeluruh
session_destroy();

// Redirect ke halaman login
header('Location: login.php');
exit;
?>
