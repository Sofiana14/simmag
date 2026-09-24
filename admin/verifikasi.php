<?php
require_once '../config/database.php';
checkRole('admin');

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$id_pendaftaran = $_GET['id'];
$success = '';
$error = '';

// Ambil Data Detail Pendaftaran Dulu Untuk Kebutuhan ID Peserta
$stmt = $pdo->prepare("SELECT peserta_id FROM pendaftaran WHERE id = ?");
$stmt->execute([$id_pendaftaran]);
$peserta_data = $stmt->fetch();
if (!$peserta_data) die("Data pendaftaran tidak ditemukan.");
$peserta_id = $peserta_data['peserta_id'];

// 1. Proses Terima (Upload LOA) & Tolak
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action == 'terima') {
        $nomor_surat = $_POST['nomor_surat'];
        $tanggal_surat = $_POST['tanggal_surat'];

        // Cek dan Upload File LOA
        if (isset($_FILES['file_loa']) && $_FILES['file_loa']['error'] == 0) {
            $upload_dir = __DIR__ . "/../uploads/loa/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $db_dir = "../uploads/loa/";

            if ($_FILES['file_loa']['type'] == 'application/pdf') {
                $filename = time() . "_LOA_" . preg_replace("/[^a-zA-Z0-9.]/", "_", basename($_FILES['file_loa']['name']));
                
                if (move_uploaded_file($_FILES['file_loa']['tmp_name'], $upload_dir . $filename)) {
                    $file_path = $db_dir . $filename;
                    
                    try {
                        $pdo->beginTransaction();
                        // 1. Ubah status pendaftaran jadi diterima
                        $stmt = $pdo->prepare("UPDATE pendaftaran SET status = 'diterima' WHERE id = ?");
                        $stmt->execute([$id_pendaftaran]);
                        
                        // 2. Simpan data LOA ke tabel loa
                        $stmt = $pdo->prepare("INSERT INTO loa (peserta_id, nomor_surat, tanggal_surat, file_path, uploaded_by) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$peserta_id, $nomor_surat, $tanggal_surat, $file_path, $_SESSION['user_id']]);
                        
                        $pdo->commit();
                        $success = "Peserta DITERIMA dan Surat LOA berhasil dikirim!";
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $error = "Terjadi kesalahan sistem: " . $e->getMessage();
                    }
                } else {
                    $error = "Gagal memindahkan file LOA.";
                }
            } else {
                $error = "Format dokumen LOA harus berupa PDF.";
            }
        } else {
            $error = "Dokumen Surat LOA wajib dilampirkan untuk menerima peserta!";
        }

    } elseif ($action == 'tolak') {
        $alasan = $_POST['alasan_penolakan'];
        try {
            $stmt = $pdo->prepare("UPDATE pendaftaran SET status = 'ditolak', alasan_penolakan = ? WHERE id = ?");
            $stmt->execute([$alasan, $id_pendaftaran]);
            $success = "Peserta telah DITOLAK beserta alasannya.";
        } catch (Exception $e) {
            $error = "Gagal menolak data: " . $e->getMessage();
        }
    }
}

