<?php require __DIR__ . '/../includes/bootstrap.php';
$u = require_login('admin'); $judul = 'Data Mahasiswa'; $cari = trim($_GET['q'] ?? '');
$sql = "SELECT u.*, (SELECT COUNT(*) FROM pengajuan p WHERE p.id_pengguna=u.id_pengguna) jml FROM pengguna u WHERE u.role='mahasiswa'"; $par = [];
if ($cari !== '') { $sql .= ' AND (u.nim LIKE ? OR u.nama LIKE ?)'; $par = ["%$cari%", "%$cari%"]; }
$rows = q($pdo, $sql . ' ORDER BY u.nama LIMIT 300', $par)->fetchAll();
require __DIR__ . '/../includes/header.php'; ?>
<h1>Data Mahasiswa</h1>
<form class="filters" method="get"><div><label>Cari NIM atau nama</label><input type="text" name="q" value="<?= e($cari) ?>"></div><button class="btn" type="submit">Cari</button></form>
<div class="card tablewrap"><table><tr><th>NIM</th><th>Nama</th><th>Prodi</th><th>Smt</th><th>Email</th><th>No. HP</th><th>Pengajuan</th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['nim']) ?></td><td><?= e($r['nama']) ?></td><td><?= e($r['prodi']) ?></td><td><?= e($r['semester']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['no_hp']) ?></td><td><?= (int)$r['jml'] ?></td></tr><?php endforeach; ?>
<?php if (!$rows) echo '<tr><td colspan="7" class="muted">Data tidak ditemukan.</td></tr>'; ?></table></div>
<?php require __DIR__ . '/../includes/footer.php';
