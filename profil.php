<?php
require 'config.php';
wajib_akses();

$u = user();
$adalahPelanggan = ($u['role'] === 'pelanggan' && $u['pelanggan_id']);

// ---------- PROSES FORM ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'data') {
        $nama = trim($_POST['nama'] ?? '');
        if ($nama === '') {
            flash('Nama wajib diisi.', 'err');
            redirect('profil.php');
        }
        $pdo->prepare('UPDATE pengguna SET nama=? WHERE id=?')->execute([$nama, $u['id']]);
        if ($adalahPelanggan) {
            $pdo->prepare('UPDATE pelanggan SET nama=?, telepon=?, alamat=? WHERE id=?')
                ->execute([$nama, trim($_POST['telepon'] ?? ''), trim($_POST['alamat'] ?? ''), $u['pelanggan_id']]);
        }
        $_SESSION['user']['nama'] = $nama;
        flash('Profil berhasil diperbarui.');
        redirect('profil.php');
    }

    if ($aksi === 'password') {
        $lama = $_POST['lama'] ?? '';
        $baru = $_POST['baru'] ?? '';
        $konf = $_POST['konfirmasi'] ?? '';

        $st = $pdo->prepare('SELECT password FROM pengguna WHERE id=?');
        $st->execute([$u['id']]);
        $hash = $st->fetchColumn();

        if (!$hash || !password_verify($lama, $hash)) {
            flash('Password lama salah.', 'err');
        } elseif (strlen($baru) < 6) {
            flash('Password baru minimal 6 karakter.', 'err');
        } elseif ($baru !== $konf) {
            flash('Konfirmasi password baru tidak sama.', 'err');
        } else {
            $pdo->prepare('UPDATE pengguna SET password=? WHERE id=?')
                ->execute([password_hash($baru, PASSWORD_DEFAULT), $u['id']]);
            flash('Password berhasil diganti.');
        }
        redirect('profil.php');
    }
}

// ---------- AMBIL DATA ----------
$pel = ['telepon' => '', 'alamat' => ''];
if ($adalahPelanggan) {
    $st = $pdo->prepare('SELECT telepon, alamat FROM pelanggan WHERE id=?');
    $st->execute([$u['pelanggan_id']]);
    $pel = $st->fetch() ?: $pel;
}

$judul = 'Profil Saya';
require 'header.php';
?>
<div class="card">
    <h2 style="margin-top:0">Data Akun</h2>
    <form method="post">
        <input type="hidden" name="aksi" value="data">
        <div class="form-grid">
            <div><label>Username</label><input type="text" value="<?= e($u['username']) ?>" disabled></div>
            <div><label>Role</label><input type="text" value="<?= e(label_role($u['role'])) ?>" disabled></div>
            <div><label>Nama *</label><input type="text" name="nama" value="<?= e($u['nama']) ?>" required></div>
            <?php if ($adalahPelanggan): ?>
                <div><label>No. Telepon</label><input type="text" name="telepon" value="<?= e($pel['telepon']) ?>"></div>
                <div><label>Alamat</label><input type="text" name="alamat" value="<?= e($pel['alamat']) ?>"></div>
            <?php endif; ?>
        </div>
        <p><button class="btn" type="submit">Simpan Perubahan</button></p>
    </form>
</div>

<div class="card">
    <h2 style="margin-top:0">Ganti Password</h2>
    <form method="post" autocomplete="off">
        <input type="hidden" name="aksi" value="password">
        <div class="form-grid">
            <div><label>Password Lama *</label><input type="password" name="lama" required></div>
            <div><label>Password Baru * (min. 6 karakter)</label><input type="password" name="baru" required></div>
            <div><label>Ulangi Password Baru *</label><input type="password" name="konfirmasi" required></div>
        </div>
        <p><button class="btn" type="submit">Ganti Password</button></p>
    </form>
</div>
<?php require 'footer.php'; ?>
