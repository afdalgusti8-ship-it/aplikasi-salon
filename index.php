<?php
require 'config.php';
wajib_akses();

$hari = date('Y-m-d');

function hitung(PDO $pdo, string $sql, array $p = []) {
    $st = $pdo->prepare($sql);
    $st->execute($p);
    return (int)$st->fetchColumn();
}

// ---------- BERANDA PELANGGAN ----------
if (role() === 'pelanggan') {
    $pid = (int)(user()['pelanggan_id'] ?? 0);

    $selesai  = hitung($pdo, "SELECT COUNT(*) FROM janji_temu WHERE pelanggan_id=? AND status='Selesai'", [$pid]);
    $mendatang = hitung($pdo, "SELECT COUNT(*) FROM janji_temu WHERE pelanggan_id=? AND status='Terjadwal' AND tanggal>=?", [$pid, $hari]);

    $st = $pdo->prepare("SELECT j.tanggal, j.jam, l.nama AS layanan, l.harga
                         FROM janji_temu j JOIN layanan l ON l.id = j.layanan_id
                         WHERE j.pelanggan_id=? AND j.status='Terjadwal' AND j.tanggal>=?
                         ORDER BY j.tanggal, j.jam LIMIT 5");
    $st->execute([$pid, $hari]);
    $akan = $st->fetchAll();

    $judul = 'Selamat Datang, ' . user()['nama'];
    require 'header.php';
    ?>
    <div class="grid">
        <div class="stat"><div class="label">Janji Mendatang</div><div class="nilai"><?= $mendatang ?></div></div>
        <div class="stat"><div class="label">Kunjungan Selesai</div><div class="nilai"><?= $selesai ?></div></div>
    </div>
    <p><a class="btn" href="janji_saya.php">Buat / Lihat Janji Temu</a> <a class="btn abu" href="layanan.php">Lihat Daftar Layanan</a></p>

    <h2>Janji Temu Mendatang</h2>
    <table>
        <tr><th>Tanggal</th><th>Jam</th><th>Layanan</th><th class="kanan">Harga</th></tr>
        <?php foreach ($akan as $j): ?>
        <tr>
            <td><?= e($j['tanggal']) ?></td>
            <td><?= e(substr($j['jam'], 0, 5)) ?></td>
            <td><?= e($j['layanan']) ?></td>
            <td class="kanan"><?= rupiah($j['harga']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$akan): ?><tr><td colspan="4">Belum ada janji temu mendatang.</td></tr><?php endif; ?>
    </table>
    <?php
    require 'footer.php';
    exit;
}

// ---------- BERANDA PEMILIK & ADMIN ----------
$jumlahPelanggan = hitung($pdo, 'SELECT COUNT(*) FROM pelanggan');
$janjiHariIni    = hitung($pdo, "SELECT COUNT(*) FROM janji_temu WHERE tanggal=? AND status<>'Dibatalkan'", [$hari]);
$menunggu        = hitung($pdo, "SELECT COUNT(*) FROM janji_temu WHERE tanggal=? AND status='Terjadwal'", [$hari]);
$pendapatan      = hitung($pdo, 'SELECT COALESCE(SUM(total),0) FROM transaksi WHERE DATE(waktu)=?', [$hari]);

$st = $pdo->prepare("SELECT j.jam, j.status, p.nama AS pelanggan, l.nama AS layanan
                     FROM janji_temu j
                     JOIN pelanggan p ON p.id = j.pelanggan_id
                     JOIN layanan l   ON l.id = j.layanan_id
                     WHERE j.tanggal=? AND j.status<>'Dibatalkan'
                     ORDER BY j.jam");
$st->execute([$hari]);
$jadwal = $st->fetchAll();

$judul = 'Beranda';
require 'header.php';
?>
<div class="grid">
    <div class="stat"><div class="label">Total Pelanggan</div><div class="nilai"><?= $jumlahPelanggan ?></div></div>
    <div class="stat"><div class="label">Janji Hari Ini</div><div class="nilai"><?= $janjiHariIni ?></div></div>
    <div class="stat"><div class="label">Belum Dilayani / Dibayar</div><div class="nilai"><?= $menunggu ?></div></div>
    <?php if (role() === 'pemilik'): ?>
    <div class="stat"><div class="label">Pendapatan Hari Ini</div><div class="nilai"><?= rupiah($pendapatan) ?></div></div>
    <?php endif; ?>
</div>

<h2>Jadwal Hari Ini (<?= e($hari) ?>)</h2>
<table>
    <tr><th>Jam</th><th>Pelanggan</th><th>Layanan</th><th>Status</th></tr>
    <?php foreach ($jadwal as $j): ?>
    <tr>
        <td><?= e(substr($j['jam'], 0, 5)) ?></td>
        <td><?= e($j['pelanggan']) ?></td>
        <td><?= e($j['layanan']) ?></td>
        <td><span class="badge <?= e($j['status']) ?>"><?= e($j['status']) ?></span></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$jadwal): ?><tr><td colspan="4">Belum ada janji temu hari ini.</td></tr><?php endif; ?>
</table>
<?php require 'footer.php'; ?>
