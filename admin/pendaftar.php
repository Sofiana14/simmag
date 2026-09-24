<?php
require_once '../config/database.php';
checkRole('admin');

// Fitur Filter
$where = "1=1";
$params = [];
$filter_status = $_GET['status'] ?? '';

if ($filter_status) {
    $where .= " AND p.status = ?";
    $params[] = $filter_status;
}

// Ambil seluruh data pendaftar
$stmt = $pdo->prepare("
    SELECT p.id as pendaftaran_id, p.periode, p.status, p.tanggal_daftar, pst.nama, pst.nim, pst.institusi 
    FROM pendaftaran p
    JOIN peserta pst ON p.peserta_id = pst.id
    WHERE $where
    ORDER BY p.tanggal_daftar DESC
");
$stmt->execute($params);
$data_pendaftar = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Pendaftar - SIMMAG BPS</title>
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
            <a href="dashboard.php" class="flex items-center gap-3 p-3 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
            </a>
            <a href="pendaftar.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-600 text-white transition">
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

    <main class="flex-1 flex flex-col overflow-hidden relative">
        <header class="bg-white border-b border-gray-200 p-4 px-8 flex justify-between items-center z-10">
            <h1 class="text-xl font-semibold text-gray-800">Semua Data Pendaftar</h1>
            <div class="flex items-center gap-3">
                <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($_SESSION['name']) ?></span>
                <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold">A</div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h2 class="text-lg font-bold text-gray-800">Daftar Pengajuan Magang</h2>
                    <!-- Filter Status -->
                    <form action="" method="GET" class="flex items-center gap-2">
                        <select name="status" onchange="this.form.submit()" class="border border-gray-300 rounded-lg p-2 text-sm focus:ring-blue-500 outline-none">
                            <option value="">Semua Status</option>
                            <option value="menunggu" <?= $filter_status == 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
                            <option value="diterima" <?= $filter_status == 'diterima' ? 'selected' : '' ?>>Diterima</option>
                            <option value="ditolak" <?= $filter_status == 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                        </select>
                    </form>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="bg-white text-gray-500 border-b">
                                <th class="p-4 font-medium">Tanggal Daftar</th>
                                <th class="p-4 font-medium">Nama / Institusi</th>
                                <th class="p-4 font-medium">NIM</th>
                                <th class="p-4 font-medium">Periode</th>
                                <th class="p-4 font-medium">Status</th>
                                <th class="p-4 font-medium text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($data_pendaftar) > 0): ?>
                                <?php foreach ($data_pendaftar as $row): ?>
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="p-4 text-gray-700"><?= date('d M Y', strtotime($row['tanggal_daftar'])) ?></td>
                                        <td class="p-4">
                                            <p class="font-bold text-gray-800"><?= htmlspecialchars($row['nama']) ?></p>
                                            <p class="text-xs text-gray-500"><?= htmlspecialchars($row['institusi']) ?></p>
                                        </td>
                                        <td class="p-4 text-gray-700"><?= htmlspecialchars($row['nim']) ?></td>
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
                                <tr><td colspan="6" class="p-8 text-center text-gray-500">Tidak ada data pendaftar yang sesuai.</td></tr>
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