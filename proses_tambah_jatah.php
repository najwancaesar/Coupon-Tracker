<?php
session_start();
require 'koneksi.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['id'];
    
    // Menangkap data dari form POST
    $tanggal_input = $_POST['tanggal_input'];
    $jumlah_kupon = (int)$_POST['jumlah_kupon']; // Cast ke integer untuk keamanan
    $tanggal_expired = $_POST['tanggal_expired'];

    // Mendapatkan tanggal hari ini
    $hari_ini = date('Y-m-d');

    // Validasi 0: Jumlah kupon harus dalam rentang logis (1-31)
    if ($jumlah_kupon < 1 || $jumlah_kupon > 31) {
        $_SESSION['error'] = 'Jumlah kupon tidak logis! Jatah maksimal per bulan adalah 31 kupon.';
        $_SESSION['pesan'] = 'Jumlah kupon tidak logis! Jatah maksimal per bulan adalah 31 kupon.';
        $_SESSION['tipe_pesan'] = 'danger';

        header("Location: dashboard.php");
        exit();
    }

    // Validasi 1: Tanggal input tidak boleh melebihi tanggal hari ini
    if ($tanggal_input > $hari_ini) {
        $_SESSION['error'] = 'Tanggal input tidak boleh lebih dari hari ini!';
        
        // Backward compatibility variabel pesan
        $_SESSION['pesan'] = 'Tanggal input tidak boleh lebih dari hari ini!'; 
        $_SESSION['tipe_pesan'] = 'danger';
        
        header("Location: dashboard.php");
        exit();
    }

    // Validasi 2: Tanggal kedaluwarsa tidak boleh lebih kecil/sebelum tanggal input
    if ($tanggal_expired < $tanggal_input) {
        $_SESSION['error'] = 'Tanggal kedaluwarsa tidak boleh lebih kecil dari tanggal input!';
        
        // Backward compatibility variabel pesan
        $_SESSION['pesan'] = 'Tanggal kedaluwarsa tidak boleh lebih kecil dari tanggal input!'; 
        $_SESSION['tipe_pesan'] = 'danger';
        
        header("Location: dashboard.php");
        exit();
    }

    // Jika semua validasi lolos, lakukan eksekusi INSERT
    $query = "INSERT INTO pemasukan_kupon (user_id, tanggal_input, jumlah_kupon, sisa_kupon, tanggal_expired) VALUES (?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);
    
    if ($stmt) {
        // sisa_kupon diset sama dengan jumlah_kupon saat awal diinput (isiis)
        $stmt->bind_param("isiis", $user_id, $tanggal_input, $jumlah_kupon, $jumlah_kupon, $tanggal_expired);
        
        if ($stmt->execute()) {
            $pesan_sukses = "Berhasil menambahkan $jumlah_kupon kupon. Kupon berlaku hingga " . date('d M Y', strtotime($tanggal_expired)) . ".";
            $_SESSION['sukses'] = $pesan_sukses;
            
            // Backward compatibility
            $_SESSION['pesan'] = $pesan_sukses;
            $_SESSION['tipe_pesan'] = "success";
        } else {
            $_SESSION['error'] = "Gagal menyimpan data pemasukan kupon: " . $stmt->error;
            $_SESSION['pesan'] = "Gagal menyimpan data pemasukan kupon: " . $stmt->error;
            $_SESSION['tipe_pesan'] = "danger";
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Terjadi kesalahan koneksi saat memproses data.";
        $_SESSION['pesan'] = "Terjadi kesalahan koneksi saat memproses data.";
        $_SESSION['tipe_pesan'] = "danger";
    }

    // Redirect kembali ke dashboard setelah proses selesai
    header("Location: dashboard.php");
    exit();
} else {
    // Jika diakses tidak melalui metode POST
    header("Location: dashboard.php");
    exit();
}
?>
