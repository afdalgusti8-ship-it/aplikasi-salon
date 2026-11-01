<?php
require 'config.php';

if (user()) {
    redirect('index.php');
}

// Buat akun awal bila tabel pengguna masih kosong
try {
    if ((int)$pdo->query('SELECT COUNT(*) FROM pengguna')->fetchColumn() === 0) {
        $st = $pdo->prepare('INSERT INTO pengguna (username, password, nama, role) VALUES (?,?,?,?)');
        $st->execute(['pemilik', password_hash('pemilik123', PASSWORD_DEFAULT), 'Pemilik Salon', 'pemilik']);
        $st->execute(['admin',   password_hash('admin123',   PASSWORD_DEFAULT), 'Admin Salon',  'admin']);
    }
} catch (PDOException $ex) {
    die('Tabel pengguna belum ada. Import ulang file database.sql lewat phpMyAdmin, lalu muat ulang halaman ini.');
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $st = $pdo->prepare('SELECT * FROM pengguna WHERE username=?');
    $st->execute([$username]);
    $u = $st->fetch();

    if ($u && password_verify($password, $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'           => (int)$u['id'],
            'username'     => $u['username'],
            'nama'         => $u['nama'],
            'role'         => $u['role'],
            'pelanggan_id' => $u['pelanggan_id'] !== null ? (int)$u['pelanggan_id'] : null,
        ];
        redirect('index.php');
    }
    usleep(400000);   // memperlambat tebak-tebakan password
    $error = 'Username atau password salah.';
}

$judul = 'Masuk';
require 'header.php';
?>
<div class="card login-box">
    <?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
    <form method="post" autocomplete="off">
        <label>Username</label>
        <input type="text" name="username" value="<?= e($username) ?>" required autofocus>
        <label>Password</label>
        <input type="password" name="password" required>
        <button class="btn" type="submit">Masuk</button>
    </form>
    <div class="tautan">Belum punya akun? <a href="register.php">Daftar sebagai pelanggan</a></div>
</div>
<?php require 'footer.php'; ?>
