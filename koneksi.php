<?php
$config_file = __DIR__ . '/config.php';

if (!file_exists($config_file)) {
    die("Koneksi gagal: File 'config.php' tidak ditemukan. Silakan salin 'config.example.php' menjadi 'config.php' dan sesuaikan kredensial database.");
}

$config = require $config_file;

// Membuat koneksi (Object-Oriented)
$mysqli = new mysqli($config['host'], $config['username'], $config['password'], $config['database']);

// Memeriksa koneksi
if ($mysqli->connect_error) {
    die("Koneksi gagal: " . $mysqli->connect_error);
}
?>
