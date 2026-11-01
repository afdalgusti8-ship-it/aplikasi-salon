<?php
// ============================================================
// KONFIGURASI, AUTENTIKASI & FUNGSI BANTU
// ============================================================
date_default_timezone_set('Asia/Makassar');   // WITA, ubah bila perlu

$DB_HOST = 'localhost';
$DB_NAME = 'db_salon';
$DB_USER = 'root';
$DB_PASS = '';            // default XAMPP: kosong

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $ex) {
    die('Koneksi database gagal. Pastikan MySQL aktif dan database.sql sudah di-import. Detail: ' . htmlspecialchars($ex->getMessage()));
}

ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
session_start();

// ------------------------------------------------------------
// HAK AKSES HALAMAN  (ubah di sini untuk mengatur siapa boleh buka apa)
//   'file.php' => ['Nama Menu', [role yang boleh], tampil di menu?]
// ------------------------------------------------------------
const HAK = [
    'index.php'      => ['Beranda',         ['pemilik', 'admin', 'pelanggan'], true],
    'pelanggan.php'  => ['Pelanggan',       ['pemilik', 'admin'],              true],
    'layanan.php'    => ['Layanan',         ['pemilik', 'admin', 'pelanggan'], true],
    'janji.php'      => ['Janji Temu',      ['pemilik', 'admin'],              true],
    'janji_saya.php' => ['Janji Saya',      ['pelanggan'],                     true],
    'transaksi.php'  => ['Pembayaran',      ['pemilik', 'admin'],              true],
    'nota.php'       => ['Nota',            ['pemilik', 'admin', 'pelanggan'], false],
    'laporan.php'    => ['Laporan Harian',  ['pemilik'],                       true],
    'pengguna.php'   => ['Kelola Pengguna', ['pemilik'],                       true],
    'profil.php'     => ['Profil',          ['pemilik', 'admin', 'pelanggan'], true],
];

function user()  { return $_SESSION['user'] ?? null; }
function role()  { return $_SESSION['user']['role'] ?? ''; }

function label_role($r) {
    return ['pemilik' => 'Pemilik', 'admin' => 'Admin', 'pelanggan' => 'Pelanggan'][$r] ?? $r;
}

function wajib_login() {
    if (!user()) {
        redirect('login.php');
    }
}

/** Panggil di baris awal setiap halaman terproteksi (sebelum output apa pun). */
function wajib_akses() {
    wajib_login();
    $file  = basename($_SERVER['SCRIPT_NAME']);
    $boleh = HAK[$file][1] ?? [];
    if (!in_array(role(), $boleh, true)) {
        flash('Anda tidak memiliki akses ke halaman tersebut.', 'err');
        redirect('index.php');
    }
}

// ------------------------------------------------------------
// FUNGSI BANTU UMUM
// ------------------------------------------------------------
function e($teks)           { return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8'); }
function rupiah($angka)     { return 'Rp ' . number_format((float)$angka, 0, ',', '.'); }
function angka($teks)       { return (int)preg_replace('/\D/', '', (string)$teks); }
function flash($pesan, $jenis = 'ok') { $_SESSION['flash'] = [$pesan, $jenis]; }
function redirect($url)     { header("Location: $url"); exit; }

function valid_tanggal($s) {
    $d = DateTime::createFromFormat('Y-m-d', $s);
    return $d && $d->format('Y-m-d') === $s;
}
function valid_jam($s) {
    $d = DateTime::createFromFormat('H:i', $s);
    return $d && $d->format('H:i') === $s;
}
