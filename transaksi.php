<?php
require 'config.php';
wajib_akses();

// ---------- PROSES FORM ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'bayar') {
        $janjiId = (int)($_POST['janji_id'] ?? 0);
        $bayar   = angka($_POST['bayar'] ?? '');

        $st = $pdo->prepare("SELECT j.id, l.harga FROM janji_temu j
                             JOIN layanan l ON l.id = j.layanan_id
                             WHERE j.id=? AND j.status='Terjadwal'");
        $st->execute([$janjiId]);
        $janji = $st->fetch();

        if (!$janji) {
            flash('Pilih janji temu yang belum dibayar.', 'err');
            redirect('transaksi.php');
        }
        $total = (int)$janji['harga'];
        if ($bayar < $total) {
            flash('Uang yang diterima kurang dari total tagihan (' . rupiah($total) . ').', 'err');
            redirect('transaksi.php');
        }

        try {
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO transaksi (janji_id, waktu, total, bayar, kembalian) VALUES (?,?,?,?,?)')
                ->execute([$janjiId, date('Y-m-d H:i:s'), $total, $bayar, $bayar - $total]);
            $trxId = $pdo->lastInsertId();
            $pdo->prepare("UPDATE janji_temu SET status='Selesai' WHERE id=?")->execute([$janjiId]);
            $pdo->commit();
        } catch (PDOException $ex) {
            $pdo->rollBack();
            flash('Pembayaran gagal diproses. Silakan coba lagi.', 'err');
            redirect('transaksi.php');
        }
        flash('Pembayaran berhasil diproses.');
        redirect('nota.php?id=' . $trxId);
    }

    if ($aksi === 'batal') {
        $trxId = (int)($_POST['id'] ?? 0);
        $st = $pdo->prepare('SELECT janji_id FROM transaksi WHERE id=?');
        $st->execute([$trxId]);
        $janjiId = $st->fetchColumn();
        if ($janjiId) {
            $pdo->beginTransaction();
            $pdo->prepare('DELETE FROM transaksi WHERE id=?')->execute([$trxId]);
            $pdo->prepare("UPDATE janji_temu SET status='Terjadwal' WHERE id=?")->execute([$janjiId]);
            $pdo->commit();
            flash("Transaksi dibatalkan. Status janji kembali menjadi 'Terjadwal'.");
        }
        redirect('transaksi.php');
    }
}

// ---------- AMBIL DATA ----------
$belumBayar = $pdo->query("SELECT j.id, j.tanggal, j.jam, p.nama AS pelanggan, l.nama AS layanan, l.harga
                           FROM janji_temu j
                           JOIN pelanggan p ON p.id = j.pelanggan_id
                           JOIN layanan l   ON l.id = j.layanan_id
                           WHERE j.status='Terjadwal'
                           ORDER BY j.tanggal, j.jam")->fetchAll();

$riwayat = $pdo->query("SELECT t.*, p.nama AS pelanggan, l.nama AS layanan
                        FROM transaksi t
                        JOIN janji_temu j ON j.id = t.janji_id
                        JOIN pelanggan p  ON p.id = j.pelanggan_id
                        JOIN layanan l    ON l.id = j.layanan_id
                        ORDER BY t.id DESC LIMIT 100")->fetchAll();

$judul = 'Transaksi Pembayaran';
require 'header.php';
?>
<div class="card">
    <form method="post" id="formBayar">
        <input type="hidden" name="aksi" value="bayar">
        <div class="form-grid">
            <div style="grid-column: 1 / -1;">
                <label>Janji Temu (belum dibayar) *</label>
                <select name="janji_id" id="janji" required>
                    <option value="" data-harga="0">-- pilih janji temu --</option>
                    <?php foreach ($belumBayar as $b): ?>
                        <option value="<?= $b['id'] ?>" data-harga="<?= (int)$b['harga'] ?>">
                            <?= e($b['tanggal'] . ' ' . substr($b['jam'], 0, 5)) ?> | <?= e($b['pelanggan']) ?> | <?= e($b['layanan']) ?> | <?= rupiah($b['harga']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>Total Tagihan</label><div class="besar" id="total">Rp 0</div></div>
            <div><label>Uang Diterima (Rp, tunai) *</label><input type="number" name="bayar" id="bayar" min="0" required></div>
            <div><label>Kembalian</label><div class="besar" id="kembali">Rp 0</div></div>
        </div>
        <p><button class="btn" type="submit">Proses Pembayaran</button></p>
    </form>
</div>

<h2>Riwayat Transaksi</h2>
<table>
    <tr><th>No.</th><th>Waktu</th><th>Pelanggan</th><th>Layanan</th><th class="kanan">Total</th><th class="kanan">Dibayar</th><th class="kanan">Kembalian</th><th>Aksi</th></tr>
    <?php foreach ($riwayat as $t): ?>
    <tr>
        <td><?= $t['id'] ?></td>
        <td><?= e(substr($t['waktu'], 0, 16)) ?></td>
        <td><?= e($t['pelanggan']) ?></td>
        <td><?= e($t['layanan']) ?></td>
        <td class="kanan"><?= rupiah($t['total']) ?></td>
        <td class="kanan"><?= rupiah($t['bayar']) ?></td>
        <td class="kanan"><?= rupiah($t['kembalian']) ?></td>
        <td class="aksi">
            <a class="btn kecil" href="nota.php?id=<?= $t['id'] ?>">Nota</a>
            <form method="post" onsubmit="return confirm('Batalkan transaksi ini? Status janji akan kembali menjadi Terjadwal.')">
                <input type="hidden" name="aksi" value="batal">
                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                <button class="btn kecil merah" type="submit">Batalkan</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$riwayat): ?><tr><td colspan="8">Belum ada transaksi.</td></tr><?php endif; ?>
</table>

<script>
const fmt = n => 'Rp ' + Math.max(n, 0).toLocaleString('id-ID');
const selJanji = document.getElementById('janji');
const inBayar  = document.getElementById('bayar');
function hitung() {
    const harga = parseInt(selJanji.selectedOptions[0].dataset.harga || 0, 10);
    const bayar = parseInt(inBayar.value || 0, 10);
    document.getElementById('total').textContent  = fmt(harga);
    document.getElementById('kembali').textContent = fmt(bayar - harga);
}
selJanji.addEventListener('change', hitung);
inBayar.addEventListener('input', hitung);
</script>
<?php require 'footer.php'; ?>
