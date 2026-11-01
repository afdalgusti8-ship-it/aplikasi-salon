<?php
require 'config.php';
wajib_akses();

function sudah_dibayar(PDO $pdo, int $id): bool {
    $st = $pdo->prepare('SELECT 1 FROM transaksi WHERE janji_id=?');
    $st->execute([$id]);
    return (bool)$st->fetchColumn();
}

// ---------- PROSES FORM ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'simpan') {
        $id      = (int)($_POST['id'] ?? 0);
        $pel     = (int)($_POST['pelanggan_id'] ?? 0);
        $lay     = (int)($_POST['layanan_id'] ?? 0);
        $tanggal = trim($_POST['tanggal'] ?? '');
        $jam     = substr(trim($_POST['jam'] ?? ''), 0, 5);
        $status  = ($_POST['status'] ?? '') === 'Dibatalkan' ? 'Dibatalkan' : 'Terjadwal';
        $catatan = trim($_POST['catatan'] ?? '');
        $balik   = 'janji.php' . ($id ? "?edit=$id" : '');

        if (!$pel || !$lay)           { flash('Pelanggan dan layanan wajib dipilih.', 'err'); redirect($balik); }
        if (!valid_tanggal($tanggal)) { flash('Format tanggal tidak valid.', 'err'); redirect($balik); }
        if (!valid_jam($jam))         { flash('Format jam tidak valid.', 'err'); redirect($balik); }

        if ($id) {
            if (sudah_dibayar($pdo, $id)) {
                flash('Janji ini sudah dibayar dan tidak dapat diubah. Batalkan transaksinya terlebih dahulu.', 'err');
                redirect('janji.php');
            }
            $pdo->prepare('UPDATE janji_temu SET pelanggan_id=?, layanan_id=?, tanggal=?, jam=?, status=?, catatan=? WHERE id=?')
                ->execute([$pel, $lay, $tanggal, $jam, $status, $catatan, $id]);
            flash('Janji temu berhasil diperbarui.');
        } else {
            $pdo->prepare('INSERT INTO janji_temu (pelanggan_id, layanan_id, tanggal, jam, status, catatan) VALUES (?,?,?,?,?,?)')
                ->execute([$pel, $lay, $tanggal, $jam, $status, $catatan]);
            flash('Janji temu berhasil ditambahkan.');
        }
        redirect('janji.php?tgl=' . urlencode($tanggal));
    }

    if ($aksi === 'batal') {
        $id = (int)($_POST['id'] ?? 0);
        if (sudah_dibayar($pdo, $id)) {
            flash('Janji ini sudah dibayar dan tidak dapat dibatalkan.', 'err');
        } else {
            $pdo->prepare("UPDATE janji_temu SET status='Dibatalkan' WHERE id=?")->execute([$id]);
            flash('Janji temu dibatalkan.');
        }
        redirect('janji.php');
    }

    if ($aksi === 'hapus') {
        try {
            $pdo->prepare('DELETE FROM janji_temu WHERE id=?')->execute([(int)($_POST['id'] ?? 0)]);
            flash('Janji temu berhasil dihapus.');
        } catch (PDOException $ex) {
            flash('Janji temu sudah memiliki transaksi. Batalkan transaksinya terlebih dahulu.', 'err');
        }
        redirect('janji.php');
    }
}

// ---------- AMBIL DATA ----------
$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM janji_temu WHERE id=?');
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch() ?: null;
}
$pelanggan = $pdo->query('SELECT id, nama FROM pelanggan ORDER BY nama')->fetchAll();
$layanan   = $pdo->query('SELECT id, nama, harga FROM layanan ORDER BY nama')->fetchAll();

// filter tanggal: default hari ini; ?tgl= (kosong) = tampilkan semua
$tgl = isset($_GET['tgl']) ? trim($_GET['tgl']) : date('Y-m-d');
$sql = 'SELECT j.*, p.nama AS pelanggan, l.nama AS layanan
        FROM janji_temu j
        JOIN pelanggan p ON p.id = j.pelanggan_id
        JOIN layanan l   ON l.id = j.layanan_id ';
$params = [];
if ($tgl !== '') { $sql .= 'WHERE j.tanggal = ? '; $params[] = $tgl; }
$sql .= 'ORDER BY j.tanggal, j.jam';
$st = $pdo->prepare($sql);
$st->execute($params);
$daftar = $st->fetchAll();

