<?php
require_once '../config/database.php';
checkRole('admin');

$success = '';
$error = '';

// 1. Proses Form Penempatan (Input Teks Bebas)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'simpan_penempatan') {
    $peserta_id = $_POST['peserta_id'];
    $nama_tim = trim($_POST['nama_tim']);
    $nama_pembimbing = trim($_POST['nama_pembimbing']);

    try {
        $stmt = $pdo->prepare("SELECT id FROM penempatan WHERE peserta_id = ?");
        $stmt->execute([$peserta_id]);
        $exists = $stmt->fetch();

        if ($exists) {
            // Update penempatan
            $stmt = $pdo->prepare("UPDATE penempatan SET nama_tim = ?, nama_pembimbing = ?, tanggal_penempatan = CURDATE() WHERE peserta_id = ?");
            $stmt->execute([$nama_tim, $nama_pembimbing, $peserta_id]);
            $success = "Data penempatan berhasil diperbarui!";
        } else {
            // Insert baru
            $stmt = $pdo->prepare("INSERT INTO penempatan (peserta_id, nama_tim, nama_pembimbing, tanggal_penempatan) VALUES (?, ?, ?, CURDATE())");
            $stmt->execute([$peserta_id, $nama_tim, $nama_pembimbing]);
            $success = "Peserta berhasil ditempatkan!";
        }
    } catch (Exception $e) {
        $error = "Terjadi kesalahan database: " . $e->getMessage();
    }
}

// 2. Ambil Data Peserta Diterima beserta data penempatannya
$stmt = $pdo->query("
    SELECT pst.id as peserta_id, pst.nama, pst.nim, pst.institusi, p.periode,
           pn.id as penempatan_id, pn.nama_tim, pn.nama_pembimbing
    FROM pendaftaran p
    JOIN peserta pst ON p.peserta_id = pst.id
    LEFT JOIN penempatan pn ON pst.id = pn.peserta_id
    WHERE p.status = 'diterima'
    ORDER BY pn.id ASC, p.tanggal_daftar DESC
");
$peserta_diterima = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Penempatan Peserta - SIMMAG BPS</title>
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
            <a href="kuota.php" class="flex items-center gap-3 p-3 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition">
                <i data-lucide="pie-chart" class="w-5 h-5"></i> Kuota Magang
            </a>
            <a href="penempatan.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-600 text-white transition">
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
            <h1 class="text-xl font-semibold text-gray-800">Manajemen Penempatan</h1>
            <div class="flex items-center gap-3">
                <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($_SESSION['name']) ?></span>
                <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold">A</div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8">
            
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

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <div>
                        <h2 class="text-lg font-bold text-gray-800">Peserta Diterima</h2>
                        <p class="text-sm text-gray-500">Ketik nama tim kerja dan pembimbing secara manual untuk setiap peserta.</p>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="bg-white text-gray-500 border-b">
                                <th class="p-4 font-medium">No</th>
                                <th class="p-4 font-medium">Peserta / Institusi</th>
                                <th class="p-4 font-medium">Periode</th>
                                <th class="p-4 font-medium">Penempatan</th>
                                <th class="p-4 font-medium text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($peserta_diterima) > 0): ?>
                                <?php $no=1; foreach ($peserta_diterima as $row): ?>
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="p-4 text-gray-600"><?= $no++ ?></td>
                                        <td class="p-4">
                                            <p class="font-bold text-gray-800"><?= htmlspecialchars($row['nama']) ?></p>
                                            <p class="text-xs text-gray-500"><?= htmlspecialchars($row['nim']) ?> • <?= htmlspecialchars($row['institusi']) ?></p>
                                        </td>
                                        <td class="p-4 text-gray-700 font-medium"><?= htmlspecialchars($row['periode']) ?></td>
                                        <td class="p-4">
                                            <?php if ($row['penempatan_id']): ?>
                                                <div class="bg-blue-50 border border-blue-100 p-2 rounded-lg">
                                                    <p class="text-xs text-blue-800 font-bold"><i data-lucide="briefcase" class="w-3 h-3 inline"></i> <?= htmlspecialchars($row['nama_tim']) ?></p>
                                                    <p class="text-xs text-gray-600 mt-1"><i data-lucide="user" class="w-3 h-3 inline"></i> Pembimbing: <?= htmlspecialchars($row['nama_pembimbing']) ?></p>
                                                </div>
                                            <?php else: ?>
                                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">Belum Ditempatkan</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-4 text-center">
                                            <button 
                                                onclick="bukaModalPenempatan(<?= htmlspecialchars(json_encode([
                                                    'peserta_id' => $row['peserta_id'],
                                                    'nama' => $row['nama'],
                                                    'nim' => $row['nim'],
                                                    'nama_tim' => $row['nama_tim'] ?? '',
                                                    'nama_pembimbing' => $row['nama_pembimbing'] ?? ''
                                                ])) ?>)"
                                                class="inline-flex items-center gap-1 bg-yellow-50 text-yellow-700 px-3 py-1.5 rounded-lg hover:bg-yellow-100 font-medium transition">
                                                <i data-lucide="settings-2" class="w-4 h-4"></i> Atur
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="p-8 text-center text-gray-500">Belum ada data peserta yang diterima.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- MODAL PENEMPATAN MANUAL -->
    <div id="modalPenempatan" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex justify-center items-center z-50">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md overflow-hidden">
            <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="text-lg font-bold text-gray-800">Atur Penempatan Manual</h3>
                <button onclick="tutupModalPenempatan()" class="text-gray-400 hover:text-red-500"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            
            <form action="" method="POST" class="p-6">
                <input type="hidden" name="action" value="simpan_penempatan">
                <input type="hidden" name="peserta_id" id="m_peserta_id">

                <div class="mb-4 bg-gray-50 p-3 rounded-lg border border-gray-100">
                    <p class="text-xs text-gray-500 mb-1">Peserta</p>
                    <p class="font-bold text-gray-800" id="m_nama"></p>
                    <p class="text-xs text-gray-500" id="m_nim"></p>
                </div>

                <!-- Input Teks Nama Tim -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Tim Kerja <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_tim" id="m_nama_tim" required class="w-full border p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white text-sm" placeholder="Contoh: Tim IPDS / Tim Neraca...">
                </div>

                <!-- Input Teks Nama Pembimbing -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Pembimbing <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_pembimbing" id="m_nama_pembimbing" required class="w-full border p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white text-sm" placeholder="Contoh: Budi Santoso, SST., M.Stat...">
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" onclick="tutupModalPenempatan()" class="px-5 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium">Batal</button>
                    <button type="submit" class="px-5 py-2 text-sm text-white bg-blue-600 hover:bg-blue-700 rounded-lg font-bold flex items-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function bukaModalPenempatan(data) {
            document.getElementById('m_peserta_id').value = data.peserta_id;
            document.getElementById('m_nama').innerText = data.nama;
            document.getElementById('m_nim').innerText = data.nim;
            
            // Set input teks manual
            document.getElementById('m_nama_tim').value = data.nama_tim || "";
            document.getElementById('m_nama_pembimbing').value = data.nama_pembimbing || "";
            
            document.getElementById('modalPenempatan').classList.remove('hidden');
        }

        function tutupModalPenempatan() {
            document.getElementById('modalPenempatan').classList.add('hidden');
        }
    </script>
</body>
</html>