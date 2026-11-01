<?php
$halaman = basename($_SERVER['SCRIPT_NAME']);
$u = user();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($judul ?? 'Aplikasi Salon') ?> - Aplikasi Salon</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
    <div class="brand">💇 Aplikasi Manajemen Jasa Salon</div>
    <?php if ($u): ?>
    <nav>
        <?php foreach (HAK as $file => $def): ?>
            <?php if ($def[2] && in_array($u['role'], $def[1], true)): ?>
                <a href="<?= $file ?>" class="<?= $halaman === $file ? 'aktif' : '' ?>"><?= e($def[0]) ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
    <div class="user-info">
        <?= e($u['nama']) ?> <span class="role-tag"><?= e(label_role($u['role'])) ?></span>
        <a href="logout.php">Keluar</a>
    </div>
    <?php endif; ?>
</header>
<main class="container">
<h1><?= e($judul ?? '') ?></h1>
<?php
if (!empty($_SESSION['flash'])) {
    [$pesan, $jenis] = $_SESSION['flash'];
    unset($_SESSION['flash']);
    echo '<div class="alert ' . e($jenis) . '">' . e($pesan) . '</div>';
}
?>
