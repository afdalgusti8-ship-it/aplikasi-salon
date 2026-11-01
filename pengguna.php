<?php
require 'config.php';
wajib_akses();

// ---------- PROSES FORM ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? '';
        $pid      = (int)($_POST['pelanggan_id'] ?? 0);
        $nama     = trim($_POST['nama'] ?? '');

        if (!preg_match('/^[A-Za-z0-9_.]{4,30}$/', $username)) {
            flash('Username 4-30 karakter, hanya huruf, angka, titik, dan garis bawah.', 'err'); redirect('pengguna.php');
        }
        if (strlen($password) < 6) {
            flash('Password minimal 6 karakter.', 'err'); redirect('pengguna.php');
        }
        if (!in_array($role, ['pemilik', 'admin', 'pelanggan'], true)) {
            flash('Role tidak valid.', 'err'); redirect('pengguna.php');
        }

        if ($role === 'pelanggan') {
            $st = $pdo->prepare('SELECT nama FROM pelanggan WHERE id=?');
            $st->execute([$pid]);
            $nama = $st->fetchColumn();
            if (!$nama) {
                flash('Pilih data pelanggan untuk akun ini.', 'err'); redirect('pengguna.php');
            }
            $st = $pdo->prepare('SELECT 1 FROM pengguna WHERE pelanggan_id=?');
            $st->execute([$pid]);
            if ($st->fetchColumn()) {
                flash('Pelanggan tersebut sudah memiliki akun.', 'err'); redirect('pengguna.php');
            }
        } else {
            $pid = null;
            if ($nama === '') {
                flash('Nama wajib diisi.', 'err'); redirect('pengguna.php');
            }
        }

        try {
            $pdo->prepare('INSERT INTO pengguna (username, password, nama, role, pelanggan_id) VALUES (?,?,?,?,?)')
                ->execute([$username, password_hash($password, PASSWORD_DEFAULT), $nama, $role, $pid]);
            flash('Akun berhasil ditambahkan.');
        } catch (PDOException $ex) {
            flash('Username sudah dipakai.', 'err');
        }
        redirect('pengguna.php');
    }

    if ($aksi === 'reset') {
        $id = (int)($_POST['id'] ?? 0);
        $baru = $_POST['password_baru'] ?? '';
        if (strlen($baru) < 6) {
            flash('Password baru minimal 6 karakter.', 'err');
        } else {
            $pdo->prepare('UPDATE pengguna SET password=? WHERE id=?')
                ->execute([password_hash($baru, PASSWORD_DEFAULT), $id]);
            flash('Password berhasil direset.');
        }
        redirect('pengguna.php');
    }

    if ($aksi === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);
        $st = $pdo->prepare('SELECT role FROM pengguna WHERE id=?');
        $st->execute([$id]);
        $target = $st->fetchColumn();

        if ($id === (int)user()['id']) {
            flash('Anda tidak dapat menghapus akun Anda sendiri.', 'err');
        } elseif ($target === 'pemilik' && (int)$pdo->query("SELECT COUNT(*) FROM pengguna WHERE role='pemilik'")->fetchColumn() <= 1) {
            flash('Akun pemilik terakhir tidak boleh dihapus.', 'err');
        } elseif ($target) {
            $pdo->prepare('DELETE FROM pengguna WHERE id=?')->execute([$id]);
            flash('Akun berhasil dihapus.');
        }
        redirect('pengguna.php');
    }
}

// ---------- AMBIL DATA ----------
$daftar = $pdo->query('SELECT * FROM pengguna ORDER BY FIELD(role, "pemilik", "admin", "pelanggan"), nama')->fetchAll();
$pelangganTanpaAkun = $pdo->query('SELECT id, nama FROM pelanggan
                                   WHERE id NOT IN (SELECT pelanggan_id FROM pengguna WHERE pelanggan_id IS NOT NULL)
                                   ORDER BY nama')->fetchAll();

$judul = 'Kelola Pengguna';
require 'header.php';
?>
<div class="card">
    <form method="post" autocomplete="off">
        <input type="hidden" name="aksi" value="tambah">
        <div class="form-grid">
            <div>
                <label>Role *</label>
                <select name="role" id="role" required>
                    <option value="admin">Admin</option>
                    <option value="pemilik">Pemilik</option>
                    <option value="pelanggan">Pelanggan</option>
                </select>
            </div>
            <div id="boxNama"><label>Nama *</label><input type="text" name="nama"></div>
            <div id="boxPelanggan" style="display:none">
                <label>Data Pelanggan *</label>
                <select name="pelanggan_id">
                    <option value="">-- pilih pelanggan --</option>
                    <?php foreach ($pelangganTanpaAkun as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= e($p['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>Username *</label><input type="text" name="username" required></div>
            <div><label>Password * (min. 6 karakter)</label><input type="password" name="password" required></div>
        </div>
        <p><button class="btn" type="submit">Tambah Akun</button></p>
    </form>
</div>

<table>
    <tr><th>Username</th><th>Nama</th><th>Role</th><th>Reset Password</th><th>Aksi</th></tr>
    <?php foreach ($daftar as $a): ?>
    <tr>
        <td><?= e($a['username']) ?></td>
        <td><?= e($a['nama']) ?></td>
        <td><span class="role-tag"><?= e(label_role($a['role'])) ?></span></td>
        <td>
            <form method="post" class="inline-form">
                <input type="hidden" name="aksi" value="reset">
                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                <input type="password" name="password_baru" placeholder="password baru" required minlength="6">
                <button class="btn kecil" type="submit">Reset</button>
            </form>
        </td>
        <td class="aksi">
            <?php if ((int)$a['id'] !== (int)user()['id']): ?>
            <form method="post" onsubmit="return confirm('Hapus akun ini?')">
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                <button class="btn kecil merah" type="submit">Hapus</button>
            </form>
            <?php else: ?><small>(akun Anda)</small><?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<script>
const role = document.getElementById('role');
function atur() {
    const pel = role.value === 'pelanggan';
    document.getElementById('boxPelanggan').style.display = pel ? '' : 'none';
    document.getElementById('boxNama').style.display = pel ? 'none' : '';
}
role.addEventListener('change', atur);
atur();
</script>
<?php require 'footer.php'; ?>
