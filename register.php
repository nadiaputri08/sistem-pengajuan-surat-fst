<?php require __DIR__ . '/includes/bootstrap.php';
if (user()) redirect('index.php');

// Halaman konfirmasi (Gambar 2.62): tampil sekali setelah daftar berhasil
$ok = $_SESSION['reg_ok'] ?? null;
if ($ok) unset($_SESSION['reg_ok']);

$errors = [];
$v = ['nama' => '', 'nim' => '', 'no_hp' => '', 'email' => '', 'prodi' => '', 'semester' => ''];
$setuju = false;

if (!$ok && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($v as $k => $_) $v[$k] = trim($_POST[$k] ?? '');
    $pw = $_POST['kata_sandi'] ?? ''; $pw2 = $_POST['ulang_sandi'] ?? '';
    $setuju = !empty($_POST['setuju']);

    if ($v['nama'] === '') $errors[] = 'Nama lengkap wajib diisi.';
    if (!preg_match('/^\d{6,20}$/', $v['nim'])) $errors[] = 'NIM harus berupa angka (6-20 digit).';
    if (!preg_match('/^[0-9+]{8,20}$/', $v['no_hp'])) $errors[] = 'Nomor HP tidak valid.';
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
    if (!in_array($v['prodi'], PRODI, true)) $errors[] = 'Pilih program studi.';
    if (!ctype_digit($v['semester']) || $v['semester'] < 1 || $v['semester'] > 14) $errors[] = 'Pilih semester (1-14).';
    if (!valid_password($pw)) $errors[] = 'Kata sandi minimal 8 karakter dan memuat huruf serta angka.';
    if ($pw !== $pw2) $errors[] = 'Pengulangan kata sandi tidak sama.';
    if (!$setuju) $errors[] = 'Centang pernyataan bahwa data yang Anda isi sudah benar.';

    if (!$errors) {
        if (q($pdo, 'SELECT 1 FROM pengguna WHERE nim=?', [$v['nim']])->fetch()) $errors[] = 'NIM sudah terdaftar.';
        if (q($pdo, 'SELECT 1 FROM pengguna WHERE email=?', [$v['email']])->fetch()) $errors[] = 'Email sudah terdaftar.';
    }
    if (!$errors) {
        q($pdo, "INSERT INTO pengguna(nim,nama,email,kata_sandi,no_hp,prodi,semester,role) VALUES(?,?,?,?,?,?,?,'mahasiswa')",
            [$v['nim'], $v['nama'], $v['email'], password_hash($pw, PASSWORD_DEFAULT), $v['no_hp'], $v['prodi'], (int)$v['semester']]);
        $_SESSION['reg_ok'] = ['nama' => $v['nama'], 'nim' => $v['nim']];
        redirect('register.php');
    }
}
?><!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $ok ? 'Akun berhasil dibuat' : 'Daftar' ?> - <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
<link rel="stylesheet" href="<?= url('assets/css/register.css') ?>"></head>
<body class="reg-body">
<?php if ($ok): ?>
<main class="reg-done"><div class="reg-done-card">
  <div class="reg-done-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="#1e6b16" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></div>
  <h1>Akun berhasil dibuat</h1>
  <p>Akun atas nama <strong><?= e($ok['nama']) ?></strong> (NIM <?= e($ok['nim']) ?>) siap dipakai. Silakan masuk untuk mulai mengajukan surat.</p>
  <a class="reg-btn" href="<?= url('login.php') ?>">Masuk ke portal mahasiswa</a>
