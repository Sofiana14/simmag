<?php
require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($email) && !empty($password)) {
        // Cek apakah email terdaftar di database
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $role = $user['role'];
            $domain = strtolower(substr(strrchr($email, "@"), 1)); // Mengambil domain email

            $is_valid = true;
            $pesan_error = "";

            // ATURAN VALIDASI DOMAIN BERDASARKAN ROLE
            if ($role == 'admin') {
                // Admin Wajib menggunakan email perusahaan/instansi (contoh: @bps.go.id)
                if ($domain !== 'bps.go.id') {
                    $is_valid = false;
                    $pesan_error = "Akses ditolak! Akun Admin wajib menggunakan email instansi (@bps.go.id).";
                }
            } 
            elseif ($role == 'pembimbing') {
                // Pembimbing fleksibel: boleh menggunakan email pribadi (gmail, yahoo, dll) ATAU instansi
                // Karena fleksibel, is_valid tetap true
                $is_valid = true;
            } 
            elseif ($role == 'peserta') {
                // Peserta Wajib menggunakan email kampus (mengandung ac.id atau student)
                if (!str_contains($domain, 'ac.id') && !str_contains($domain, 'student')) {
                    $is_valid = false;
                    $pesan_error = "Akses ditolak! Peserta magang wajib menggunakan email kampus (berakhiran .ac.id).";
                }
            }

            // Jika validasi domain gagal
            if (!$is_valid) {
                $error = $pesan_error;
            } else {
                // Jika lolos, langsung set session dan arahkan ke dashboard masing-masing
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $role;
                $_SESSION['name'] = $user['nama'];

                if ($role == 'admin') {
                    header("Location: admin/dashboard.php");
                } elseif ($role == 'pembimbing') {
                    header("Location: pembimbing/dashboard.php");
                } elseif ($role == 'peserta') {
                    header("Location: peserta/dashboard.php");
                }
                exit;
            }
        } else {
            $error = "Email atau Password salah!";
        }
    } else {
        $error = "Harap isi email dan password terlebih dahulu!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - SIMMAG BPS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-slate-900 flex items-center justify-center h-screen">

    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md">
        <div class="text-center mb-6">
            <div class="inline-flex p-3 bg-blue-50 text-blue-600 rounded-xl mb-3">
                <i data-lucide="bar-chart-2" class="w-8 h-8"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">SIMMAG BPS</h1>
            <p class="text-sm text-gray-500 mt-1">Sistem Informasi Manajemen Magang</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 text-red-700 p-3 rounded-lg mb-5 text-sm flex items-center gap-2 border border-red-200">
                <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Akun</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <i data-lucide="mail" class="w-5 h-5"></i>
                    </span>
                    <input type="email" name="email" required placeholder="nama@domain.com" class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <i data-lucide="lock" class="w-5 h-5"></i>
                    </span>
                    <input type="password" name="password" required placeholder="••••••••" class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                </div>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-lg transition shadow-lg shadow-blue-600/30 flex items-center justify-center gap-2 mt-2">
                <i data-lucide="log-in" class="w-5 h-5"></i> Masuk Sistem
            </button>
        </form>

        <div class="text-center mt-6">
            <p class="text-sm text-gray-500">Belum punya akun? <a href="register.php" class="text-blue-600 font-bold hover:underline">Daftar di sini</a></p>
        </div>
    </div>

    <script> lucide.createIcons(); </script>
</body>
</html>