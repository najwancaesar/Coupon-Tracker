<?php
session_start();
require 'koneksi.php';

// Cek session untuk memastikan user sudah login
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['id'];

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Gunakan prepared statement agar aman
    $query = "UPDATE riwayat_kupon SET status = 'Selesai' WHERE id = ? AND user_id = ?";
    $stmt = $mysqli->prepare($query);
    
    if ($stmt) {
        $stmt->bind_param("ii", $id, $user_id);
        if ($stmt->execute()) {
            $_SESSION['sukses'] = "Status pemakaian kupon berhasil diubah menjadi Selesai!";
            $_SESSION['pesan'] = "Status pemakaian kupon berhasil diubah menjadi Selesai!";
            $_SESSION['tipe_pesan'] = 'success';
        } else {
            $_SESSION['error'] = "Gagal memperbarui status: " . $stmt->error;
            $_SESSION['pesan'] = "Gagal memperbarui status.";
            $_SESSION['tipe_pesan'] = 'danger';
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Error pada kueri database.";
        $_SESSION['pesan'] = "Error pada kueri database.";
        $_SESSION['tipe_pesan'] = 'danger';
    }
} else {
    $_SESSION['error'] = "ID tidak ditemukan.";
}

header("Location: dashboard.php");
exit();
?>