</div></main>
<?php else: ?>
<div class="reg">
  <aside class="reg-side">
    <a class="reg-brand" href="<?= url('index.php') ?>">
      <span class="reg-logo" aria-hidden="true"><svg viewBox="0 0 24 24" width="22" height="22" fill="#fff"><path d="M21.5 3.2 2.9 10.4c-.8.3-.8 1.4 0 1.7l4.6 1.8 1.8 4.6c.3.8 1.4.8 1.7 0l1.4-3.5 4.3 3.2c.6.5 1.5.1 1.7-.7l2.6-14.6c.1-.9-.7-1.5-1.5-1.2zM9 13.2l8.6-5.9-6.9 7.4z"/></svg></span>
      <span>E-Surat FST</span>
    </a>
    <div class="reg-pitch">
      <h2>Buat akun mahasiswa Anda.</h2>
      <p>Satu akun untuk mengajukan, memantau, dan mengunduh surat dari Administrasi FST.</p>
      <ul>
        <li>Tujuh jenis surat dalam satu formulir</li>
        <li>Status dan catatan admin terlihat langsung</li>
        <li>Surat final diunduh sebagai PDF</li>
      </ul>
    </div>
    <small>Administrasi FST UIN Imam Bonjol Padang</small>
  </aside>

  <main class="reg-main"><div class="reg-form-wrap">
    <h1>Daftar</h1>
    <p class="reg-sub">Isi data sesuai identitas mahasiswa Anda. Semua kolom wajib diisi.</p>

    <?php if ($errors): ?><div class="alert err" role="alert"><ul><?php foreach ($errors as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

    <form method="post" novalidate><?= csrf_field() ?>
      <div class="reg-grid">
        <div class="full"><label for="nama">Nama lengkap</label>
          <input id="nama" type="text" name="nama" value="<?= e($v['nama']) ?>" placeholder="Sesuai data kampus" autocomplete="name" required></div>

        <div><label for="nim">NIM</label>
          <input id="nim" type="text" name="nim" value="<?= e($v['nim']) ?>" placeholder="contoh: 271028619" inputmode="numeric" required></div>
        <div><label for="no_hp">Nomor HP</label>
          <input id="no_hp" type="text" name="no_hp" value="<?= e($v['no_hp']) ?>" placeholder="0972836857" inputmode="tel" autocomplete="tel" required></div>

        <div class="full"><label for="email">Email</label>
          <input id="email" type="email" name="email" value="<?= e($v['email']) ?>" placeholder="nama@gmail.com" autocomplete="email" required></div>

        <div><label for="prodi">Program studi</label>
          <select id="prodi" name="prodi" required><option value="">Pilih program studi</option>
          <?php foreach (PRODI as $p): ?><option <?= $v['prodi'] === $p ? 'selected' : '' ?>><?= e($p) ?></option><?php endforeach; ?></select></div>
        <div><label for="semester">Semester</label>
          <select id="semester" name="semester" required><option value="">Pilih semester</option>
          <?php for ($i = 1; $i <= 14; $i++): ?><option value="<?= $i ?>" <?= $v['semester'] === (string)$i ? 'selected' : '' ?>>Semester <?= $i ?></option><?php endfor; ?></select></div>

        <div><label for="kata_sandi">Kata sandi</label>
          <input id="kata_sandi" type="password" name="kata_sandi" placeholder="Minimal 8 karakter" autocomplete="new-password" required>
          <span class="reg-hint">Gabungan huruf dan angka</span></div>
        <div><label for="ulang_sandi">Ulangi kata sandi</label>
          <input id="ulang_sandi" type="password" name="ulang_sandi" placeholder="Ketik ulang kata sandi" autocomplete="new-password" required></div>
      </div>

      <label class="reg-check"><input type="checkbox" name="setuju" value="1" <?= $setuju ? 'checked' : '' ?>>
        <span>Data yang saya isi benar dan saya setuju digunakan untuk keperluan layanan surat FST.</span></label>

      <div class="reg-actions">
        <button class="reg-btn" type="submit">Daftar</button>
        <p>Sudah punya akun? <a href="<?= url('login.php') ?>">Masuk</a></p>
      </div>
    </form>
  </div></main>
</div>
<?php endif; ?>
<script src="<?= url('assets/js/app.js') ?>"></script>
</body></html>
