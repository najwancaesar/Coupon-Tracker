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
    $keterangan = $_POST['keterangan'];
    
    $hari_ini = date('Y-m-d');

    // Validasi Tanggal: Tanggal pemakaian tidak boleh lebih dari hari ini
    if ($tanggal_pakai > $hari_ini) {
        $_SESSION['error'] = 'Tanggal pemakaian tidak boleh lebih dari hari ini!';
        $_SESSION['pesan'] = 'Tanggal pemakaian tidak boleh lebih dari hari ini!';
        $_SESSION['tipe_pesan'] = 'danger';
        header("Location: dashboard.php");
        exit();
    }

    // 1. Cek Ketersediaan Sisa Kupon yang Belum Expired
    $total_sisa_kupon = 0;
    $query_saldo = "SELECT SUM(sisa_kupon) as total FROM pemasukan_kupon WHERE user_id = ? AND tanggal_expired >= CURRENT_DATE()";
    $stmt_saldo = $mysqli->prepare($query_saldo);
    if ($stmt_saldo) {
        $stmt_saldo->bind_param("i", $user_id);
        $stmt_saldo->execute();
        $res_saldo = $stmt_saldo->get_result();
        if ($row = $res_saldo->fetch_assoc()) {
            $total_sisa_kupon = $row['total'] ? (int)$row['total'] : 0;
        }
        $stmt_saldo->close();
    }

    // Jika saldo tidak mencukupi, tolak
    if ($total_sisa_kupon < $jumlah_pakai) {
        $_SESSION['error'] = 'Sisa kupon tidak mencukupi!';
        $_SESSION['pesan'] = 'Sisa kupon tidak mencukupi!';
        $_SESSION['tipe_pesan'] = 'danger';
        header("Location: dashboard.php");
        exit();
    }

    // 2. Terapkan Sistem FIFO (First In First Out)
    // Tarik data pemasukan kupon yang saldonya masih ada (>0) dan belum expired
    // Diurutkan berdasarkan tanggal expired paling dekat / paling cepat hangus (ASC)
    $query_fifo = "SELECT id, sisa_kupon FROM pemasukan_kupon WHERE user_id = ? AND tanggal_expired >= CURRENT_DATE() AND sisa_kupon > 0 ORDER BY tanggal_expired ASC";
    $stmt_fifo = $mysqli->prepare($query_fifo);
    
    if ($stmt_fifo) {
        $stmt_fifo->bind_param("i", $user_id);
        $stmt_fifo->execute();
        $result = $stmt_fifo->get_result();
        
        $sisa_pakai = $jumlah_pakai; // Variabel pelacak jumlah yang belum dipotong
        
        // Memulai Transaksi SQL agar aman. Jika di tengah proses gagal, data tidak jadi terpotong sebagian.
        $mysqli->begin_transaction();
        
        try {
            // Melakukan looping (while) per baris data kupon
            while ($row = $result->fetch_assoc()) {
                // Jika kebutuhan pemakaian sudah terpenuhi (0), hentikan loop (break)
                if ($sisa_pakai <= 0) {
                    break;
                }
                
                $id_pemasukan = $row['id'];
                $stok_baris_ini = $row['sisa_kupon'];
                
                // Menentukan berapa yang akan dipotong di baris ini
                if ($stok_baris_ini >= $sisa_pakai) {
                    // Baris ini bisa mencukupi semua sisa kebutuhan pemakaian
                    $stok_baru = $stok_baris_ini - $sisa_pakai;
                    $sisa_pakai = 0; // Kebutuhan terpenuhi total
                } else {
                    // Baris ini tidak cukup, maka kuras habis stok di baris ini
                    $stok_baru = 0;
                    $sisa_pakai -= $stok_baris_ini; // Masih ada sisa kebutuhan untuk loop berikutnya
                }
                
                // Eksekusi UPDATE per baris (memperbarui sisa_kupon)
                $q_update = "UPDATE pemasukan_kupon SET sisa_kupon = ? WHERE id = ?";
                $stmt_upd = $mysqli->prepare($q_update);
                $stmt_upd->bind_param("ii", $stok_baru, $id_pemasukan);
                $stmt_upd->execute();
                $stmt_upd->close();
            }
            
            // 3. Catat Riwayat Pemakaian
            $query_insert = "INSERT INTO riwayat_kupon (user_id, tanggal_pakai, jumlah_pakai, keterangan) VALUES (?, ?, ?, ?)";
            $stmt_insert = $mysqli->prepare($query_insert);
            $stmt_insert->bind_param("isis", $user_id, $tanggal_pakai, $jumlah_pakai, $keterangan);
            $stmt_insert->execute();
            $stmt_insert->close();
            
            // Commit (Simpan Permanen) transaksi jika semua langkah di atas sukses tanpa error
            $mysqli->commit();
            
            $_SESSION['sukses'] = "Sebanyak $jumlah_pakai kupon berhasil terpakai!";
            $_SESSION['pesan'] = "Sebanyak $jumlah_pakai kupon berhasil terpakai!";
            $_SESSION['tipe_pesan'] = 'success';
            
        } catch (Exception $e) {
            // Rollback (Batalkan Semua) jika terjadi error di tengah proses pemotongan
            $mysqli->rollback();
            $_SESSION['error'] = 'Terjadi kesalahan sistem saat memproses kupon FIFO.';
            $_SESSION['pesan'] = 'Terjadi kesalahan sistem saat memproses kupon FIFO.';
            $_SESSION['tipe_pesan'] = 'danger';
        }
        $stmt_fifo->close();
    } else {
        $_SESSION['error'] = 'Kesalahan eksekusi kueri FIFO.';
    }

    header("Location: dashboard.php");
    exit();
} else {
    header("Location: dashboard.php");
    exit();
}
?>
