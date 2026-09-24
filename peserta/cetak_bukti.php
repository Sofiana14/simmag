<?php
require_once '../config/database.php';
require_once '../libs/fpdf.php'; // Panggil file FPDF
checkRole('peserta');

$user_id = $_SESSION['user_id'];

// Ambil data pendaftaran terakhir yang aktif
$stmt = $pdo->prepare("
    SELECT p.*, pst.nama, pst.nim, pst.institusi, pst.prodi
    FROM pendaftaran p
    JOIN peserta pst ON p.peserta_id = pst.id
    WHERE pst.user_id = ? 
    ORDER BY p.id DESC LIMIT 1
");
$stmt->execute([$user_id]);
$data = $stmt->fetch();

if (!$data) {
    die("Data pendaftaran tidak ditemukan.");
}

// Inisialisasi FPDF (Potrait, Milimeter, ukuran A4)
$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();

// --- HEADER ---
// (Opsional) Jika ada logo, gunakan fungsi Image: $pdf->Image('../assets/logo_bps.png', 10, 10, 30);
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'BUKTI PENDAFTARAN MAGANG', 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 8, 'BPS KOTA YOGYAKARTA', 0, 1, 'C');
$pdf->SetLineWidth(0.5);
$pdf->Line(10, 30, 200, 30); // Garis bawah header
$pdf->Ln(10); // Spasi baris

// --- INFORMASI DOKUMEN ---
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(40, 8, 'No. Pendaftaran', 0, 0);
$pdf->Cell(5, 8, ':', 0, 0);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 8, $data['nomor_pendaftaran'], 0, 1);

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(40, 8, 'Tanggal Daftar', 0, 0);
$pdf->Cell(5, 8, ':', 0, 0);
$pdf->Cell(0, 8, date('d F Y', strtotime($data['tanggal_daftar'])), 0, 1);
$pdf->Ln(5);

// --- BIODATA PESERTA ---
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'A. Biodata Peserta', 0, 1);
$pdf->SetFont('Arial', '', 11);

// Fungsi buatan untuk mempermudah cetak baris
function printRow($pdf, $label, $value) {
    $pdf->Cell(10, 8, '', 0, 0); // Indentasi
    $pdf->Cell(40, 8, $label, 0, 0);
    $pdf->Cell(5, 8, ':', 0, 0);
    $pdf->Cell(0, 8, $value, 0, 1);
}

printRow($pdf, 'Nama Lengkap', $data['nama']);
printRow($pdf, 'NIM / NIS', $data['nim']);
printRow($pdf, 'Asal Institusi', $data['institusi']);
printRow($pdf, 'Program Studi', $data['prodi']);
$pdf->Ln(5);

// --- DETAIL MAGANG ---
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'B. Detail Magang', 0, 1);
$pdf->SetFont('Arial', '', 11);

printRow($pdf, 'Periode Magang', $data['periode']);
printRow($pdf, 'Tanggal Mulai', date('d-m-Y', strtotime($data['tanggal_mulai'])));
printRow($pdf, 'Tanggal Selesai', date('d-m-Y', strtotime($data['tanggal_selesai'])));

// Status Pendaftaran
$pdf->Cell(10, 8, '', 0, 0);
$pdf->Cell(40, 8, 'Status Saat Ini', 0, 0);
$pdf->Cell(5, 8, ':', 0, 0);
$pdf->SetFont('Arial', 'B', 11);
// Berikan teks yang berbeda berdasarkan status
$status_text = strtoupper($data['status']);
if ($status_text == 'MENUNGGU') $status_text = 'MENUNGGU VERIFIKASI ADMIN';
$pdf->Cell(0, 8, $status_text, 0, 1);
$pdf->Ln(15);

// --- FOOTER & CATATAN ---
$pdf->SetFont('Arial', 'I', 10);
$pdf->MultiCell(0, 6, "Catatan:\n1. Bukti pendaftaran ini dicetak otomatis oleh sistem SIMMAG BPS Kota Yogyakarta.\n2. Harap simpan bukti ini dan pantau status pendaftaran Anda secara berkala melalui dashboard sistem.\n3. Jika status diterima, Anda akan mendapatkan dokumen Letter of Acceptance (LOA).", 0, 'L');

$pdf->Ln(15);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(120, 6, '', 0, 0); // Spacer kiri
$pdf->Cell(70, 6, 'Yogyakarta, ' . date('d F Y'), 0, 1, 'C');
$pdf->Cell(120, 6, '', 0, 0);
$pdf->Cell(70, 6, 'Sistem Informasi Magang', 0, 1, 'C');
$pdf->Ln(20);
$pdf->Cell(120, 6, '', 0, 0);
$pdf->Cell(70, 6, '( Dokumen Elektronik )', 0, 1, 'C');

// 3. Output file (I = Tampilkan di browser, D = Langsung Download)
$nama_file = 'Bukti_Pendaftaran_' . $data['nomor_pendaftaran'] . '.pdf';
$pdf->Output('I', $nama_file);
?>