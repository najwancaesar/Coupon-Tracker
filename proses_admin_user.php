<?php
session_start();
require 'koneksi.php';

// Proteksi file: Hanya Admin yang bisa mengakses halaman eksekusi ini
if (!isset($_SESSION['id']) || isset($_SESSION['role']) && $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$aksi = $_REQUEST['aksi'] ?? '';

if ($aksi === 'tambah') {
    $nim = $_POST['nim'];
    $username = $_POST['username'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $status_pekerjaan = $_POST['status_pekerjaan'];
    
    // Cek apakah NIM, Username, atau Nama Lengkap sudah digunakan
    $cek = $mysqli->query("SELECT id FROM users WHERE nim = '$nim' OR username = '$username' OR nama_lengkap = '$nama_lengkap'");
    if ($cek && $cek->num_rows > 0) {
        $_SESSION['error'] = 'Gagal Menambah User! NIM/NIP, Username, atau Nama Lengkap tersebut sudah terdaftar di sistem. Silakan gunakan data yang lain.';
        header("Location: admin_dashboard.php");
        exit();
    }
    
    // Hash password yang defaultnya sama dengan NIM
    $password_hash = password_hash($nim, PASSWORD_DEFAULT);
    
    $stmt = $mysqli->prepare("INSERT INTO users (nim, username, nama_lengkap, password, status_pekerjaan, role) VALUES (?, ?, ?, ?, ?, 'user')");
    
    if ($stmt) {
        $stmt->bind_param("sssss", $nim, $username, $nama_lengkap, $password_hash, $status_pekerjaan);
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
    $id = (int)$_POST['id'];
    $nim = $_POST['nim'];
    $username = $_POST['username'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $status_pekerjaan = $_POST['status_pekerjaan'];
    
    // Pengecekan NIM & Username agar tidak bentrok dengan user lain
    $cek = $mysqli->query("SELECT id FROM users WHERE (nim = '$nim' OR username = '$username') AND id != $id");
    if ($cek && $cek->num_rows > 0) {
        $_SESSION['error'] = 'NIM atau Username bentrok dengan pengguna lain!';
        header("Location: admin_dashboard.php");
        exit();
    }
    
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
    $id = (int)$_GET['id'];
    
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
    $id = (int)$_GET['id'];
    $nim = $_GET['nim'];
    
    // Hash ulang NIM untuk dijadikan password baru
    $password_baru = password_hash($nim, PASSWORD_DEFAULT);
    
    $stmt = $mysqli->prepare("UPDATE users SET password=? WHERE id=?");
    if ($stmt) {
        $stmt->bind_param("si", $password_baru, $id);
        if ($stmt->execute()) {
            $_SESSION['sukses'] = "Password berhasil di-reset menjadi NIM ($nim).";
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
