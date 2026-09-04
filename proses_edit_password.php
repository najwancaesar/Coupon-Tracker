<?php
session_start();
require 'koneksi.php';

// Proteksi sesi (Wajib login)
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi_password_baru = $_POST['konfirmasi_password_baru'];

    // 1. Validasi konfirmasi password
    if ($password_baru !== $konfirmasi_password_baru) {
        $_SESSION['error'] = 'Password baru dan konfirmasi password tidak cocok/sama!';
        header("Location: profile.php");
        exit();
    }

    // 2. Tarik password lama user (yang dienkripsi) dari database
    $query = "SELECT password FROM users WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            
            // 3. Verifikasi apakah password lama yang diinput cocok dengan database
            if (password_verify($password_lama, $user['password'])) {
                
                // 4. Jika cocok, hash (enkripsi) password baru
                $hash_baru = password_hash($password_baru, PASSWORD_DEFAULT);
                
                // 5. UPDATE data di tabel users
                $query_update = "UPDATE users SET password = ? WHERE id = ?";
                $stmt_upd = $mysqli->prepare($query_update);
                if ($stmt_upd) {
                    $stmt_upd->bind_param("si", $hash_baru, $user_id);
                    if ($stmt_upd->execute()) {
                        $_SESSION['sukses'] = 'Password Anda berhasil diperbarui!';
                    } else {
                        $_SESSION['error'] = 'Gagal menyimpan password baru ke database.';
                    }
                    $stmt_upd->close();
                } else {
                    $_SESSION['error'] = 'Kesalahan saat menyiapkan kueri update.';
                }
                
            } else {
                $_SESSION['error'] = 'Password lama yang Anda masukkan salah!';
            }
        } else {
            $_SESSION['error'] = 'Data user tidak ditemukan di database.';
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = 'Terjadi kesalahan pada kueri pembacaan database.';
    }

    // Kembalikan user ke halaman profil beserta notifikasi (berhasil/gagal)
    header("Location: profile.php");
    exit();
} else {
    // Tolak akses jika dibuka tanpa metode POST
    header("Location: profile.php");
    exit();
}
?>