$judul = 'Jadwal Janji Temu';
require 'header.php';
?>
<div class="card">
    <form method="post">
        <input type="hidden" name="aksi" value="simpan">
        <input type="hidden" name="id" value="<?= e($edit['id'] ?? '') ?>">
        <div class="form-grid">
            <div>
                <label>Pelanggan *</label>
                <select name="pelanggan_id" required>
                    <option value="">-- pilih pelanggan --</option>
                    <?php foreach ($pelanggan as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= ($edit['pelanggan_id'] ?? '') == $p['id'] ? 'selected' : '' ?>><?= e($p['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Layanan *</label>
                <select name="layanan_id" required>
                    <option value="">-- pilih layanan --</option>
                    <?php foreach ($layanan as $l): ?>
                        <option value="<?= $l['id'] ?>" <?= ($edit['layanan_id'] ?? '') == $l['id'] ? 'selected' : '' ?>><?= e($l['nama']) ?> (<?= rupiah($l['harga']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>Tanggal *</label><input type="date" name="tanggal" value="<?= e($edit['tanggal'] ?? date('Y-m-d')) ?>" required></div>
            <div><label>Jam *</label><input type="time" name="jam" value="<?= e(substr($edit['jam'] ?? date('H:i'), 0, 5)) ?>" required></div>
            <div>
                <label>Status</label>
                <select name="status">
                    <option value="Terjadwal" <?= ($edit['status'] ?? '') !== 'Dibatalkan' ? 'selected' : '' ?>>Terjadwal</option>
                    <option value="Dibatalkan" <?= ($edit['status'] ?? '') === 'Dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                </select>
            </div>
            <div><label>Catatan</label><input type="text" name="catatan" value="<?= e($edit['catatan'] ?? '') ?>"></div>
        </div>
        <p>
            <button class="btn" type="submit"><?= $edit ? 'Simpan Perubahan' : 'Tambah Janji Temu' ?></button>
            <?php if ($edit): ?><a class="btn abu" href="janji.php">Batal</a><?php endif; ?>
        </p>
        <small>Status "Selesai" otomatis terisi saat pembayaran diproses di menu Pembayaran.</small>
    </form>
</div>

<form method="get" class="toolbar">
    <div><label>Tampilkan tanggal</label><input type="date" name="tgl" value="<?= e($tgl) ?>"></div>
    <button class="btn" type="submit">Terapkan</button>
    <a class="btn abu" href="janji.php">Hari Ini</a>
    <a class="btn abu" href="janji.php?tgl=">Semua</a>
</form>

<table>
    <tr><th>Tanggal</th><th>Jam</th><th>Pelanggan</th><th>Layanan</th><th>Status</th><th>Catatan</th><th>Aksi</th></tr>
    <?php foreach ($daftar as $j): ?>
    <tr>
        <td><?= e($j['tanggal']) ?></td>
        <td><?= e(substr($j['jam'], 0, 5)) ?></td>
        <td><?= e($j['pelanggan']) ?></td>
        <td><?= e($j['layanan']) ?></td>
        <td><span class="badge <?= e($j['status']) ?>"><?= e($j['status']) ?></span></td>
        <td><?= e($j['catatan']) ?></td>
        <td class="aksi">
            <?php if ($j['status'] === 'Terjadwal'): ?>
                <a class="btn kecil" href="janji.php?edit=<?= $j['id'] ?>">Ubah</a>
                <form method="post" onsubmit="return confirm('Batalkan janji ini?')">
                    <input type="hidden" name="aksi" value="batal">
                    <input type="hidden" name="id" value="<?= $j['id'] ?>">
                    <button class="btn kecil abu" type="submit">Batalkan</button>
                </form>
            <?php elseif ($j['status'] === 'Dibatalkan'): ?>
                <a class="btn kecil" href="janji.php?edit=<?= $j['id'] ?>">Ubah</a>
            <?php endif; ?>
            <?php if ($j['status'] !== 'Selesai'): ?>
                <form method="post" onsubmit="return confirm('Hapus janji temu ini?')">
                    <input type="hidden" name="aksi" value="hapus">
                    <input type="hidden" name="id" value="<?= $j['id'] ?>">
                    <button class="btn kecil merah" type="submit">Hapus</button>
                </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$daftar): ?><tr><td colspan="7">Tidak ada janji temu.</td></tr><?php endif; ?>
</table>
<?php require 'footer.php'; ?>
