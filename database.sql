-- ============================================================
-- DATABASE APLIKASI MANAJEMEN JASA SALON
-- Import lewat phpMyAdmin (tab Import) atau jalankan di MySQL.
-- ============================================================
CREATE DATABASE IF NOT EXISTS db_salon CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE db_salon;

-- ------------------------------------------------------------
-- 1. Tabel pelanggan
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pelanggan (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    nama    VARCHAR(100) NOT NULL,
    telepon VARCHAR(20)  DEFAULT NULL,
    alamat  VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 2. Tabel layanan
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS layanan (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    nama    VARCHAR(100) NOT NULL,
    harga   INT NOT NULL,
    durasi  INT NOT NULL DEFAULT 30          -- dalam menit
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3. Tabel janji_temu
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS janji_temu (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    pelanggan_id  INT NOT NULL,
    layanan_id    INT NOT NULL,
    tanggal       DATE NOT NULL,
    jam           TIME NOT NULL,
    status        ENUM('Terjadwal','Selesai','Dibatalkan') NOT NULL DEFAULT 'Terjadwal',
    catatan       VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id),
    FOREIGN KEY (layanan_id)   REFERENCES layanan(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 4. Tabel transaksi (pembayaran tunai)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS transaksi (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    janji_id   INT NOT NULL UNIQUE,
    waktu      DATETIME NOT NULL,
    total      INT NOT NULL,
    bayar      INT NOT NULL,
    kembalian  INT NOT NULL,
    FOREIGN KEY (janji_id) REFERENCES janji_temu(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 5. Tabel pengguna (akun login: pemilik, admin, pelanggan)
--    Akun awal (pemilik & admin) dibuat otomatis saat halaman login
--    pertama kali dibuka. Password disimpan dalam bentuk hash.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pengguna (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,
    nama          VARCHAR(100) NOT NULL,
    role          ENUM('pemilik','admin','pelanggan') NOT NULL,
    pelanggan_id  INT DEFAULT NULL,
    FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- DATA CONTOH (boleh dihapus; aman diimport berulang kali)
-- ============================================================
INSERT IGNORE INTO layanan (id, nama, harga, durasi) VALUES
(1, 'Potong Rambut',       35000, 30),
(2, 'Creambath',           75000, 60),
(3, 'Facial',             120000, 60),
(4, 'Manicure & Pedicure', 90000, 75);

INSERT IGNORE INTO pelanggan (id, nama, telepon, alamat) VALUES
(1, 'Siti Aminah',  '081234567801', 'Jl. Mawar No. 5'),
(2, 'Rina Pratiwi', '081234567802', 'Jl. Melati No. 12'),
(3, 'Dewi Lestari', '081234567803', 'Jl. Kenanga No. 7');

INSERT IGNORE INTO janji_temu (id, pelanggan_id, layanan_id, tanggal, jam, status, catatan) VALUES
(1, 1, 1, CURDATE(),                          '09:00:00', 'Selesai',    NULL),
(2, 2, 3, CURDATE(),                          '11:00:00', 'Terjadwal',  'Kulit sensitif'),
(3, 3, 2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', 'Terjadwal',  NULL);

INSERT IGNORE INTO transaksi (id, janji_id, waktu, total, bayar, kembalian) VALUES
(1, 1, CONCAT(CURDATE(), ' 09:40:00'), 35000, 50000, 15000);
