-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 20 Okt 2026 pada 08.39
-- Versi server: 8.0.30
-- Versi PHP: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Basis data: `e-ticket_bus`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `bookings`
--

CREATE TABLE `bookings` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `schedule_id` int NOT NULL,
  `nomor_kursi` varchar(60) NOT NULL,
  `jumlah_dewasa` tinyint UNSIGNED NOT NULL DEFAULT '1',
  `jumlah_bayi` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `kode_unik` smallint UNSIGNED NOT NULL DEFAULT '0',
  `batas_bayar` datetime DEFAULT NULL,
  `kode_booking` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `schedule_id`, `nomor_kursi`, `jumlah_dewasa`, `jumlah_bayi`, `kode_unik`, `batas_bayar`, `kode_booking`) VALUES
(20, 12, 11, '1', 1, 0, 760, NULL, 'TKT-261013-56C10'),
(21, 12, 108, '1', 1, 0, 763, NULL, 'TKT-261013-EC7B3'),
(23, 12, 103, '10, 11', 2, 1, 158, NULL, 'TKT-261014-7407E'),
(24, 12, 83, '1', 1, 0, 598, NULL, 'TKT-261014-CB329'),
(25, 12, 86, '2', 1, 0, 647, NULL, 'TKT-261014-9F214'),
(26, 14, 11, '4, 5, 7, 8', 4, 2, 315, NULL, 'TKT-261014-FC3D0'),
(27, 12, 15, '9, 10, 11, 12', 4, 0, 564, NULL, 'TKT-261014-87829'),
(28, 12, 15, '13, 14, 17, 18', 4, 1, 664, NULL, 'TKT-261014-4CE83'),
(29, 12, 15, '15, 16', 2, 1, 707, NULL, 'TKT-261014-7ECCB'),
(30, 12, 42, '2', 1, 0, 434, NULL, 'TKT-261017-2BE3D'),
(32, 14, 58, '1, 2, 5, 6', 4, 0, 891, NULL, 'TKT-261018-ABC8A'),
(33, 16, 58, '20, 24, 27, 28', 4, 0, 331, NULL, 'TKT-261018-7939F'),
(34, 12, 161, '1, 2, 11, 12', 4, 0, 775, NULL, 'TKT-261019-A4873'),
(35, 12, 161, '13, 14, 17, 18', 4, 0, 471, NULL, 'TKT-261019-65AB5'),
(36, 12, 161, '39, 40, 43, 44', 4, 0, 871, NULL, 'TKT-261019-3018C'),
(37, 12, 94, '1', 1, 0, 485, NULL, 'TKT-261019-86023'),
(38, 12, 95, '13', 1, 0, 491, NULL, 'TKT-261020-E5980');

-- --------------------------------------------------------

--
-- Struktur dari tabel `booking_penumpang`
--

