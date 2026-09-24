<?php
require_once '../config/database.php';
checkRole('admin');

// 1. Ambil Statistik Pendaftaran
$stmt = $pdo->query("SELECT status, COUNT(id) as total FROM pendaftaran GROUP BY status");
$stats = ['menunggu' => 0, 'diterima' => 0, 'ditolak' => 0];
$total_pendaftar = 0;

while ($row = $stmt->fetch()) {
    $stats[$row['status']] = $row['total'];
    $total_pendaftar += $row['total'];
}

// 2. Ambil 5 Pendaftar Terbaru
$stmt = $pdo->query("
    SELECT p.id as pendaftaran_id, p.periode, p.status, p.tanggal_daftar, pst.nama, pst.institusi 
    FROM pendaftaran p
    JOIN peserta pst ON p.peserta_id = pst.id
    ORDER BY p.tanggal_daftar DESC, p.id DESC
    LIMIT 5
");
$pendaftar_terbaru = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - SIMMAG BPS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <!-- Sidebar Admin -->
    <aside class="w-64 bg-slate-800 text-white flex flex-col hidden md:flex">
        <div class="p-6 text-xl font-bold border-b border-slate-700 flex items-center gap-2">
            <i data-lucide="bar-chart-2" class="text-blue-400"></i> SIMMAG BPS
        </div>
        <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
            <a href="dashboard.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-600 text-white transition">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
            </a>
            <a href="pendaftar.php" class="flex items-center gap-3 p-3 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition">
                <i data-lucide="users" class="w-5 h-5"></i> Data Pendaftar
            </a>
            <a href="kuota.php" class="flex items-center gap-3 p-3 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition">
                <i data-lucide="pie-chart" class="w-5 h-5"></i> Kuota Magang
            </a>
            <a href="penempatan.php" class="flex items-center gap-3 p-3 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition">
                <i data-lucide="map-pin" class="w-5 h-5"></i> Penempatan
            </a>
            <a href="export_excel.php" class="flex items-center gap-3 p-3 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition">
                <i data-lucide="download" class="w-5 h-5"></i> Export Laporan
            </a>
            <a href="../logout.php" class="flex items-center gap-3 p-3 mt-8 rounded-lg text-red-400 hover:bg-red-500 hover:text-white transition">
                <i data-lucide="log-out" class="w-5 h-5"></i> Logout
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col overflow-hidden relative">
        <header class="bg-white border-b border-gray-200 p-4 px-8 flex justify-between items-center z-10">
            <h1 class="text-xl font-semibold text-gray-800">Dashboard Administrator</h1>
            <div class="flex items-center gap-3">
                <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($_SESSION['name']) ?></span>
                <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold">
                    A
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8">
            <!-- STATISTIK -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-blue-500">
                    <p class="text-sm text-gray-500 mb-1">Total Pendaftar</p>
                    <p class="text-2xl font-bold text-gray-800"><?= $total_pendaftar ?></p>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-yellow-500">
                    <p class="text-sm text-gray-500 mb-1">Menunggu Verifikasi</p>
                    <p class="text-2xl font-bold text-gray-800"><?= $stats['menunggu'] ?></p>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-green-500">
                    <p class="text-sm text-gray-500 mb-1">Diterima</p>
                    <p class="text-2xl font-bold text-gray-800"><?= $stats['diterima'] ?></p>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-red-500">
                    <p class="text-sm text-gray-500 mb-1">Ditolak</p>
                    <p class="text-2xl font-bold text-gray-800"><?= $stats['ditolak'] ?></p>
                </div>
            </div>

            <!-- TABEL PENDAFTAR TERBARU -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h2 class="text-lg font-bold text-gray-800">Pendaftar Terbaru</h2>
                    <a href="pendaftar.php" class="text-sm text-blue-600 hover:underline font-medium">Lihat Semua Data</a>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="bg-white text-gray-500 border-b">
                                <th class="p-4 font-medium">Tanggal Daftar</th>
                                <th class="p-4 font-medium">Nama / Institusi</th>
                                <th class="p-4 font-medium">Periode</th>
                                <th class="p-4 font-medium">Status</th>
                                <th class="p-4 font-medium text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($pendaftar_terbaru) > 0): ?>
                                <?php foreach ($pendaftar_terbaru as $row): ?>
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="p-4 text-gray-700"><?= date('d M Y', strtotime($row['tanggal_daftar'])) ?></td>
                                        <td class="p-4">
                                            <p class="font-bold text-gray-800"><?= htmlspecialchars($row['nama']) ?></p>
                                            <p class="text-xs text-gray-500"><?= htmlspecialchars($row['institusi']) ?></p>
                                        </td>
                                        <td class="p-4 text-gray-700"><?= htmlspecialchars($row['periode']) ?></td>
                                        <td class="p-4">
                                            <?php if($row['status'] == 'menunggu'): ?>
                                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Menunggu</span>
                                            <?php elseif($row['status'] == 'diterima'): ?>
                                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Diterima</span>
                                            <?php else: ?>
                                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">Ditolak</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-4 text-center">
                                            <a href="verifikasi.php?id=<?= $row['pendaftaran_id'] ?>" class="inline-flex items-center gap-1 bg-blue-50 text-blue-600 px-3 py-1.5 rounded-lg hover:bg-blue-100 font-medium transition">
                                                <i data-lucide="eye" class="w-4 h-4"></i> Detail
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="p-8 text-center text-gray-500">Belum ada data pendaftar.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script> lucide.createIcons(); </script>
</body>
</html>