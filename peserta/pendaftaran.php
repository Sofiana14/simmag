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
checkRole('peserta');

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';
$cek_daftar = false;

// =================================================================================================
// 1. CEK PROFIL PESERTA & PROSES LENGKAPI PROFIL
// =================================================================================================

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_profil'])) {
    $nim = trim($_POST['nim_baru']);
    $no_hp = trim($_POST['no_hp_baru']);
    $nama_lengkap = $_SESSION['name']; 

    try {
        $stmt_insert = $pdo->prepare("INSERT INTO peserta (user_id, nama, nim, no_hp) VALUES (?, ?, ?, ?)");
        $stmt_insert->execute([$user_id, $nama_lengkap, $nim, $no_hp]);
        $success = "Profil berhasil dilengkapi! Silakan lanjutkan pendaftaran magang di bawah ini.";
    } catch (Exception $e) {
        $error = "Gagal menyimpan profil: " . $e->getMessage();
    }
}

$stmt = $pdo->prepare("SELECT * FROM peserta WHERE user_id = ?");
$stmt->execute([$user_id]);
$peserta = $stmt->fetch();


// =================================================================================================
// 2. PROSES PENDAFTARAN MAGANG
// =================================================================================================
if ($peserta) {
    $peserta_id = $peserta['id'];

    $stmt = $pdo->prepare("SELECT * FROM pendaftaran WHERE peserta_id = ? AND status IN ('menunggu', 'diterima') ORDER BY id DESC LIMIT 1");
    $stmt->execute([$peserta_id]);
    $cek_daftar = $stmt->fetch();

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_pendaftaran']) && !$cek_daftar) {
        $institusi = trim($_POST['institusi']);
        $fakultas = trim($_POST['fakultas']);
        $prodi = trim($_POST['prodi']);
        $tanggal_mulai = $_POST['tanggal_mulai'];
        $tanggal_selesai = $_POST['tanggal_selesai'];
        
        $bulan_array = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
        $bulan = date('m', strtotime($tanggal_mulai));
        $tahun = date('Y', strtotime($tanggal_mulai));
        $periode = $bulan_array[$bulan] . " " . $tahun;

        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM pendaftaran WHERE periode = ? AND status != 'ditolak'");
        $stmt->execute([$periode]);
        $kuota = $stmt->fetch();

        if ($kuota['total'] >= 6) {
            $error = "Pendaftaran gagal: Kuota magang untuk periode $periode sudah penuh (Maksimal 6 peserta). Silakan pilih periode lain.";
        } else {
            
            // PERBAIKAN AUTO-FIX DIRECTORY:
            // 1. Definisikan path target
            $upload_base = __DIR__ . '/../uploads';
            $upload_dokumen = __DIR__ . '/../uploads/dokumen';

            // 2. Hancurkan penghalang jika 'uploads' itu adalah file biasa (bukan folder)
            if (file_exists($upload_base) && !is_dir($upload_base)) {
                unlink($upload_base); // Hapus file pengganggu
            }
            // Buat foldernya
            if (!file_exists($upload_base)) {
                mkdir($upload_base, 0777, true);
            }

            // 3. Hancurkan penghalang jika 'dokumen' itu adalah file biasa
            if (file_exists($upload_dokumen) && !is_dir($upload_dokumen)) {
                unlink($upload_dokumen); // Hapus file pengganggu
            }
            // Buat foldernya
            if (!file_exists($upload_dokumen)) {
                mkdir($upload_dokumen, 0777, true);
            }

            // Path final yang sudah dijamin 100% adalah folder asli
            $target_dir = $upload_dokumen . '/';
            $db_dir = "../uploads/dokumen/";

            $cv_file = $_FILES['cv'];
            $surat_file = $_FILES['surat_pengantar'];
            $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

            if ($cv_file['error'] !== UPLOAD_ERR_OK || $surat_file['error'] !== UPLOAD_ERR_OK) {
                $error = "Gagal membaca file! Pastikan ukuran file PDF Anda tidak melebihi batas maksimal.";
            } elseif (!in_array($cv_file['type'], $allowed_types) || !in_array($surat_file['type'], $allowed_types)) {
                $error = "Format file tidak diizinkan. Harap gunakan format PDF atau DOC/DOCX.";
            } else {
                $cv_filename = time() . "_CV_" . preg_replace("/[^a-zA-Z0-9.]/", "_", basename($cv_file['name']));
                $surat_filename = time() . "_Surat_" . preg_replace("/[^a-zA-Z0-9.]/", "_", basename($surat_file['name']));

                // PROSES UPLOAD FILE
                if (move_uploaded_file($cv_file['tmp_name'], $target_dir . $cv_filename) && move_uploaded_file($surat_file['tmp_name'], $target_dir . $surat_filename)) {
                    try {
                        $pdo->beginTransaction();

                        $no_pendaftaran = "MAG-" . date('Ymd') . "-" . rand(1000, 9999);

                        $stmt = $pdo->prepare("INSERT INTO pendaftaran (peserta_id, nomor_pendaftaran, tanggal_daftar, tanggal_mulai, tanggal_selesai, periode, status) VALUES (?, ?, CURDATE(), ?, ?, ?, 'menunggu')");
                        $stmt->execute([$peserta_id, $no_pendaftaran, $tanggal_mulai, $tanggal_selesai, $periode]);
                        $pendaftaran_id = $pdo->lastInsertId();

                        $stmt = $pdo->prepare("INSERT INTO dokumen (peserta_id, pendaftaran_id, jenis_dokumen, nama_file, file_path) VALUES (?, ?, 'CV', ?, ?)");
                        $stmt->execute([$peserta_id, $pendaftaran_id, $cv_file['name'], $db_dir . $cv_filename]);

                        $stmt = $pdo->prepare("INSERT INTO dokumen (peserta_id, pendaftaran_id, jenis_dokumen, nama_file, file_path) VALUES (?, ?, 'SURAT_PENGANTAR', ?, ?)");
                        $stmt->execute([$peserta_id, $pendaftaran_id, $surat_file['name'], $db_dir . $surat_filename]);

                        $stmt = $pdo->prepare("UPDATE peserta SET institusi = ?, fakultas = ?, prodi = ? WHERE id = ?");
                        $stmt->execute([$institusi, $fakultas, $prodi, $peserta_id]);

                        $pdo->commit();
                        $success = "Pendaftaran berhasil dikirim! Menunggu verifikasi admin.";
                        $cek_daftar = true; 
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $error = "Terjadi kesalahan sistem database: " . $e->getMessage();
                    }
                } else {
                    $error = "Sistem gagal memindahkan dokumen. Server memblokir akses ke folder tujuan.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pendaftaran Magang - SIMMAG BPS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style> .step-content { display: none; } .step-content.active { display: block; } </style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <!-- SIDEBAR -->
    <aside class="w-64 bg-slate-800 text-white flex flex-col hidden md:flex">
        <div class="p-6 text-xl font-bold border-b border-slate-700 flex items-center gap-2">
            <i data-lucide="bar-chart-2" class="text-blue-400"></i> SIMMAG BPS
        </div>
        <nav class="flex-1 p-4 space-y-2">
            <a href="dashboard.php" class="flex items-center gap-3 p-3 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
            </a>
            <a href="pendaftaran.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-600 text-white transition">
                <i data-lucide="file-text" class="w-5 h-5"></i> Pendaftaran Magang
            </a>
            <a href="../logout.php" class="flex items-center gap-3 p-3 mt-8 rounded-lg text-red-400 hover:bg-red-500 hover:text-white transition">
                <i data-lucide="log-out" class="w-5 h-5"></i> Logout
            </a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="flex-1 overflow-y-auto">
        <header class="bg-white border-b border-gray-200 p-4 px-8 flex justify-between items-center">
            <h1 class="text-xl font-semibold text-gray-800">Formulir Pendaftaran Magang</h1>
            <div class="flex items-center gap-3">
                <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($_SESSION['name']) ?></span>
            </div>
        </header>

        <div class="p-8 max-w-4xl mx-auto">
            
            <!-- PESAN NOTIFIKASI -->
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 text-red-700 rounded-r-lg">
                    <p class="font-bold flex items-center gap-1"><i data-lucide="alert-circle" class="w-4 h-4"></i> Gagal</p>
                    <p><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 text-green-700 rounded-r-lg">
                    <p class="font-bold flex items-center gap-1"><i data-lucide="check-circle" class="w-4 h-4"></i> Berhasil!</p>
                    <p><?= htmlspecialchars($success) ?></p>
                </div>
            <?php endif; ?>

            <!-- LOGIKA TAMPILAN: PROFIL BELUM LENGKAP -->
            <?php if (!$peserta): ?>
                <div class="bg-white p-8 rounded-xl shadow-sm border border-yellow-200">
                    <div class="text-center mb-6">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-yellow-50 mb-4 border border-yellow-100">
                            <i data-lucide="user-plus" class="w-8 h-8 text-yellow-600"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-2">Lengkapi Profil Anda</h2>
                        <p class="text-gray-600 max-w-md mx-auto text-sm">Sebelum mendaftar magang, Anda wajib melengkapi data Nomor Induk Mahasiswa/Siswa (NIM/NIS) dan Nomor HP/WhatsApp yang aktif.</p>
                    </div>

                    <form action="" method="POST" class="max-w-md mx-auto space-y-4">
                        <input type="hidden" name="submit_profil" value="1">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">NIM / NIS <span class="text-red-500">*</span></label>
                            <input type="text" name="nim_baru" required placeholder="Masukkan Nomor Induk Anda" class="w-full border border-gray-300 p-3 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nomor HP / WhatsApp <span class="text-red-500">*</span></label>
                            <input type="text" name="no_hp_baru" required placeholder="Contoh: 081234567890" class="w-full border border-gray-300 p-3 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        
                        <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition flex items-center justify-center gap-2 mt-4">
                            <i data-lucide="save" class="w-5 h-5"></i> Simpan Profil & Lanjutkan
                        </button>
                    </form>
                </div>

            <!-- LOGIKA TAMPILAN: SUDAH DAFTAR MAGANG -->
            <?php elseif ($cek_daftar): ?>
                <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-200 text-center">
                    <i data-lucide="check-circle-2" class="w-16 h-16 text-green-500 mx-auto mb-4"></i>
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Anda Sudah Terdaftar!</h2>
                    <p class="text-gray-600 mb-6">Berkas pendaftaran Anda saat ini dalam status: 
                        <span class="font-bold text-yellow-600 uppercase">MENUNGGU VERIFIKASI ADMIN</span>
                    </p>
                    <a href="dashboard.php" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Kembali ke Dashboard</a>
                </div>

            <!-- LOGIKA TAMPILAN: PROFIL ADA & BELUM DAFTAR MAGANG -->
            <?php else: ?>

                <!-- PROGRESS BAR -->
                <div class="mb-8">
                    <div class="flex items-center justify-between relative">
                        <div class="absolute left-0 top-1/2 transform -translate-y-1/2 w-full h-1 bg-gray-200 -z-10"></div>
                        
                        <div class="step-indicator text-center" id="ind-1">
                            <div class="w-10 h-10 mx-auto bg-blue-600 text-white rounded-full flex items-center justify-center font-bold border-4 border-white">1</div>
                            <p class="text-xs mt-2 font-medium">Biodata</p>
                        </div>
                        <div class="step-indicator text-center" id="ind-2">
                            <div class="w-10 h-10 mx-auto bg-gray-200 text-gray-600 rounded-full flex items-center justify-center font-bold border-4 border-white">2</div>
                            <p class="text-xs mt-2 font-medium text-gray-500">Instansi</p>
                        </div>
                        <div class="step-indicator text-center" id="ind-3">
                            <div class="w-10 h-10 mx-auto bg-gray-200 text-gray-600 rounded-full flex items-center justify-center font-bold border-4 border-white">3</div>
                            <p class="text-xs mt-2 font-medium text-gray-500">Periode</p>
                        </div>
                        <div class="step-indicator text-center" id="ind-4">
                            <div class="w-10 h-10 mx-auto bg-gray-200 text-gray-600 rounded-full flex items-center justify-center font-bold border-4 border-white">4</div>
                            <p class="text-xs mt-2 font-medium text-gray-500">Dokumen</p>
                        </div>
                        <div class="step-indicator text-center" id="ind-5">
                            <div class="w-10 h-10 mx-auto bg-gray-200 text-gray-600 rounded-full flex items-center justify-center font-bold border-4 border-white">5</div>
                            <p class="text-xs mt-2 font-medium text-gray-500">Review</p>
                        </div>
                    </div>
                </div>

                <!-- FORM MULTI-STEP PENDAFTARAN -->
                <form action="" method="POST" enctype="multipart/form-data" id="pendaftaranForm" class="bg-white p-8 rounded-xl shadow-sm border border-gray-100">
                    <input type="hidden" name="submit_pendaftaran" value="1">

                    <!-- STEP 1: BIODATA -->
                    <div class="step-content active" id="step-1">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Langkah 1: Konfirmasi Biodata</h2>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Nama Lengkap</label>
                                <input type="text" value="<?= htmlspecialchars($peserta['nama'] ?? '') ?>" class="w-full border bg-gray-50 p-2 rounded-lg" readonly>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">NIM / NIS</label>
                                <input type="text" value="<?= htmlspecialchars($peserta['nim'] ?? '') ?>" class="w-full border bg-gray-50 p-2 rounded-lg" readonly>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-sm text-gray-600 mb-1">Nomor HP / WhatsApp</label>
                                <input type="text" value="<?= htmlspecialchars($peserta['no_hp'] ?? '') ?>" class="w-full border bg-gray-50 p-2 rounded-lg" readonly>
                            </div>
                        </div>
                        <p class="text-xs text-red-500 mt-2">*Data biodata ditarik dari profil Anda.</p>
                        
                        <div class="mt-6 flex justify-end">
                            <button type="button" onclick="nextStep(2)" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Selanjutnya</button>
                        </div>
                    </div>

                    <!-- STEP 2: INSTANSI -->
                    <div class="step-content" id="step-2">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Langkah 2: Data Instansi/Kampus</h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Universitas / Sekolah <span class="text-red-500">*</span></label>
                                <input type="text" name="institusi" id="institusi" required class="w-full border p-2 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" value="<?= htmlspecialchars($peserta['institusi'] ?? '') ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fakultas (Opsional)</label>
                                <input type="text" name="fakultas" id="fakultas" class="w-full border p-2 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" value="<?= htmlspecialchars($peserta['fakultas'] ?? '') ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Program Studi / Jurusan <span class="text-red-500">*</span></label>
                                <input type="text" name="prodi" id="prodi" required class="w-full border p-2 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" value="<?= htmlspecialchars($peserta['prodi'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="mt-6 flex justify-between">
                            <button type="button" onclick="prevStep(1)" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300">Kembali</button>
                            <button type="button" onclick="nextStep(3)" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Selanjutnya</button>
                        </div>
                    </div>

                    <!-- STEP 3: PERIODE -->
                    <div class="step-content" id="step-3">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Langkah 3: Periode Magang</h2>
                        
                        <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg mb-4 text-sm text-blue-800">
                            <i data-lucide="info" class="w-4 h-4 inline-block mr-1"></i>
                            Sistem akan menolak pendaftaran secara otomatis jika kuota pada periode yang Anda pilih (Maksimal 6 peserta) telah penuh.
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai <span class="text-red-500">*</span></label>
                                <input type="date" name="tanggal_mulai" id="tanggal_mulai" required class="w-full border p-2 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Selesai <span class="text-red-500">*</span></label>
                                <input type="date" name="tanggal_selesai" id="tanggal_selesai" required class="w-full border p-2 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                            </div>
                        </div>
                        
                        <div class="mt-6 flex justify-between">
                            <button type="button" onclick="prevStep(2)" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300">Kembali</button>
                            <button type="button" onclick="nextStep(4)" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Selanjutnya</button>
                        </div>
                    </div>

                    <!-- STEP 4: UPLOAD DOKUMEN -->
                    <div class="step-content" id="step-4">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Langkah 4: Upload Dokumen</h2>
                        
                        <div class="space-y-6">
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:bg-gray-50 transition">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Curriculum Vitae (CV) <span class="text-red-500">*</span></label>
                                <p class="text-xs text-gray-500 mb-4">Format: PDF/DOCX (Maks 2MB)</p>
                                <input type="file" name="cv" id="cv" accept=".pdf,.doc,.docx" required class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </div>

                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:bg-gray-50 transition">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Surat Pengantar Instansi/Kampus <span class="text-red-500">*</span></label>
                                <p class="text-xs text-gray-500 mb-4">Format: PDF/DOCX (Maks 2MB)</p>
                                <input type="file" name="surat_pengantar" id="surat_pengantar" accept=".pdf,.doc,.docx" required class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </div>
                        </div>

                        <div class="mt-6 flex justify-between">
                            <button type="button" onclick="prevStep(3)" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300">Kembali</button>
                            <button type="button" onclick="reviewData(); nextStep(5)" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Review Data</button>
                        </div>
                    </div>

                    <!-- STEP 5: REVIEW -->
                    <div class="step-content" id="step-5">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Langkah 5: Konfirmasi Pendaftaran</h2>
                        <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200 mb-6">
                            <p class="text-sm text-yellow-800 font-medium">Apakah Anda yakin data pendaftaran sudah benar?</p>
                        </div>

                        <table class="w-full text-sm text-left text-gray-600 mb-6">
                            <tbody>
                                <tr class="border-b"><th class="py-2 w-1/3">Institusi</th><td class="py-2 font-medium" id="rev-inst"></td></tr>
                                <tr class="border-b"><th class="py-2">Program Studi</th><td class="py-2 font-medium" id="rev-prodi"></td></tr>
                                <tr class="border-b"><th class="py-2">Tanggal Mulai</th><td class="py-2 font-medium" id="rev-mulai"></td></tr>
                                <tr class="border-b"><th class="py-2">Tanggal Selesai</th><td class="py-2 font-medium" id="rev-selesai"></td></tr>
                                <tr class="border-b"><th class="py-2">File CV</th><td class="py-2 font-medium text-blue-600" id="rev-cv"></td></tr>
                                <tr><th class="py-2">Surat Pengantar</th><td class="py-2 font-medium text-blue-600" id="rev-surat"></td></tr>
                            </tbody>
                        </table>
                        
                        <div class="mt-6 flex justify-between">
                            <button type="button" onclick="prevStep(4)" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300">Kembali</button>
                            <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 font-bold flex items-center gap-2">
                                <i data-lucide="send" class="w-4 h-4"></i> Kirim Pendaftaran
                            </button>
                        </div>
                    </div>
                </form>

            <?php endif; ?>
        </div>
    </main>

    <script>
        lucide.createIcons();

        function nextStep(step) {
            const currentStepDiv = document.querySelector('.step-content.active');
            const inputs = currentStepDiv.querySelectorAll('input[required]');
            let valid = true;
            inputs.forEach(input => {
                if(!input.value) {
                    input.reportValidity();
                    valid = false;
                }
            });
            
            if(!valid) return;

            document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
            const nextElem = document.getElementById(`step-${step}`);
            if (nextElem) {
                nextElem.classList.add('active');
                updateIndicator(step);
            }
        }

        function prevStep(step) {
            document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
            const prevElem = document.getElementById(`step-${step}`);
            if (prevElem) {
                prevElem.classList.add('active');
                updateIndicator(step);
            }
        }

        function updateIndicator(step) {
            for(let i=1; i<=5; i++){
                const indContainer = document.getElementById(`ind-${i}`);
                if (!indContainer) continue;
                
                const ind = indContainer.querySelector('div');
                const text = indContainer.querySelector('p');
                
                if(i < step) {
                    ind.className = "w-10 h-10 mx-auto bg-green-500 text-white rounded-full flex items-center justify-center font-bold border-4 border-white";
                    ind.innerHTML = "✓";
                    text.classList.replace("text-gray-500", "text-green-600");
                } else if (i === step) {
                    ind.className = "w-10 h-10 mx-auto bg-blue-600 text-white rounded-full flex items-center justify-center font-bold border-4 border-white";
                    ind.innerHTML = i;
                    text.classList.remove("text-gray-500");
                } else {
                    ind.className = "w-10 h-10 mx-auto bg-gray-200 text-gray-600 rounded-full flex items-center justify-center font-bold border-4 border-white";
                    ind.innerHTML = i;
                    text.classList.add("text-gray-500");
                }
            }
        }

        function reviewData() {
            document.getElementById('rev-inst').innerText = document.getElementById('institusi').value;
            document.getElementById('rev-prodi').innerText = document.getElementById('prodi').value;
            document.getElementById('rev-mulai').innerText = document.getElementById('tanggal_mulai').value;
            document.getElementById('rev-selesai').innerText = document.getElementById('tanggal_selesai').value;
            
            const cvFile = document.getElementById('cv').files[0];
            document.getElementById('rev-cv').innerText = cvFile ? cvFile.name : '-';
            
            const suratFile = document.getElementById('surat_pengantar').files[0];
            document.getElementById('rev-surat').innerText = suratFile ? suratFile.name : '-';
        }
    </script>
</body>
</html>