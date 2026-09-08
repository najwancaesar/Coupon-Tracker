<?php
require 'session_config.php';
require 'koneksi.php';
require 'csrf.php';

// Cek session untuk memastikan user sudah login
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Verifikasi CSRF Token ---
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Permintaan tidak valid (token keamanan salah). Silakan coba lagi.';
        header("Location: dashboard.php");
        exit();
    }

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($id > 0) {
        // Gunakan prepared statement dengan filter user_id agar user hanya bisa selesaikan miliknya sendiri
        $query = "UPDATE riwayat_kupon SET status = 'Selesai' WHERE id = ? AND user_id = ?";
        $stmt  = $mysqli->prepare($query);

        if ($stmt) {
            $stmt->bind_param("ii", $id, $user_id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $_SESSION['sukses'] = "Status pemakaian kupon berhasil diubah menjadi Selesai!";
                $_SESSION['pesan']  = "Status pemakaian kupon berhasil diubah menjadi Selesai!";
                $_SESSION['tipe_pesan'] = 'success';
            } else {
                $_SESSION['error'] = "Gagal memperbarui status atau data tidak ditemukan.";
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
        $_SESSION['error'] = "ID tidak valid.";
    }
} else {
    // Tolak akses yang bukan POST
    $_SESSION['error'] = "Akses tidak diizinkan.";
}

header("Location: dashboard.php");
exit();
?>
