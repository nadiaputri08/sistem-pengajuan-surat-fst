<?php require __DIR__ . '/../includes/bootstrap.php';
$u = require_login('admin'); $judul = 'Dashboard Admin';
$c = ['Menunggu' => 0, 'Diproses' => 0, 'Disetujui' => 0, 'Ditolak' => 0];
foreach ($pdo->query('SELECT status, COUNT(*) n FROM pengajuan GROUP BY status')->fetchAll() as $r) $c[$r['status']] = $r['n'];
$bulan = []; for ($i = 5; $i >= 0; $i--) $bulan[date('Y-m', strtotime("first day of -$i month"))] = 0;
foreach (q($pdo, "SELECT DATE_FORMAT(tanggal_pengajuan,'%Y-%m') ym, COUNT(*) n FROM pengajuan WHERE tanggal_pengajuan >= ? GROUP BY ym", [date('Y-m-01', strtotime('first day of -5 month'))])->fetchAll() as $r) if (isset($bulan[$r['ym']])) $bulan[$r['ym']] = (int)$r['n'];
$max = max(1, max($bulan));
$terbaru = $pdo->query('SELECT p.*, u.nama, u.nim, j.nama_jenis FROM pengajuan p JOIN pengguna u ON u.id_pengguna=p.id_pengguna JOIN jenis_surat j ON j.id_jenis=p.id_jenis ORDER BY p.tanggal_pengajuan DESC LIMIT 8')->fetchAll();
require __DIR__ . '/../includes/header.php'; ?>
<h1>Dashboard Admin</h1>
<div class="grid g4"><?php foreach ($c as $s => $n): ?><a class="card stat" style="text-decoration:none;color:inherit" href="<?= url('admin/pengajuan.php?status=' . $s) ?>"><div class="n"><?= $n ?></div><?= pill($s) ?></a><?php endforeach; ?></div>
<div class="card"><h2>Pengajuan per bulan (6 bulan terakhir)</h2><div class="chart"><?php foreach ($bulan as $ym => $n): ?><div><small><?= $n ?></small><i style="height:<?= round($n / $max * 120) ?>px"></i><small><?= date('M y', strtotime($ym . '-01')) ?></small></div><?php endforeach; ?></div></div>
<div class="card tablewrap"><h2>Pengajuan terbaru</h2><table><tr><th>No.</th><th>Mahasiswa</th><th>Jenis</th><th>Tanggal</th><th>Status</th><th></th></tr>
<?php foreach ($terbaru as $r): ?><tr><td><?= e($r['nomor_pengajuan']) ?></td><td><?= e($r['nama']) ?><br><span class="muted"><?= e($r['nim']) ?></span></td><td><?= e($r['nama_jenis']) ?></td><td><?= tgl($r['tanggal_pengajuan']) ?></td><td><?= pill($r['status']) ?></td>
<td><a class="btn sm sec" href="<?= url('admin/detail.php?id=' . $r['id_pengajuan']) ?>">Buka</a></td></tr><?php endforeach; ?>
<?php if (!$terbaru) echo '<tr><td colspan="6" class="muted">Belum ada pengajuan.</td></tr>'; ?></table></div>
<?php require __DIR__ . '/../includes/footer.php';
