<?php
require 'session_config.php';

// Cek apakah user sudah login dengan mengecek session 'id' atau 'user_id'
if (isset($_SESSION['id']) || isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
} else {
    // Jika belum login, redirect ke login
    header("Location: login.php");
    exit();
}
?>
