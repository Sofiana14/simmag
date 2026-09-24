<?php
// Nonaktifkan session strict jika hanya untuk landing page, tapi butuh koneksi DB untuk cek kuota
require_once 'config/database.php';

// Menghitung Kuota Realtime Bulan Ini (Contoh: September 2026)
$bulan_array = [
    '01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April',
    '05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus',
    '09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'
];
$bulan_sekarang = date('m');
$tahun_sekarang = date('Y');
$periode_aktif = $bulan_array[$bulan_sekarang] . " " . $tahun_sekarang;

// Ambil jumlah peserta yang sudah mendaftar dan tidak ditolak pada periode ini
$stmt = $pdo->prepare("SELECT COUNT(*) as terisi FROM pendaftaran WHERE periode = ? AND status != 'ditolak'");
$stmt->execute([$periode_aktif]);
$data_kuota = $stmt->fetch();

$terisi = $data_kuota['terisi'];
$kuota_maksimal = 6; // Sesuai requirement (6 peserta per bulan)
$persentase = ($terisi / $kuota_maksimal) * 100;
if($persentase > 100) $persentase = 100;

$status_kuota = ($terisi >= $kuota_maksimal) ? 'Kuota Penuh' : 'Masih Tersedia';
$warna_status = ($terisi >= $kuota_maksimal) ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700';
$warna_progress = ($terisi >= $kuota_maksimal) ? 'bg-red-500' : 'bg-green-500';
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIMMAG - Magang BPS Kota Yogyakarta</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-slate-50 text-slate-800">

    <!-- NAVBAR -->
    <nav class="bg-white/80 backdrop-blur-md border-b border-gray-100 fixed w-full z-50 top-0 transition-all">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-blue-600/20">
                        <i data-lucide="bar-chart-2" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-xl leading-tight text-slate-900">SIMMAG BPS</h1>
                        <p class="text-[10px] text-slate-500 font-medium uppercase tracking-wider">Kota Yogyakarta</p>
                    </div>
                </div>
                
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#beranda" class="text-sm font-medium text-blue-600">Beranda</a>
                    <a href="#persyaratan" class="text-sm font-medium text-slate-600 hover:text-blue-600 transition">Persyaratan</a>
                    <a href="#alur" class="text-sm font-medium text-slate-600 hover:text-blue-600 transition">Alur Pendaftaran</a>
                    <a href="#kuota" class="text-sm font-medium text-slate-600 hover:text-blue-600 transition">Info Kuota</a>
                </div>

                <div class="flex items-center gap-4">
                    <a href="login.php" class="hidden md:block text-sm font-semibold text-slate-600 hover:text-blue-600 transition">Login</a>
                    <a href="login.php" class="bg-blue-600 text-white px-5 py-2.5 rounded-full text-sm font-semibold hover:bg-blue-700 transition shadow-lg shadow-blue-600/30">
                        Daftar Magang
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- HERO SECTION -->
    <section id="beranda" class="pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden relative">
        <!-- Background Decoration -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-7xl h-full -z-10">
            <div class="absolute top-20 left-20 w-72 h-72 bg-blue-400 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
            <div class="absolute top-20 right-20 w-72 h-72 bg-teal-400 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-50 border border-blue-100 text-blue-600 text-xs font-bold uppercase tracking-wider mb-8">
                <span class="relative flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                </span>
                Pendaftaran Periode <?= date('Y') ?> Dibuka
            </div>
            
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-slate-900 tracking-tight mb-6 max-w-4xl mx-auto leading-tight">
                Sistem Informasi Pendaftaran Magang <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-teal-500">BPS Kota Yogyakarta</span>
            </h1>
            
            <p class="mt-4 text-lg text-slate-600 max-w-2xl mx-auto mb-10 leading-relaxed">
                Daftarkan kegiatan magang Anda secara mudah, cepat, dan terintegrasi melalui sistem informasi magang resmi Badan Pusat Statistik Kota Yogyakarta.
            </p>
            
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="login.php" class="bg-blue-600 text-white px-8 py-3.5 rounded-full text-base font-semibold hover:bg-blue-700 transition shadow-xl shadow-blue-600/20 flex items-center justify-center gap-2">
                    Daftar Sekarang <i data-lucide="arrow-right" class="w-5 h-5"></i>
                </a>
                <a href="login.php" class="bg-white text-slate-700 border border-slate-200 px-8 py-3.5 rounded-full text-base font-semibold hover:bg-slate-50 transition flex items-center justify-center gap-2">
                    <i data-lucide="log-in" class="w-5 h-5"></i> Login Peserta
                </a>
            </div>
        </div>
    </section>

    <!-- INFO KUOTA REALTIME -->
    <section id="kuota" class="py-10">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-100 p-8 md:p-10 relative overflow-hidden">
                <!-- Watermark Icon -->
                <i data-lucide="pie-chart" class="absolute -bottom-10 -right-10 w-64 h-64 text-slate-50 transform -rotate-12"></i>
                
                <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-8">
                    <div>
                        <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-1">Status Kuota Magang</h3>
                        <h2 class="text-3xl font-extrabold text-slate-800 mb-2">Periode <?= $periode_aktif ?></h2>
                        <span class="inline-block px-4 py-1.5 rounded-full text-sm font-bold <?= $warna_status ?> border border-white/20 shadow-sm">
                            <?= $status_kuota ?>
                        </span>
                    </div>
                    
                    <div class="w-full md:w-1/2 bg-slate-50 p-6 rounded-2xl border border-slate-100">
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-sm font-semibold text-slate-500">Terisi</span>
                            <span class="text-2xl font-black text-slate-800"><?= $terisi ?> <span class="text-sm font-medium text-slate-500">/ <?= $kuota_maksimal ?> Peserta</span></span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-3 mb-2 overflow-hidden">
                            <div class="<?= $warna_progress ?> h-3 rounded-full transition-all duration-1000 ease-out" style="width: <?= $persentase ?>%"></div>
                        </div>
                        <p class="text-xs text-slate-500 text-right font-medium">Sistem update secara realtime</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- PERSYARATAN SECTION -->
    <section id="persyaratan" class="py-20 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-3xl font-bold text-slate-900 mb-4">Persyaratan Pendaftaran</h2>
                <p class="text-slate-600">Pastikan Anda telah memenuhi persyaratan dan menyiapkan dokumen berikut sebelum memulai proses pendaftaran magang.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Card 1 -->
                <div class="bg-white p-8 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md transition">
                    <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center mb-6">
                        <i data-lucide="graduation-cap" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Status Aktif</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Merupakan mahasiswa tingkat akhir atau siswa SMK aktif dari institusi pendidikan terdaftar.</p>
                </div>
                <!-- Card 2 -->
                <div class="bg-white p-8 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md transition">
                    <div class="w-12 h-12 bg-teal-100 text-teal-600 rounded-xl flex items-center justify-center mb-6">
                        <i data-lucide="file-badge" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Surat Pengantar</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Memiliki surat pengantar magang resmi dari Kampus/Fakultas atau Sekolah (Format PDF).</p>
                </div>
                <!-- Card 3 -->
                <div class="bg-white p-8 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md transition">
                    <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center mb-6">
                        <i data-lucide="contact" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Curriculum Vitae</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">Menyiapkan Curriculum Vitae (CV) terbaru yang mencantumkan keahlian dan kontak aktif (Format PDF).</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ALUR PENDAFTARAN -->
    <section id="alur" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-3xl font-bold text-slate-900 mb-4">Alur Pendaftaran & Pelaksanaan</h2>
                <p class="text-slate-600">Proses pendaftaran hingga selesai magang dilakukan sepenuhnya melalui sistem.</p>
            </div>

            <div class="relative max-w-4xl mx-auto">
                <!-- Line background -->
                <div class="absolute left-[28px] md:left-1/2 top-0 bottom-0 w-0.5 bg-blue-100 md:-translate-x-1/2"></div>

                <div class="space-y-8">
                    <!-- Step 1 -->
                    <div class="relative flex flex-col md:flex-row items-start md:justify-between group">
                        <div class="md:w-5/12 order-2 md:order-1 ml-16 md:ml-0 md:text-right pt-2">
                            <h3 class="text-lg font-bold text-slate-800">Registrasi Akun</h3>
                            <p class="text-sm text-slate-500 mt-1">Buat akun pada sistem SIMMAG menggunakan email aktif.</p>
                        </div>
                        <div class="absolute left-0 md:left-1/2 md:-translate-x-1/2 w-14 h-14 bg-white border-4 border-blue-100 rounded-full flex items-center justify-center text-blue-600 font-bold text-lg group-hover:border-blue-600 group-hover:bg-blue-600 group-hover:text-white transition z-10">01</div>
                        <div class="md:w-5/12 order-3 md:order-3 hidden md:block"></div>
                    </div>
                    
                    <!-- Step 2 -->
                    <div class="relative flex flex-col md:flex-row items-start md:justify-between group">
                        <div class="md:w-5/12 order-1 md:order-1 hidden md:block"></div>
                        <div class="absolute left-0 md:left-1/2 md:-translate-x-1/2 w-14 h-14 bg-white border-4 border-blue-100 rounded-full flex items-center justify-center text-blue-600 font-bold text-lg group-hover:border-blue-600 group-hover:bg-blue-600 group-hover:text-white transition z-10">02</div>
                        <div class="md:w-5/12 order-2 md:order-3 ml-16 md:ml-0 pt-2">
                            <h3 class="text-lg font-bold text-slate-800">Ajukan Pendaftaran</h3>
                            <p class="text-sm text-slate-500 mt-1">Pilih periode magang, lengkapi data instansi, dan unggah CV serta Surat Pengantar.</p>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div class="relative flex flex-col md:flex-row items-start md:justify-between group">
                        <div class="md:w-5/12 order-2 md:order-1 ml-16 md:ml-0 md:text-right pt-2">
                            <h3 class="text-lg font-bold text-slate-800">Verifikasi Admin</h3>
                            <p class="text-sm text-slate-500 mt-1">Admin BPS akan memverifikasi berkas Anda. Pantau status pada dashboard secara berkala.</p>
                        </div>
                        <div class="absolute left-0 md:left-1/2 md:-translate-x-1/2 w-14 h-14 bg-white border-4 border-blue-100 rounded-full flex items-center justify-center text-blue-600 font-bold text-lg group-hover:border-blue-600 group-hover:bg-blue-600 group-hover:text-white transition z-10">03</div>
                        <div class="md:w-5/12 order-3 md:order-3 hidden md:block"></div>
                    </div>

                    <!-- Step 4 -->
                    <div class="relative flex flex-col md:flex-row items-start md:justify-between group">
                        <div class="md:w-5/12 order-1 md:order-1 hidden md:block"></div>
                        <div class="absolute left-0 md:left-1/2 md:-translate-x-1/2 w-14 h-14 bg-white border-4 border-blue-100 rounded-full flex items-center justify-center text-blue-600 font-bold text-lg group-hover:border-blue-600 group-hover:bg-blue-600 group-hover:text-white transition z-10">04</div>
                        <div class="md:w-5/12 order-2 md:order-3 ml-16 md:ml-0 pt-2">
                            <h3 class="text-lg font-bold text-slate-800">Pelaksanaan & Logbook</h3>
                            <p class="text-sm text-slate-500 mt-1">Lakukan magang sesuai penempatan, isi presensi harian, dan lengkapi logbook kegiatan.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="bg-slate-900 text-slate-400 py-12 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="flex justify-center items-center gap-2 mb-6">
                <i data-lucide="bar-chart-2" class="w-8 h-8 text-blue-500"></i>
                <span class="text-xl font-bold text-white tracking-wide">SIMMAG BPS</span>
            </div>
            <p class="mb-6 max-w-md mx-auto text-sm">
                Sistem Informasi Pendaftaran dan Monitoring Magang<br>Badan Pusat Statistik Kota Yogyakarta
            </p>
            <p class="text-xs text-slate-500">
                &copy; <?= date('Y') ?> BPS Kota Yogyakarta. All rights reserved.
            </p>
        </div>
    </footer>

    <script>
        lucide.createIcons();
        
        // Animasi Navbar (Blur saat scroll)
        window.addEventListener('scroll', function() {
            const nav = document.querySelector('nav');
            if (window.scrollY > 20) {
                nav.classList.add('shadow-sm');
            } else {
                nav.classList.remove('shadow-sm');
            }
        });
    </script>
</body>
</html>