// 2. Ambil Ulang Data Detail Pendaftaran untuk Tampilan UI
$stmt = $pdo->prepare("
    SELECT p.*, pst.nama, pst.nim, pst.institusi, pst.prodi, pst.no_hp, u.email 
    FROM pendaftaran p
    JOIN peserta pst ON p.peserta_id = pst.id
    JOIN users u ON pst.user_id = u.id
    WHERE p.id = ?
");
$stmt->execute([$id_pendaftaran]);
$data = $stmt->fetch();

// 3. Ambil Data Dokumen Lampiran (CV & Surat Pengantar)
$stmt_docs = $pdo->prepare("SELECT jenis_dokumen, file_path, nama_file FROM dokumen WHERE pendaftaran_id = ?");
$stmt_docs->execute([$id_pendaftaran]);
$dokumen = [];
while ($row = $stmt_docs->fetch()) {
    $dokumen[$row['jenis_dokumen']] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Verifikasi Pendaftar - SIMMAG BPS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-50 p-8">

    <div class="max-w-4xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <a href="dashboard.php" class="text-gray-500 hover:text-blue-600 flex items-center gap-2 font-medium">
                <i data-lucide="arrow-left" class="w-5 h-5"></i> Kembali ke Dashboard
            </a>
            <span class="px-4 py-1.5 rounded-full text-sm font-bold shadow-sm 
                <?= $data['status'] == 'menunggu' ? 'bg-yellow-100 text-yellow-800' : ($data['status'] == 'diterima' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') ?>">
                Status: <?= strtoupper($data['status']) ?>
            </span>
        </div>

        <?php if ($success): ?>
            <div class="bg-green-50 text-green-700 p-4 rounded-lg mb-6 shadow-sm flex items-center gap-2 border border-green-200">
                <i data-lucide="check-circle" class="w-5 h-5"></i> <?= $success ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-50 text-red-700 p-4 rounded-lg mb-6 shadow-sm flex items-center gap-2 border border-red-200">
                <i data-lucide="alert-circle" class="w-5 h-5"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
            <div class="p-6 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Informasi Peserta</h2>
                    <p class="text-sm text-gray-500">Nomor Pendaftaran: <span class="font-mono text-gray-700"><?= htmlspecialchars($data['nomor_pendaftaran']) ?></span></p>
                </div>
            </div>
            
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Nama Lengkap</p>
                    <p class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($data['nama']) ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 mb-1">NIM / NIS</p>
                    <p class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($data['nim']) ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 mb-1">Institusi</p>
                    <p class="font-medium text-gray-800"><?= htmlspecialchars($data['institusi']) ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 mb-1">Program Studi</p>
                    <p class="font-medium text-gray-800"><?= htmlspecialchars($data['prodi']) ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 mb-1">Kontak</p>
                    <p class="text-gray-800"><i data-lucide="mail" class="w-4 h-4 inline text-gray-400 mr-1"></i> <?= htmlspecialchars($data['email']) ?></p>
                    <p class="text-gray-800"><i data-lucide="phone" class="w-4 h-4 inline text-gray-400 mr-1"></i> <?= htmlspecialchars($data['no_hp']) ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 mb-1">Periode Diajukan</p>
                    <p class="font-bold text-blue-600 bg-blue-50 inline-block px-3 py-1 rounded-lg border border-blue-100">
                        <?= date('d M', strtotime($data['tanggal_mulai'])) ?> - <?= date('d M Y', strtotime($data['tanggal_selesai'])) ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
            <div class="p-6 border-b border-gray-100 bg-gray-50">
                <h2 class="text-lg font-bold text-gray-800">Dokumen Lampiran</h2>
            </div>
            <div class="p-6 flex flex-col md:flex-row gap-4">
                <div class="flex-1 border rounded-lg p-4 flex items-center justify-between hover:bg-gray-50 transition">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-red-100 text-red-600 rounded-lg"><i data-lucide="file-text" class="w-6 h-6"></i></div>
                        <div>
                            <p class="font-bold text-gray-800">Curriculum Vitae (CV)</p>
                            <p class="text-xs text-gray-500 truncate w-40"><?= htmlspecialchars($dokumen['CV']['nama_file'] ?? 'Tidak ada') ?></p>
                        </div>
                    </div>
                    <?php if (isset($dokumen['CV'])): ?>
                        <a href="<?= htmlspecialchars($dokumen['CV']['file_path']) ?>" target="_blank" class="px-3 py-1.5 text-sm bg-blue-100 text-blue-700 rounded hover:bg-blue-200 font-medium">Download</a>
                    <?php endif; ?>
                </div>

                <div class="flex-1 border rounded-lg p-4 flex items-center justify-between hover:bg-gray-50 transition">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-100 text-blue-600 rounded-lg"><i data-lucide="file-badge-2" class="w-6 h-6"></i></div>
                        <div>
                            <p class="font-bold text-gray-800">Surat Pengantar</p>
                            <p class="text-xs text-gray-500 truncate w-40"><?= htmlspecialchars($dokumen['SURAT_PENGANTAR']['nama_file'] ?? 'Tidak ada') ?></p>
                        </div>
                    </div>
                    <?php if (isset($dokumen['SURAT_PENGANTAR'])): ?>
                        <a href="<?= htmlspecialchars($dokumen['SURAT_PENGANTAR']['file_path']) ?>" target="_blank" class="px-3 py-1.5 text-sm bg-blue-100 text-blue-700 rounded hover:bg-blue-200 font-medium">Download</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- AREA AKSI / STATUS KEPUTUSAN -->
        <?php if ($data['status'] == 'menunggu'): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex justify-end gap-4">
            <button onclick="bukaModalTolak()" class="px-6 py-2.5 bg-red-50 text-red-600 font-bold rounded-lg hover:bg-red-100 transition border border-red-200">
                <i data-lucide="x-circle" class="w-5 h-5 inline mr-1"></i> Tolak
            </button>
            <button onclick="bukaModalTerima()" class="px-6 py-2.5 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition shadow-lg shadow-green-600/30">
                <i data-lucide="check-circle-2" class="w-5 h-5 inline mr-1"></i> Terima & Upload LOA
            </button>
        </div>
        
        <?php elseif ($data['status'] == 'ditolak'): ?>
        <div class="bg-red-50 border border-red-200 p-5 rounded-xl text-red-800 shadow-sm flex gap-4">
            <i data-lucide="alert-triangle" class="w-8 h-8 text-red-500 shrink-0"></i>
            <div>
                <h3 class="font-bold text-lg mb-1">Peserta Ditolak</h3>
                <p class="text-sm"><span class="font-semibold">Alasan:</span> <?= nl2br(htmlspecialchars($data['alasan_penolakan'])) ?></p>
            </div>
        </div>
        <?php elseif ($data['status'] == 'diterima'): ?>
        <div class="bg-green-50 border border-green-200 p-5 rounded-xl text-green-800 shadow-sm flex items-center justify-between">
            <div class="flex gap-4 items-center">
                <i data-lucide="mail-check" class="w-8 h-8 text-green-500 shrink-0"></i>
                <div>
                    <h3 class="font-bold text-lg">Peserta Diterima</h3>
                    <p class="text-sm">Dokumen LOA telah dikirim ke dashboard peserta.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- MODAL TOLAK -->
    <div id="modalTolak" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex justify-center items-center z-50">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-lg overflow-hidden">
            <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="text-lg font-bold text-red-600 flex items-center gap-2">
                    <i data-lucide="x-circle" class="w-5 h-5"></i> Tolak Pendaftaran
                </h3>
                <button onclick="tutupModalTolak()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <form action="" method="POST" class="p-6">
                <input type="hidden" name="action" value="tolak">
                <label class="block text-sm font-medium text-gray-700 mb-2">Berikan Alasan Penolakan <span class="text-red-500">*</span></label>
                <textarea name="alasan_penolakan" rows="4" required class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-red-500 outline-none mb-4 text-sm" placeholder="Contoh: Kuota penuh, Jurusan tidak relevan dengan kebutuhan divisi..."></textarea>
                
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="tutupModalTolak()" class="px-5 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium">Batal</button>
                    <button type="submit" class="px-5 py-2 text-sm text-white bg-red-600 hover:bg-red-700 rounded-lg font-bold flex items-center gap-2"><i data-lucide="send" class="w-4 h-4"></i> Tolak Peserta</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL TERIMA & UPLOAD LOA -->
    <div id="modalTerima" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex justify-center items-center z-50">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-lg overflow-hidden">
            <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-green-50">
                <h3 class="text-lg font-bold text-green-700 flex items-center gap-2">
                    <i data-lucide="check-circle-2" class="w-5 h-5"></i> Terima & Kirim LOA
                </h3>
                <button onclick="tutupModalTerima()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data" class="p-6">
                <input type="hidden" name="action" value="terima">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Surat Resmi <span class="text-red-500">*</span></label>
                    <input type="text" name="nomor_surat" required class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-green-500 outline-none text-sm" placeholder="Contoh: 123/BPS/MAGANG/2026">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Surat <span class="text-red-500">*</span></label>
                    <input type="date" name="tanggal_surat" required class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-green-500 outline-none text-sm" value="<?= date('Y-m-d') ?>">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Unggah PDF LOA <span class="text-red-500">*</span></label>
                    <input type="file" name="file_loa" accept="application/pdf" required class="w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 border border-gray-300 rounded-lg">
                    <p class="text-[11px] text-gray-500 mt-1">Harus berupa file PDF.</p>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" onclick="tutupModalTerima()" class="px-5 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium">Batal</button>
                    <button type="submit" class="px-5 py-2 text-sm text-white bg-green-600 hover:bg-green-700 rounded-lg font-bold flex items-center gap-2"><i data-lucide="upload" class="w-4 h-4"></i> Terima & Upload</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();
        function bukaModalTolak() { document.getElementById('modalTolak').classList.remove('hidden'); }
        function tutupModalTolak() { document.getElementById('modalTolak').classList.add('hidden'); }
        function bukaModalTerima() { document.getElementById('modalTerima').classList.remove('hidden'); }
        function tutupModalTerima() { document.getElementById('modalTerima').classList.add('hidden'); }
    </script>
</body>
</html>