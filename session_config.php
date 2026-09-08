<?php
/**
 * session_config.php
 * Konfigurasi session terpusat yang aman.
 * Di-require paling awal di semua entry point, menggantikan session_start() polos.
 * Menerapkan cookie flag: HttpOnly, SameSite=Lax, dan Secure (aktif hanya di HTTPS).
 */

// Pastikan belum ada session yang berjalan sebelum konfigurasi diterapkan
if (session_status() === PHP_SESSION_NONE) {
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

    session_set_cookie_params([
        'lifetime' => 0,           // Session cookie (berakhir saat browser ditutup)
        'path'     => '/',
        'domain'   => '',          // Otomatis menggunakan domain saat ini
        'secure'   => $is_https,   // Hanya kirim cookie via HTTPS (jika server HTTPS)
        'httponly' => true,        // Tidak bisa diakses via JavaScript
        'samesite' => 'Lax',       // Proteksi CSRF dasar dari browser
    ]);

    session_start();
}
