<?php require __DIR__ . '/../includes/bootstrap.php';
$u = require_login('mahasiswa'); $judul = 'Dashboard';
$c = ['Menunggu' => 0, 'Diproses' => 0, 'Disetujui' => 0, 'Ditolak' => 0];
foreach (q($pdo, 'SELECT status, COUNT(*) n FROM pengajuan WHERE id_pengguna=? GROUP BY status', [$u['id']]) as $r) $c[$r['status']] = $r['n'];
$jalan = q($pdo, "SELECT p.*, j.nama_jenis, s.id_surat FROM pengajuan p JOIN jenis_surat j ON j.id_jenis=p.id_jenis LEFT JOIN surat s ON s.id_pengajuan=p.id_pengajuan
  WHERE p.id_pengguna=? AND (p.status IN ('Menunggu','Diproses') OR (p.status='Disetujui' AND s.id_surat IS NULL)) ORDER BY p.tanggal_pengajuan DESC", [$u['id']])->fetchAll();
$terbaru = q($pdo, 'SELECT p.*, j.nama_jenis FROM pengajuan p JOIN jenis_surat j ON j.id_jenis=p.id_jenis WHERE p.id_pengguna=? ORDER BY p.tanggal_pengajuan DESC LIMIT 5', [$u['id']])->fetchAll();
$notifs = q($pdo, 'SELECT * FROM notifikasi WHERE id_pengguna=? ORDER BY waktu DESC LIMIT 3', [$u['id']])->fetchAll();
require __DIR__ . '/../includes/header.php'; ?>
<h1>Halo, <?= e($u['nama']) ?></h1><p class="muted">Ringkasan pengajuan surat Anda.</p>
<div class="grid g4"><?php foreach ($c as $s => $n): ?><div class="card stat"><div class="n"><?= $n ?></div><?= pill($s) ?></div><?php endforeach; ?></div>
<div class="card"><h2>Pengajuan berjalan</h2>
<?php if (!$jalan): ?><p class="muted">Tidak ada pengajuan berjalan. <a href="<?= url('mahasiswa/ajukan.php') ?>">Ajukan surat</a></p><?php endif; ?>
<?php foreach ($jalan as $p): ?><p><a href="<?= url('mahasiswa/detail.php?id=' . $p['id_pengajuan']) ?>"><?= e($p['nama_jenis']) ?></a> <?= pill($p['status']) ?></p>
<div class="bar"><i style="width:<?= progres($p['status'], $p['id_surat']) ?>%"></i></div><?php endforeach; ?></div>
<div class="grid g2">
<div class="card"><h2>Notifikasi terbaru</h2><?php if (!$notifs) echo '<p class="muted">Belum ada notifikasi.</p>'; ?>
<?php foreach ($notifs as $n): ?><p><a href="<?= url('notifikasi.php?buka=' . $n['id_notifikasi']) ?>"><?= e($n['pesan']) ?></a><br><span class="muted"><?= tgl($n['waktu'], true) ?></span></p><?php endforeach; ?></div>
<div class="card"><h2>Pengajuan terbaru</h2><?php if (!$terbaru) echo '<p class="muted">Belum ada pengajuan.</p>'; ?>
<?php foreach ($terbaru as $p): ?><p><a href="<?= url('mahasiswa/detail.php?id=' . $p['id_pengajuan']) ?>"><?= e($p['nama_jenis']) ?></a> <?= pill($p['status']) ?><br><span class="muted"><?= tgl($p['tanggal_pengajuan']) ?></span></p><?php endforeach; ?></div>
</div>
<?php require __DIR__ . '/../includes/footer.php';
