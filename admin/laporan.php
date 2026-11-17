<?php require __DIR__ . '/../includes/bootstrap.php';
$u = require_login('admin');
$dr = $_GET['dari'] ?? date('Y-01-01'); $sp = $_GET['sampai'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dr)) $dr = date('Y-01-01'); if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $sp)) $sp = date('Y-m-d');
$par = [$dr . ' 00:00:00', $sp . ' 23:59:59'];
$rows = q($pdo, 'SELECT p.nomor_pengajuan, p.tanggal_pengajuan, p.status, u.nama, u.nim, j.nama_jenis, s.nomor_surat FROM pengajuan p JOIN pengguna u ON u.id_pengguna=p.id_pengguna JOIN jenis_surat j ON j.id_jenis=p.id_jenis LEFT JOIN surat s ON s.id_pengajuan=p.id_pengajuan WHERE p.tanggal_pengajuan BETWEEN ? AND ? ORDER BY p.tanggal_pengajuan', $par)->fetchAll();
if (($_GET['ekspor'] ?? '') === 'csv') {   // CSV titik-koma agar terbuka rapi di Excel Indonesia
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="laporan-surat-' . $dr . '_' . $sp . '.csv"');
    $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF");
    fputcsv($o, ['No. Pengajuan', 'Tanggal', 'NIM', 'Nama', 'Jenis Surat', 'Status', 'Nomor Surat'], ';');
    foreach ($rows as $r) fputcsv($o, [$r['nomor_pengajuan'], $r['tanggal_pengajuan'], '="' . $r['nim'] . '"', $r['nama'], $r['nama_jenis'], $r['status'], $r['nomor_surat']], ';');
    exit;
}
$ring = ['Menunggu' => 0, 'Diproses' => 0, 'Disetujui' => 0, 'Ditolak' => 0]; $rekap = [];
foreach ($rows as $r) { $ring[$r['status']]++; $rekap[$r['nama_jenis']][$r['status']] = ($rekap[$r['nama_jenis']][$r['status']] ?? 0) + 1; }
$judul = 'Laporan'; require __DIR__ . '/../includes/header.php'; ?>
<h1>Laporan Pengajuan</h1>
<form class="filters" method="get"><div><label>Dari</label><input type="date" name="dari" value="<?= e($dr) ?>"></div><div><label>Sampai</label><input type="date" name="sampai" value="<?= e($sp) ?>"></div>
<button class="btn" type="submit">Tampilkan</button>
<a class="btn sec" href="?dari=<?= e($dr) ?>&sampai=<?= e($sp) ?>&ekspor=csv">Ekspor Excel (CSV)</a><button class="btn sec" type="button" data-print>Cetak / PDF</button></form>
<p class="muted">Periode <?= tgl($dr) ?> s.d. <?= tgl($sp) ?> &middot; total <?= count($rows) ?> pengajuan</p>
<div class="grid g4"><?php foreach ($ring as $s => $n): ?><div class="card stat"><div class="n"><?= $n ?></div><?= pill($s) ?></div><?php endforeach; ?></div>
<div class="card tablewrap"><h2>Rekap per jenis surat</h2><table><tr><th>Jenis surat</th><th>Menunggu</th><th>Diproses</th><th>Disetujui</th><th>Ditolak</th><th>Total</th></tr>
<?php foreach ($rekap as $jn => $v): ?><tr><td><?= e($jn) ?></td><?php foreach (['Menunggu', 'Diproses', 'Disetujui', 'Ditolak'] as $s) echo '<td>' . ($v[$s] ?? 0) . '</td>'; ?><td><b><?= array_sum($v) ?></b></td></tr><?php endforeach; ?>
<?php if (!$rekap) echo '<tr><td colspan="6" class="muted">Tidak ada data pada periode ini.</td></tr>'; ?></table></div>
<div class="card tablewrap"><h2>Rincian</h2><table><tr><th>No.</th><th>Tanggal</th><th>Mahasiswa</th><th>Jenis</th><th>Status</th><th>No. Surat</th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['nomor_pengajuan']) ?></td><td><?= tgl($r['tanggal_pengajuan']) ?></td><td><?= e($r['nama']) ?> (<?= e($r['nim']) ?>)</td><td><?= e($r['nama_jenis']) ?></td><td><?= pill($r['status']) ?></td><td><?= e($r['nomor_surat']) ?></td></tr><?php endforeach; ?></table></div>
<?php require __DIR__ . '/../includes/footer.php';
