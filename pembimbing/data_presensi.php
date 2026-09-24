<?php
session_start();
require_once '../config/database.php';

// Pastikan fungsi checkRole sudah ada (atau gunakan logika manual jika belum ada)
if (!function_exists('checkRole')) {
    function checkRole($role) {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
            header("Location: ../login.php");
            exit;
        }
    }
}
checkRole('pembimbing');

$user_id = $_SESSION['user_id'];

// 1. Ambil ID Pembimbing berdasarkan akun yang sedang login
$stmt_pem = $pdo->prepare("SELECT id FROM pembimbing WHERE user_id = ?");
$stmt_pem->execute([$user_id]);
$pembimbing = $stmt_pem->fetch();

if (!$pembimbing) {
    die("Data profil pembimbing belum lengkap. Hubungi Admin.");
}
$pembimbing_id = $pembimbing['id'];

// 2. Ambil data presensi khusus peserta bimbingannya
// Melakukan JOIN: Presensi -> Peserta -> Penempatan
$query = "
    SELECT pr.tanggal, pr.status, pr.catatan, p.nama AS nama_peserta, p.institusi 
    FROM presensi pr
    JOIN peserta p ON pr.peserta_id = p.id
    JOIN penempatan pen ON pen.peserta_id = p.id
    WHERE pen.pembimbing_id = ?
    ORDER BY pr.tanggal DESC, p.nama ASC
";
$stmt = $pdo->prepare($query);
$stmt->execute([$pembimbing_id]);
$data_presensi = $stmt->fetchAll();

// Hitung rekap singkat
$total_hadir = 0;
$total_izin = 0;
$total_sakit = 0;

foreach ($data_presensi as $row) {
    if ($row['status'] == 'hadir') $total_hadir++;
    elseif ($row['status'] == 'izin') $total_izin++;
    elseif ($row['status'] == 'sakit') $total_sakit++;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Presensi Peserta - SIMMAG BPS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <!-- Sidebar Pembimbing -->
    <aside class="w-64 bg-slate-800 text-white flex flex-col hidden md:flex">
        <div class="p-6 text-xl font-bold border-b border-slate-700 flex items-center gap-2">
            <i data-lucide="bar-chart-2" class="text-blue-400"></i> SIMMAG BPS
        </div>
        <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
            <a href="dashboard.php" class="flex items-center gap-3 p-3 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
            </a>
            <!-- Sesuaikan link ini dengan nama file data peserta Anda -->
            <a href="data_peserta.php" class="flex items-center gap-3 p-3 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition">
                <i data-lucide="users" class="w-5 h-5"></i> Peserta Bimbingan
            </a>
            <a href="logbook.php" class="flex items-center gap-3 p-3 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition">
                <i data-lucide="book-open" class="w-5 h-5"></i> Cek Logbook
            </a>
            <a href="data_presensi.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-600 text-white transition">
                <i data-lucide="calendar-check" class="w-5 h-5"></i> Data Presensi
            </a>
            <a href="../logout.php" class="flex items-center gap-3 p-3 mt-8 rounded-lg text-red-400 hover:bg-red-500 hover:text-white transition">
                <i data-lucide="log-out" class="w-5 h-5"></i> Logout
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col overflow-hidden relative">
        <header class="bg-white border-b border-gray-200 p-4 px-8 flex justify-between items-center z-10">
            <h1 class="text-xl font-semibold text-gray-800">Pemantauan Presensi</h1>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold">
                    <?= substr($_SESSION['name'], 0, 1) ?>
                </div>
                <span class="text-sm font-medium text-gray-700">Pembimbing: <?= htmlspecialchars($_SESSION['name']) ?></span>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8">
            
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-800">Rekapitulasi Kehadiran</h2>
                <p class="text-gray-500 text-sm mt-1">Data presensi harian dari seluruh mahasiswa di bawah bimbingan Anda.</p>
            </div>

            <!-- Kartu Statistik Singkat -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500 mb-1">Total Rekaman</p>
                    <p class="text-3xl font-bold text-gray-800"><?= count($data_presensi) ?></p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 border-l-4 border-green-500">
                    <p class="text-sm text-gray-500 mb-1">Total Hadir</p>
                    <p class="text-3xl font-bold text-green-600"><?= $total_hadir ?></p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 border-l-4 border-yellow-500">
                    <p class="text-sm text-gray-500 mb-1">Total Izin</p>
                    <p class="text-3xl font-bold text-yellow-600"><?= $total_izin ?></p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 border-l-4 border-red-500">
                    <p class="text-sm text-gray-500 mb-1">Total Sakit</p>
                    <p class="text-3xl font-bold text-red-600"><?= $total_sakit ?></p>
                </div>
            </div>

            <!-- Tabel Data Presensi -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 border-b">
                                <th class="px-6 py-4 font-semibold">No</th>
                                <th class="px-6 py-4 font-semibold">Tanggal</th>
                                <th class="px-6 py-4 font-semibold">Nama Peserta</th>
                                <th class="px-6 py-4 font-semibold">Institusi</th>
                                <th class="px-6 py-4 font-semibold">Status</th>
                                <th class="px-6 py-4 font-semibold">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (count($data_presensi) > 0): ?>
                                <?php $no = 1; foreach ($data_presensi as $row): ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 font-medium text-gray-500"><?= $no++ ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?= date('d M Y', strtotime($row['tanggal'])) ?>
                                        </td>
                                        <td class="px-6 py-4 font-bold text-gray-800">
                                            <?= htmlspecialchars($row['nama_peserta']) ?>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600">
                                            <?= htmlspecialchars($row['institusi'] ?? '-') ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($row['status'] == 'hadir'): ?>
                                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">
                                                    <i data-lucide="check" class="w-3 h-3"></i> Hadir
                                                </span>
                                            <?php elseif ($row['status'] == 'izin'): ?>
                                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-700">
                                                    <i data-lucide="info" class="w-3 h-3"></i> Izin
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700">
                                                    <i data-lucide="thermometer" class="w-3 h-3"></i> Sakit
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600 truncate max-w-xs" title="<?= htmlspecialchars($row['catatan']) ?>">
                                            <?= htmlspecialchars($row['catatan']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <i data-lucide="calendar-off" class="w-12 h-12 text-gray-300 mb-3"></i>
                                            <p>Belum ada data presensi dari mahasiswa bimbingan Anda.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>