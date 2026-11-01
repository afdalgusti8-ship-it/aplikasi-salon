<?php
require 'config.php';

if (user()) {
    redirect('index.php');
}

$error = '';
$f = ['nama' => '', 'telepon' => '', 'alamat' => '', 'username' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($f as $k => $_) {
        $f[$k] = trim($_POST[$k] ?? '');
    }
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';

    if ($f['nama'] === '') {
        $error = 'Nama wajib diisi.';
    } elseif (!preg_match('/^[A-Za-z0-9_.]{4,30}$/', $f['username'])) {
        $error = 'Username 4-30 karakter, hanya huruf, angka, titik, dan garis bawah.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak sama.';
    } else {
        $berhasil = false;
        try {
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO pelanggan (nama, telepon, alamat) VALUES (?,?,?)')
                ->execute([$f['nama'], $f['telepon'], $f['alamat']]);
            $pid = (int)$pdo->lastInsertId();
            $pdo->prepare("INSERT INTO pengguna (username, password, nama, role, pelanggan_id) VALUES (?,?,?,'pelanggan',?)")
                ->execute([$f['username'], password_hash($password, PASSWORD_DEFAULT), $f['nama'], $pid]);
            $pdo->commit();
            $berhasil = true;
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = ((string)$ex->getCode() === '23000')
                ? 'Username sudah dipakai, pilih username lain.'
                : 'Pendaftaran gagal. Silakan coba lagi.';
        }
        if ($berhasil) {
            flash('Pendaftaran berhasil. Silakan masuk dengan akun Anda.');
            redirect('login.php');
        }
    }
}

$judul = 'Daftar Akun Pelanggan';
require 'header.php';
?>
<div class="card login-box">
    <?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
    <form method="post" autocomplete="off">
        <label>Nama Lengkap *</label>
        <input type="text" name="nama" value="<?= e($f['nama']) ?>" required>
        <label>No. Telepon</label>
        <input type="text" name="telepon" value="<?= e($f['telepon']) ?>">
        <label>Alamat</label>
        <input type="text" name="alamat" value="<?= e($f['alamat']) ?>">
        <label>Username *</label>
        <input type="text" name="username" value="<?= e($f['username']) ?>" required>
        <label>Password * (min. 6 karakter)</label>
        <input type="password" name="password" required>
        <label>Ulangi Password *</label>
        <input type="password" name="konfirmasi" required>
        <button class="btn" type="submit">Daftar</button>
    </form>
    <div class="tautan">Sudah punya akun? <a href="login.php">Masuk</a></div>
</div>
<?php require 'footer.php'; ?>
