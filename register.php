<?php
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_input = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $konfirmasi_password = $_POST['konfirmasi_password'];
    $role = $_POST['role']; 
    
    $domain = strtolower(substr(strrchr($email, "@"), 1)); // Ambil domain email

    // 1. Validasi Password
    if ($password !== $konfirmasi_password) {
        $error = "Konfirmasi password tidak sesuai!";
    } 
    // 2. Validasi Domain Sesuai Role
    elseif ($role == 'admin' && $domain !== 'bps.go.id') {
        $error = "Gagal! Pendaftaran Admin wajib menggunakan email instansi (@bps.go.id).";
    } 
    elseif ($role == 'peserta' && !str_contains($domain, 'ac.id') && !str_contains($domain, 'student')) {
        $error = "Gagal! Pendaftaran Peserta wajib menggunakan email kampus (berakhiran .ac.id).";
    } 
    else {
        // 3. Cek apakah email sudah terdaftar
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = "Email tersebut sudah terdaftar di sistem! Silakan gunakan email lain.";
        } else {
            try {
                $pdo->beginTransaction();

                // Simpan ke tabel users (Menggunakan kolom 'name' sesuai struktur database Anda)
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nama_input, $email, $hashed_password, $role]);
                $user_id = $pdo->lastInsertId();

                // Jika Peserta, otomatis buat data di tabel peserta
                if ($role == 'peserta') {
                    $no_hp = trim($_POST['no_hp']);
                    $stmt = $pdo->prepare("INSERT INTO peserta (user_id, nama, no_hp) VALUES (?, ?, ?)");
                    $stmt->execute([$user_id, $nama_input, $no_hp]);
                } 
                // Jika Pembimbing, otomatis buat data di tabel pembimbing
                elseif ($role == 'pembimbing') {
                    $nip = trim($_POST['nip']);
                    $stmt = $pdo->prepare("INSERT INTO pembimbing (nama, nip) VALUES (?, ?)");
                    $stmt->execute([$nama_input, $nip]);
                }

                $pdo->commit();
                $success = "Registrasi akun berhasil! Silakan kembali ke halaman login.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Terjadi kesalahan sistem: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Registrasi Akun - SIMMAG BPS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-slate-900 flex items-center justify-center min-h-screen py-12">

    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-lg my-auto">
        <div class="text-center mb-6">
            <div class="inline-flex p-3 bg-blue-50 text-blue-600 rounded-xl mb-3">
                <i data-lucide="user-plus" class="w-8 h-8"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">Pendaftaran Akun SIMMAG</h1>
            <p class="text-sm text-gray-500 mt-1">Pilih peran Anda dan lengkapi data registrasi</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 text-red-700 p-3 rounded-lg mb-6 text-sm flex items-center gap-2 border border-red-200">
                <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-50 text-green-700 p-6 rounded-lg mb-6 text-sm flex flex-col gap-2 border border-green-200 text-center">
                <i data-lucide="check-circle-2" class="w-12 h-12 text-green-600 mx-auto mb-2"></i>
                <span class="font-medium text-lg"><?= $success ?></span>
                <a href="login.php" class="mt-4 inline-block bg-blue-600 text-white py-2.5 px-4 rounded-lg font-bold hover:bg-blue-700 transition">Ke Halaman Login</a>
            </div>
        <?php else: ?>

            <form action="" method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Daftar Sebagai (Role) <span class="text-red-500">*</span></label>
                    <select name="role" id="roleSelect" onchange="toggleFields()" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white text-sm">
                        <option value="peserta">Peserta Magang (Mahasiswa/Siswa)</option>
                        <option value="pembimbing">Pembimbing Lapangan (Pegawai)</option>
                        <option value="admin">Administrator (Admin BPS)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap & Gelar <span class="text-red-500">*</span></label>
                    <input type="text" name="nama" required placeholder="Contoh: Budi Santoso, S.Stat" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" id="emailInput" required placeholder="nama@student.ac.id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                    
                    <div class="mt-2 text-[11px] text-gray-500 bg-gray-50 p-2.5 rounded-lg border border-gray-100" id="emailHelp">
                        <span class="text-green-600 font-bold">* Calon Peserta wajib menggunakan email kampus (.ac.id)</span>
                    </div>
                </div>

                <!-- Field Khusus Peserta -->
                <div id="fieldPeserta">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nomor HP / WhatsApp <span class="text-red-500">*</span></label>
                    <input type="text" name="no_hp" placeholder="08xxxxxxxxxx" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                </div>

                <!-- Field Khusus Pembimbing -->
                <div id="fieldPembimbing" style="display: none;">
                    <label class="block text-sm font-medium text-gray-700 mb-1">NIP Pegawai <span class="text-red-500">*</span></label>
                    <input type="text" name="nip" placeholder="1980xxxxxxxxxxxxxx" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                        <input type="password" name="password" required placeholder="••••••••" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi <span class="text-red-500">*</span></label>
                        <input type="password" name="konfirmasi_password" required placeholder="••••••••" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                    </div>
                </div>

                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition shadow-lg shadow-blue-600/30 flex items-center justify-center gap-2 mt-4">
                    <i data-lucide="user-check" class="w-5 h-5"></i> Daftar Sekarang
                </button>
            </form>

            <div class="text-center mt-6">
                <p class="text-sm text-gray-500">Sudah punya akun? <a href="login.php" class="text-blue-600 font-bold hover:underline">Login di sini</a></p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        lucide.createIcons();

        function toggleFields() {
            const role = document.getElementById('roleSelect').value;
            const fieldPeserta = document.getElementById('fieldPeserta');
            const fieldPembimbing = document.getElementById('fieldPembimbing');
            const emailHelp = document.getElementById('emailHelp');
            const emailInput = document.getElementById('emailInput');

            if (role === 'peserta') {
                fieldPeserta.style.display = 'block';
                fieldPembimbing.style.display = 'none';
                emailHelp.innerHTML = '<span class="text-green-600 font-bold">* Calon Peserta wajib menggunakan email kampus (.ac.id)</span>';
                emailInput.placeholder = 'nama@student.ac.id';
            } else if (role === 'pembimbing') {
                fieldPeserta.style.display = 'none';
                fieldPembimbing.style.display = 'block';
                emailHelp.innerHTML = '<span class="text-blue-600 font-bold">* Pembimbing bebas menggunakan email pribadi atau instansi</span>';
                emailInput.placeholder = 'nama.pegawai@domain.com';
            } else if (role === 'admin') {
                fieldPeserta.style.display = 'none';
                fieldPembimbing.style.display = 'none';
                emailHelp.innerHTML = '<span class="text-purple-600 font-bold">* Admin Wajib menggunakan email instansi (@bps.go.id)</span>';
                emailInput.placeholder = 'admin@bps.go.id';
            }
        }
    </script>
</body>
</html>