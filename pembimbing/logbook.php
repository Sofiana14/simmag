<?php
require_once '../config/database.php';
checkRole('pembimbing');

$nama_pembimbing = $_SESSION['name'];
$success = '';
$error = '';

// Proses validasi (ACC / Revisi)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'validasi') {
    $logbook_id = $_POST['logbook_id'];
    $status = $_POST['status']; // 'disetujui' atau 'revisi'
    $catatan = trim($_POST['catatan_pembimbing']);

    try {
        $stmt = $pdo->prepare("UPDATE logbook SET status = ?, catatan_pembimbing = ? WHERE id = ?");
        $stmt->execute([$status, $catatan, $logbook_id]);
        $success = "Status logbook berhasil diperbarui!";
    } catch (Exception $e) {
        $error = "Gagal memperbarui logbook: " . $e->getMessage();
    }
}

// Ambil daftar logbook dari peserta yang dibimbing berdasarkan kecocokan nama pembimbing
$stmt = $pdo->prepare("
    SELECT lb.*, pst.nama as nama_peserta, pst.nim, pn.nama_tim
    FROM logbook lb
    JOIN peserta pst ON lb.peserta_id = pst.id
    JOIN penempatan pn ON pst.id = pn.peserta_id
    WHERE pn.nama_pembimbing LIKE ?
    ORDER BY lb.tanggal DESC, lb.id DESC
");
$stmt->execute(["%$nama_pembimbing%"]);
$daftar_logbook = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Validasi Logbook - SIMMAG BPS</title>
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
            <a href="logbook.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-600 text-white transition">
                <i data-lucide="book-open" class="w-5 h-5"></i> Validasi Logbook
            </a>
            <a href="../logout.php" class="flex items-center gap-3 p-3 mt-8 rounded-lg text-red-400 hover:bg-red-500 hover:text-white transition">
                <i data-lucide="log-out" class="w-5 h-5"></i> Logout
            </a>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col overflow-hidden relative">
        <header class="bg-white border-b border-gray-200 p-4 px-8 flex justify-between items-center z-10">
            <h1 class="text-xl font-semibold text-gray-800">Validasi Logbook Peserta</h1>
            <div class="flex items-center gap-3">
                <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($_SESSION['name']) ?></span>
                <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold">P</div>
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
                <div class="p-6 border-b border-gray-100 bg-gray-50">
                    <h2 class="text-lg font-bold text-gray-800">Daftar Logbook Harian Peserta</h2>
                    <p class="text-sm text-gray-500">Periksa dan berikan status persetujuan atau catatan revisi pada aktivitas harian peserta.</p>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="bg-white text-gray-500 border-b">
                                <th class="p-4 font-medium">Tanggal</th>
                                <th class="p-4 font-medium">Peserta / Tim</th>
                                <th class="p-4 font-medium">Kegiatan</th>
                                <th class="p-4 font-medium">Status</th>
                                <th class="p-4 font-medium text-center">Aksi / Validasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($daftar_logbook) > 0): ?>
                                <?php foreach ($daftar_logbook as $row): ?>
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="p-4 text-gray-600 font-medium"><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                                        <td class="p-4">
                                            <p class="font-bold text-gray-800"><?= htmlspecialchars($row['nama_peserta']) ?></p>
                                            <p class="text-xs text-blue-600 font-semibold"><?= htmlspecialchars($row['nama_tim']) ?></p>
                                        </td>
                                        <td class="p-4">
                                            <p class="font-bold text-gray-800"><?= htmlspecialchars($row['judul_kegiatan']) ?></p>
                                            <p class="text-xs text-gray-600 mt-1 line-clamp-2"><?= htmlspecialchars($row['deskripsi']) ?></p>
                                            <?php if ($row['dokumentasi']): ?>
                                                <a href="<?= htmlspecialchars($row['dokumentasi']) ?>" target="_blank" class="text-xs text-blue-600 hover:underline mt-2 inline-flex items-center gap-1">
                                                    <i data-lucide="image" class="w-3 h-3"></i> Lihat Foto
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-4">
                                            <?php if ($row['status'] == 'menunggu'): ?>
                                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Menunggu</span>
                                            <?php elseif ($row['status'] == 'disetujui'): ?>
                                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Disetujui (ACC)</span>
                                            <?php else: ?>
                                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">Revisi</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-4 text-center">
                                            <button 
                                                onclick="bukaModalValidasi(<?= htmlspecialchars(json_encode([
                                                    'id' => $row['id'],
                                                    'nama' => $row['nama_peserta'],
                                                    'judul' => $row['judul_kegiatan'],
                                                    'status' => $row['status'],
                                                    'catatan' => $row['catatan_pembimbing'] ?? ''
                                                ])) ?>)"
                                                class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 px-3 py-1.5 rounded-lg hover:bg-blue-100 font-medium transition">
                                                <i data-lucide="check-square" class="w-4 h-4"></i> Validasi
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="p-8 text-center text-gray-500">Belum ada logbook dari peserta bimbingan Anda.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- MODAL VALIDASI LOGBOOK -->
    <div id="modalValidasi" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex justify-center items-center z-50">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md overflow-hidden">
            <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="text-lg font-bold text-gray-800">Validasi Logbook</h3>
                <button onclick="tutupModalValidasi()" class="text-gray-400 hover:text-red-500"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            
            <form action="" method="POST" class="p-6">
                <input type="hidden" name="action" value="validasi">
                <input type="hidden" name="logbook_id" id="m_logbook_id">

                <div class="mb-4 bg-gray-50 p-3 rounded-lg border border-gray-100">
                    <p class="text-xs text-gray-500 mb-1">Peserta & Kegiatan</p>
                    <p class="font-bold text-gray-800" id="m_nama"></p>
                    <p class="text-xs text-blue-600 font-medium" id="m_judul"></p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Keputusan <span class="text-red-500">*</span></label>
                    <select name="status" id="m_status" required class="w-full border p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white text-sm">
                        <option value="disetujui">Setujui (ACC)</option>
                        <option value="revisi">Minta Revisi</option>
                    </select>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catatan / Alasan Revisi (Opsional)</label>
                    <textarea name="catatan_pembimbing" id="m_catatan" rows="3" class="w-full border p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm" placeholder="Tuliskan catatan jika ada bagian yang perlu diperbaiki..."></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" onclick="tutupModalValidasi()" class="px-5 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium">Batal</button>
                    <button type="submit" class="px-5 py-2 text-sm text-white bg-blue-600 hover:bg-blue-700 rounded-lg font-bold flex items-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i> Simpan Validasi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function bukaModalValidasi(data) {
            document.getElementById('m_logbook_id').value = data.id;
            document.getElementById('m_nama').innerText = data.nama;
            document.getElementById('m_judul').innerText = data.judul;
            document.getElementById('m_status').value = data.status;
            document.getElementById('m_catatan').value = data.catatan;
            
            document.getElementById('modalValidasi').classList.remove('hidden');
        }

        function tutupModalValidasi() {
            document.getElementById('modalValidasi').classList.add('hidden');
        }
    </script>
</body>
</html>