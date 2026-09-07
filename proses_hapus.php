<?php
session_start();
require 'koneksi.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['id'];

// Tangkap variabel dari URL (GET)
$jenis = isset($_GET['jenis']) ? $_GET['jenis'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    if ($jenis == 'pemakaian') {
        // 1. Ambil jumlah_pakai dari riwayat yang akan dihapus
        $query_get = $mysqli->query("SELECT jumlah_pakai FROM riwayat_kupon WHERE id = $id AND user_id = $user_id");
        
        if ($query_get && $query_get->num_rows > 0) {
            $row = $query_get->fetch_assoc();
            $jumlah_refund = (int)$row['jumlah_pakai'];
            
            // 2. Hapus data dari riwayat_kupon
            $query_delete = $mysqli->query("DELETE FROM riwayat_kupon WHERE id = $id AND user_id = $user_id");
            
            if ($query_delete) {
                // 3. Logika Refund: Kembalikan saldo kupon ke batch yang masa aktifnya paling lama
                // Pastikan hanya mengembalikan ke kupon yang belum expired
                $query_refund = "UPDATE pemasukan_kupon 
                                 SET sisa_kupon = sisa_kupon + $jumlah_refund 
                                 WHERE user_id = $user_id AND tanggal_expired >= CURRENT_DATE() 
                                 ORDER BY tanggal_expired DESC 
                                 LIMIT 1";
                $mysqli->query($query_refund);
                
                $_SESSION['sukses'] = 'Data riwayat pemakaian berhasil dihapus dan saldo kupon telah dikembalikan!';
            } else {
                $_SESSION['error'] = 'Gagal menghapus data pemakaian.';
            }
        } else {
            $_SESSION['error'] = 'Data riwayat tidak ditemukan atau Anda tidak memiliki hak akses.';
        }
        
    } elseif ($jenis == 'pemasukan') {
        // Hapus data langsung dari tabel pemasukan_kupon
        $query_delete = $mysqli->query("DELETE FROM pemasukan_kupon WHERE id = $id AND user_id = $user_id");
        
        if ($query_delete) {
            $_SESSION['sukses'] = 'Data pemasukan jatah kupon berhasil dihapus!';
        } else {
            $_SESSION['error'] = 'Gagal menghapus data pemasukan kupon.';
        }
    } else {
        $_SESSION['error'] = 'Jenis parameter penghapusan tidak valid.';
    }
} else {
    $_SESSION['error'] = 'ID data tidak valid.';
}

// Kembali ke halaman dashboard
header("Location: dashboard.php");
exit();
?>
