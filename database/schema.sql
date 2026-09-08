-- ==============================================================================
-- DATABASE SCHEMA & SEEDER: SISTEM MANAJEMEN KUPON MAKAN
-- Politeknik Gajah Tunggal / PT Gajah Tunggal Tbk
-- Deskripsi: Skema database lengkap beserta data dummy fiktif untuk setup awal.
-- ==============================================================================

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- --------------------------------------------------------
-- 1. PEMBUATAN DATABASE
-- --------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `db_kupon_makan` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `db_kupon_makan`;

-- --------------------------------------------------------
-- 2. TABEL: users
-- Deskripsi: Kredensial pengguna, profil, role (admin/user), dan flag ganti password.
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nim` varchar(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `status_pekerjaan` enum('Mahasiswa','Karyawan') DEFAULT 'Mahasiswa',
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `must_change_password` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_users_nim` (`nim`),
  UNIQUE KEY `idx_users_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- DATA DUMMY (users)
-- Akun Admin Default:
--   NIM: ADMIN001 | Password: ADMIN001 | Role: admin
-- Akun Mahasiswa 1:
--   NIM: 2400001  | Password: 2400001  | Role: user
-- Akun Mahasiswa 2:
--   NIM: 2400002  | Password: 2400002  | Role: user
-- Akun Karyawan 1:
--   NIM: 1990001  | Password: 1990001  | Role: user
INSERT INTO `users` (`id`, `nim`, `username`, `password`, `nama_lengkap`, `status_pekerjaan`, `role`, `must_change_password`) VALUES
  (1, 'ADMIN001', 'admin', '$2y$10$jVixAQ.dzLJnP6sPG9T6V.m2R06wwrCsEQAelgyxEtyU2sE6Rt27.', 'Administrator Sistem', 'Karyawan', 'admin', 1),
  (2, '2400001', 'budi', '$2y$10$Wj484t3GNOFVQfnAarccou4AUL.p36.MLDZpN.bLZoK4dAAy/shgm', 'Budi Santoso', 'Mahasiswa', 'user', 1),
  (3, '2400002', 'siti', '$2y$10$Y8kO6cDMdP1YH8C7F1LqhebTE9kjMwUWvViMfY1VitvQJ7q3B/OWS', 'Siti Rahmawati', 'Mahasiswa', 'user', 1),
  (4, '1990001', 'agus', '$2y$10$xzoaU6sU/IRNB3Ih7IQAUuZKpD7oajMj3yCvfyo6rN7U1PbL5haRG', 'Agus Setiawan', 'Karyawan', 'user', 1);

-- --------------------------------------------------------
-- 3. TABEL: pemasukan_kupon
-- Deskripsi: Pencatatan alokasi jatah kupon dan pelacakan sisa kupon berbasis FEFO.
-- --------------------------------------------------------
DROP TABLE IF EXISTS `pemasukan_kupon`;
CREATE TABLE `pemasukan_kupon` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `tanggal_input` date NOT NULL,
  `jumlah_kupon` int NOT NULL,
  `sisa_kupon` int NOT NULL,
  `tanggal_expired` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `pemasukan_kupon_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- DATA DUMMY (pemasukan_kupon)
INSERT INTO `pemasukan_kupon` (`id`, `user_id`, `tanggal_input`, `jumlah_kupon`, `sisa_kupon`, `tanggal_expired`) VALUES
  (1, 2, '2026-08-01', 20, 15, '2026-10-01'),
  (2, 2, '2026-09-01', 22, 22, '2026-11-01'),
  (3, 3, '2026-09-01', 20, 18, '2026-11-01'),
  (4, 4, '2026-09-01', 25, 25, '2026-11-01');

-- --------------------------------------------------------
-- 4. TABEL: riwayat_kupon
-- Deskripsi: Histori transaksi pemakaian kupon makan di kantin.
-- --------------------------------------------------------
DROP TABLE IF EXISTS `riwayat_kupon`;
CREATE TABLE `riwayat_kupon` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `tanggal_pakai` date NOT NULL,
  `jumlah_pakai` int NOT NULL,
  `keterangan` varchar(255) DEFAULT 'Makan di kantin',
  `status` enum('Selesai','Pending') NOT NULL DEFAULT 'Selesai',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `riwayat_kupon_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- DATA DUMMY (riwayat_kupon)
INSERT INTO `riwayat_kupon` (`id`, `user_id`, `tanggal_pakai`, `jumlah_pakai`, `keterangan`, `status`) VALUES
  (1, 2, '2026-08-05', 2, 'Makan Siang Kantin', 'Selesai'),
  (2, 2, '2026-08-10', 3, 'Makan Siang Kantin', 'Selesai'),
  (3, 3, '2026-09-02', 2, 'Makan Siang Kantin', 'Selesai');

-- --------------------------------------------------------
-- 5. TABEL: login_attempts
-- Deskripsi: Menyimpan riwayat percobaan login gagal untuk mitigasi brute force.
-- --------------------------------------------------------
DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `identifier` varchar(120) NOT NULL,
  `waktu` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_identifier_waktu` (`identifier`, `waktu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
