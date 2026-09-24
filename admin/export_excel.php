<?php
require_once '../config/database.php';
checkRole('admin');

// 1. Ambil seluruh data pendaftaran beserta dokumen CV
$stmt = $pdo->query("
    SELECT p.*, pst.nama, pst.nim, pst.institusi, pst.prodi, pst.no_hp, u.email,
           (SELECT file_path FROM dokumen WHERE pendaftaran_id = p.id AND jenis_dokumen = 'CV' LIMIT 1) as link_cv
    FROM pendaftaran p
    JOIN peserta pst ON p.peserta_id = pst.id
    JOIN users u ON pst.user_id = u.id
    ORDER BY p.tanggal_daftar DESC
");
$data_laporan = $stmt->fetchAll();

// 2. Set Header untuk mengubah output HTML menjadi file Excel
header("Content-Type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Magang_BPS_".date('Ymd').".xls");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!-- Generate Tabel HTML (Akan dibaca sebagai grid Excel) -->
<table border="1">
    <thead>
        <tr>
            <th colspan="11" style="font-size: 16px; font-weight: bold; background-color: #f3f4f6;">
                Laporan Data Peserta Magang BPS Kota Yogyakarta
            </th>
        </tr>
        <tr>
            <th style="background-color: #bfdbfe;">No</th>
            <th style="background-color: #bfdbfe;">Nomor Pendaftaran</th>
            <th style="background-color: #bfdbfe;">Tanggal Daftar</th>
            <th style="background-color: #bfdbfe;">Nama Peserta</th>
            <th style="background-color: #bfdbfe;">NIM/NIS</th>
            <th style="background-color: #bfdbfe;">Program Studi</th>
            <th style="background-color: #bfdbfe;">Institusi</th>
            <th style="background-color: #bfdbfe;">Email</th>
            <th style="background-color: #bfdbfe;">No HP</th>
            <th style="background-color: #bfdbfe;">Status</th>
            <th style="background-color: #bfdbfe;">Link CV</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $no = 1;
        foreach ($data_laporan as $row): 
            // Buat absolute URL untuk Link CV agar bisa diklik dari Excel
            $base_url = "http://localhost/simmag/uploads/dokumen/"; // Sesuaikan dengan base URL Anda
            $cv_url = $row['link_cv'] ? str_replace('../uploads/dokumen/', $base_url, $row['link_cv']) : '';
        ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= $row['nomor_pendaftaran'] ?></td>
                <td><?= date('d-m-Y', strtotime($row['tanggal_daftar'])) ?></td>
                <td><?= htmlspecialchars($row['nama']) ?></td>
                <td><?= htmlspecialchars($row['nim']) ?></td>
                <td><?= htmlspecialchars($row['prodi']) ?></td>
                <td><?= htmlspecialchars($row['institusi']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td>'<?= htmlspecialchars($row['no_hp']) ?></td> <!-- Tanda petik agar nomor HP tidak jadi rumus matematika -->
                <td><?= strtoupper($row['status']) ?></td>
                <td>
                    <?php if ($cv_url): ?>
                        <a href="<?= $cv_url ?>">Download CV</a>
                    <?php else: ?>
                        Tidak ada
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>