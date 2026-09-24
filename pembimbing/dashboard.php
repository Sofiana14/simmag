<?php
require_once '../config/database.php';

// Pastikan fungsi checkRole sudah ada
if (!function_exists('checkRole')) {
    function checkRole($role) {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
            header("Location: ../login.php");
            exit;
        }
    }
}
checkRole('peserta');

$user_id = $_SESSION['user_id'];

// 1. Ambil data progress peserta (Pendaftaran & LOA)
$stmt = $pdo->prepare("
    SELECT p.id as peserta_id, p.*, pkt.status as status_daftar, pkt.alasan_penolakan, 
           (SELECT file_path FROM loa WHERE peserta_id = p.id LIMIT 1) as loa_path
    FROM peserta p 
    LEFT JOIN pendaftaran pkt ON p.id = pkt.peserta_id 
    WHERE p.user_id = ? 
    ORDER BY pkt.id DESC LIMIT 1
");
$stmt->execute([$user_id]);
$data_peserta = $stmt->fetch();

$peserta_id = $data_peserta['peserta_id'] ?? null;

// 2. Ambil data Pembimbing dan Tim (Sesuai struktur database Anda)
$data_penempatan = null;
$data_tim = [];

if ($peserta_id) {
    // Info Pembimbing (Langsung ambil dari kolom nama_pembimbing di tabel penempatan)
    $stmt_pem = $pdo->prepare("
        SELECT nama_pembimbing
        FROM penempatan 
        WHERE peserta_id = ?
    ");
    $stmt_pem->execute([$peserta_id]);
    $data_penempatan = $stmt_pem->fetch();

    // Info Anggota Tim (Cari peserta lain yang memiliki nama_pembimbing yang sama)
    if ($data_penempatan && !empty($data_penempatan['nama_pembimbing'])) {
        $stmt_tim = $pdo->prepare("
            SELECT p.nama, p.institusi 
            FROM penempatan pen 
            JOIN peserta p ON pen.peserta_id = p.id 
            WHERE pen.nama_pembimbing = ? 
            AND pen.peserta_id != ?
        ");
        $stmt_tim->execute([$data_penempatan['nama_pembimbing'], $peserta_id]);
        $data_tim = $stmt_tim->fetchAll();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Peserta - SIMMAG BPS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <aside class="w-64 bg-slate-800 text-white flex flex-col hidden md:flex">
        <div class="p-6 text-xl font-bold border-b border-slate-700 flex items-center gap-2">
            <i data-lucide="bar-chart-2" class="text-blue-400"></i> SIMMAG BPS
        </div>
        <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
            <a href="dashboard.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-600 text-white transition">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
            </a>
            <a href="pendaftaran.php" class="flex items-center gap-3 p-3 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition">
                <i data-lucide="file-text" class="w-5 h-5"></i> Pendaftaran Magang
            </a>
            <a href="logbook.php" class="flex items-center gap-3 p-3 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition">
                <i data-lucide="book-open" class="w-5 h-5"></i> Logbook
            </a>
            <a href="presensi.php" class="flex items-center gap-3 p-3 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition">
                <i data-lucide="calendar-check" class="w-5 h-5"></i> Presensi
            </a>
            <a href="cetak_bukti.php" target="_blank" class="flex items-center gap-3 p-3 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition">
                <i data-lucide="printer" class="w-5 h-5"></i> Cetak Bukti PDF
            </a>
            <a href="../logout.php" class="flex items-center gap-3 p-3 mt-8 rounded-lg text-red-400 hover:bg-red-500 hover:text-white transition">
                <i data-lucide="log-out" class="w-5 h-5"></i> Logout
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto relative">
        <header class="bg-white border-b border-gray-200 p-4 px-8 flex justify-between items-center sticky top-0 z-10">
            <h1 class="text-xl font-semibold text-gray-800">Dashboard</h1>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold">
                    <?= substr($_SESSION['name'], 0, 1) ?>
                </div>
                <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($_SESSION['name']) ?></span>
            </div>
        </header>

        <div class="p-8 max-w-7xl mx-auto">
            
            <!-- NOTIFIKASI STATUS PENDAFTARAN -->
            <?php if (isset($data_peserta['status_daftar']) && $data_peserta['status_daftar'] == 'ditolak'): ?>
                <div class="bg-red-50 border border-red-200 p-6 rounded-xl mb-8 flex items-start gap-4 shadow-sm">
                    <i data-lucide="alert-triangle" class="w-8 h-8 text-red-500 shrink-0"></i>
                    <div>
                        <h2 class="text-lg font-bold text-red-800 mb-1">Pendaftaran Anda Ditolak</h2>
                        <p class="text-sm text-red-700 mb-3">Mohon maaf, pengajuan magang Anda tidak dapat diproses lebih lanjut karena alasan berikut:</p>
                        <div class="bg-white p-4 rounded-lg border border-red-100 text-gray-700 text-sm italic font-medium">
                            "<?= nl2br(htmlspecialchars($data_peserta['alasan_penolakan'])) ?>"
                        </div>
                    </div>
                </div>
            <?php elseif (isset($data_peserta['status_daftar']) && $data_peserta['status_daftar'] == 'diterima'): ?>
                <div class="bg-green-50 border border-green-200 p-6 rounded-xl mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-sm">
                    <div class="flex items-center gap-4">
                        <i data-lucide="check-circle" class="w-10 h-10 text-green-500 shrink-0"></i>
                        <div>
                            <h2 class="text-lg font-bold text-green-800 mb-1">Selamat! Anda Diterima Magang</h2>
                            <p class="text-sm text-green-700">Dokumen Surat Letter of Acceptance (LOA) resmi dari BPS telah diterbitkan.</p>
                        </div>
                    </div>
                    <?php if ($data_peserta['loa_path']): ?>
                        <a href="<?= htmlspecialchars($data_peserta['loa_path']) ?>" target="_blank" class="bg-blue-600 text-white px-6 py-2.5 rounded-full font-bold hover:bg-blue-700 transition shadow-md shadow-blue-600/30 flex items-center justify-center gap-2 whitespace-nowrap">
                            <i data-lucide="download" class="w-5 h-5"></i> Download LOA
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Welcome Card -->
            <div class="bg-slate-800 rounded-xl shadow-sm text-white p-8 mb-8 flex justify-between items-center relative overflow-hidden">
                <div class="absolute right-0 top-0 opacity-10">
                    <i data-lucide="graduation-cap" class="w-48 h-48 -mt-10 -mr-10"></i>
                </div>
                <div class="relative z-10">
                    <h2 class="text-2xl font-bold mb-2">Selamat Datang, <?= htmlspecialchars($_SESSION['name']) ?>!</h2>
                    <p class="text-slate-300">Pantau proses pendaftaran, presensi harian, dan pengisian logbook Anda di sini.</p>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="text-sm text-gray-500 mb-2">Status Pendaftaran</h3>
                    <?php
                        $status = $data_peserta['status_daftar'] ?? '';
                        if ($status == 'menunggu') echo '<span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-800 uppercase tracking-wide">Menunggu Verifikasi</span>';
                        elseif ($status == 'diterima') echo '<span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800 uppercase tracking-wide">Diterima</span>';
                        elseif ($status == 'ditolak') echo '<span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 uppercase tracking-wide">Ditolak</span>';
                        else echo '<span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-600 uppercase tracking-wide">Belum Mendaftar</span>';
                    ?>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="text-sm text-gray-500 mb-2">Periode Magang</h3>
                    <p class="text-xl font-bold text-gray-800">Sept 2026</p>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="text-sm text-gray-500 mb-2">Total Presensi</h3>
                    <p class="text-2xl font-bold text-blue-600">-- <span class="text-sm text-gray-400 font-normal">Hari</span></p>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="text-sm text-gray-500 mb-2">Logbook ACC</h3>
                    <p class="text-2xl font-bold text-green-600">-- <span class="text-sm text-gray-400 font-normal">Kegiatan</span></p>
                </div>
            </div>

            <!-- KARTU INFORMASI PEMBIMBING & TIM -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                
                <!-- Info Pembimbing -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 h-full flex flex-col">
                    <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4 flex items-center gap-2">
                        <i data-lucide="user-check" class="w-5 h-5 text-blue-600"></i> Informasi Pembimbing
                    </h3>
                    
                    <div class="flex-1 flex flex-col justify-center">
                        <?php if ($data_penempatan && !empty($data_penempatan['nama_pembimbing'])): ?>
                            <div class="flex items-center gap-5 bg-blue-50 border border-blue-100 p-5 rounded-xl">
                                <div class="w-14 h-14 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-2xl shadow-sm">
                                    <?= substr($data_penempatan['nama_pembimbing'], 0, 1) ?>
                                </div>
                                <div>
                                    <p class="font-bold text-gray-900 text-lg"><?= htmlspecialchars($data_penempatan['nama_pembimbing']) ?></p>
                                    <p class="text-sm text-gray-600">Pegawai / Pembimbing BPS</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-6 text-gray-500">
                                <i data-lucide="clock" class="w-12 h-12 mx-auto text-gray-300 mb-3"></i>
                                <p class="font-medium text-gray-600">Belum Ada Pembimbing</p>
                                <p class="text-sm mt-1">Admin belum menetapkan pembimbing lapangan untuk Anda.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Info Tim -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 h-full flex flex-col">
                    <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4 flex items-center gap-2">
                        <i data-lucide="users" class="w-5 h-5 text-green-600"></i> Anggota Tim Anda
                    </h3>
                    
                    <div class="flex-1 overflow-y-auto">
                        <?php if (count($data_tim) > 0): ?>
                            <ul class="space-y-3">
                                <?php foreach ($data_tim as $tim): ?>
                                    <li class="flex items-center gap-4 p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition border border-gray-100">
                                        <div class="w-10 h-10 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center font-bold text-sm shadow-inner">
                                            <?= substr($tim['nama'], 0, 1) ?>
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-800 text-sm"><?= htmlspecialchars($tim['nama']) ?></p>
                                            <p class="text-xs text-gray-500 flex items-center gap-1 mt-0.5">
                                                <i data-lucide="building-2" class="w-3 h-3"></i> <?= htmlspecialchars($tim['institusi']) ?>
                                            </p>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="text-center py-8 text-gray-500 flex flex-col justify-center h-full">
                                <i data-lucide="user-minus" class="w-12 h-12 mx-auto text-gray-300 mb-3"></i>
                                <p class="font-medium text-gray-600">Belum Ada Anggota Tim</p>
                                <p class="text-sm mt-1">Anda belum digabungkan dengan peserta magang lain.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

        </div>
    </main>

    <script> lucide.createIcons(); </script>
</body>
</html>