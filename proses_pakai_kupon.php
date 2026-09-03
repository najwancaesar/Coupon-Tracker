<?php
session_start();
require 'koneksi.php';

// Cek session untuk memastikan user sudah login
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['id'];
    $tanggal_pakai = $_POST['tanggal_pakai'];
    $jumlah_pakai = (int)$_POST['jumlah_pakai'];

    $hari_ini = date('Y-m-d');

    // Validasi Tanggal: Tanggal pemakaian tidak boleh lebih dari hari ini
    if ($tanggal_pakai > $hari_ini) {
        $_SESSION['error'] = 'Tanggal pemakaian tidak boleh lebih dari hari ini!';
        
        // Backward compatibility
        $_SESSION['pesan'] = 'Tanggal pemakaian tidak boleh lebih dari hari ini!';
        $_SESSION['tipe_pesan'] = 'danger';
        
        header("Location: dashboard.php");
        exit();
    }

    // 1. Logika Validasi Saldo: Hitung Sisa Kupon
    // Hitung Total Kupon Aktif (Pemasukan yang belum expired)
    $total_aktif = 0;
    $query_aktif = "SELECT SUM(jumlah_kupon) as total FROM pemasukan_kupon WHERE user_id = ? AND tanggal_expired >= CURRENT_DATE()";
    $stmt_aktif = $mysqli->prepare($query_aktif);
    if ($stmt_aktif) {
        $stmt_aktif->bind_param("i", $user_id);
        $stmt_aktif->execute();
        $res_aktif = $stmt_aktif->get_result();
        if ($row = $res_aktif->fetch_assoc()) {
            $total_aktif = $row['total'] ? $row['total'] : 0;
        }
        $stmt_aktif->close();
    }

    // Hitung Total Kupon Terpakai
    $total_terpakai = 0;
    $query_pakai = "SELECT SUM(jumlah_pakai) as total FROM riwayat_kupon WHERE user_id = ?";
    $stmt_pakai = $mysqli->prepare($query_pakai);
    if ($stmt_pakai) {
        $stmt_pakai->bind_param("i", $user_id);
        $stmt_pakai->execute();
        $res_pakai = $stmt_pakai->get_result();
        if ($row = $res_pakai->fetch_assoc()) {
            $total_terpakai = $row['total'] ? $row['total'] : 0;
        }
        $stmt_pakai->close();
    }

    // Sisa Kupon
    $sisa_kupon = $total_aktif - $total_terpakai;

    // 2. Cek menggunakan IF
    if ($sisa_kupon >= $jumlah_pakai) {
        // Jika CUKUP: Lakukan query INSERT
        $keterangan = "Makan di kantin";
        $query_insert = "INSERT INTO riwayat_kupon (user_id, tanggal_pakai, jumlah_pakai, keterangan) VALUES (?, ?, ?, ?)";
        $stmt_insert = $mysqli->prepare($query_insert);
        
        if ($stmt_insert) {
            $stmt_insert->bind_param("isis", $user_id, $tanggal_pakai, $jumlah_pakai, $keterangan);
            if ($stmt_insert->execute()) {
                $_SESSION['sukses'] = 'Kupon berhasil dipakai';
                
                // Tambahan fallback agar tetap berfungsi dengan UI dashboard yang sudah ada
                $_SESSION['pesan'] = 'Kupon berhasil dipakai';
                $_SESSION['tipe_pesan'] = 'success';
            } else {
                $_SESSION['error'] = 'Terjadi kesalahan sistem';
            }
            $stmt_insert->close();
        }
    } else {
        // Jika KURANG/TIDAK CUKUP: Set pesan error tanpa insert
        $_SESSION['error'] = 'Sisa kupon tidak mencukupi!';
        
        // Tambahan fallback
        $_SESSION['pesan'] = 'Sisa kupon tidak mencukupi!';
        $_SESSION['tipe_pesan'] = 'danger';
    }

    header("Location: dashboard.php");
    exit();
} else {
    header("Location: dashboard.php");
    exit();
}
?>
