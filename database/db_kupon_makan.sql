-- ==============================================================================
-- DATABASE DUMP: SISTEM MANAJEMEN KUPON MAKAN
-- Deskripsi: Skema database lengkap beserta data dummy untuk testing awal.
-- Fitur Inti: Mendukung logika First Expired First Out (FEFO) pada saldo kupon.
-- ==============================================================================

-- Konfigurasi standar untuk kompatibilitas zona waktu dan karakter
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;

-- Mematikan pengecekan Foreign Key sementara agar proses import tidak error jika tabel ditimpa
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- --------------------------------------------------------
-- 1. PEMBUATAN DATABASE
-- Script ini akan otomatis membuat database jika belum ada di local server user
-- --------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `db_kupon_makan` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `db_kupon_makan`;

-- --------------------------------------------------------
-- 2. TABEL: users
-- Deskripsi: Menyimpan data pengguna, kredensial login (NIM & Password Hash), serta status pekerjaan.
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nim` varchar(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `status_pekerjaan` enum('Mahasiswa','Karyawan') DEFAULT 'Mahasiswa',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- MENGISI DATA DUMMY (users)
-- PENTING UNTUK USER BARU: 
-- Login menggunakan field NIM. Password default disamakan dengan NIM masing-masing.
-- Contoh: Akun Najwan -> NIM: 2404023 | Password: 2404023
INSERT INTO `users` (`id`, `nim`, `username`, `password`, `nama_lengkap`, `status_pekerjaan`) VALUES
	(1, '2404023', 'najwancf', '$2y$10$jnvHphAInPW9UC1akrltIuYeTDOAOqOMUcPidFSzHrtE2vimAy2c.', 'Najwan', 'Mahasiswa'),
	(2, '2404018', 'dzaky', '$2y$10$jxSLsQYlcmGOqjEWrysp7.No3Y9MxJMo8Rp.hwNUhTBK4rZfu3bUm', 'Muhammad Dzaky Ramadhani', 'Mahasiswa'),
	(3, '2404028', 'tangguh', '$2y$10$WANR16lRHL1QxbC7NEjy2.VojvTBSbzyNhM8T0QnzxOvawo.mv0EC', 'Tangguh Putra Mahardika', 'Mahasiswa');

-- --------------------------------------------------------
-- 3. TABEL: pemasukan_kupon
-- Deskripsi: Bertindak sebagai "dompet" tempat masuknya jatah kupon bulanan[cite: 1].
-- Kolom sisa_kupon digunakan oleh backend PHP untuk memotong kupon dengan tanggal_expired terdekat (FEFO)[cite: 1].
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pemasukan_kupon` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `tanggal_input` date NOT NULL,
  `jumlah_kupon` int NOT NULL,
  `sisa_kupon` int NOT NULL,
  `tanggal_expired` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `pemasukan_kupon_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- MENGISI DATA DUMMY (pemasukan_kupon)
-- Berisi contoh input kupon untuk akun Najwan, Dzaky, dan Tangguh[cite: 1]
INSERT INTO `pemasukan_kupon` (`id`, `user_id`, `tanggal_input`, `jumlah_kupon`, `sisa_kupon`, `tanggal_expired`) VALUES
	(1, 1, '2026-08-31', 8, 8, '2026-11-01'),
	(2, 1, '2026-09-01', 21, 21, '2026-12-01'),
	(3, 2, '2026-09-01', 5, 5, '2026-11-30'),
	(4, 2, '2026-09-01', 5, 5, '2026-11-01'),
	(5, 3, '2026-08-01', 5, 1, '2026-09-30'),
	(6, 3, '2026-09-07', 3, 3, '2026-09-30');

-- --------------------------------------------------------
-- 4. TABEL: riwayat_kupon
-- Deskripsi: Mencatat histori pemakaian kupon harian pengguna di kantin beserta catatan (notes)[cite: 1].
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `riwayat_kupon` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `tanggal_pakai` date NOT NULL,
  `jumlah_pakai` int NOT NULL,
  `keterangan` varchar(255) DEFAULT 'Makan di kantin',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `riwayat_kupon_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- MENGISI DATA DUMMY (riwayat_kupon)
-- Berisi simulasi histori pemakaian kupon dari user Tangguh (ID: 3)[cite: 1]
INSERT INTO `riwayat_kupon` (`id`, `user_id`, `tanggal_pakai`, `jumlah_pakai`, `keterangan`) VALUES
	(1, 3, '2026-08-27', 1, 'Makan Siang'),
	(2, 3, '2026-08-28', 1, 'Makan Siang'),
	(3, 3, '2026-08-31', 1, 'Makan Siang'),
	(4, 3, '2026-09-02', 1, 'Makan Siang'); 

-- Mengembalikan pengaturan default MySQL setelah import selesai[cite: 1]
/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;