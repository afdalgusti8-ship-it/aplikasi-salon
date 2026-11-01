<?php
require 'config.php';
wajib_akses();

$tanggal = trim($_GET['tanggal'] ?? date('Y-m-d'));
if (!valid_tanggal($tanggal)) {
    $tanggal = date('Y-m-d');
}

$st = $pdo->prepare("SELECT t.waktu, t.total, p.nama AS pelanggan, l.nama AS layanan
                     FROM transaksi t
                     JOIN janji_temu j ON j.id = t.janji_id
                     JOIN pelanggan p  ON p.id = j.pelanggan_id
                     JOIN layanan l    ON l.id = j.layanan_id
                     WHERE DATE(t.waktu) = ?
                     ORDER BY t.waktu");
$st->execute([$tanggal]);
$baris = $st->fetchAll();

$jumlah = count($baris);
$pendapatan = 0;
$rekap = [];
foreach ($baris as $b) {
    $pendapatan += $b['total'];
    $rekap[$b['layanan']]['jumlah']     = ($rekap[$b['layanan']]['jumlah'] ?? 0) + 1;
    $rekap[$b['layanan']]['pendapatan'] = ($rekap[$b['layanan']]['pendapatan'] ?? 0) + $b['total'];
}
ksort($rekap);

// ---------- EKSPOR CSV (harus sebelum ada output HTML) ----------
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan_' . $tanggal . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");   // BOM agar terbaca benar di Excel
    fputcsv($out, ['Tanggal', 'Jam', 'Pelanggan', 'Layanan', 'Total']);
    foreach ($baris as $b) {
        fputcsv($out, [$tanggal, substr($b['waktu'], 11, 5), $b['pelanggan'], $b['layanan'], $b['total']]);
    }
    fputcsv($out, []);
    fputcsv($out, ['Jumlah layanan', $jumlah]);
    fputcsv($out, ['Total pendapatan', $pendapatan]);
    fclose($out);
    exit;
}

$judul = 'Laporan Layanan Harian';
require 'header.php';
?>
<form method="get" class="toolbar no-print">
    <div><label>Tanggal</label><input type="date" name="tanggal" value="<?= e($tanggal) ?>"></div>
    <button class="btn" type="submit">Tampilkan</button>
    <a class="btn abu" href="laporan.php">Hari Ini</a>
    <a class="btn abu" href="laporan.php?tanggal=<?= e($tanggal) ?>&export=csv">Ekspor CSV</a>
    <button class="btn abu" type="button" onclick="window.print()">Cetak</button>
</form>

<div class="grid">
    <div class="stat"><div class="label">Tanggal Laporan</div><div class="nilai"><?= e($tanggal) ?></div></div>
    <div class="stat"><div class="label">Jumlah Layanan Selesai</div><div class="nilai"><?= $jumlah ?></div></div>
    <div class="stat"><div class="label">Total Pendapatan</div><div class="nilai"><?= rupiah($pendapatan) ?></div></div>
</div>

<h2>Rincian Layanan</h2>
<table>
    <tr><th>No</th><th>Jam Bayar</th><th>Pelanggan</th><th>Layanan</th><th class="kanan">Total</th></tr>
    <?php foreach ($baris as $i => $b): ?>
    <tr>
        <td><?= $i + 1 ?></td>
        <td><?= e(substr($b['waktu'], 11, 5)) ?></td>
        <td><?= e($b['pelanggan']) ?></td>
        <td><?= e($b['layanan']) ?></td>
        <td class="kanan"><?= rupiah($b['total']) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$baris): ?><tr><td colspan="5">Belum ada layanan yang dibayar pada tanggal ini.</td></tr><?php endif; ?>
</table>

<h2>Rekap per Jenis Layanan</h2>
<table>
    <tr><th>Layanan</th><th>Jumlah</th><th class="kanan">Pendapatan</th></tr>
    <?php foreach ($rekap as $nama => $r): ?>
    <tr><td><?= e($nama) ?></td><td><?= $r['jumlah'] ?></td><td class="kanan"><?= rupiah($r['pendapatan']) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$rekap): ?><tr><td colspan="3">-</td></tr><?php endif; ?>
</table>
<?php require 'footer.php'; ?>
