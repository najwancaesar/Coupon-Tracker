-- phpMyAdmin SQL Dump
-- Sistem Manajemen Kupon Makan (FEFO Logic)
-- Generation Time: Sep 2026

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- 1. Buat Database
-- --------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `db_kupon_makan` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `db_kupon_makan`;

-- --------------------------------------------------------
-- 2. Struktur dari tabel `users`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nim` varchar(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `status_pekerjaan` enum('Mahasiswa','Karyawan') DEFAULT 'Mahasiswa',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 
-- Dummy Data untuk tabel `users`
-- Password default adalah: 123456
-- 
INSERT INTO `users` (`id`, `nim`, `username`, `password`, `nama_lengkap`, `status_pekerjaan`) VALUES
(1, '2404023', 'najwancf', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Najwan', 'Mahasiswa');

-- --------------------------------------------------------
-- 3. Struktur dari tabel `pemasukan_kupon`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `pemasukan_kupon`;
CREATE TABLE `pemasukan_kupon` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `tanggal_input` date NOT NULL,
  `jumlah_kupon` int(11) NOT NULL,
  `sisa_kupon` int(11) NOT NULL COMMENT 'Digunakan untuk logika FEFO',
  `tanggal_expired` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_pemasukan_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 4. Struktur dari tabel `riwayat_kupon`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `riwayat_kupon`;
CREATE TABLE `riwayat_kupon` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `tanggal_pakai` date NOT NULL,
  `jumlah_pakai` int(11) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_riwayat_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;