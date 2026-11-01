<?php
require 'config.php';
wajib_akses();

$kembali = (role() === 'pelanggan') ? 'janji_saya.php' : 'transaksi.php';

$st = $pdo->prepare("SELECT t.*, j.pelanggan_id, p.nama AS pelanggan, l.nama AS layanan
                     FROM transaksi t
                     JOIN janji_temu j ON j.id = t.janji_id
                     JOIN pelanggan p  ON p.id = j.pelanggan_id
                     JOIN layanan l    ON l.id = j.layanan_id
                     WHERE t.id=?");
$st->execute([(int)($_GET['id'] ?? 0)]);
$t = $st->fetch();

// pelanggan hanya boleh melihat nota miliknya sendiri
if ($t && role() === 'pelanggan' && (int)$t['pelanggan_id'] !== (int)(user()['pelanggan_id'] ?? 0)) {
    $t = false;
}
if (!$t) {
    flash('Transaksi tidak ditemukan.', 'err');
    redirect($kembali);
}

$judul = 'Nota Pembayaran';
require 'header.php';
?>
<div class="nota">
    <div style="text-align:center"><strong>NOTA PEMBAYARAN</strong></div>
    <hr>
    <div class="baris"><span>No. Transaksi</span><span><?= $t['id'] ?></span></div>
    <div class="baris"><span>Waktu</span><span><?= e(substr($t['waktu'], 0, 16)) ?></span></div>
    <div class="baris"><span>Pelanggan</span><span><?= e($t['pelanggan']) ?></span></div>
    <hr>
    <div class="baris"><span>Layanan</span><span><?= e($t['layanan']) ?></span></div>
    <div class="baris"><span>Total</span><span><?= rupiah($t['total']) ?></span></div>
    <div class="baris"><span>Tunai</span><span><?= rupiah($t['bayar']) ?></span></div>
    <div class="baris"><span>Kembalian</span><span><?= rupiah($t['kembalian']) ?></span></div>
    <hr>
    <div style="text-align:center">Terima kasih atas kunjungan Anda</div>
</div>
<p class="no-print" style="text-align:center">
    <button class="btn" onclick="window.print()">Cetak Nota</button>
    <a class="btn abu" href="<?= $kembali ?>">Kembali</a>
</p>
<?php require 'footer.php'; ?>
