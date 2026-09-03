<?php
session_start();

// Cek apakah user sudah login dengan mengecek session 'id'
if (isset($_SESSION['id'])) {
    // Jika sudah login, redirect ke dashboard
    header("Location: dashboard.php");
    exit();
} else {
    // Jika belum login, redirect ke login
    header("Location: login.php");
    exit();
}
?>
