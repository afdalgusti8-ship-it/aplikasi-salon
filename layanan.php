<?php
require 'config.php';
wajib_akses();

$bisaUbah = in_array(role(), ['pemilik', 'admin'], true);

// ---------- PROSES FORM (hanya pemilik & admin) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $bisaUbah) {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'simpan') {
        $id     = (int)($_POST['id'] ?? 0);
        $nama   = trim($_POST['nama'] ?? '');
        $harga  = angka($_POST['harga'] ?? '');
        $durasi = angka($_POST['durasi'] ?? '');

        if ($nama === '' || $harga <= 0 || $durasi <= 0) {
            flash('Nama layanan, harga, dan durasi wajib diisi dengan benar.', 'err');
            redirect('layanan.php' . ($id ? "?edit=$id" : ''));
        }
        if ($id) {
            $pdo->prepare('UPDATE layanan SET nama=?, harga=?, durasi=? WHERE id=?')
                ->execute([$nama, $harga, $durasi, $id]);
            flash('Layanan berhasil diperbarui.');
        } else {
            $pdo->prepare('INSERT INTO layanan (nama, harga, durasi) VALUES (?,?,?)')
                ->execute([$nama, $harga, $durasi]);
            flash('Layanan berhasil ditambahkan.');
        }
        redirect('layanan.php');
    }

    if ($aksi === 'hapus') {
        try {
            $pdo->prepare('DELETE FROM layanan WHERE id=?')->execute([(int)($_POST['id'] ?? 0)]);
            flash('Layanan berhasil dihapus.');
        } catch (PDOException $ex) {
            flash('Layanan tidak dapat dihapus karena sudah dipakai pada janji temu.', 'err');
        }
        redirect('layanan.php');
    }
}

// ---------- AMBIL DATA ----------
$edit = null;
if ($bisaUbah && isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM layanan WHERE id=?');
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch() ?: null;
}
$daftar = $pdo->query('SELECT * FROM layanan ORDER BY nama')->fetchAll();

$judul = 'Daftar Layanan';
require 'header.php';
?>
<?php if ($bisaUbah): ?>
<div class="card">
    <form method="post">
        <input type="hidden" name="aksi" value="simpan">
        <input type="hidden" name="id" value="<?= e($edit['id'] ?? '') ?>">
        <div class="form-grid">
            <div><label>Nama Layanan *</label><input type="text" name="nama" value="<?= e($edit['nama'] ?? '') ?>" required></div>
            <div><label>Harga (Rp) *</label><input type="number" name="harga" min="0" value="<?= e($edit['harga'] ?? '') ?>" required></div>
            <div><label>Durasi (menit) *</label><input type="number" name="durasi" min="1" value="<?= e($edit['durasi'] ?? 30) ?>" required></div>
        </div>
        <p>
            <button class="btn" type="submit"><?= $edit ? 'Simpan Perubahan' : 'Tambah Layanan' ?></button>
            <?php if ($edit): ?><a class="btn abu" href="layanan.php">Batal</a><?php endif; ?>
        </p>
    </form>
</div>
<?php endif; ?>

<table>
    <tr><th>No</th><th>Nama Layanan</th><th class="kanan">Harga</th><th>Durasi</th><?php if ($bisaUbah): ?><th>Aksi</th><?php endif; ?></tr>
    <?php foreach ($daftar as $i => $l): ?>
    <tr>
        <td><?= $i + 1 ?></td>
        <td><?= e($l['nama']) ?></td>
        <td class="kanan"><?= rupiah($l['harga']) ?></td>
        <td><?= (int)$l['durasi'] ?> menit</td>
        <?php if ($bisaUbah): ?>
        <td class="aksi">
            <a class="btn kecil" href="layanan.php?edit=<?= $l['id'] ?>">Ubah</a>
            <form method="post" onsubmit="return confirm('Hapus layanan ini?')">
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="id" value="<?= $l['id'] ?>">
                <button class="btn kecil merah" type="submit">Hapus</button>
            </form>
        </td>
        <?php endif; ?>
    </tr>
    <?php endforeach; ?>
    <?php if (!$daftar): ?><tr><td colspan="5">Belum ada data layanan.</td></tr><?php endif; ?>
</table>
<?php require 'footer.php'; ?>
