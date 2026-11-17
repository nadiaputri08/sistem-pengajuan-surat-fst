<?php require __DIR__ . '/includes/bootstrap.php';
$judul = 'Beranda';
$jenis = $pdo->query('SELECT nama_jenis FROM jenis_surat ORDER BY id_jenis')->fetchAll();
require __DIR__ . '/includes/public_header.php'; ?>
<section class="hero"><h1>Ajukan dan pantau surat mahasiswa tanpa antre</h1>
<p>Ajukan surat, unggah persyaratan, dan pantau statusnya kapan saja dari ponsel atau komputer.</p>
<a class="btn" href="<?= url('register.php') ?>">Ajukan Surat</a> <a class="btn sec" href="<?= url('login.php') ?>">Masuk</a></section>
<div class="wrap">
<h2>Jenis surat yang dilayani</h2>
<div class="grid g3"><?php foreach ($jenis as $j): ?><div class="card"><?= e($j['nama_jenis']) ?></div><?php endforeach; ?></div>
<h2 style="margin-top:2rem">Cara kerja</h2>
<div class="grid g4">
<div class="card"><b>1. Daftar &amp; masuk</b><p class="muted">Buat akun dengan NIM Anda.</p></div>
<div class="card"><b>2. Ajukan</b><p class="muted">Pilih jenis surat, isi keperluan, unggah persyaratan.</p></div>
<div class="card"><b>3. Pantau</b><p class="muted">Lihat status, progres, dan catatan admin.</p></div>
<div class="card"><b>4. Unduh</b><p class="muted">Surat yang terbit bisa dilihat dan dicetak.</p></div></div>
<div class="card" style="margin-top:2rem"><b>Butuh bantuan?</b> Hubungi Administrasi FST UIN Imam Bonjol Padang pada jam layanan.</div>
</div>
<?php require __DIR__ . '/includes/public_footer.php';
