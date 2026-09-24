<?php
require_once '../config/database.php';
checkRole('admin');

// Ambil rekapitulasi jumlah pendaftar per periode yang TIDAK ditolak
$stmt = $pdo->query("
    SELECT periode, COUNT(id) as total_terisi 
    FROM pendaftaran 
    WHERE status != 'ditolak' 
    GROUP BY periode 
    ORDER BY MIN(tanggal_mulai) DESC
");
$data_kuota = $stmt->fetchAll();

$kuota_maksimal = 6; // Sesuai kesepakatan default sistem
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Monitoring Kuota - SIMMAG BPS</title>
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
            <a href="pendaftar.php" class="flex items-center gap-3 p-3 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition">
                <i data-lucide="users" class="w-5 h-5"></i> Data Pendaftar
            </a>
            <a href="kuota.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-600 text-white transition">
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
            <h1 class="text-xl font-semibold text-gray-800">Monitoring Kuota Magang</h1>
            <div class="flex items-center gap-3">
                <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($_SESSION['name']) ?></span>
                <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold">A</div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8">
            <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg mb-6 flex items-start gap-3">
                <i data-lucide="info" class="w-5 h-5 text-blue-600 mt-0.5"></i>
                <div>
                    <p class="text-blue-800 font-bold">Informasi Sistem</p>
                    <p class="text-sm text-blue-700 mt-1">Kuota default sistem adalah <strong><?= $kuota_maksimal ?> peserta per bulan</strong>. Jika pendaftar pada suatu periode mencapai batas ini, sistem akan otomatis menolak pendaftar baru untuk periode tersebut.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (count($data_kuota) > 0): ?>
                    <?php foreach ($data_kuota as $row): 
                        $terisi = $row['total_terisi'];
                        $persentase = ($terisi / $kuota_maksimal) * 100;
                        if($persentase > 100) $persentase = 100;
                        
                        $is_full = ($terisi >= $kuota_maksimal);
                    ?>
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 relative overflow-hidden">
                            <?php if ($is_full): ?>
                                <div class="absolute top-0 right-0 bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-bl-lg">PENUH</div>
                            <?php endif; ?>
                            
                            <h3 class="text-lg font-bold text-gray-800 mb-4"><?= htmlspecialchars($row['periode']) ?></h3>
                            
                            <div class="flex justify-between items-end mb-2">
                                <span class="text-sm text-gray-500">Kapasitas Terisi</span>
                                <span class="text-xl font-bold <?= $is_full ? 'text-red-600' : 'text-gray-800' ?>"><?= $terisi ?> <span class="text-sm font-normal text-gray-500">/ <?= $kuota_maksimal ?></span></span>
                            </div>
                            
                            <div class="w-full bg-gray-200 rounded-full h-2.5 mb-2">
                                <div class="<?= $is_full ? 'bg-red-500' : 'bg-blue-600' ?> h-2.5 rounded-full" style="width: <?= $persentase ?>%"></div>
                            </div>
                            
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <a href="pendaftar.php" class="text-sm text-blue-600 hover:underline font-medium flex items-center gap-1">
                                    Lihat Pendaftar <i data-lucide="arrow-right" class="w-4 h-4"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full bg-white p-8 rounded-xl shadow-sm border border-gray-100 text-center">
                        <i data-lucide="inbox" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                        <p class="text-gray-500">Belum ada data pendaftaran yang tercatat.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script> lucide.createIcons(); </script>
</body>
</html>