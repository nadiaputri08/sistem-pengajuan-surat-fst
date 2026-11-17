<?php require __DIR__ . '/../includes/bootstrap.php';
$u = require_login('mahasiswa'); $judul = 'Riwayat Pengajuan';
$cari = trim($_GET['q'] ?? ''); $st = $_GET['status'] ?? '';
$sql = 'SELECT p.*, j.nama_jenis FROM pengajuan p JOIN jenis_surat j ON j.id_jenis=p.id_jenis WHERE p.id_pengguna=?'; $par = [$u['id']];
if ($cari !== '') { $sql .= ' AND (p.nomor_pengajuan LIKE ? OR j.nama_jenis LIKE ?)'; $par[] = "%$cari%"; $par[] = "%$cari%"; }
if (in_array($st, ['Menunggu', 'Diproses', 'Disetujui', 'Ditolak'], true)) { $sql .= ' AND p.status=?'; $par[] = $st; }
$rows = q($pdo, $sql . ' ORDER BY p.tanggal_pengajuan DESC', $par)->fetchAll();
require __DIR__ . '/../includes/header.php'; ?>
<h1>Riwayat Pengajuan</h1>
<form class="filters" method="get"><div><label>Cari</label><input type="text" name="q" value="<?= e($cari) ?>" placeholder="Nomor / jenis surat"></div>
<div><label>Status</label><select name="status"><option value="">Semua</option><?php foreach (['Menunggu', 'Diproses', 'Disetujui', 'Ditolak'] as $s): ?><option <?= $st === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></div>
<button class="btn" type="submit">Terapkan</button><button class="btn sec" type="button" data-print>Cetak / Simpan PDF</button></form>
<div class="card tablewrap"><table><tr><th>No. Pengajuan</th><th>Jenis Surat</th><th>Tanggal</th><th>Status</th><th></th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['nomor_pengajuan']) ?></td><td><?= e($r['nama_jenis']) ?></td><td><?= tgl($r['tanggal_pengajuan']) ?></td><td><?= pill($r['status']) ?></td>
<td><a class="btn sm sec" href="<?= url('mahasiswa/detail.php?id=' . $r['id_pengajuan']) ?>">Detail</a></td></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="5" class="muted">Tidak ada data.</td></tr><?php endif; ?></table></div>
<?php require __DIR__ . '/../includes/footer.php';
