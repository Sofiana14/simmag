<?php
// Set zona waktu ke Waktu Indonesia Barat (WIB) agar jam server akurat
date_default_timezone_set('Asia/Jakarta');

require_once '../config/database.php';
checkRole('peserta');

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';
$tanggal_hari_ini = date('Y-m-d');
$waktu_sekarang = date('H:i');

// 1. Ambil Data Profil Peserta & Info Penempatan (Diperbarui untuk PDF)
$stmt = $pdo->prepare("
    SELECT p.id, p.nama, p.institusi, pen.nama_pembimbing 
    FROM peserta p 
    LEFT JOIN penempatan pen ON p.id = pen.peserta_id
    WHERE p.user_id = ?
");
$stmt->execute([$user_id]);
$peserta = $stmt->fetch();

if (!$peserta) {
    die("Data profil belum lengkap. Silakan lengkapi profil terlebih dahulu.");
}
$peserta_id = $peserta['id'];
$nama_user = !empty($_SESSION['name']) ? $_SESSION['name'] : ($peserta['nama'] ?? 'Peserta');
$institusi = htmlspecialchars($peserta['institusi'] ?? '-');
$pembimbing = htmlspecialchars($peserta['nama_pembimbing'] ?? 'Belum Ditentukan');

// 2. Cek apakah hari ini sudah absen
$stmt = $pdo->prepare("SELECT status FROM presensi WHERE peserta_id = ? AND tanggal = ?");
$stmt->execute([$peserta_id, $tanggal_hari_ini]);
$absen_hari_ini = $stmt->fetch();

// 3. Proses Form Presensi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$absen_hari_ini) {
    $status = $_POST['status'];
    $catatan = trim($_POST['catatan']) ?: '-';

    $hari_ini = date('N'); 
    $waktu_post = date('H:i');
    
    $bisa_presensi = false;
    $pesan_error_waktu = "";

    if ($hari_ini >= 1 && $hari_ini <= 4) { 
        if ($waktu_post >= '07:30' && $waktu_post <= '16:00') {
            $bisa_presensi = true;
        } else {
            $pesan_error_waktu = "Gagal! Presensi hari Senin-Kamis hanya dibuka pukul 07:30 - 16:00 WIB. Waktu saat ini: $waktu_post WIB.";
        }
    } elseif ($hari_ini == 5) { 
        if ($waktu_post >= '07:30' && $waktu_post <= '16:30') {
            $bisa_presensi = true;
        } else {
            $pesan_error_waktu = "Gagal! Presensi hari Jumat hanya dibuka pukul 07:30 - 16:30 WIB. Waktu saat ini: $waktu_post WIB.";
        }
    } else { 
        $pesan_error_waktu = "Sistem ditolak! Hari libur (Sabtu/Minggu) tidak dapat melakukan presensi.";
    }

    if (!$bisa_presensi) {
        $error = $pesan_error_waktu;
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO presensi (peserta_id, tanggal, status, catatan) VALUES (?, ?, ?, ?)");
            $stmt->execute([$peserta_id, $tanggal_hari_ini, $status, $catatan]);
            $success = "Presensi hari ini berhasil dicatat pada pukul $waktu_post WIB!";
            $absen_hari_ini = ['status' => $status];
        } catch (Exception $e) {
            $error = "Gagal menyimpan presensi: " . $e->getMessage();
        }
    }
}

// 4. Ambil Data Statistik Presensi
$stmt = $pdo->prepare("SELECT status, COUNT(id) as total FROM presensi WHERE peserta_id = ? GROUP BY status");
$stmt->execute([$peserta_id]);
$stats = ['hadir' => 0, 'izin' => 0, 'sakit' => 0];
$total_hari = 0;

while ($row = $stmt->fetch()) {
    $stats[$row['status']] = $row['total'];
    $total_hari += $row['total'];
}
$persentase = ($total_hari > 0) ? round(($stats['hadir'] / $total_hari) * 100) : 0;

