<?php require __DIR__ . '/includes/bootstrap.php';
$u = require_login(); $judul = 'Profil';
$me = q($pdo, 'SELECT * FROM pengguna WHERE id_pengguna=?', [$u['id']])->fetch(); $errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['aksi'] ?? '') === 'kontak') {
        $em = trim($_POST['email'] ?? ''); $hp = trim($_POST['no_hp'] ?? '');
        if (!filter_var($em, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
        if (!preg_match('/^[0-9+]{8,20}$/', $hp)) $errors[] = 'Nomor HP tidak valid.';
        if (!$errors && q($pdo, 'SELECT 1 FROM pengguna WHERE email=? AND id_pengguna<>?', [$em, $u['id']])->fetch()) $errors[] = 'Email sudah dipakai akun lain.';
        if (!$errors) { q($pdo, 'UPDATE pengguna SET email=?, no_hp=? WHERE id_pengguna=?', [$em, $hp, $u['id']]); flash('ok', 'Data kontak diperbarui.'); redirect('profil.php'); }
    } else {
        $lama = $_POST['lama'] ?? ''; $baru = $_POST['baru'] ?? ''; $ulang = $_POST['ulang'] ?? '';
        if (!password_verify($lama, $me['kata_sandi'])) $errors[] = 'Kata sandi lama salah.';
        if (!valid_password($baru)) $errors[] = 'Kata sandi baru minimal 8 karakter dan memuat huruf serta angka.';
        if ($baru !== $ulang) $errors[] = 'Pengulangan kata sandi baru tidak sama.';
        if (!$errors) { q($pdo, 'UPDATE pengguna SET kata_sandi=? WHERE id_pengguna=?', [password_hash($baru, PASSWORD_DEFAULT), $u['id']]); flash('ok', 'Kata sandi diperbarui.'); redirect('profil.php'); }
    }
}
require __DIR__ . '/includes/header.php'; ?>
<h1>Profil dan Pengaturan Akun</h1>
<?php if ($errors): ?><div class="alert err"><ul><?php foreach ($errors as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="card"><h2>Data akun</h2><dl class="kv"><dt>Nama</dt><dd><?= e($me['nama']) ?></dd>
<?php if ($me['role'] === 'mahasiswa'): ?><dt>NIM</dt><dd><?= e($me['nim']) ?></dd><dt>Program studi</dt><dd><?= e($me['prodi']) ?></dd><dt>Semester</dt><dd><?= e($me['semester']) ?></dd>
<?php else: ?><dt>Username</dt><dd><?= e($me['username']) ?></dd><dt>Jabatan</dt><dd><?= e($me['jabatan']) ?></dd><?php endif; ?></dl></div>
<div class="grid g2"><form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="aksi" value="kontak"><h2>Data kontak</h2>
<label>Email</label><input type="email" name="email" value="<?= e($me['email']) ?>" required><label>Nomor HP</label><input type="text" name="no_hp" value="<?= e($me['no_hp']) ?>" required>
<p><button class="btn" type="submit">Simpan</button></p></form>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="aksi" value="sandi"><h2>Ubah kata sandi</h2>
<label>Kata sandi lama</label><input type="password" name="lama" required><label>Kata sandi baru</label><input type="password" name="baru" required><label>Ulangi kata sandi baru</label><input type="password" name="ulang" required>
<p><button class="btn" type="submit">Ubah</button></p></form></div>
<?php require __DIR__ . '/includes/footer.php';
