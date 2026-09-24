<?php
require_once '../config/database.php';

// Pastikan fungsi checkRole sudah ada di database.php atau file auth lainnya
if (!function_exists('checkRole')) {
    function checkRole($role) {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
            header("Location: ../login.php");
            exit;
        }
    }
}
// 1. WAJIB role pembimbing
checkRole('pembimbing');

$user_id = $_SESSION['user_id'];
$nama_pembimbing_login = $_SESSION['name']; 

$peserta_bimbingan = [];
$total_peserta = 0;

try {
    // 2. Ambil daftar peserta bimbingan berdasarkan kecocokan nama pembimbing
    $stmt = $pdo->prepare("
        SELECT pn.*, pst.nama as nama_peserta, pst.nim, pst.institusi, p.periode 
        FROM penempatan pn
        JOIN peserta pst ON pn.peserta_id = pst.id
        JOIN pendaftaran p ON pst.id = p.peserta_id
        WHERE pn.nama_pembimbing LIKE ?
        ORDER BY pst.nama ASC
    ");
    $stmt->execute(["%$nama_pembimbing_login%"]);
    $peserta_bimbingan = $stmt->fetchAll();

    $total_peserta = count($peserta_bimbingan);
} catch (PDOException $e) {
    // Menangkap error jika ada kesalahan query
    $error_database = "Terjadi kesalahan sistem: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Pembimbing - SIMMAG BPS</title>
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
            <a href="dashboard.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-600 text-white transition">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
            </a>
            <a href="logbook.php" class="flex items-center gap-3 p-3 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition">
                <i data-lucide="book-open" class="w-5 h-5"></i> Validasi Logbook
            </a>
            <a href="../logout.php" class="flex items-center gap-3 p-3 mt-8 rounded-lg text-red-400 hover:bg-red-500 hover:text-white transition">
                <i data-lucide="log-out" class="w-5 h-5"></i> Logout
            </a>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col overflow-hidden relative">
        <header class="bg-white border-b border-gray-200 p-4 px-8 flex justify-between items-center z-10">
            <h1 class="text-xl font-semibold text-gray-800">Dashboard Pembimbing Lapangan</h1>
            <div class="flex items-center gap-3">
                <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($_SESSION['name']) ?></span>
                <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold">
                    <?= substr($_SESSION['name'], 0, 1) ?>
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8">
            
            <?php if(isset($error_database)): ?>
                <div class="bg-red-50 text-red-700 p-4 rounded-lg mb-6 border border-red-200">
                    <?= $error_database ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-blue-500">
                    <p class="text-sm text-gray-500 mb-1">Total Peserta Bimbingan</p>
                    <p class="text-3xl font-bold text-gray-800"><?= $total_peserta ?> <span class="text-sm font-normal text-gray-400">Orang</span></p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gray-50">
                    <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <i data-lucide="users" class="w-5 h-5 text-blue-600"></i> Daftar Peserta Magang di Bawah Bimbingan Anda
                    </h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="bg-white text-gray-500 border-b">
                                <th class="p-4 font-medium">No</th>
                                <th class="p-4 font-medium">Nama Peserta / NIM</th>
                                <th class="p-4 font-medium">Institusi</th>
                                <th class="p-4 font-medium">Tim Kerja</th>
                                <th class="p-4 font-medium">Periode</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($peserta_bimbingan) > 0): ?>
                                <?php $no=1; foreach ($peserta_bimbingan as $row): ?>
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="p-4 text-gray-600 font-medium"><?= $no++ ?></td>
                                        <td class="p-4">
                                            <p class="font-bold text-gray-800"><?= htmlspecialchars($row['nama_peserta']) ?></p>
                                            <p class="text-xs text-gray-500"><?= htmlspecialchars($row['nim']) ?></p>
                                        </td>
                                        <td class="p-4 text-gray-700"><?= htmlspecialchars($row['institusi']) ?></td>
                                        <td class="p-4">
                                            <span class="bg-blue-50 text-blue-700 font-semibold px-2.5 py-1 rounded-md text-xs">
                                                <?= htmlspecialchars($row['nama_tim'] ?? '-') ?>
                                            </span>
                                        </td>
                                        <td class="p-4 text-gray-700"><?= htmlspecialchars($row['periode']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="p-8 text-center text-gray-500">
                                        <i data-lucide="inbox" class="w-12 h-12 mx-auto text-gray-300 mb-3"></i>
                                        Belum ada peserta magang yang ditempatkan di bawah bimbingan Anda.
                                    </td>
                                </tr>
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