CREATE TABLE `booking_penumpang` (
  `id` int NOT NULL,
  `booking_id` int NOT NULL,
  `schedule_id` int NOT NULL,
  `nama` varchar(150) NOT NULL,
  `tipe` enum('dewasa','bayi') NOT NULL DEFAULT 'dewasa',
  `nomor_kursi` varchar(10) DEFAULT NULL,
  `kode_tiket` varchar(40) DEFAULT NULL,
  `status_naik` tinyint(1) NOT NULL DEFAULT '0',
  `waktu_naik` datetime DEFAULT NULL,
  `diperiksa_oleh` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `booking_penumpang`
--

INSERT INTO `booking_penumpang` (`id`, `booking_id`, `schedule_id`, `nama`, `tipe`, `nomor_kursi`, `kode_tiket`, `status_naik`, `waktu_naik`, `diperiksa_oleh`) VALUES
(1, 20, 11, 'lista ateu ambarianti', 'dewasa', '1', 'TKT-261013-56C10-1', 1, '2026-10-14 13:43:45', 3),
(2, 21, 108, 'lista ateu ambarianti', 'dewasa', '1', 'TKT-261013-EC7B3-2', 0, NULL, NULL),
(4, 23, 103, 'lista ateu ambarianti', 'dewasa', '11', 'TKT-261014-7407E-4', 0, NULL, NULL),
(5, 23, 103, 'shifa nur fauziah', 'dewasa', '10', 'TKT-261014-7407E-5', 0, NULL, NULL),
(6, 23, 103, 'athaya', 'bayi', NULL, NULL, 0, NULL, NULL),
(7, 24, 83, 'lista ateu ambarianti', 'dewasa', '1', 'TKT-261014-CB329-7', 0, NULL, NULL),
(8, 25, 86, 'lista ateu ambarianti', 'dewasa', '2', 'TKT-261014-9F214-8', 0, NULL, NULL),
(9, 26, 11, 'Shifa Nur Fauziah', 'dewasa', '4', 'TKT-261014-FC3D0-9', 1, '2026-10-14 15:01:39', 5),
(10, 26, 11, 'lista', 'dewasa', '5', 'TKT-261014-FC3D0-10', 1, '2026-10-14 15:01:45', 5),
(11, 26, 11, 'ateu', 'dewasa', '7', 'TKT-261014-FC3D0-11', 1, '2026-10-14 15:02:04', 5),
(12, 26, 11, 'ambarianti', 'dewasa', '8', 'TKT-261014-FC3D0-12', 1, '2026-10-14 15:02:00', 5),
(13, 26, 11, 'zahra', 'bayi', NULL, NULL, 0, NULL, NULL),
(14, 26, 11, 'cimel', 'bayi', NULL, NULL, 0, NULL, NULL),
(15, 27, 15, 'lista ateu ambarianti', 'dewasa', '9', 'TKT-261014-87829-15', 1, '2026-10-14 16:19:28', 5),
(16, 27, 15, 'karina eka nasifa', 'dewasa', '10', 'TKT-261014-87829-16', 1, '2026-10-14 16:19:24', 5),
(17, 27, 15, 'cinta amelia putri', 'dewasa', '11', 'TKT-261014-87829-17', 1, '2026-10-14 16:19:05', 5),
(18, 27, 15, 'zahra mutia ningsih', 'dewasa', '12', 'TKT-261014-87829-18', 1, '2026-10-14 16:19:18', 5),
(19, 28, 15, 'lista ateu ambarianti', 'dewasa', '13', NULL, 0, NULL, NULL),
(20, 28, 15, 'lisna rinjani', 'dewasa', '14', NULL, 0, NULL, NULL),
(21, 28, 15, 'kalinnarizki riyyah', 'dewasa', '17', NULL, 0, NULL, NULL),
(22, 28, 15, 'diki agustian', 'dewasa', '18', NULL, 0, NULL, NULL),
(23, 28, 15, 'indra hermawan', 'bayi', NULL, NULL, 0, NULL, NULL),
(24, 29, 15, 'lista ateu ambarianti', 'dewasa', '15', 'TKT-261014-7ECCB-24', 1, '2026-10-14 16:26:32', 5),
(25, 29, 15, 'lisna rinjani', 'dewasa', '16', 'TKT-261014-7ECCB-25', 1, '2026-10-14 16:26:42', 5),
(26, 29, 15, 'lutpi', 'bayi', NULL, NULL, 0, NULL, NULL),
(27, 30, 42, 'lista ateu ambarianti', 'dewasa', '2', 'TKT-261017-2BE3D-27', 1, '2026-10-17 13:27:01', 6),
(32, 32, 58, 'Shifa Nur Fauziah', 'dewasa', '1', 'TKT-261018-ABC8A-32', 1, '2026-10-19 09:25:23', 6),
(33, 32, 58, 'lisna rinjani', 'dewasa', '2', 'TKT-261018-ABC8A-33', 1, '2026-10-19 09:25:57', 6),
(34, 32, 58, 'kalinna rizki riyah', 'dewasa', '5', 'TKT-261018-ABC8A-34', 1, '2026-10-19 00:31:49', 6),
(35, 32, 58, 'lutpi aaceh', 'dewasa', '6', 'TKT-261018-ABC8A-35', 0, NULL, NULL),
(36, 33, 58, 'Lisna Rinjani', 'dewasa', '20', NULL, 0, NULL, NULL),
(37, 33, 58, 'valeno axel pramono', 'dewasa', '24', NULL, 0, NULL, NULL),
(38, 33, 58, 'zufar lutfi maliki', 'dewasa', '28', NULL, 0, NULL, NULL),
(39, 33, 58, 'indra hermawan', 'dewasa', '27', NULL, 0, NULL, NULL),
(40, 34, 161, 'lista ateu ambarianti', 'dewasa', '1', 'TKT-261019-A4873-40', 1, '2026-10-19 13:45:11', 5),
(41, 34, 161, 'karina eka nasifa', 'dewasa', '2', 'TKT-261019-A4873-41', 1, '2026-10-19 13:45:48', 5),
(42, 34, 161, 'cinta amelia putri', 'dewasa', '11', 'TKT-261019-A4873-42', 0, NULL, NULL),
(43, 34, 161, 'zahra mutianingsih', 'dewasa', '12', 'TKT-261019-A4873-43', 0, NULL, NULL),
(44, 35, 161, 'valeno axel pramono', 'dewasa', '13', 'TKT-261019-65AB5-44', 0, NULL, NULL),
(45, 35, 161, 'lutpi', 'dewasa', '14', 'TKT-261019-65AB5-45', 0, NULL, NULL),
(46, 35, 161, 'zufar lutfi maliki', 'dewasa', '17', 'TKT-261019-65AB5-46', 0, NULL, NULL),
(47, 35, 161, 'diki agustian', 'dewasa', '18', 'TKT-261019-65AB5-47', 0, NULL, NULL),
(48, 36, 161, 'lista ateu ambarianti', 'dewasa', '43', 'TKT-261019-3018C-48', 1, '2026-10-19 14:23:01', 5),
(49, 36, 161, 'karin', 'dewasa', '44', 'TKT-261019-3018C-49', 1, '2026-10-19 14:23:24', 5),
(50, 36, 161, 'zahra', 'dewasa', '40', 'TKT-261019-3018C-50', 0, NULL, NULL),
(51, 36, 161, 'amel', 'dewasa', '39', 'TKT-261019-3018C-51', 0, NULL, NULL),
(52, 37, 94, 'lista ateu ambarianti', 'dewasa', '1', 'TKT-261019-86023-52', 0, NULL, NULL),
(53, 38, 95, 'lista ateu ambarianti', 'dewasa', '13', 'TKT-261020-E5980-53', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `buses`
--

CREATE TABLE `buses` (
  `id` int NOT NULL,
  `nama_bus` varchar(255) NOT NULL,
  `plat_nomor` varchar(50) NOT NULL,
  `kapasitas_kursi` bigint NOT NULL,
  `kelas` varchar(50) NOT NULL,
  `tata_letak` varchar(10) DEFAULT NULL,
  `fasilitas` varchar(255) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `buses`
--

INSERT INTO `buses` (`id`, `nama_bus`, `plat_nomor`, `kapasitas_kursi`, `kelas`, `tata_letak`, `fasilitas`, `foto`) VALUES
(2, 'Busweday', 'R 2024 TR', 50, 'Eksekutif', '2-2', 'ac,kursi_rebah,usb,bagasi,toilet,wifi,selimut', 'bus_0dd538470b3043be.webp'),
(3, 'Damri', 'C 0103 DA', 20, 'Ekonomi', '2-2', 'ac,bagasi', 'bus_78cb53abc8a5cc1a.jpg'),
(6, 'Pariwisata', 'L 0612 AP', 45, 'Eksekutif', '2-2', 'ac,kursi_rebah,usb,bagasi,wifi,tv,selimut,snack', 'bus_e8118b4e3f474952.webp'),
(7, 'Budiman', 'T 6655 UT', 50, 'Bisnis', '2-2', 'ac,usb,bagasi,tv,istirahat', 'bus_fc84cbe74e77d9b2.webp'),
(8, 'Cakra Prima', 'D 7421 AD', 27, 'Eksekutif', '2-1', 'ac,kursi_rebah,usb,bagasi,toilet,wifi,tv,selimut,snack', 'bus_3829b598c34cf6fd.png'),
(9, 'Priangan Express', 'Z 7035 KB', 32, 'Bisnis', '2-2', 'ac,kursi_rebah,usb,bagasi,tv', 'bus_3660b5e335076ab9.jpg'),
(10, 'Lintas Pantura', 'E 7512 QA', 50, 'Ekonomi', '2-3', 'ac,bagasi,istirahat', 'bus_2d83f449cee63b6f.webp'),
(11, 'Mataram Jaya', 'AB 7306 CA', 32, 'Eksekutif', '2-2', 'ac,kursi_rebah,usb,bagasi,wifi,selimut,snack', 'bus_eebbf27df11a8550.jpg'),
(12, 'Surya Wijaya', 'L 7219 UD', 40, 'Bisnis', '2-2', 'ac,usb,bagasi,tv', 'bus_21b1f11a05c67593.jpg'),
(13, 'Galuh Trans', 'Z 7144 AG', 43, 'Ekonomi', '2-2', 'ac,usb,bagasi', 'bus_40d62b22f7e0a3b9.jpg'),
(15, 'Damri', 'AZ  009 TA', 20, 'Ekonomi', '2-1', 'usb,toilet', 'bus_56a73ea8491b4faf.png');

-- --------------------------------------------------------

--
-- Struktur dari tabel `payments`
--

CREATE TABLE `payments` (
  `id` int NOT NULL,
  `booking_id` int NOT NULL,
  `metode_pembayaran` enum('Transfer BCA','Transfer BRI','Transfer Mandiri','QRIS','DANA') NOT NULL,
  `bukti_transfer` varchar(255) DEFAULT NULL,
  `status` enum('pending','lunas','gagal','ditolak') NOT NULL DEFAULT 'pending',
  `alasan_penolakan` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `payments`
--

INSERT INTO `payments` (`id`, `booking_id`, `metode_pembayaran`, `bukti_transfer`, `status`, `alasan_penolakan`) VALUES
(11, 20, 'QRIS', 'TKT-261013-56C10-0feb8200.jpg', 'lunas', NULL),
(12, 21, 'Transfer BCA', 'TKT-261013-EC7B3-4ad3bfb7.jpg', 'lunas', NULL),
(13, 23, 'QRIS', 'TKT-261014-7407E-3c799c55.png', 'lunas', NULL),
(14, 24, 'Transfer BCA', 'TKT-261014-CB329-051eda6f.png', 'lunas', NULL),
(15, 25, 'QRIS', 'TKT-261014-9F214-9764e9ba.png', 'lunas', NULL),
(16, 26, 'QRIS', 'TKT-261014-FC3D0-724e51ce.png', 'lunas', NULL),
(17, 27, 'QRIS', 'TKT-261014-87829-d5b6bac0.png', 'lunas', NULL),
(18, 28, 'QRIS', 'TKT-261014-4CE83-cf09467d.png', 'lunas', NULL),
(19, 29, 'QRIS', 'TKT-261014-7ECCB-094e0f63.png', 'lunas', NULL),
(20, 30, 'QRIS', 'TKT-261017-2BE3D-cba31fbe.png', 'lunas', NULL),
(22, 32, 'Transfer BCA', 'TKT-261018-ABC8A-2c7d5c8f.png', 'lunas', NULL),
(23, 33, 'QRIS', 'TKT-261018-7939F-ab21e76c.png', 'lunas', NULL),
(24, 34, 'QRIS', 'TKT-261019-A4873-7ba988b9.png', 'lunas', NULL),
(25, 35, 'QRIS', 'TKT-261019-65AB5-583da65b.png', 'lunas', NULL),
(26, 36, 'QRIS', 'TKT-261019-3018C-9be09444.png', 'lunas', NULL),
(27, 37, 'QRIS', 'TKT-261019-86023-3c276ea5.png', 'lunas', NULL),
(28, 38, 'QRIS', 'TKT-261020-E5980-3a62b897.jpg', 'lunas', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `schedules`
--

CREATE TABLE `schedules` (
  `id` int NOT NULL,
  `bus_id` int NOT NULL,
  `sopir` varchar(255) NOT NULL,
  `kota_asal` varchar(100) NOT NULL,
  `kota_tujuan` varchar(100) NOT NULL,
  `jam_berangkat` datetime NOT NULL,
  `jam_tiba` datetime DEFAULT NULL,
  `harga` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `schedules`
--

INSERT INTO `schedules` (`id`, `bus_id`, `sopir`, `kota_asal`, `kota_tujuan`, `jam_berangkat`, `jam_tiba`, `harga`) VALUES
(1, 3, 'Agus Kurniadi Khaer', 'Yogyakarta', 'Solo', '2026-10-14 06:00:00', '2026-10-14 08:00:00', 35000.00),
(2, 10, 'Arya Asa Fikarda', 'Jakarta', 'Cirebon', '2026-10-14 06:00:00', '2026-10-14 11:00:00', 90000.00),
(3, 9, 'Salomon', 'Jakarta', 'Bandung', '2026-10-14 07:00:00', '2026-10-14 10:30:00', 110000.00),
(4, 7, 'Zupar Lutpi Maliki', 'Yogyakarta', 'Surabaya', '2026-10-14 08:00:00', '2026-10-14 14:30:00', 190000.00),
(5, 3, 'Agus Kurniadi Khaer', 'Solo', 'Yogyakarta', '2026-10-14 09:00:00', '2026-10-14 11:00:00', 35000.00),
(6, 8, 'Valeno Axel Pramono', 'Jakarta', 'Bandung', '2026-10-14 09:00:00', '2026-10-14 12:15:00', 175000.00),
(7, 11, 'Lutpi Aceh', 'Surabaya', 'Yogyakarta', '2026-10-14 09:00:00', '2026-10-14 15:00:00', 260000.00),
(8, 3, 'Agus Kurniadi Khaer', 'Yogyakarta', 'Solo', '2026-10-14 13:00:00', '2026-10-14 15:00:00', 35000.00),
(9, 9, 'Salomon', 'Bandung', 'Jakarta', '2026-10-14 14:00:00', '2026-10-14 17:30:00', 110000.00),
(10, 10, 'Arya Asa Fikarda', 'Cirebon', 'Jakarta', '2026-10-14 14:00:00', '2026-10-14 19:00:00', 90000.00),
(11, 8, 'Lutpi Aceh', 'Bandung', 'Jakarta', '2026-10-14 15:30:00', '2026-10-14 18:45:00', 175000.00),
(12, 3, 'Agus Kurniadi Khaer', 'Solo', 'Yogyakarta', '2026-10-14 16:30:00', '2026-10-14 18:30:00', 35000.00),
(13, 3, 'Hardianto', 'Yogyakarta', 'Solo', '2026-10-15 06:00:00', '2026-10-15 08:00:00', 35000.00),
(14, 13, 'Salomon', 'Semarang', 'Yogyakarta', '2026-10-15 06:30:00', '2026-10-15 10:00:00', 65000.00),
(15, 9, 'Lutpi Aceh', 'Jakarta', 'Bandung', '2026-10-15 07:00:00', '2026-10-15 10:30:00', 110000.00),
(16, 12, 'Zupar Lutpi Maliki', 'Surabaya', 'Malang', '2026-10-15 07:00:00', '2026-10-15 10:00:00', 70000.00),
(17, 7, 'Agus Kurniadi Khaer', 'Surabaya', 'Yogyakarta', '2026-10-15 08:00:00', '2026-10-15 14:30:00', 190000.00),
(18, 3, 'Hardianto', 'Solo', 'Yogyakarta', '2026-10-15 09:00:00', '2026-10-15 11:00:00', 35000.00),
(19, 8, 'Lutpi Aceh', 'Jakarta', 'Bandung', '2026-10-15 09:00:00', '2026-10-15 12:15:00', 175000.00),
(20, 3, 'Hardianto', 'Yogyakarta', 'Solo', '2026-10-15 13:00:00', '2026-10-15 15:00:00', 35000.00),
(21, 12, 'Zupar Lutpi Maliki', 'Malang', 'Surabaya', '2026-10-15 13:30:00', '2026-10-15 16:30:00', 70000.00),
(22, 13, 'Salomon', 'Yogyakarta', 'Semarang', '2026-10-15 13:30:00', '2026-10-15 17:00:00', 65000.00),
(23, 9, 'Lutpi Aceh', 'Bandung', 'Jakarta', '2026-10-15 14:00:00', '2026-10-15 17:30:00', 110000.00),
(24, 8, 'Valeno Axel Pramono', 'Bandung', 'Jakarta', '2026-10-15 15:30:00', '2026-10-15 18:45:00', 175000.00),
(25, 3, 'Hardianto', 'Solo', 'Yogyakarta', '2026-10-15 16:30:00', '2026-10-15 18:30:00', 35000.00),
(26, 13, 'Hardianto', 'Semarang', 'Yogyakarta', '2026-10-16 06:30:00', '2026-10-16 10:00:00', 65000.00),
(27, 9, 'Arya Asa Fikarda', 'Jakarta', 'Bandung', '2026-10-16 07:00:00', '2026-10-16 10:30:00', 110000.00),
(28, 12, 'Valeno Axel Pramono', 'Surabaya', 'Malang', '2026-10-16 07:00:00', '2026-10-16 10:00:00', 70000.00),
(29, 8, 'Salomon', 'Jakarta', 'Bandung', '2026-10-16 09:00:00', '2026-10-16 12:15:00', 175000.00),
(30, 12, 'Valeno Axel Pramono', 'Malang', 'Surabaya', '2026-10-16 13:30:00', '2026-10-16 16:30:00', 70000.00),
(31, 13, 'Hardianto', 'Yogyakarta', 'Semarang', '2026-10-16 13:30:00', '2026-10-16 17:00:00', 65000.00),
(32, 9, 'Arya Asa Fikarda', 'Bandung', 'Jakarta', '2026-10-16 14:00:00', '2026-10-16 17:30:00', 110000.00),
(33, 8, 'Salomon', 'Bandung', 'Jakarta', '2026-10-16 15:30:00', '2026-10-16 18:45:00', 175000.00),
(34, 2, 'Lutpi Aceh', 'Jakarta', 'Yogyakarta', '2026-10-16 19:00:00', '2026-10-17 04:00:00', 350000.00),
(35, 6, 'Zupar Lutpi Maliki', 'Yogyakarta', 'Jakarta', '2026-10-16 20:00:00', '2026-10-17 05:00:00', 375000.00),
(36, 3, 'Hardianto', 'Yogyakarta', 'Solo', '2026-10-17 06:00:00', '2026-10-17 08:00:00', 35000.00),
(37, 9, 'Agus Kurniadi Khaer', 'Jakarta', 'Bandung', '2026-10-17 07:00:00', '2026-10-17 10:30:00', 110000.00),
(38, 3, 'Hardianto', 'Solo', 'Yogyakarta', '2026-10-17 09:00:00', '2026-10-17 11:00:00', 35000.00),
(39, 8, 'Arya Asa Fikarda', 'Jakarta', 'Bandung', '2026-10-17 09:00:00', '2026-10-17 12:15:00', 175000.00),
(40, 11, 'Salomon', 'Yogyakarta', 'Surabaya', '2026-10-17 09:00:00', '2026-10-17 15:00:00', 260000.00),
(41, 3, 'Hardianto', 'Yogyakarta', 'Solo', '2026-10-17 13:00:00', '2026-10-17 15:00:00', 35000.00),
(42, 9, 'Agus Kurniadi Khaer', 'Bandung', 'Jakarta', '2026-10-17 14:00:00', '2026-10-17 17:30:00', 110000.00),
(43, 8, 'Arya Asa Fikarda', 'Bandung', 'Jakarta', '2026-10-17 15:30:00', '2026-10-17 18:45:00', 175000.00),
(44, 3, 'Hardianto', 'Solo', 'Yogyakarta', '2026-10-17 16:30:00', '2026-10-17 18:30:00', 35000.00),
(45, 2, 'Valeno Axel Pramono', 'Yogyakarta', 'Jakarta', '2026-10-17 19:00:00', '2026-10-18 04:00:00', 350000.00),
(46, 6, 'Zupar Lutpi Maliki', 'Jakarta', 'Yogyakarta', '2026-10-17 20:00:00', '2026-10-18 05:00:00', 375000.00),
(47, 9, 'Arya Asa Fikarda', 'Jakarta', 'Bandung', '2026-10-18 07:00:00', '2026-10-18 10:30:00', 110000.00),
(48, 12, 'Agus Kurniadi Khaer', 'Surabaya', 'Malang', '2026-10-18 07:00:00', '2026-10-18 10:00:00', 70000.00),
(49, 8, 'Lutpi Aceh', 'Jakarta', 'Bandung', '2026-10-18 09:00:00', '2026-10-18 12:15:00', 175000.00),
(50, 12, 'Agus Kurniadi Khaer', 'Malang', 'Surabaya', '2026-10-18 13:30:00', '2026-10-18 16:30:00', 70000.00),
(51, 9, 'Arya Asa Fikarda', 'Bandung', 'Jakarta', '2026-10-18 14:00:00', '2026-10-18 17:30:00', 110000.00),
(52, 8, 'Lutpi Aceh', 'Bandung', 'Jakarta', '2026-10-18 15:30:00', '2026-10-18 18:45:00', 175000.00),
(53, 2, 'Hardianto', 'Jakarta', 'Yogyakarta', '2026-10-18 19:00:00', '2026-10-19 04:00:00', 350000.00),
(54, 6, 'Valeno Axel Pramono', 'Yogyakarta', 'Jakarta', '2026-10-18 20:00:00', '2026-10-19 05:00:00', 375000.00),
(55, 3, 'Salomon', 'Yogyakarta', 'Solo', '2026-10-19 06:00:00', '2026-10-19 08:00:00', 35000.00),
(56, 10, 'Zupar Lutpi Maliki', 'Jakarta', 'Cirebon', '2026-10-19 06:00:00', '2026-10-19 11:00:00', 90000.00),
(57, 13, 'Arya Asa Fikarda', 'Semarang', 'Yogyakarta', '2026-10-19 06:30:00', '2026-10-19 10:00:00', 65000.00),
(58, 9, 'Agus Kurniadi Khaer', 'Jakarta', 'Bandung', '2026-10-19 07:00:00', '2026-10-19 10:30:00', 110000.00),
(59, 12, 'Lutpi Aceh', 'Surabaya', 'Malang', '2026-10-19 07:00:00', '2026-10-19 10:00:00', 70000.00),
(60, 3, 'Salomon', 'Solo', 'Yogyakarta', '2026-10-19 09:00:00', '2026-10-19 11:00:00', 35000.00),
(61, 3, 'Salomon', 'Yogyakarta', 'Solo', '2026-10-19 13:00:00', '2026-10-19 15:00:00', 35000.00),
(62, 12, 'Lutpi Aceh', 'Malang', 'Surabaya', '2026-10-19 13:30:00', '2026-10-19 16:30:00', 70000.00),
(63, 13, 'Arya Asa Fikarda', 'Yogyakarta', 'Semarang', '2026-10-19 13:30:00', '2026-10-19 17:00:00', 65000.00),
(64, 9, 'Agus Kurniadi Khaer', 'Bandung', 'Jakarta', '2026-10-19 14:00:00', '2026-10-19 17:30:00', 110000.00),
(65, 10, 'Zupar Lutpi Maliki', 'Cirebon', 'Jakarta', '2026-10-19 14:00:00', '2026-10-19 19:00:00', 90000.00),
(66, 3, 'Salomon', 'Solo', 'Yogyakarta', '2026-10-19 16:30:00', '2026-10-19 18:30:00', 35000.00),
(67, 3, 'Lutpi Aceh', 'Yogyakarta', 'Solo', '2026-10-20 06:00:00', '2026-10-20 08:00:00', 35000.00),
(68, 10, 'Hardianto', 'Jakarta', 'Cirebon', '2026-10-20 06:00:00', '2026-10-20 11:00:00', 90000.00),
(69, 13, 'Valeno Axel Pramono', 'Semarang', 'Yogyakarta', '2026-10-20 06:30:00', '2026-10-20 10:00:00', 65000.00),
(70, 12, 'Arya Asa Fikarda', 'Surabaya', 'Malang', '2026-10-20 07:00:00', '2026-10-20 10:00:00', 70000.00),
(71, 3, 'Lutpi Aceh', 'Solo', 'Yogyakarta', '2026-10-20 09:00:00', '2026-10-20 11:00:00', 35000.00),
(72, 8, 'Zupar Lutpi Maliki', 'Jakarta', 'Bandung', '2026-10-20 09:00:00', '2026-10-20 12:15:00', 175000.00),
(73, 11, 'Agus Kurniadi Khaer', 'Surabaya', 'Yogyakarta', '2026-10-20 09:00:00', '2026-10-20 15:00:00', 260000.00),
(74, 3, 'Lutpi Aceh', 'Yogyakarta', 'Solo', '2026-10-20 13:00:00', '2026-10-20 15:00:00', 35000.00),
(75, 12, 'Arya Asa Fikarda', 'Malang', 'Surabaya', '2026-10-20 13:30:00', '2026-10-20 16:30:00', 70000.00),
(76, 13, 'Valeno Axel Pramono', 'Yogyakarta', 'Semarang', '2026-10-20 13:30:00', '2026-10-20 17:00:00', 65000.00),
(77, 10, 'Hardianto', 'Cirebon', 'Jakarta', '2026-10-20 14:00:00', '2026-10-20 19:00:00', 90000.00),
(78, 8, 'Zupar Lutpi Maliki', 'Bandung', 'Jakarta', '2026-10-20 15:30:00', '2026-10-20 18:45:00', 175000.00),
(79, 3, 'Lutpi Aceh', 'Solo', 'Yogyakarta', '2026-10-20 16:30:00', '2026-10-20 18:30:00', 35000.00),
(80, 3, 'Salomon', 'Yogyakarta', 'Solo', '2026-10-21 06:00:00', '2026-10-21 08:00:00', 35000.00),
(81, 10, 'Hardianto', 'Jakarta', 'Cirebon', '2026-10-21 06:00:00', '2026-10-21 11:00:00', 90000.00),
(82, 9, 'Agus Kurniadi Khaer', 'Jakarta', 'Bandung', '2026-10-21 07:00:00', '2026-10-21 10:30:00', 110000.00),
(83, 7, 'Valeno Axel Pramono', 'Yogyakarta', 'Surabaya', '2026-10-21 08:00:00', '2026-10-21 14:30:00', 190000.00),
(84, 3, 'Salomon', 'Solo', 'Yogyakarta', '2026-10-21 09:00:00', '2026-10-21 11:00:00', 35000.00),
(85, 8, 'Lutpi Aceh', 'Jakarta', 'Bandung', '2026-10-21 09:00:00', '2026-10-21 12:15:00', 175000.00),
(86, 11, 'Zupar Lutpi Maliki', 'Yogyakarta', 'Surabaya', '2026-10-21 09:00:00', '2026-10-21 15:00:00', 260000.00),
(87, 3, 'Salomon', 'Yogyakarta', 'Solo', '2026-10-21 13:00:00', '2026-10-21 15:00:00', 35000.00),
(88, 9, 'Agus Kurniadi Khaer', 'Bandung', 'Jakarta', '2026-10-21 14:00:00', '2026-10-21 17:30:00', 110000.00),
(89, 10, 'Hardianto', 'Cirebon', 'Jakarta', '2026-10-21 14:00:00', '2026-10-21 19:00:00', 90000.00),
(90, 8, 'Lutpi Aceh', 'Bandung', 'Jakarta', '2026-10-21 15:30:00', '2026-10-21 18:45:00', 175000.00),
(91, 3, 'Salomon', 'Solo', 'Yogyakarta', '2026-10-21 16:30:00', '2026-10-21 18:30:00', 35000.00),
(92, 3, 'Hardianto', 'Yogyakarta', 'Solo', '2026-10-22 06:00:00', '2026-10-22 08:00:00', 35000.00),
(93, 13, 'Salomon', 'Semarang', 'Yogyakarta', '2026-10-22 06:30:00', '2026-10-22 10:00:00', 65000.00),
(94, 9, 'Arya Asa Fikarda', 'Jakarta', 'Bandung', '2026-10-22 07:00:00', '2026-10-22 10:30:00', 110000.00),
(95, 12, 'Zupar Lutpi Maliki', 'Surabaya', 'Malang', '2026-10-22 07:00:00', '2026-10-22 10:00:00', 70000.00),
(96, 7, 'Lutpi Aceh', 'Surabaya', 'Yogyakarta', '2026-10-22 08:00:00', '2026-10-22 14:30:00', 190000.00),
(97, 3, 'Hardianto', 'Solo', 'Yogyakarta', '2026-10-22 09:00:00', '2026-10-22 11:00:00', 35000.00),
(98, 8, 'Valeno Axel Pramono', 'Jakarta', 'Bandung', '2026-10-22 09:00:00', '2026-10-22 12:15:00', 175000.00),
(99, 3, 'Hardianto', 'Yogyakarta', 'Solo', '2026-10-22 13:00:00', '2026-10-22 15:00:00', 35000.00),
(100, 12, 'Zupar Lutpi Maliki', 'Malang', 'Surabaya', '2026-10-22 13:30:00', '2026-10-22 16:30:00', 70000.00),
(101, 13, 'Salomon', 'Yogyakarta', 'Semarang', '2026-10-22 13:30:00', '2026-10-22 17:00:00', 65000.00),
(102, 9, 'Arya Asa Fikarda', 'Bandung', 'Jakarta', '2026-10-22 14:00:00', '2026-10-22 17:30:00', 110000.00),
(103, 8, 'Valeno Axel Pramono', 'Bandung', 'Jakarta', '2026-10-22 15:30:00', '2026-10-22 18:45:00', 175000.00),
(104, 3, 'Hardianto', 'Solo', 'Yogyakarta', '2026-10-22 16:30:00', '2026-10-22 18:30:00', 35000.00),
(105, 13, 'Arya Asa Fikarda', 'Semarang', 'Yogyakarta', '2026-10-23 06:30:00', '2026-10-23 10:00:00', 65000.00),
(106, 9, 'Salomon', 'Jakarta', 'Bandung', '2026-10-23 07:00:00', '2026-10-23 10:30:00', 110000.00),
(107, 12, 'Agus Kurniadi Khaer', 'Surabaya', 'Malang', '2026-10-23 07:00:00', '2026-10-23 10:00:00', 70000.00),
(108, 8, 'Hardianto', 'Jakarta', 'Bandung', '2026-10-23 09:00:00', '2026-10-23 12:15:00', 175000.00),
(109, 12, 'Agus Kurniadi Khaer', 'Malang', 'Surabaya', '2026-10-23 13:30:00', '2026-10-23 16:30:00', 70000.00),
(110, 13, 'Arya Asa Fikarda', 'Yogyakarta', 'Semarang', '2026-10-23 13:30:00', '2026-10-23 17:00:00', 65000.00),
(111, 9, 'Salomon', 'Bandung', 'Jakarta', '2026-10-23 14:00:00', '2026-10-23 17:30:00', 110000.00),
(112, 8, 'Hardianto', 'Bandung', 'Jakarta', '2026-10-23 15:30:00', '2026-10-23 18:45:00', 175000.00),
(113, 2, 'Zupar Lutpi Maliki', 'Yogyakarta', 'Jakarta', '2026-10-23 19:00:00', '2026-10-24 04:00:00', 350000.00),
(114, 6, 'Valeno Axel Pramono', 'Jakarta', 'Yogyakarta', '2026-10-23 20:00:00', '2026-10-24 05:00:00', 375000.00),
(115, 3, 'Lutpi Aceh', 'Yogyakarta', 'Solo', '2026-10-24 06:00:00', '2026-10-24 08:00:00', 35000.00),
(116, 9, 'Arya Asa Fikarda', 'Jakarta', 'Bandung', '2026-10-24 07:00:00', '2026-10-24 10:30:00', 110000.00),
(117, 3, 'Lutpi Aceh', 'Solo', 'Yogyakarta', '2026-10-24 09:00:00', '2026-10-24 11:00:00', 35000.00),
(118, 8, 'Hardianto', 'Jakarta', 'Bandung', '2026-10-24 09:00:00', '2026-10-24 12:15:00', 175000.00),
(119, 11, 'Agus Kurniadi Khaer', 'Surabaya', 'Yogyakarta', '2026-10-24 09:00:00', '2026-10-24 15:00:00', 260000.00),
(120, 3, 'Lutpi Aceh', 'Yogyakarta', 'Solo', '2026-10-24 13:00:00', '2026-10-24 15:00:00', 35000.00),
(121, 9, 'Arya Asa Fikarda', 'Bandung', 'Jakarta', '2026-10-24 14:00:00', '2026-10-24 17:30:00', 110000.00),
(122, 8, 'Hardianto', 'Bandung', 'Jakarta', '2026-10-24 15:30:00', '2026-10-24 18:45:00', 175000.00),
(123, 3, 'Lutpi Aceh', 'Solo', 'Yogyakarta', '2026-10-24 16:30:00', '2026-10-24 18:30:00', 35000.00),
(124, 2, 'Salomon', 'Jakarta', 'Yogyakarta', '2026-10-24 19:00:00', '2026-10-25 04:00:00', 350000.00),
(125, 6, 'Valeno Axel Pramono', 'Yogyakarta', 'Jakarta', '2026-10-24 20:00:00', '2026-10-25 05:00:00', 375000.00),
(126, 9, 'Agus Kurniadi Khaer', 'Jakarta', 'Bandung', '2026-10-25 07:00:00', '2026-10-25 10:30:00', 110000.00),
(127, 12, 'Zupar Lutpi Maliki', 'Surabaya', 'Malang', '2026-10-25 07:00:00', '2026-10-25 10:00:00', 70000.00),
(128, 8, 'Arya Asa Fikarda', 'Jakarta', 'Bandung', '2026-10-25 09:00:00', '2026-10-25 12:15:00', 175000.00),
(129, 12, 'Zupar Lutpi Maliki', 'Malang', 'Surabaya', '2026-10-25 13:30:00', '2026-10-25 16:30:00', 70000.00),
(130, 9, 'Agus Kurniadi Khaer', 'Bandung', 'Jakarta', '2026-10-25 14:00:00', '2026-10-25 17:30:00', 110000.00),
(131, 8, 'Arya Asa Fikarda', 'Bandung', 'Jakarta', '2026-10-25 15:30:00', '2026-10-25 18:45:00', 175000.00),
(132, 2, 'Lutpi Aceh', 'Yogyakarta', 'Jakarta', '2026-10-25 19:00:00', '2026-10-26 04:00:00', 350000.00),
(133, 6, 'Salomon', 'Jakarta', 'Yogyakarta', '2026-10-25 20:00:00', '2026-10-26 05:00:00', 375000.00),
(134, 3, 'Agus Kurniadi Khaer', 'Yogyakarta', 'Solo', '2026-10-26 06:00:00', '2026-10-26 08:00:00', 35000.00),
(135, 10, 'Hardianto', 'Jakarta', 'Cirebon', '2026-10-26 06:00:00', '2026-10-26 11:00:00', 90000.00),
(136, 13, 'Arya Asa Fikarda', 'Semarang', 'Yogyakarta', '2026-10-26 06:30:00', '2026-10-26 10:00:00', 65000.00),
(137, 9, 'Valeno Axel Pramono', 'Jakarta', 'Bandung', '2026-10-26 07:00:00', '2026-10-26 10:30:00', 110000.00),
(138, 12, 'Zupar Lutpi Maliki', 'Surabaya', 'Malang', '2026-10-26 07:00:00', '2026-10-26 10:00:00', 70000.00),
(139, 3, 'Agus Kurniadi Khaer', 'Solo', 'Yogyakarta', '2026-10-26 09:00:00', '2026-10-26 11:00:00', 35000.00),
(140, 3, 'Agus Kurniadi Khaer', 'Yogyakarta', 'Solo', '2026-10-26 13:00:00', '2026-10-26 15:00:00', 35000.00),
(141, 12, 'Zupar Lutpi Maliki', 'Malang', 'Surabaya', '2026-10-26 13:30:00', '2026-10-26 16:30:00', 70000.00),
(142, 13, 'Arya Asa Fikarda', 'Yogyakarta', 'Semarang', '2026-10-26 13:30:00', '2026-10-26 17:00:00', 65000.00),
(143, 9, 'Valeno Axel Pramono', 'Bandung', 'Jakarta', '2026-10-26 14:00:00', '2026-10-26 17:30:00', 110000.00),
(144, 10, 'Hardianto', 'Cirebon', 'Jakarta', '2026-10-26 14:00:00', '2026-10-26 19:00:00', 90000.00),
(145, 3, 'Agus Kurniadi Khaer', 'Solo', 'Yogyakarta', '2026-10-26 16:30:00', '2026-10-26 18:30:00', 35000.00),
(146, 3, 'Lutpi Aceh', 'Yogyakarta', 'Solo', '2026-10-27 06:00:00', '2026-10-27 08:00:00', 35000.00),
(147, 10, 'Salomon', 'Jakarta', 'Cirebon', '2026-10-27 06:00:00', '2026-10-27 11:00:00', 90000.00),
(148, 13, 'Hardianto', 'Semarang', 'Yogyakarta', '2026-10-27 06:30:00', '2026-10-27 10:00:00', 65000.00),
(149, 12, 'Agus Kurniadi Khaer', 'Surabaya', 'Malang', '2026-10-27 07:00:00', '2026-10-27 10:00:00', 70000.00),
(150, 3, 'Lutpi Aceh', 'Solo', 'Yogyakarta', '2026-10-27 09:00:00', '2026-10-27 11:00:00', 35000.00),
(151, 8, 'Valeno Axel Pramono', 'Jakarta', 'Bandung', '2026-10-27 09:00:00', '2026-10-27 12:15:00', 175000.00),
(152, 11, 'Zupar Lutpi Maliki', 'Yogyakarta', 'Surabaya', '2026-10-27 09:00:00', '2026-10-27 15:00:00', 260000.00),
(153, 3, 'Lutpi Aceh', 'Yogyakarta', 'Solo', '2026-10-27 13:00:00', '2026-10-27 15:00:00', 35000.00),
(154, 12, 'Agus Kurniadi Khaer', 'Malang', 'Surabaya', '2026-10-27 13:30:00', '2026-10-27 16:30:00', 70000.00),
(155, 13, 'Hardianto', 'Yogyakarta', 'Semarang', '2026-10-27 13:30:00', '2026-10-27 17:00:00', 65000.00),
(156, 10, 'Salomon', 'Cirebon', 'Jakarta', '2026-10-27 14:00:00', '2026-10-27 19:00:00', 90000.00),
(157, 8, 'Valeno Axel Pramono', 'Bandung', 'Jakarta', '2026-10-27 15:30:00', '2026-10-27 18:45:00', 175000.00),
(158, 3, 'Lutpi Aceh', 'Solo', 'Yogyakarta', '2026-10-27 16:30:00', '2026-10-27 18:30:00', 35000.00),
(159, 7, 'Agus Kurniadi Khaer', 'Malang', 'Surabaya', '2026-10-20 05:00:00', '2026-10-20 08:00:00', 70000.00),
(160, 2, 'Agus Kurniadi Khaer', 'Yogyakarta', 'Surabaya', '2026-10-20 17:00:00', '2026-10-20 23:30:00', 260000.00),
(161, 6, 'Lutpi Aceh', 'Jakarta', 'Bandung', '2026-10-19 16:30:00', '2026-10-19 19:45:00', 175000.00),
(162, 8, 'Agus Kurniadi Khaer', 'Jakarta', 'Bandung', '2026-10-19 18:30:00', '2026-10-19 21:45:00', 175000.00),
(163, 2, 'Lutpi Aceh', 'Jakarta', 'Bandung', '2026-10-19 20:30:00', '2026-10-19 23:45:00', 175000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `nama` varchar(150) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(150) NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','kondektur','penumpang') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nama`, `username`, `email`, `no_hp`, `password`, `role`) VALUES
(3, 'Valeno Axel Pramono', 'user3', 'valeno@gmail.com', NULL, '$2y$10$a5.lryAZMp0I7sABegEsO.P4PkZk0Nxwp7GNnpMHDQ8/U702jLRAu', 'kondektur'),
(4, 'Zupar Lutpi Maliki', 'user4', 'zupar@gmail.com', NULL, '$2y$10$FI9WjP6dhSzFu7bM/E3q/.jZUyqrAeskRTQTOdHii6E7OuNKwHp6G', 'kondektur'),
(5, 'Lutpi Aceh', 'user5', 'lutpi@gmail.com', NULL, '$2y$10$WtByKrG6KloErfyI1FtwWO1CqVWjOLF13PMTquL2n36r5g7Fyw98i', 'kondektur'),
(6, 'Agus Kurniadi Khaer', 'user6', 'agus@gmail.com', NULL, '$2y$10$oz/nTYkBtmNmnh7n2l0kBOVtcNB1wDCvKHSyhVu5ZOgmUMYSsaI32', 'kondektur'),
(7, 'Hardianto', 'user7', 'anto@gmail.com', NULL, '$2y$10$fRUnrHZp6z6Cf1F14btvxubkc4fB2ptNPu55R69FdudbbXXh6tLfC', 'kondektur'),
(8, 'Salomon', 'user8', 'salomon@gmail.com', NULL, '$2y$10$1IlyuuCGo3ySrlqA0SI16eP6i0jvXJW0AExEjHiYAdmF44le./zCS', 'kondektur'),
(9, 'Arya Asa Fikarda', 'user9', 'arya@gmail.com', NULL, '$2y$10$H.N4Lj2CAmkZ2vQ/Q3Wz4uXRlidSfKCgSFOXx876fDjCYLXbtkYyK', 'kondektur'),
(12, 'lista ateu ambarianti', 'lista', 'lista@gmail.com', '087708950844', '$2y$10$PTmtFlFNTHLPHZYNusfXm.4XXtHqq8uyu2okbqJHkpK565GCwugAC', 'penumpang'),
(14, 'Shifa Nur Fauziah', 'sipa', 'shifa@gmail.com', '09876085430322', '$2y$10$OV2W4nd8wExJ836BAmVknOYIE1qqGKC9crdRWL10GnvKSA5z2aOTW', 'penumpang'),
(15, 'Admin', 'admin', 'admin@gmail.com', '087708950844', '$2y$10$FiSIu1qspJNZ0pz1i14m1upbsHQAHGkKzLSrDptzml6At16jBP3GC', 'admin'),
(16, 'Lisna Rinjani', 'lisna', 'lisna@gmail.com', '08716524317862', '$2y$10$qTpsSWAFys9JZsrYzVuGZORKrvdG90ykveZK4qxBrdAsKMrH/cKSq', 'penumpang');

--
-- Indeks untuk tabel yang dibuang
--

--
-- Indeks untuk tabel `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_booking` (`kode_booking`),
  ADD KEY `fk_booking_user` (`user_id`),
  ADD KEY `idx_bookings_batas_bayar` (`batas_bayar`),
  ADD KEY `idx_booking_schedule` (`schedule_id`);

--
-- Indeks untuk tabel `booking_penumpang`
--
ALTER TABLE `booking_penumpang`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unik_kursi_jadwal` (`schedule_id`,`nomor_kursi`),
  ADD UNIQUE KEY `kode_tiket` (`kode_tiket`),
  ADD KEY `idx_penumpang_booking` (`booking_id`);

--
-- Indeks untuk tabel `buses`
--
ALTER TABLE `buses`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_payment_booking` (`booking_id`);

--
-- Indeks untuk tabel `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_schedule_bus` (`bus_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT untuk tabel `booking_penumpang`
--
ALTER TABLE `booking_penumpang`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT untuk tabel `buses`
--
ALTER TABLE `buses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT untuk tabel `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=164;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_booking_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_booking_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `booking_penumpang`
--
ALTER TABLE `booking_penumpang`
  ADD CONSTRAINT `fk_penumpang_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_penumpang_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `fk_schedule_bus` FOREIGN KEY (`bus_id`) REFERENCES `buses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
