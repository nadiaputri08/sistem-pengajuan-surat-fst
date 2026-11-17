<?php require __DIR__ . '/../includes/bootstrap.php';
$u = require_login('admin');
$cari = trim($_GET['q'] ?? ''); $st = $_GET['status'] ?? ''; $jn = (int)($_GET['jenis'] ?? 0); $dr = $_GET['dari'] ?? ''; $sp = $_GET['sampai'] ?? '';
$stValid = in_array($st, ['Menunggu', 'Diproses', 'Disetujui', 'Ditolak'], true);
$judul = ['Diproses' => 'Surat Diproses', 'Disetujui' => 'Surat Disetujui', 'Ditolak' => 'Surat Ditolak'][$st] ?? 'Kelola Pengajuan';
$sql = "SELECT p.*, u.nama, u.nim, j.nama_jenis, s.nomor_surat,
 (SELECT COUNT(*) FROM dokumen_pengajuan d WHERE d.id_pengajuan=p.id_pengajuan AND d.status_pemeriksaan='sesuai') sesuai,
 (SELECT COUNT(*) FROM persyaratan r WHERE r.id_jenis=p.id_jenis) total_dok
 FROM pengajuan p JOIN pengguna u ON u.id_pengguna=p.id_pengguna JOIN jenis_surat j ON j.id_jenis=p.id_jenis LEFT JOIN surat s ON s.id_pengajuan=p.id_pengajuan WHERE 1=1";
$par = [];
if ($cari !== '') { $sql .= ' AND (u.nama LIKE ? OR u.nim LIKE ? OR p.nomor_pengajuan LIKE ?)'; array_push($par, "%$cari%", "%$cari%", "%$cari%"); }
if ($stValid) { $sql .= ' AND p.status=?'; $par[] = $st; }
if ($jn) { $sql .= ' AND p.id_jenis=?'; $par[] = $jn; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dr)) { $sql .= ' AND p.tanggal_pengajuan>=?'; $par[] = $dr . ' 00:00:00'; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $sp)) { $sql .= ' AND p.tanggal_pengajuan<=?'; $par[] = $sp . ' 23:59:59'; }
$rows = q($pdo, $sql . ' ORDER BY p.tanggal_pengajuan DESC LIMIT 300', $par)->fetchAll();
$jenis = $pdo->query('SELECT * FROM jenis_surat ORDER BY id_jenis')->fetchAll();
require __DIR__ . '/../includes/header.php'; ?>
<h1><?= e($judul) ?></h1>
<form class="filters" method="get"><div><label>Cari</label><input type="text" name="q" value="<?= e($cari) ?>" placeholder="Nama / NIM / nomor"></div>
<div><label>Jenis surat</label><select name="jenis"><option value="0">Semua</option><?php foreach ($jenis as $j): ?><option value="<?= $j['id_jenis'] ?>" <?= $jn == $j['id_jenis'] ? 'selected' : '' ?>><?= e($j['nama_jenis']) ?></option><?php endforeach; ?></select></div>
<div><label>Status</label><select name="status"><option value="">Semua</option><?php foreach (['Menunggu', 'Diproses', 'Disetujui', 'Ditolak'] as $s): ?><option <?= $st === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></div>
<div><label>Dari</label><input type="date" name="dari" value="<?= e($dr) ?>"></div><div><label>Sampai</label><input type="date" name="sampai" value="<?= e($sp) ?>"></div>
<button class="btn" type="submit">Terapkan</button></form>
<div class="card tablewrap"><table><tr><th>No.</th><th>Mahasiswa</th><th>Jenis</th><th>Tanggal</th><th>Status</th><th>Keterangan</th><th></th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['nomor_pengajuan']) ?></td><td><?= e($r['nama']) ?><br><span class="muted"><?= e($r['nim']) ?></span></td><td><?= e($r['nama_jenis']) ?></td><td><?= tgl($r['tanggal_pengajuan']) ?></td><td><?= pill($r['status']) ?></td>
<td><?php if ($r['status'] === 'Disetujui') echo $r['nomor_surat'] ? e($r['nomor_surat']) : '<span class="muted">Belum terbit</span>';
 elseif ($r['status'] === 'Ditolak') echo e(mb_strimwidth((string)$r['alasan_penolakan'], 0, 70, '...'));
 else echo 'Dokumen sesuai: ' . $r['sesuai'] . '/' . $r['total_dok']; ?></td>
<td class="row-actions"><a class="btn sm sec" href="<?= url('admin/detail.php?id=' . $r['id_pengajuan']) ?>">Detail</a>
<?php if ($r['status'] === 'Disetujui' && !$r['nomor_surat']): ?><a class="btn sm" href="<?= url('admin/terbitkan.php?id=' . $r['id_pengajuan']) ?>">Terbitkan</a><?php endif; ?>
<?php if ($r['nomor_surat']): ?><a class="btn sm sec" target="_blank" href="<?= url('surat_cetak.php?id=' . $r['id_pengajuan']) ?>">Lihat surat</a><?php endif; ?></td></tr><?php endforeach; ?>
<?php if (!$rows) echo '<tr><td colspan="7" class="muted">Tidak ada data.</td></tr>'; ?></table></div>
<?php require __DIR__ . '/../includes/footer.php';