// 5. Ambil Riwayat Presensi
$stmt = $pdo->prepare("SELECT * FROM presensi WHERE peserta_id = ? ORDER BY tanggal DESC");
$stmt->execute([$peserta_id]);
$riwayat_presensi = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Presensi Harian - SIMMAG BPS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Library untuk Generate PDF langsung unduh -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <div id="sidebar-backdrop" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-40 hidden md:hidden transition-opacity"></div>

    <aside id="sidebar-menu" class="w-64 bg-slate-800 text-white flex flex-col fixed inset-y-0 left-0 z-50 transform -translate-x-full transition-transform duration-300 ease-in-out md:translate-x-0 md:static md:flex">
        <div class="h-[73px] px-4 flex items-center justify-between border-b border-slate-700/50 md:border-none">
            <img src="../src/assets/img/logo.png" alt="Logo" class="h-14 max-w-[200px] w-auto object-contain brightness-0 invert">
            <button type="button" onclick="toggleSidebar()" class="p-2 text-slate-400 hover:text-white md:hidden rounded-lg">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
            <a href="dashboard.php" class="flex items-center gap-3 p-3 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
            </a>
            <a href="pendaftaran.php" class="flex items-center gap-3 p-3 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition">
                <i data-lucide="file-text" class="w-5 h-5"></i> Pendaftaran Magang
            </a>
            <a href="logbook.php" class="flex items-center gap-3 p-3 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition">
                <i data-lucide="book-open" class="w-5 h-5"></i> Logbook
            </a>
            <a href="presensi.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-600 text-white transition">
                <i data-lucide="calendar-check" class="w-5 h-5"></i> Presensi
            </a>
            <a href="../logout.php" class="flex items-center gap-3 p-3 mt-8 rounded-lg text-red-400 hover:bg-red-500 hover:text-white transition">
                <i data-lucide="log-out" class="w-5 h-5"></i> Logout
            </a>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col overflow-hidden relative">
        <header class="bg-white border-b border-gray-200 p-4 px-4 md:px-8 flex justify-between items-center z-10 sticky top-0">
            <div class="flex items-center gap-3">
                <button type="button" onclick="toggleSidebar()" class="p-2 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-gray-900 md:hidden border border-gray-200 focus:outline-none transition">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <h1 class="text-xl font-semibold text-gray-800">Presensi Harian</h1>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold">
                    <?= substr($nama_user, 0, 1) ?>
                </div>
                <span class="text-sm font-medium text-gray-700 hidden sm:inline"><?= htmlspecialchars($nama_user) ?></span>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 md:p-8">
            
            <?php if ($success): ?>
                <div class="bg-green-50 text-green-700 p-4 rounded-lg mb-6 shadow-sm flex items-center gap-2 border-l-4 border-green-500">
                    <i data-lucide="check-circle" class="w-5 h-5"></i> <?= $success ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-50 text-red-700 p-4 rounded-lg mb-6 shadow-sm flex items-center gap-2 border-l-4 border-red-500">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4 border-l-4 border-blue-500">
                    <div class="p-3 bg-blue-100 text-blue-600 rounded-lg"><i data-lucide="calendar" class="w-6 h-6"></i></div>
                    <div>
                        <p class="text-sm text-gray-500">Total Kehadiran</p>
                        <p class="text-2xl font-bold text-gray-800"><?= $stats['hadir'] ?> <span class="text-sm font-normal text-gray-500">Hari</span></p>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4 border-l-4 border-yellow-500">
                    <div class="p-3 bg-yellow-100 text-yellow-600 rounded-lg"><i data-lucide="mail-warning" class="w-6 h-6"></i></div>
                    <div>
                        <p class="text-sm text-gray-500">Total Izin</p>
                        <p class="text-2xl font-bold text-gray-800"><?= $stats['izin'] ?> <span class="text-sm font-normal text-gray-500">Hari</span></p>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4 border-l-4 border-red-500">
                    <div class="p-3 bg-red-100 text-red-600 rounded-lg"><i data-lucide="thermometer" class="w-6 h-6"></i></div>
                    <div>
                        <p class="text-sm text-gray-500">Total Sakit</p>
                        <p class="text-2xl font-bold text-gray-800"><?= $stats['sakit'] ?> <span class="text-sm font-normal text-gray-500">Hari</span></p>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4 border-l-4 border-green-500">
                    <div class="p-3 bg-green-100 text-green-600 rounded-lg"><i data-lucide="pie-chart" class="w-6 h-6"></i></div>
                    <div>
                        <p class="text-sm text-gray-500">Persentase</p>
                        <p class="text-2xl font-bold text-green-600"><?= $persentase ?>%</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-5 border-b border-gray-100 bg-gray-50">
                            <h2 class="text-lg font-bold text-gray-800">Isi Presensi Hari Ini</h2>
                        </div>
                        <div class="p-6">
                            <?php if ($absen_hari_ini): ?>
                                <div class="text-center py-6">
                                    <i data-lucide="check-circle-2" class="w-16 h-16 text-green-500 mx-auto mb-4"></i>
                                    <h3 class="text-lg font-bold text-gray-800 mb-1">Sudah Presensi!</h3>
                                    <p class="text-sm text-gray-500 mb-4">Anda telah mengisi kehadiran untuk tanggal <strong><?= date('d M Y') ?></strong> dengan status:</p>
                                    
                                    <?php if ($absen_hari_ini['status'] == 'hadir'): ?>
                                        <span class="inline-block px-4 py-2 rounded-full text-sm font-bold bg-green-100 text-green-800 border border-green-200 uppercase">Hadir</span>
                                    <?php elseif ($absen_hari_ini['status'] == 'izin'): ?>
                                        <span class="inline-block px-4 py-2 rounded-full text-sm font-bold bg-yellow-100 text-yellow-800 border border-yellow-200 uppercase">Izin</span>
                                    <?php else: ?>
                                        <span class="inline-block px-4 py-2 rounded-full text-sm font-bold bg-red-100 text-red-800 border border-red-200 uppercase">Sakit</span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="bg-blue-50 border border-blue-200 p-3 rounded-lg mb-5 text-xs text-blue-800">
                                    <p class="font-bold flex items-center gap-1 mb-1"><i data-lucide="clock" class="w-3 h-3"></i> Jadwal Presensi:</p>
                                    <p class="ml-4">Senin - Kamis: 07:30 - 16:00 WIB</p>
                                    <p class="ml-4">Jumat: 07:30 - 16:30 WIB</p>
                                </div>
                                <form action="" method="POST">
                                    <div class="mb-4">
                                        <label class="block text-sm text-gray-500 mb-1">Waktu Saat Ini</label>
                                        <input type="text" value="<?= date('d F Y') ?> | <?= $waktu_sekarang ?> WIB" class="w-full border border-gray-200 p-2.5 rounded-lg bg-gray-50 text-blue-600 font-bold cursor-not-allowed" readonly>
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Status Kehadiran <span class="text-red-500">*</span></label>
                                        <div class="space-y-2">
                                            <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition has-[:checked]:bg-green-50 has-[:checked]:border-green-500">
                                                <input type="radio" name="status" value="hadir" class="w-4 h-4 text-green-600" required>
                                                <span class="ml-3 font-medium text-gray-700">Hadir di Kantor / Penugasan</span>
                                            </label>
                                            <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition has-[:checked]:bg-yellow-50 has-[:checked]:border-yellow-500">
                                                <input type="radio" name="status" value="izin" class="w-4 h-4 text-yellow-600">
                                                <span class="ml-3 font-medium text-gray-700">Izin (Keperluan Khusus)</span>
                                            </label>
                                            <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition has-[:checked]:bg-red-50 has-[:checked]:border-red-500">
                                                <input type="radio" name="status" value="sakit" class="w-4 h-4 text-red-600">
                                                <span class="ml-3 font-medium text-gray-700">Sakit</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="mb-6">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Tambahan (Opsional)</label>
                                        <textarea name="catatan" rows="3" class="w-full border p-3 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm" placeholder="Contoh: Izin bimbingan skripsi / Sakit demam..."></textarea>
                                    </div>
                                    <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2.5 rounded-lg hover:bg-blue-700 transition flex justify-center items-center gap-2">
                                        <i data-lucide="send" class="w-4 h-4"></i> Simpan Presensi
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden h-full flex flex-col">
                        <div class="p-5 border-b border-gray-100 bg-gray-50 flex justify-between items-center flex-wrap gap-3">
                            <h2 class="text-lg font-bold text-gray-800">Riwayat Presensi Anda</h2>
                            
                            <!-- TOMBOL UNDUH PDF LANGSUNG -->
                            <button id="btn-pdf" onclick="generatePDF()" class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors shadow-sm focus:ring-2 focus:ring-blue-300">
                                <i data-lucide="printer" class="w-4 h-4"></i> 
                                <span>Download Rekap presensi</span>
                            </button>
                        </div>
                        
                        <div class="overflow-x-auto flex-1">
                            <table class="w-full text-left text-sm border-collapse">
                                <thead>
                                    <tr class="bg-white text-gray-500 border-b sticky top-0">
                                        <th class="p-4 font-medium whitespace-nowrap">Tanggal</th>
                                        <th class="p-4 font-medium">Status</th>
                                        <th class="p-4 font-medium">Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($riwayat_presensi) > 0): ?>
                                        <?php foreach ($riwayat_presensi as $row): ?>
                                            <tr class="border-b hover:bg-gray-50">
                                                <td class="p-4 font-medium text-gray-800 whitespace-nowrap">
                                                    <?= date('l, d M Y', strtotime($row['tanggal'])) ?>
                                                </td>
                                                <td class="p-4">
                                                    <?php if ($row['status'] == 'hadir'): ?>
                                                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"><i data-lucide="check" class="w-3 h-3 inline mr-1"></i> Hadir</span>
                                                    <?php elseif ($row['status'] == 'izin'): ?>
                                                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"><i data-lucide="info" class="w-3 h-3 inline mr-1"></i> Izin</span>
                                                    <?php else: ?>
                                                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800"><i data-lucide="thermometer" class="w-3 h-3 inline mr-1"></i> Sakit</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="p-4 text-gray-600 truncate max-w-xs">
                                                    <?= htmlspecialchars($row['catatan']) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="3" class="p-8 text-center text-gray-500">Belum ada riwayat presensi.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- =========================================================================
         KONTEN TERSEMBUNYI KHUSUS UNTUK FORMAT PDF A4 (HITAM PUTIH & RAPI) 
         ========================================================================= -->
    <div id="pdf-container" style="display: none;">
        <!-- Lebar disetel 190mm (Kertas A4 = 210mm dikurangi margin) agar tidak kepotong -->
        <div id="pdf-content" style="padding: 10px; font-family: 'Times New Roman', Times, serif; color: #000; background: #fff; width: 190mm; box-sizing: border-box;">
            
            <!-- KOP SURAT (Hitam Putih) -->
            <div style="text-align: center; border-bottom: 3px solid #000; padding-bottom: 15px; margin-bottom: 20px;">
                <h1 style="margin: 0; font-size: 22px; color: #000; text-transform: uppercase;">REKAPITULASI PRESENSI MAGANG</h1>
                <p style="margin: 5px 0 0; font-size: 16px; font-weight: bold; color: #000;">BADAN PUSAT STATISTIK KOTA YOGYAKARTA</p>
            </div>

            <!-- INFO PESERTA -->
            <table style="width: 100%; margin-bottom: 25px; font-size: 14px; color: #000; border: none;">
                <tr><td style="width: 160px; font-weight: bold; padding: 5px 0;">Nama Peserta</td><td>: <?= htmlspecialchars($nama_user) ?></td></tr>
                <tr><td style="font-weight: bold; padding: 5px 0;">Asal Institusi</td><td>: <?= $institusi ?></td></tr>
                <tr><td style="font-weight: bold; padding: 5px 0;">Pembimbing BPS</td><td>: <?= $pembimbing ?></td></tr>
                <tr><td style="font-weight: bold; padding: 5px 0;">Tanggal Cetak</td><td>: <?= date('d F Y') ?></td></tr>
            </table>

            <!-- REKAPITULASI STATISTIK (Kotak Hitam Putih) -->
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 25px; font-size: 14px; color: #000;">
                <tr>
                    <td style="border: 1px solid #000; padding: 12px; text-align: center; width: 25%;">
                        <div style="font-weight: bold; text-transform: uppercase; margin-bottom: 8px;">Total Kehadiran</div>
                        <div style="font-size: 18px; font-weight: bold;"><?= $stats['hadir'] ?> Hari</div>
                    </td>
                    <td style="border: 1px solid #000; padding: 12px; text-align: center; width: 25%;">
                        <div style="font-weight: bold; text-transform: uppercase; margin-bottom: 8px;">Total Izin</div>
                        <div style="font-size: 18px; font-weight: bold;"><?= $stats['izin'] ?> Hari</div>
                    </td>
                    <td style="border: 1px solid #000; padding: 12px; text-align: center; width: 25%;">
                        <div style="font-weight: bold; text-transform: uppercase; margin-bottom: 8px;">Total Sakit</div>
                        <div style="font-size: 18px; font-weight: bold;"><?= $stats['sakit'] ?> Hari</div>
                    </td>
                    <td style="border: 1px solid #000; padding: 12px; text-align: center; width: 25%;">
                        <div style="font-weight: bold; text-transform: uppercase; margin-bottom: 8px;">Persentase</div>
                        <div style="font-size: 18px; font-weight: bold;"><?= $persentase ?>%</div>
                    </td>
                </tr>
            </table>

            <!-- TABEL DETAIL RIWAYAT PRESENSI -->
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 40px; font-size: 14px; color: #000;">
                <thead>
                    <tr>
                        <th style="border: 1px solid #000; padding: 10px; width: 5%; text-align: center; font-weight: bold;">No</th>
                        <th style="border: 1px solid #000; padding: 10px; width: 20%; text-align: center; font-weight: bold;">Tanggal</th>
                        <th style="border: 1px solid #000; padding: 10px; width: 15%; text-align: center; font-weight: bold;">Status</th>
                        <th style="border: 1px solid #000; padding: 10px; width: 60%; text-align: left; font-weight: bold;">Catatan Kehadiran</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($riwayat_presensi) > 0): ?>
                        <?php 
                        // Urutkan riwayat dari terlama ke terbaru untuk cetak rekap (ASCENDING)
                        $riwayat_cetak = array_reverse($riwayat_presensi);
                        $no = 1; 
                        foreach ($riwayat_cetak as $row): 
                        ?>
                            <tr>
                                <td style="border: 1px solid #000; padding: 8px; text-align: center;"><?= $no++ ?></td>
                                <td style="border: 1px solid #000; padding: 8px; text-align: center;"><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                                <td style="border: 1px solid #000; padding: 8px; text-align: center;"><?= ucfirst($row['status']) ?></td>
                                <td style="border: 1px solid #000; padding: 8px; text-align: left;"><?= htmlspecialchars($row['catatan'] ?: '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="border: 1px solid #000; padding: 15px; text-align: center;">Belum ada riwayat presensi.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- TANDA TANGAN (Hitam Putih & Rapi) -->
                <div style="width: 100%; text-align: right; color: #000; font-size: 14px;">
                <div style="display: inline-block; text-align: center; width: 250px;">
                    <p style="margin: 0;">Yogyakarta, <?= date('d F Y') ?></p>
                    <p style="margin: 0;">Pembimbing Magang,</p>
                    <br><br><br><br>
                    <div style="font-weight: bold; text-decoration: underline; margin-top: 10px;"><?= $pembimbing ?></div>
                </div>
            </div>
            
        </div>
    </div>
    <!-- ========================================================================= -->

    <script>
        lucide.createIcons();
        
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar-menu');
            const backdrop = document.getElementById('sidebar-backdrop');
            sidebar.classList.toggle('-translate-x-full');
            backdrop.classList.toggle('hidden');
        }

        // FUNGSI UNTUK GENERATE DAN DOWNLOAD PDF LANGSUNG (A4)
        function generatePDF() {
            const btn = document.getElementById('btn-pdf');
            const originalText = btn.innerHTML;
            
            // Ubah text tombol menjadi loading
            btn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin"></i> Memproses PDF...';
            lucide.createIcons();
            
            // Tampilkan container PDF yang tersembunyi
            const element = document.getElementById('pdf-content');
            element.parentElement.style.display = 'block';
            
            // Setting format PDF menjadi ukuran A4
            const opt = {
                margin:       10, // Margin kertas (10 mm)
                filename:     'Rekap_Presensi_<?= str_replace(" ", "_", htmlspecialchars($nama_user)) ?>.pdf',
                image:        { type: 'jpeg', quality: 1 },
                html2canvas:  { scale: 2, useCORS: true }, 
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' } 
            };

            // Proses generate dan download otomatis
            html2pdf().set(opt).from(element).save().then(() => {
                // Setelah selesai download, sembunyikan lagi container PDF
                element.parentElement.style.display = 'none';
                
                // Kembalikan tombol seperti semula
                btn.innerHTML = originalText;
                lucide.createIcons();
            }).catch(err => {
                console.error('Error saat membuat PDF:', err);
                element.parentElement.style.display = 'none';
                btn.innerHTML = originalText;
                lucide.createIcons();
                alert('Terjadi kesalahan saat memuat PDF.');
            });
        }
    </script>
</body>
</html>