<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "db_kupon_makan";

// Membuat koneksi (Object-Oriented)
$mysqli = new mysqli($host, $username, $password, $database);

// Memeriksa koneksi
if ($mysqli->connect_error) {
    die("Koneksi gagal: " . $mysqli->connect_error);
}
?>
