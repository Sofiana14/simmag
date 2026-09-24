-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 21 Sep 2026 pada 04.47
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `simmag_bps`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `dokumen`
--

CREATE TABLE `dokumen` (
  `id` int(11) NOT NULL,
  `peserta_id` int(11) DEFAULT NULL,
  `pendaftaran_id` int(11) DEFAULT NULL,
  `jenis_dokumen` enum('CV','SURAT_PENGANTAR','LOA','DOKUMENTASI') DEFAULT NULL,
  `nama_file` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `dokumen`
--

INSERT INTO `dokumen` (`id`, `peserta_id`, `pendaftaran_id`, `jenis_dokumen`, `nama_file`, `file_path`, `uploaded_at`) VALUES
(1, 3, 1, 'CV', 'Logbook_ Minggu (Ke-1)_230103039_Sofiana Dewi Larasati.pdf', '../uploads/dokumen/1789346788_CV_Logbook__Minggu__Ke_1__230103039_Sofiana_Dewi_Larasati.pdf', '2026-09-14 00:46:28'),
(2, 3, 1, 'SURAT_PENGANTAR', 'Logbook_ Minggu (Ke-1)_230103039_Sofiana Dewi Larasati.pdf', '../uploads/dokumen/1789346788_Surat_Logbook__Minggu__Ke_1__230103039_Sofiana_Dewi_Larasati.pdf', '2026-09-14 00:46:28');

-- --------------------------------------------------------

--
-- Struktur dari tabel `loa`
--

CREATE TABLE `loa` (
  `id` int(11) NOT NULL,
  `peserta_id` int(11) DEFAULT NULL,
  `nomor_surat` varchar(100) DEFAULT NULL,
  `tanggal_surat` date DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `loa`
--

INSERT INTO `loa` (`id`, `peserta_id`, `nomor_surat`, `tanggal_surat`, `file_path`, `uploaded_by`, `uploaded_at`) VALUES
(1, 2, '123/BPS/MAGANG', '2026-09-07', '../uploads/loa/1788753726_LOA_CETAK_INFOGRAFIS_RINGKASAN_KAJIAN_.pdf', 1, '2026-09-07 04:02:06'),
(2, 3, '123/BPS/MAGANG', '2026-09-14', '../uploads/loa/1789347074_LOA_Logbook__Minggu__Ke_1__230103039_Sofiana_Dewi_Larasati.pdf', 1, '2026-09-14 00:51:14');

-- --------------------------------------------------------

--
-- Struktur dari tabel `logbook`
--

CREATE TABLE `logbook` (
  `id` int(11) NOT NULL,
  `peserta_id` int(11) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `judul_kegiatan` varchar(150) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `hasil` text DEFAULT NULL,
  `kendala` text DEFAULT NULL,
  `solusi` text DEFAULT NULL,
  `dokumentasi` varchar(255) DEFAULT NULL,
  `status` enum('menunggu','disetujui','revisi') DEFAULT 'menunggu',
  `catatan_pembimbing` text DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pembimbing`
--

CREATE TABLE `pembimbing` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `nip` varchar(50) DEFAULT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `tim_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pembimbing`
--

INSERT INTO `pembimbing` (`id`, `user_id`, `nip`, `nama`, `tim_id`) VALUES
(1, NULL, '198001012005011001', 'Budi Santoso, S.ST', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `pendaftaran`
--

CREATE TABLE `pendaftaran` (
  `id` int(11) NOT NULL,
  `peserta_id` int(11) DEFAULT NULL,
  `nomor_pendaftaran` varchar(50) DEFAULT NULL,
  `tanggal_daftar` date DEFAULT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `periode` varchar(50) DEFAULT NULL,
  `status` enum('menunggu','diterima','ditolak') DEFAULT 'menunggu',
  `alasan_penolakan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pendaftaran`
--

INSERT INTO `pendaftaran` (`id`, `peserta_id`, `nomor_pendaftaran`, `tanggal_daftar`, `tanggal_mulai`, `tanggal_selesai`, `periode`, `status`, `alasan_penolakan`, `created_at`) VALUES
(1, 3, 'MAG-20260914-1463', '2026-09-14', '2026-09-14', '2026-09-21', 'September 2026', 'diterima', NULL, '2026-09-14 00:46:28');

-- --------------------------------------------------------

--
-- Struktur dari tabel `penempatan`
--

CREATE TABLE `penempatan` (
  `id` int(11) NOT NULL,
  `peserta_id` int(11) DEFAULT NULL,
  `nama_tim` varchar(150) DEFAULT NULL,
  `nama_pembimbing` varchar(150) DEFAULT NULL,
  `tanggal_penempatan` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `peserta`
--

CREATE TABLE `peserta` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `nim` varchar(50) DEFAULT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `tempat_lahir` varchar(50) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `institusi` varchar(100) DEFAULT NULL,
  `fakultas` varchar(100) DEFAULT NULL,
  `prodi` varchar(100) DEFAULT NULL,
  `semester` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `peserta`
--

INSERT INTO `peserta` (`id`, `user_id`, `nim`, `nama`, `tempat_lahir`, `tanggal_lahir`, `jenis_kelamin`, `alamat`, `no_hp`, `institusi`, `fakultas`, `prodi`, `semester`) VALUES
(2, 4, NULL, 'Sofiana', NULL, NULL, NULL, NULL, '088216214882', NULL, NULL, NULL, NULL),
(3, 3, '230103029', NULL, NULL, NULL, NULL, NULL, '088216214882', 'UDB', 'Ilmu Komputer', 'Teknik Informatika', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `presensi`
--

CREATE TABLE `presensi` (
  `id` int(11) NOT NULL,
  `peserta_id` int(11) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `status` enum('hadir','izin','sakit') DEFAULT NULL,
  `catatan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tim_kerja`
--

CREATE TABLE `tim_kerja` (
  `id` int(11) NOT NULL,
  `nama_tim` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','peserta','pembimbing') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `otp_code` varchar(6) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`, `otp_code`, `otp_expiry`) VALUES
(1, 'Admin BPS', 'admin@bps.go.id', '$2y$10$LADTmwM2G.YOy31zXvUSr.EY4uOy2148CuhbvJGXYdSHzUBK8mp6C', 'admin', '2026-09-10 01:19:02', NULL, NULL),
(2, 'Budi Santoso, S.ST', 'pembimbing@bps.go.id', '$2y$10$LADTmwM2G.YOy31zXvUSr.EY4uOy2148CuhbvJGXYdSHzUBK8mp6C', 'pembimbing', '2026-09-10 01:19:02', NULL, NULL),
(3, 'Mahasiswa Magang', 'peserta@student.ac.id', '$2y$10$LADTmwM2G.YOy31zXvUSr.EY4uOy2148CuhbvJGXYdSHzUBK8mp6C', 'peserta', '2026-09-10 01:19:02', NULL, NULL),
(4, 'Sofiana', 'sofiana@udb.ac.id', '$2y$10$Ds.kELyQxGCnCqobNsUYFun2FE1GvTJqK8F9SO9tLK0ZJcGm9VIXy', 'peserta', '2026-09-11 01:32:45', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `dokumen`
--
ALTER TABLE `dokumen`
  ADD PRIMARY KEY (`id`),
  ADD KEY `peserta_id` (`peserta_id`),
  ADD KEY `pendaftaran_id` (`pendaftaran_id`);

--
-- Indeks untuk tabel `loa`
--
ALTER TABLE `loa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `peserta_id` (`peserta_id`);

--
-- Indeks untuk tabel `logbook`
--
ALTER TABLE `logbook`
  ADD PRIMARY KEY (`id`),
  ADD KEY `peserta_id` (`peserta_id`);

--
-- Indeks untuk tabel `pembimbing`
--
ALTER TABLE `pembimbing`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `tim_id` (`tim_id`);

--
-- Indeks untuk tabel `pendaftaran`
--
ALTER TABLE `pendaftaran`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nomor_pendaftaran` (`nomor_pendaftaran`),
  ADD KEY `peserta_id` (`peserta_id`);

--
-- Indeks untuk tabel `penempatan`
--
ALTER TABLE `penempatan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `peserta_id` (`peserta_id`);

--
-- Indeks untuk tabel `peserta`
--
ALTER TABLE `peserta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `presensi`
--
ALTER TABLE `presensi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `peserta_id` (`peserta_id`);

--
-- Indeks untuk tabel `tim_kerja`
--
ALTER TABLE `tim_kerja`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `dokumen`
--
ALTER TABLE `dokumen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `loa`
--
ALTER TABLE `loa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `logbook`
--
ALTER TABLE `logbook`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pembimbing`
--
ALTER TABLE `pembimbing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `pendaftaran`
--
ALTER TABLE `pendaftaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `penempatan`
--
ALTER TABLE `penempatan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `peserta`
--
ALTER TABLE `peserta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `presensi`
--
ALTER TABLE `presensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tim_kerja`
--
ALTER TABLE `tim_kerja`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `dokumen`
--
ALTER TABLE `dokumen`
  ADD CONSTRAINT `dokumen_ibfk_1` FOREIGN KEY (`peserta_id`) REFERENCES `peserta` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dokumen_ibfk_2` FOREIGN KEY (`pendaftaran_id`) REFERENCES `pendaftaran` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `loa`
--
ALTER TABLE `loa`
  ADD CONSTRAINT `loa_ibfk_1` FOREIGN KEY (`peserta_id`) REFERENCES `peserta` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `logbook`
--
ALTER TABLE `logbook`
  ADD CONSTRAINT `logbook_ibfk_1` FOREIGN KEY (`peserta_id`) REFERENCES `peserta` (`id`);

--
-- Ketidakleluasaan untuk tabel `pembimbing`
--
ALTER TABLE `pembimbing`
  ADD CONSTRAINT `pembimbing_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `pembimbing_ibfk_2` FOREIGN KEY (`tim_id`) REFERENCES `tim_kerja` (`id`);

--
-- Ketidakleluasaan untuk tabel `pendaftaran`
--
ALTER TABLE `pendaftaran`
  ADD CONSTRAINT `pendaftaran_ibfk_1` FOREIGN KEY (`peserta_id`) REFERENCES `peserta` (`id`);

--
-- Ketidakleluasaan untuk tabel `penempatan`
--
ALTER TABLE `penempatan`
  ADD CONSTRAINT `penempatan_ibfk_1` FOREIGN KEY (`peserta_id`) REFERENCES `peserta` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `peserta`
--
ALTER TABLE `peserta`
  ADD CONSTRAINT `peserta_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `presensi`
--
ALTER TABLE `presensi`
  ADD CONSTRAINT `presensi_ibfk_1` FOREIGN KEY (`peserta_id`) REFERENCES `peserta` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
