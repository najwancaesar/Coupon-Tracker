<?php
require 'session_config.php';
require 'koneksi.php';
require 'csrf.php';

// Proteksi file: Hanya Admin yang bisa mengakses halaman eksekusi ini
if (!isset($_SESSION['id']) || isset($_SESSION['role']) && $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$aksi = $_REQUEST['aksi'] ?? '';

if ($aksi === 'tambah') {
    // --- Verifikasi CSRF ---
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Permintaan tidak valid (token keamanan salah).';
        header("Location: admin_dashboard.php");
        exit();
    }

    $nim             = trim($_POST['nim'] ?? '');
    $username        = trim($_POST['username'] ?? '');
    $nama_lengkap    = trim($_POST['nama_lengkap'] ?? '');
    $status_pekerjaan = $_POST['status_pekerjaan'] ?? 'Mahasiswa';

    // --- 1.1. Cek duplikat menggunakan prepared statement (fix SQL injection) ---
    $stmt_cek = $mysqli->prepare(
        "SELECT id FROM users WHERE nim = ? OR username = ? OR nama_lengkap = ?"
    );
    $stmt_cek->bind_param("sss", $nim, $username, $nama_lengkap);
    $stmt_cek->execute();
    $stmt_cek->store_result();

    if ($stmt_cek->num_rows > 0) {
        $stmt_cek->close();
        $_SESSION['error'] = 'Gagal Menambah User! NIM/NIP, Username, atau Nama Lengkap tersebut sudah terdaftar di sistem. Silakan gunakan data yang lain.';
        header("Location: admin_dashboard.php");
        exit();
    }
    $stmt_cek->close();

    // Hash password yang defaultnya sama dengan NIM
    $password_hash = password_hash($nim, PASSWORD_DEFAULT);

    // User baru wajib ganti password saat pertama login
    $must_change = 1;

    $stmt = $mysqli->prepare(
        "INSERT INTO users (nim, username, nama_lengkap, password, status_pekerjaan, role, must_change_password) VALUES (?, ?, ?, ?, ?, 'user', ?)"
    );

    if ($stmt) {
        $stmt->bind_param("sssssi", $nim, $username, $nama_lengkap, $password_hash, $status_pekerjaan, $must_change);
        if ($stmt->execute()) {
            $_SESSION['sukses'] = "Berhasil! User $nama_lengkap telah ditambahkan.";
        } else {
            $_SESSION['error'] = "Gagal menambahkan user: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Error pada sistem database.";
    }

} elseif ($aksi === 'edit') {
    // --- Verifikasi CSRF ---
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Permintaan tidak valid (token keamanan salah).';
        header("Location: admin_dashboard.php");
        exit();
    }

    $id              = (int)($_POST['id'] ?? 0);
    $nim             = trim($_POST['nim'] ?? '');
    $username        = trim($_POST['username'] ?? '');
    $nama_lengkap    = trim($_POST['nama_lengkap'] ?? '');
    $status_pekerjaan = $_POST['status_pekerjaan'] ?? 'Mahasiswa';

    // --- 1.1. Pengecekan duplikat menggunakan prepared statement (fix SQL injection) ---
    $stmt_cek = $mysqli->prepare(
        "SELECT id FROM users WHERE (nim = ? OR username = ?) AND id != ?"
    );
    $stmt_cek->bind_param("ssi", $nim, $username, $id);
    $stmt_cek->execute();
    $stmt_cek->store_result();

    if ($stmt_cek->num_rows > 0) {
        $stmt_cek->close();
        $_SESSION['error'] = 'NIM atau Username bentrok dengan pengguna lain!';
        header("Location: admin_dashboard.php");
        exit();
    }
    $stmt_cek->close();

    $stmt = $mysqli->prepare("UPDATE users SET nim=?, username=?, nama_lengkap=?, status_pekerjaan=? WHERE id=?");
    if ($stmt) {
        $stmt->bind_param("ssssi", $nim, $username, $nama_lengkap, $status_pekerjaan, $id);
        if ($stmt->execute()) {
            $_SESSION['sukses'] = "Data pengguna berhasil diperbarui.";
        } else {
            $_SESSION['error'] = "Gagal mengupdate pengguna.";
        }
        $stmt->close();
    }

} elseif ($aksi === 'hapus') {
    // --- Verifikasi CSRF ---
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Permintaan tidak valid (token keamanan salah).';
        header("Location: admin_dashboard.php");
        exit();
    }

    $id = (int)($_POST['id'] ?? 0);

    $stmt = $mysqli->prepare("DELETE FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['sukses'] = "User beserta seluruh datanya berhasil dihapus permanen.";
        } else {
            $_SESSION['error'] = "Gagal menghapus pengguna.";
        }
        $stmt->close();
    }

} elseif ($aksi === 'reset_pass') {
    // --- Verifikasi CSRF ---
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Permintaan tidak valid (token keamanan salah).';
        header("Location: admin_dashboard.php");
        exit();
    }

    $id  = (int)($_POST['id'] ?? 0);
    $nim = trim($_POST['nim'] ?? '');

    // Hash ulang NIM untuk dijadikan password baru + wajib ganti password
    $password_baru = password_hash($nim, PASSWORD_DEFAULT);
    $must_change   = 1;

    $stmt = $mysqli->prepare("UPDATE users SET password=?, must_change_password=? WHERE id=?");
    if ($stmt) {
        $stmt->bind_param("sii", $password_baru, $must_change, $id);
        if ($stmt->execute()) {
            $_SESSION['sukses'] = "Password berhasil di-reset menjadi NIM ($nim). User wajib ganti password saat login berikutnya.";
        } else {
            $_SESSION['error'] = "Gagal mereset kata sandi.";
        }
        $stmt->close();
    }

} else {
    $_SESSION['error'] = 'Aksi tidak valid atau tidak dikenali.';
}

// Setelah selesai melakukan apapun, kembali ke halaman admin dashboard
header("Location: admin_dashboard.php");
exit();
?>
