<?php
require 'config.php';
wajib_akses();

// ---------- PROSES FORM ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'simpan') {
        $id      = (int)($_POST['id'] ?? 0);
        $nama    = trim($_POST['nama'] ?? '');
        $telepon = trim($_POST['telepon'] ?? '');
        $alamat  = trim($_POST['alamat'] ?? '');

        if ($nama === '') {
            flash('Nama pelanggan wajib diisi.', 'err');
            redirect('pelanggan.php' . ($id ? "?edit=$id" : ''));
        }
        if ($id) {
            $pdo->prepare('UPDATE pelanggan SET nama=?, telepon=?, alamat=? WHERE id=?')
                ->execute([$nama, $telepon, $alamat, $id]);
            flash('Data pelanggan berhasil diperbarui.');
        } else {
            $pdo->prepare('INSERT INTO pelanggan (nama, telepon, alamat) VALUES (?,?,?)')
                ->execute([$nama, $telepon, $alamat]);
            flash('Pelanggan berhasil ditambahkan.');
        }
        redirect('pelanggan.php');
    }

    if ($aksi === 'hapus') {
        try {
            $pdo->prepare('DELETE FROM pelanggan WHERE id=?')->execute([(int)($_POST['id'] ?? 0)]);
            flash('Pelanggan berhasil dihapus.');
        } catch (PDOException $ex) {
            flash('Pelanggan tidak dapat dihapus karena sudah memiliki janji temu.', 'err');
        }
        redirect('pelanggan.php');
    }
}

// ---------- AMBIL DATA ----------
$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM pelanggan WHERE id=?');
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch() ?: null;
}
$cari = trim($_GET['cari'] ?? '');
$st = $pdo->prepare('SELECT * FROM pelanggan WHERE nama LIKE ? OR telepon LIKE ? ORDER BY nama');
$st->execute(["%$cari%", "%$cari%"]);
$daftar = $st->fetchAll();

$judul = 'Data Pelanggan';
require 'header.php';
?>
<div class="card">
    <form method="post">
        <input type="hidden" name="aksi" value="simpan">
        <input type="hidden" name="id" value="<?= e($edit['id'] ?? '') ?>">
        <div class="form-grid">
            <div><label>Nama *</label><input type="text" name="nama" value="<?= e($edit['nama'] ?? '') ?>" required></div>
            <div><label>No. Telepon</label><input type="text" name="telepon" value="<?= e($edit['telepon'] ?? '') ?>"></div>
            <div><label>Alamat</label><input type="text" name="alamat" value="<?= e($edit['alamat'] ?? '') ?>"></div>
        </div>
        <p>
            <button class="btn" type="submit"><?= $edit ? 'Simpan Perubahan' : 'Tambah Pelanggan' ?></button>
            <?php if ($edit): ?><a class="btn abu" href="pelanggan.php">Batal</a><?php endif; ?>
        </p>
    </form>
</div>

<form method="get" class="toolbar">
    <div><label>Cari nama / telepon</label><input type="text" name="cari" value="<?= e($cari) ?>"></div>
    <button class="btn" type="submit">Cari</button>
    <a class="btn abu" href="pelanggan.php">Reset</a>
</form>

<table>
    <tr><th>No</th><th>Nama</th><th>Telepon</th><th>Alamat</th><th>Aksi</th></tr>
    <?php foreach ($daftar as $i => $p): ?>
    <tr>
        <td><?= $i + 1 ?></td>
        <td><?= e($p['nama']) ?></td>
        <td><?= e($p['telepon']) ?></td>
        <td><?= e($p['alamat']) ?></td>
        <td class="aksi">
            <a class="btn kecil" href="pelanggan.php?edit=<?= $p['id'] ?>">Ubah</a>
            <form method="post" onsubmit="return confirm('Hapus pelanggan ini?')">
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <button class="btn kecil merah" type="submit">Hapus</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$daftar): ?><tr><td colspan="5">Belum ada data pelanggan.</td></tr><?php endif; ?>
</table>
<?php require 'footer.php'; ?>
