<?php
session_start(); // Mulai session untuk seluruh aplikasi

$host = 'localhost';
$dbname = 'simmag_bps';
$username = 'root'; // Sesuaikan dengan XAMPP Anda
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set error mode ke exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Fungsi helper untuk mengecek akses berdasarkan role
function checkRole($allowed_role) {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== $allowed_role) {
        header("Location: ../login.php");
        exit();
    }
}
?>