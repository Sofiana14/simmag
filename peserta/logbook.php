<?php
require_once '../config/database.php';
checkRole('peserta');

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

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
$nama_user = $_SESSION['name'] ?? ($peserta['nama'] ?? 'Peserta');
$institusi = htmlspecialchars($peserta['institusi'] ?? '-');
$pembimbing = htmlspecialchars($peserta['nama_pembimbing'] ?? 'Belum Ditentukan');

// 2. Proses Simpan / Update Form Logbook
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $logbook_id = $_POST['logbook_id'] ?? '';
    $tanggal = $_POST['tanggal'];
    $judul_kegiatan = $_POST['judul_kegiatan'];
    $deskripsi = $_POST['deskripsi'];
    $hasil = trim($_POST['hasil']) ?: '-';
    $kendala = trim($_POST['kendala']) ?: '-';
    $solusi = trim($_POST['solusi']) ?: '-';

    // Proses Upload File Dokumentasi
    $dokumentasi_path = '';
    
    if (isset($_FILES['dokumentasi']) && $_FILES['dokumentasi']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file_error = $_FILES['dokumentasi']['error'];
        $file_size  = $_FILES['dokumentasi']['size'];
        $tmp_name   = $_FILES['dokumentasi']['tmp_name'];

        if ($file_error === UPLOAD_ERR_INI_SIZE || $file_error === UPLOAD_ERR_FORM_SIZE || $file_size > 2 * 1024 * 1024) {
            $error = "Ukuran file terlalu besar. Maksimal 2MB.";
        } elseif ($file_error !== UPLOAD_ERR_OK) {
            $error = "Terjadi kesalahan saat mengunggah file (Kode Error: $file_error).";
        } else {
            $ext = strtolower(pathinfo($_FILES['dokumentasi']['name'], PATHINFO_EXTENSION));
            $allowed_exts  = ['jpg', 'jpeg', 'png'];

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $tmp_name);
            finfo_close($finfo);

            $allowed_mimes = ['image/jpeg', 'image/png', 'image/jpg'];

            if (!in_array($mime_type, $allowed_mimes) || !in_array($ext, $allowed_exts)) {
                $error = "Format foto tidak valid. Gunakan file JPG atau PNG.";
            } else {
                $base_upload = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';
                $target_dir  = $base_upload . DIRECTORY_SEPARATOR . 'dokumentasi' . DIRECTORY_SEPARATOR;
                
                if (!is_dir($base_upload)) {
                    @mkdir($base_upload, 0777, true);
                }

                if (!is_dir($target_dir)) {
                    @mkdir($target_dir, 0777, true);
                }

                $filename = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", basename($_FILES['dokumentasi']['name']));
                $destination = $target_dir . $filename;

                if (move_uploaded_file($tmp_name, $destination)) {
                    $dokumentasi_path = "../uploads/dokumentasi/" . $filename;
                } else {
                    $error = "Gagal memindahkan file ke server. Periksa hak akses folder penyimpanan.";
                }
            }
        }
    }

    if (!$error) {
        try {
            if ($logbook_id) {
                // UPDATE (Jika memperbaiki logbook yang direvisi)
                if ($dokumentasi_path) {
                    $stmt = $pdo->prepare("UPDATE logbook SET tanggal=?, judul_kegiatan=?, deskripsi=?, hasil=?, kendala=?, solusi=?, dokumentasi=?, status='menunggu', catatan_pembimbing=NULL WHERE id=? AND peserta_id=?");
                    $stmt->execute([$tanggal, $judul_kegiatan, $deskripsi, $hasil, $kendala, $solusi, $dokumentasi_path, $logbook_id, $peserta_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE logbook SET tanggal=?, judul_kegiatan=?, deskripsi=?, hasil=?, kendala=?, solusi=?, status='menunggu', catatan_pembimbing=NULL WHERE id=? AND peserta_id=?");
                    $stmt->execute([$tanggal, $judul_kegiatan, $deskripsi, $hasil, $kendala, $solusi, $logbook_id, $peserta_id]);
                }
                $success = "Logbook berhasil diperbaiki dan dikirim ulang untuk direview.";
            } else {
                // INSERT BARU
                if (!$dokumentasi_path) {
                    throw new Exception("Foto dokumentasi wajib diunggah.");
                }
                
                $stmt = $pdo->prepare("INSERT INTO logbook (peserta_id, tanggal, judul_kegiatan, deskripsi, hasil, kendala, solusi, dokumentasi, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'menunggu')");
                $stmt->execute([$peserta_id, $tanggal, $judul_kegiatan, $deskripsi, $hasil, $kendala, $solusi, $dokumentasi_path]);
                $success = "Logbook harian berhasil disimpan!";
            }
        } catch (Exception $e) {
            $error = "Gagal menyimpan logbook: " . $e->getMessage();
        }
    }
}

// 3. Ambil Riwayat Logbook Peserta
$stmt = $pdo->prepare("SELECT * FROM logbook WHERE peserta_id = ? ORDER BY tanggal DESC, id DESC");
$stmt->execute([$peserta_id]);
$riwayat_logbook = $stmt->fetchAll();

// 4. Hitung Statistik Logbook untuk PDF
$total_logbook = count($riwayat_logbook);
$disetujui = 0;
$menunggu = 0;
$revisi = 0;

foreach ($riwayat_logbook as $lb) {
    if ($lb['status'] == 'disetujui') $disetujui++;
    elseif ($lb['status'] == 'menunggu') $menunggu++;
    elseif ($lb['status'] == 'revisi') $revisi++;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Logbook Harian - SIMMAG BPS</title>
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
            <a href="logbook.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-600 text-white transition">
                <i data-lucide="book-open" class="w-5 h-5"></i> Logbook
            </a>
            <a href="presensi.php" class="flex items-center gap-3 p-3 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition">
                <i data-lucide="calendar-check" class="w-5 h-5"></i> Presensi
            </a>
            <a href="../logout.php" class="flex items-center gap-3 p-3 mt-8 rounded-lg text-red-400 hover:bg-red-500 hover:text-white transition">
                <i data-lucide="log-out" class="w-5 h-5"></i> Logout
            </a>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col overflow-hidden relative">
        <header class="bg-white border-b border-gray-200 p-4 px-8 flex justify-between items-center z-10 sticky top-0">
            <h1 class="text-xl font-semibold text-gray-800">Catatan Logbook Harian</h1>
            <div class="flex items-center gap-3">
                <button type="button" onclick="toggleSidebar()" class="p-2 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-gray-900 md:hidden border border-gray-200 focus:outline-none transition">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
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

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- KOLOM 1: FORM PENGISIAN -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden sticky top-0">
                        <div class="p-5 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                            <h2 class="text-lg font-bold text-gray-800" id="form-title">Isi Kegiatan Hari Ini</h2>
                            <button id="btn-cancel-edit" type="button" onclick="resetForm()" class="hidden text-xs bg-red-100 text-red-600 px-2 py-1 rounded hover:bg-red-200 font-medium">Batal Edit</button>
                        </div>
                        <div class="p-6">
                            <form id="formLogbook" action="" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="save">
                                <input type="hidden" name="logbook_id" id="f_logbook_id">

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal <span class="text-red-500">*</span></label>
                                    <input type="date" name="tanggal" id="f_tanggal" value="<?= date('Y-m-d') ?>" required class="w-full border p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                                </div>
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Judul Kegiatan <span class="text-red-500">*</span></label>
                                    <input type="text" name="judul_kegiatan" id="f_judul" required class="w-full border p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm" placeholder="Contoh: Entry Data Susenas">
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Kegiatan <span class="text-red-500">*</span></label>
                                    <textarea name="deskripsi" id="f_deskripsi" rows="3" required class="w-full border p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm" placeholder="Jelaskan apa saja yang Anda kerjakan..."></textarea>
                                </div>

                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Hasil (Opsional)</label>
                                        <textarea name="hasil" id="f_hasil" rows="2" class="w-full border p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Kendala (Opsional)</label>
                                        <textarea name="kendala" id="f_kendala" rows="2" class="w-full border p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm"></textarea>
                                    </div>
                                </div>

                                <div class="mb-4 hidden" id="div-solusi">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Solusi</label>
                                    <textarea name="solusi" id="f_solusi" rows="2" class="w-full border p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm"></textarea>
                                </div>

                                <div class="mb-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Foto Dokumentasi <span class="text-red-500" id="req-foto">*</span></label>
                                    <p class="text-xs text-gray-500 mb-2" id="hint-foto">Format JPG/PNG (Maks 2MB).</p>
                                    <input type="file" name="dokumentasi" id="f_dokumentasi" accept="image/jpeg, image/png" required class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                </div>

                                <button type="submit" id="btn-submit" class="w-full bg-blue-600 text-white font-bold py-2.5 rounded-lg hover:bg-blue-700 transition flex justify-center items-center gap-2">
                                    <i data-lucide="send" class="w-4 h-4"></i> Kirim Logbook
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- KOLOM 2: RIWAYAT -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden h-full flex flex-col">
                        <div class="p-5 border-b border-gray-100 bg-gray-50 flex justify-between items-center flex-wrap gap-3">
                            <h2 class="text-lg font-bold text-gray-800">Riwayat Logbook</h2>
                            
                            <!-- TOMBOL UNDUH PDF LANGSUNG -->
                            <button id="btn-pdf" onclick="generatePDF()" class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors shadow-sm focus:ring-2 focus:ring-blue-300">
                                <i data-lucide="printer" class="w-4 h-4"></i> 
                                <span>Download Rekap Logbook</span>
                            </button>
                        </div>
                        
                        <div class="overflow-y-auto flex-1 p-6 space-y-4">
                            <?php if (count($riwayat_logbook) > 0): ?>
                                <?php foreach ($riwayat_logbook as $row): ?>
                                    
                                    <div class="border rounded-xl p-5 hover:bg-gray-50 transition <?= $row['status'] == 'revisi' ? 'border-red-200 bg-red-50/30' : 'border-gray-200' ?>">
                                        <div class="flex justify-between items-start mb-3">
                                            <div>
                                                <p class="text-sm text-gray-500 mb-1"><i data-lucide="calendar" class="w-4 h-4 inline mr-1"></i> <?= date('d M Y', strtotime($row['tanggal'])) ?></p>
                                                <h3 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($row['judul_kegiatan']) ?></h3>
                                            </div>
                                            
                                            <!-- Badge Status -->
                                            <?php if ($row['status'] == 'menunggu'): ?>
                                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200 whitespace-nowrap"><i data-lucide="clock" class="w-3 h-3 inline mr-1"></i> Menunggu Review</span>
                                            <?php elseif ($row['status'] == 'disetujui'): ?>
                                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200 whitespace-nowrap"><i data-lucide="check-circle-2" class="w-3 h-3 inline mr-1"></i> Disetujui</span>
                                            <?php else: ?>
                                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200 whitespace-nowrap"><i data-lucide="alert-triangle" class="w-3 h-3 inline mr-1"></i> Perlu Revisi</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <p class="text-sm text-gray-700 mb-4"><?= nl2br(htmlspecialchars($row['deskripsi'])) ?></p>
                                        
                                        <!-- Catatan revisi pembimbing -->
                                        <?php if ($row['status'] == 'revisi' && $row['catatan_pembimbing']): ?>
                                            <div class="bg-white border border-red-200 p-3 rounded-lg mb-4 flex gap-3 shadow-sm">
                                                <i data-lucide="message-square-warning" class="w-5 h-5 text-red-500 shrink-0"></i>
                                                <div>
                                                    <p class="text-xs font-bold text-red-700 mb-1">Catatan Pembimbing:</p>
                                                    <p class="text-sm text-gray-700"><?= nl2br(htmlspecialchars($row['catatan_pembimbing'])) ?></p>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="flex items-center gap-3 pt-3 border-t border-gray-100">
                                            <a href="<?= htmlspecialchars($row['dokumentasi']) ?>" target="_blank" class="text-sm font-medium text-blue-600 hover:text-blue-800 flex items-center gap-1 bg-blue-50 px-3 py-1.5 rounded-lg">
                                                <i data-lucide="image" class="w-4 h-4"></i> Lihat Foto
                                            </a>
                                            
                                            <?php if ($row['status'] == 'revisi'): ?>
                                                <button type="button" 
                                                    onclick="editLogbook(<?= htmlspecialchars(json_encode([
                                                        'id' => $row['id'],
                                                        'tanggal' => $row['tanggal'],
                                                        'judul' => $row['judul_kegiatan'],
                                                        'deskripsi' => $row['deskripsi'],
                                                        'hasil' => $row['hasil'] !== '-' ? $row['hasil'] : '',
                                                        'kendala' => $row['kendala'] !== '-' ? $row['kendala'] : '',
                                                        'solusi' => $row['solusi'] !== '-' ? $row['solusi'] : ''
                                                    ])) ?>)" 
                                                    class="text-sm font-medium text-red-600 hover:text-red-800 flex items-center gap-1 bg-red-100 px-3 py-1.5 rounded-lg">
                                                    <i data-lucide="edit-3" class="w-4 h-4"></i> Perbaiki Logbook
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-10">
                                    <i data-lucide="book-dashed" class="w-16 h-16 text-gray-300 mx-auto mb-3"></i>
                                    <p class="text-gray-500 font-medium">Belum ada logbook yang dicatat.</p>
                                </div>
                            <?php endif; ?>
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
                <h1 style="margin: 0; font-size: 22px; color: #000; text-transform: uppercase;">REKAPITULASI LOGBOOK MAGANG</h1>
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
                        <div style="font-weight: bold; text-transform: uppercase; margin-bottom: 8px;">Total Kegiatan</div>
                        <div style="font-size: 18px; font-weight: bold;"><?= $total_logbook ?></div>
                    </td>
                    <td style="border: 1px solid #000; padding: 12px; text-align: center; width: 25%;">
                        <div style="font-weight: bold; text-transform: uppercase; margin-bottom: 8px;">Disetujui</div>
                        <div style="font-size: 18px; font-weight: bold;"><?= $disetujui ?></div>
                    </td>
                    <td style="border: 1px solid #000; padding: 12px; text-align: center; width: 25%;">
                        <div style="font-weight: bold; text-transform: uppercase; margin-bottom: 8px;">Menunggu Review</div>
                        <div style="font-size: 18px; font-weight: bold;"><?= $menunggu ?></div>
                    </td>
                    <td style="border: 1px solid #000; padding: 12px; text-align: center; width: 25%;">
                        <div style="font-weight: bold; text-transform: uppercase; margin-bottom: 8px;">Perlu Revisi</div>
                        <div style="font-size: 18px; font-weight: bold;"><?= $revisi ?></div>
                    </td>
                </tr>
            </table>

            <!-- TABEL DETAIL RIWAYAT LOGBOOK -->
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 40px; font-size: 14px; color: #000;">
                <thead>
                    <tr>
                        <th style="border: 1px solid #000; padding: 10px; width: 5%; text-align: center; font-weight: bold;">No</th>
                        <th style="border: 1px solid #000; padding: 10px; width: 15%; text-align: center; font-weight: bold;">Tanggal</th>
                        <th style="border: 1px solid #000; padding: 10px; width: 25%; text-align: center; font-weight: bold;">Judul Kegiatan</th>
                        <th style="border: 1px solid #000; padding: 10px; width: 40%; text-align: left; font-weight: bold;">Deskripsi Kegiatan</th>
                        <th style="border: 1px solid #000; padding: 10px; width: 15%; text-align: center; font-weight: bold;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($riwayat_logbook) > 0): ?>
                        <?php 
                        // Urutkan riwayat dari terlama ke terbaru untuk cetak rekap (ASCENDING)
                        $riwayat_cetak = array_reverse($riwayat_logbook);
                        $no = 1; 
                        foreach ($riwayat_cetak as $row): 
                        ?>
                            <tr>
                                <td style="border: 1px solid #000; padding: 8px; text-align: center; vertical-align: top;"><?= $no++ ?></td>
                                <td style="border: 1px solid #000; padding: 8px; text-align: center; vertical-align: top;"><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                                <td style="border: 1px solid #000; padding: 8px; text-align: left; vertical-align: top; font-weight: bold;"><?= htmlspecialchars($row['judul_kegiatan']) ?></td>
                                <td style="border: 1px solid #000; padding: 8px; text-align: left; vertical-align: top;"><?= nl2br(htmlspecialchars($row['deskripsi'])) ?></td>
                                <td style="border: 1px solid #000; padding: 8px; text-align: center; vertical-align: top;"><?= ucfirst($row['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="border: 1px solid #000; padding: 15px; text-align: center;">Belum ada logbook kegiatan yang dicatat.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- TANDA TANGAN (Pembimbing) -->
            <div style="width: 100%; text-align: right; color: #000; font-size: 14px; page-break-inside: avoid;">
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

        function editLogbook(data) {
            document.getElementById('form-title').innerText = "Perbaiki Logbook";
            document.getElementById('f_logbook_id').value = data.id;
            document.getElementById('f_tanggal').value = data.tanggal;
            document.getElementById('f_judul').value = data.judul;
            document.getElementById('f_deskripsi').value = data.deskripsi;
            document.getElementById('f_hasil').value = data.hasil;
            document.getElementById('f_kendala').value = data.kendala;
            document.getElementById('f_solusi').value = data.solusi;
            
            document.getElementById('div-solusi').classList.remove('hidden');

            document.getElementById('f_dokumentasi').removeAttribute('required');
            document.getElementById('req-foto').classList.add('hidden');
            document.getElementById('hint-foto').innerText = "Biarkan kosong jika tidak ingin mengubah foto dokumentasi.";
            
            const btnSubmit = document.getElementById('btn-submit');
            btnSubmit.innerHTML = '<i data-lucide="refresh-cw" class="w-4 h-4"></i> Kirim Ulang Logbook';
            btnSubmit.classList.replace('bg-blue-600', 'bg-yellow-600');
            btnSubmit.classList.replace('hover:bg-blue-700', 'hover:bg-yellow-700');
            
            document.getElementById('btn-cancel-edit').classList.remove('hidden');
            
            lucide.createIcons();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function resetForm() {
            document.getElementById('formLogbook').reset();
            document.getElementById('f_tanggal').value = "<?= date('Y-m-d') ?>";
            
            document.getElementById('form-title').innerText = "Isi Kegiatan Hari Ini";
            document.getElementById('f_logbook_id').value = "";
            
            document.getElementById('div-solusi').classList.add('hidden');

            document.getElementById('f_dokumentasi').setAttribute('required', 'required');
            document.getElementById('req-foto').classList.remove('hidden');
            document.getElementById('hint-foto').innerText = "Format JPG/PNG (Maks 2MB).";

            const btnSubmit = document.getElementById('btn-submit');
            btnSubmit.innerHTML = '<i data-lucide="send" class="w-4 h-4"></i> Kirim Logbook';
            btnSubmit.classList.replace('bg-yellow-600', 'bg-blue-600');
            btnSubmit.classList.replace('hover:bg-yellow-700', 'hover:bg-blue-700');

            document.getElementById('btn-cancel-edit').classList.add('hidden');
            lucide.createIcons();
        }

        document.getElementById('f_kendala').addEventListener('input', function() {
            const divSolusi = document.getElementById('div-solusi');
            if (this.value.trim() !== '') {
                divSolusi.classList.remove('hidden');
            } else {
                divSolusi.classList.add('hidden');
                document.getElementById('f_solusi').value = ''; 
            }
        });

        // FUNGSI UNTUK GENERATE DAN DOWNLOAD PDF LOGBOOK (A4 Hitam Putih)
        function generatePDF() {
            const btn = document.getElementById('btn-pdf');
            const originalText = btn.innerHTML;
            
            btn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin"></i> Memproses PDF...';
            lucide.createIcons();
            
            const element = document.getElementById('pdf-content');
            element.parentElement.style.display = 'block';
            
            const opt = {
                margin:       10, 
                filename:     'Rekap_Logbook_<?= str_replace(" ", "_", htmlspecialchars($nama_user)) ?>.pdf',
                image:        { type: 'jpeg', quality: 1 },
                html2canvas:  { scale: 2, useCORS: true }, 
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' } 
            };

            html2pdf().set(opt).from(element).save().then(() => {
                element.parentElement.style.display = 'none';
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