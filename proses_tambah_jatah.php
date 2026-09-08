<?php
require 'session_config.php';
require 'koneksi.php';
require 'csrf.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Verifikasi CSRF Token ---
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Permintaan tidak valid (token keamanan salah). Silakan coba lagi.';
        header("Location: dashboard.php");
        exit();
    }

    $user_id          = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['id'];
    $tanggal_input    = $_POST['tanggal_input'] ?? '';
    $jumlah_kupon     = (int)($_POST['jumlah_kupon'] ?? 0);
    $tanggal_expired  = $_POST['tanggal_expired'] ?? '';

    // Validasi 0: Jumlah kupon harus dalam rentang logis (1-31)
    if ($jumlah_kupon < 1 || $jumlah_kupon > 31) {
        $_SESSION['error'] = 'Jumlah kupon tidak logis! Jatah maksimal per bulan adalah 31 kupon.';
        $_SESSION['pesan'] = 'Jumlah kupon tidak logis! Jatah maksimal per bulan adalah 31 kupon.';
        $_SESSION['tipe_pesan'] = 'danger';
        header("Location: dashboard.php");
        exit();
    }

    // Validasi 2: Tanggal kedaluwarsa tidak boleh lebih kecil/sebelum tanggal input
    if ($tanggal_expired < $tanggal_input) {
        $_SESSION['error'] = 'Tanggal kedaluwarsa tidak boleh lebih kecil dari tanggal input!';
        $_SESSION['pesan'] = 'Tanggal kedaluwarsa tidak boleh lebih kecil dari tanggal input!';
        $_SESSION['tipe_pesan'] = 'danger';
        header("Location: dashboard.php");
        exit();
    }

    // --- 1.6. Validasi duplikasi input jatah dalam bulan & tahun yang sama ---
    // CATATAN PENGEMBANG: Ini adalah validasi client-self-reported.
    // Batasan ini mencegah spam duplikasi, BUKAN sumber kebenaran resmi.
    // Jika sistem dihubungkan ke mesin RFID/HRD di masa depan, validasi ini
    // sebaiknya digantikan dengan sumber data resmi dari sistem tersebut.
    $bulan_input = date('m', strtotime($tanggal_input));
    $tahun_input = date('Y', strtotime($tanggal_input));

    $stmt_cek = $mysqli->prepare(
        "SELECT id FROM pemasukan_kupon 
         WHERE user_id = ? AND MONTH(tanggal_input) = ? AND YEAR(tanggal_input) = ?"
    );
    $stmt_cek->bind_param("iii", $user_id, $bulan_input, $tahun_input);
    $stmt_cek->execute();
    $stmt_cek->store_result();

    if ($stmt_cek->num_rows > 0) {
        $stmt_cek->close();
        $nama_bulan = strftime('%B %Y', strtotime($tanggal_input));
        $_SESSION['error'] = "Anda sudah pernah menginput jatah kupon untuk bulan ini. Setiap bulan hanya diperbolehkan satu kali input jatah.";
        $_SESSION['pesan'] = "Input jatah sudah ada untuk bulan ini.";
        $_SESSION['tipe_pesan'] = 'danger';
        header("Location: dashboard.php");
        exit();
    }
    $stmt_cek->close();

    // Jika semua validasi lolos, lakukan eksekusi INSERT
    $query = "INSERT INTO pemasukan_kupon (user_id, tanggal_input, jumlah_kupon, sisa_kupon, tanggal_expired) VALUES (?, ?, ?, ?, ?)";
    $stmt  = $mysqli->prepare($query);

    if ($stmt) {
        // sisa_kupon diset sama dengan jumlah_kupon saat awal diinput
        $stmt->bind_param("isiis", $user_id, $tanggal_input, $jumlah_kupon, $jumlah_kupon, $tanggal_expired);

        if ($stmt->execute()) {
            $pesan_sukses = "Berhasil menambahkan $jumlah_kupon kupon. Kupon berlaku hingga " . date('d M Y', strtotime($tanggal_expired)) . ".";
            $_SESSION['sukses']     = $pesan_sukses;
            $_SESSION['pesan']      = $pesan_sukses;
            $_SESSION['tipe_pesan'] = "success";
        } else {
            $_SESSION['error']      = "Gagal menyimpan data pemasukan kupon: " . $stmt->error;
            $_SESSION['pesan']      = "Gagal menyimpan data pemasukan kupon: " . $stmt->error;
            $_SESSION['tipe_pesan'] = "danger";
        }
        $stmt->close();
    } else {
        $_SESSION['error']      = "Terjadi kesalahan koneksi saat memproses data.";
        $_SESSION['pesan']      = "Terjadi kesalahan koneksi saat memproses data.";
        $_SESSION['tipe_pesan'] = "danger";
    }

    header("Location: dashboard.php");
    exit();
} else {
    // Jika diakses tidak melalui metode POST
    header("Location: dashboard.php");
    exit();
}
?>
