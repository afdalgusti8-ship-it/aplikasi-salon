<?php
require 'config.php';
wajib_akses();

$pid = (int)(user()['pelanggan_id'] ?? 0);
if (!$pid) {
    flash('Akun Anda belum terhubung dengan data pelanggan. Hubungi admin salon.', 'err');
    redirect('index.php');
}

// ---------- PROSES FORM ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'buat') {
        $lay     = (int)($_POST['layanan_id'] ?? 0);
        $tanggal = trim($_POST['tanggal'] ?? '');
        $jam     = substr(trim($_POST['jam'] ?? ''), 0, 5);
        $catatan = trim($_POST['catatan'] ?? '');

        $st = $pdo->prepare('SELECT 1 FROM layanan WHERE id=?');
        $st->execute([$lay]);

        if (!$st->fetchColumn())      { flash('Pilih layanan terlebih dahulu.', 'err'); redirect('janji_saya.php'); }
        if (!valid_tanggal($tanggal)) { flash('Format tanggal tidak valid.', 'err'); redirect('janji_saya.php'); }
        if (!valid_jam($jam))         { flash('Format jam tidak valid.', 'err'); redirect('janji_saya.php'); }
        if (strtotime("$tanggal $jam") < time()) {
            flash('Waktu janji temu tidak boleh di masa lalu.', 'err'); redirect('janji_saya.php');
        }

        $pdo->prepare("INSERT INTO janji_temu (pelanggan_id, layanan_id, tanggal, jam, status, catatan) VALUES (?,?,?,?,'Terjadwal',?)")
            ->execute([$pid, $lay, $tanggal, $jam, $catatan]);
        flash('Janji temu berhasil dibuat. Pembayaran dilakukan di salon.');
        redirect('janji_saya.php');
    }

    if ($aksi === 'batal') {
        $id = (int)($_POST['id'] ?? 0);
        $st = $pdo->prepare("UPDATE janji_temu SET status='Dibatalkan' WHERE id=? AND pelanggan_id=? AND status='Terjadwal'");
        $st->execute([$id, $pid]);
        flash($st->rowCount() ? 'Janji temu dibatalkan.' : 'Janji tidak dapat dibatalkan.', $st->rowCount() ? 'ok' : 'err');
        redirect('janji_saya.php');
    }
}

// ---------- AMBIL DATA ----------
$layanan = $pdo->query('SELECT * FROM layanan ORDER BY nama')->fetchAll();

$st = $pdo->prepare("SELECT j.*, l.nama AS layanan, l.harga, t.id AS trx_id
                     FROM janji_temu j
                     JOIN layanan l ON l.id = j.layanan_id
                     LEFT JOIN transaksi t ON t.janji_id = j.id
                     WHERE j.pelanggan_id = ?
                     ORDER BY j.tanggal DESC, j.jam DESC");
$st->execute([$pid]);
$daftar = $st->fetchAll();

$judul = 'Janji Temu Saya';
require 'header.php';
?>
<div class="card">
    <h2 style="margin-top:0">Buat Janji Temu Baru</h2>
    <form method="post">
        <input type="hidden" name="aksi" value="buat">
        <div class="form-grid">
            <div>
                <label>Layanan *</label>
                <select name="layanan_id" required>
                    <option value="">-- pilih layanan --</option>
                    <?php foreach ($layanan as $l): ?>
                        <option value="<?= $l['id'] ?>"><?= e($l['nama']) ?> - <?= rupiah($l['harga']) ?> (<?= (int)$l['durasi'] ?> menit)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>Tanggal *</label><input type="date" name="tanggal" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required></div>
            <div><label>Jam *</label><input type="time" name="jam" required></div>
            <div><label>Catatan</label><input type="text" name="catatan" placeholder="Opsional"></div>
        </div>
        <p><button class="btn" type="submit">Buat Janji Temu</button></p>
    </form>
</div>

<h2>Riwayat Janji Temu</h2>
<table>
    <tr><th>Tanggal</th><th>Jam</th><th>Layanan</th><th class="kanan">Harga</th><th>Status</th><th>Aksi</th></tr>
    <?php foreach ($daftar as $j): ?>
    <tr>
        <td><?= e($j['tanggal']) ?></td>
        <td><?= e(substr($j['jam'], 0, 5)) ?></td>
        <td><?= e($j['layanan']) ?></td>
        <td class="kanan"><?= rupiah($j['harga']) ?></td>
        <td><span class="badge <?= e($j['status']) ?>"><?= e($j['status']) ?></span></td>
        <td class="aksi">
            <?php if ($j['status'] === 'Terjadwal'): ?>
                <form method="post" onsubmit="return confirm('Batalkan janji temu ini?')">
                    <input type="hidden" name="aksi" value="batal">
                    <input type="hidden" name="id" value="<?= $j['id'] ?>">
                    <button class="btn kecil merah" type="submit">Batalkan</button>
                </form>
            <?php elseif ($j['trx_id']): ?>
                <a class="btn kecil" href="nota.php?id=<?= $j['trx_id'] ?>">Lihat Nota</a>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$daftar): ?><tr><td colspan="6">Anda belum memiliki janji temu.</td></tr><?php endif; ?>
</table>
<?php require 'footer.php'; ?>
