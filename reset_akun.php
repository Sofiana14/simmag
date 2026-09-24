<?php
require_once 'config/database.php';

try {
    // 1. KOSONGKAN TABEL (Bypass Foreign Key)
    // Diletakkan DI LUAR beginTransaction karena TRUNCATE bersifat auto-commit di MySQL
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE users");
    $pdo->exec("TRUNCATE TABLE peserta");
    $pdo->exec("TRUNCATE TABLE pembimbing");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // 2. Mulai transaksi BARU khusus untuk proses Input Data
    $pdo->beginTransaction();

    // Deteksi cerdas: Cek apakah kolom di tabel users bernama 'nama' atau 'name'
    $stmt_check = $pdo->query("SHOW COLUMNS FROM users LIKE 'nama'");
    $has_nama = $stmt_check->fetch();
    $kolom_nama = $has_nama ? 'nama' : 'name';

    // Password default: 123456
    $password_default = password_hash('123456', PASSWORD_DEFAULT);

    // 3. INSERT AKUN ADMIN
    $stmt = $pdo->prepare("INSERT INTO users ($kolom_nama, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute(['Admin BPS', 'admin@bps.go.id', $password_default, 'admin']);

    // 4. INSERT AKUN PEMBIMBING
    $stmt->execute(['Budi Santoso, S.ST', 'pembimbing@bps.go.id', $password_default, 'pembimbing']);
    $pdo->exec("INSERT INTO pembimbing (nama, nip) VALUES ('Budi Santoso, S.ST', '198001012005011001')");

    // 5. INSERT AKUN PESERTA
    $stmt->execute(['Mahasiswa Magang', 'peserta@student.ac.id', $password_default, 'peserta']);
    $peserta_user_id = $pdo->lastInsertId(); 
    $pdo->exec("INSERT INTO peserta (user_id, nama, no_hp) VALUES ($peserta_user_id, 'Mahasiswa Magang', '081234567890')");

    // Simpan semua perubahan
    $pdo->commit();

    // Tampilan Sukses
    echo "<div style='font-family: Arial; padding: 20px; background: #e8f5e9; border: 1px solid #4caf50; border-radius: 8px; max-width: 500px; margin: 50px auto;'>";
    echo "<h2 style='color: #2e7d32; margin-top:0;'>✅ Reset Akun Berhasil!</h2>";
    echo "<p>Semua data akun lama telah dihapus dan diganti dengan 3 akun baru yang sesuai aturan.</p>";
    echo "<b>Gunakan akun berikut untuk login:</b><br><br>";
    echo "1. <b>Admin</b><br>Email: admin@bps.go.id<br>Pass: 123456<br><br>";
    echo "2. <b>Pembimbing</b><br>Email: pembimbing@bps.go.id<br>Pass: 123456<br><br>";
    echo "3. <b>Peserta</b><br>Email: peserta@student.ac.id<br>Pass: 123456<br><br>";
    echo "<a href='login.php' style='display:inline-block; padding: 10px 15px; background: #1976d2; color: white; text-decoration: none; border-radius: 5px;'>Menuju Halaman Login</a>";
    echo "</div>";

} catch (Exception $e) {
    // Batalkan input HANYA jika transaksi memang sedang aktif
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Tampilkan Error Asli agar kita tahu letak masalah sebenarnya
    echo "<div style='font-family: Arial; padding: 20px; background: #ffebee; border: 1px solid #f44336; border-radius: 8px; max-width: 500px; margin: 50px auto;'>";
    echo "<h2 style='color: #c62828; margin-top:0;'>❌ Terjadi Kesalahan SQL</h2>";
    echo "<p>Pesan Error Asli:<br><b style='color:red;'>" . $e->getMessage() . "</b></p>";
    echo "</div>";
}
?>