<?php
/**
 * csrf.php
 * Helper CSRF token: generate dan verify.
 * Di-require di semua halaman yang merender form atau memproses aksi state-changing.
 */

/**
 * Generate CSRF token dan simpan di session.
 * Memanggil session_start() tidak diperlukan di sini — pastikan session_config.php 
 * sudah di-require sebelum file ini dipanggil.
 *
 * @return string Token CSRF yang valid untuk sesi ini
 */
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifikasi token CSRF dari input dengan token yang tersimpan di session.
 * Gunakan perbandingan timing-safe untuk mencegah timing attack.
 *
 * @param string $token Token dari input form ($_POST['csrf_token'])
 * @return bool True jika valid, false jika tidak
 */
function verify_csrf_token(string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
