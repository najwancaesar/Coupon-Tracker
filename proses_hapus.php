<?php
require 'session_config.php';
require 'koneksi.php';
require 'csrf.php';

// Pastikan user sudah login
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

    // Tangkap variabel dari POST (bukan lagi GET)
    $jenis = $_POST['jenis'] ?? '';
    $id    = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($id > 0) {
        if ($jenis == 'pemakaian') {
            // 1. Ambil jumlah_pakai dari riwayat yang akan dihapus (prepared statement)
            $stmt_get = $mysqli->prepare("SELECT jumlah_pakai FROM riwayat_kupon WHERE id = ? AND user_id = ?");
            $stmt_get->bind_param("ii", $id, $user_id);
            $stmt_get->execute();
            $res_get = $stmt_get->get_result();

            if ($res_get && $res_get->num_rows > 0) {
                $row_get       = $res_get->fetch_assoc();
                $jumlah_refund = (int)$row_get['jumlah_pakai'];
                $stmt_get->close();

                // 2. Hapus data dari riwayat_kupon (prepared statement)
                $stmt_del = $mysqli->prepare("DELETE FROM riwayat_kupon WHERE id = ? AND user_id = ?");
                $stmt_del->bind_param("ii", $id, $user_id);

                if ($stmt_del->execute()) {
                    $stmt_del->close();

                    // 3. Logika Refund: Kembalikan saldo ke batch yang masa aktifnya paling lama
                    // Pastikan hanya mengembalikan ke kupon yang belum expired
                    $stmt_refund = $mysqli->prepare(
                        "UPDATE pemasukan_kupon 
                         SET sisa_kupon = sisa_kupon + ? 
                         WHERE user_id = ? AND tanggal_expired >= CURRENT_DATE() 
                         ORDER BY tanggal_expired DESC 
                         LIMIT 1"
                    );
                    $stmt_refund->bind_param("ii", $jumlah_refund, $user_id);
                    $stmt_refund->execute();
                    $stmt_refund->close();

                    $_SESSION['sukses'] = 'Data riwayat pemakaian berhasil dihapus dan saldo kupon telah dikembalikan!';
                } else {
                    $stmt_del->close();
                    $_SESSION['error'] = 'Gagal menghapus data pemakaian.';
                }
            } else {
                $stmt_get->close();
                $_SESSION['error'] = 'Data riwayat tidak ditemukan atau Anda tidak memiliki hak akses.';
            }

        } elseif ($jenis == 'pemasukan') {
            // Hapus data langsung dari tabel pemasukan_kupon (prepared statement)
            $stmt_del = $mysqli->prepare("DELETE FROM pemasukan_kupon WHERE id = ? AND user_id = ?");
            $stmt_del->bind_param("ii", $id, $user_id);

            if ($stmt_del->execute()) {
                $_SESSION['sukses'] = 'Data pemasukan jatah kupon berhasil dihapus!';
            } else {
                $_SESSION['error'] = 'Gagal menghapus data pemasukan kupon.';
            }
            $stmt_del->close();
        } else {
            $_SESSION['error'] = 'Jenis parameter penghapusan tidak valid.';
        }
    } else {
        $_SESSION['error'] = 'ID data tidak valid.';
    }
} else {
    // Tolak semua akses yang bukan POST
    $_SESSION['error'] = 'Akses tidak diizinkan.';
}

// Kembali ke halaman dashboard
header("Location: dashboard.php");
exit();
?>
