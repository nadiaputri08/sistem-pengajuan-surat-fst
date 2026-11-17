<?php require __DIR__ . '/../includes/bootstrap.php';
$u = require_login('mahasiswa');
$p = q($pdo, 'SELECT p.*, j.nama_jenis, s.nomor_surat, s.tanggal_terbit FROM pengajuan p JOIN jenis_surat j ON j.id_jenis=p.id_jenis LEFT JOIN surat s ON s.id_pengajuan=p.id_pengajuan WHERE p.id_pengajuan=? AND p.id_pengguna=?', [(int)($_GET['id'] ?? 0), $u['id']])->fetch();
if (!$p) { http_response_code(404); exit('Pengajuan tidak ditemukan.'); }
$docs = q($pdo, 'SELECT d.*, r.nama_persyaratan FROM dokumen_pengajuan d JOIN persyaratan r ON r.id_persyaratan=d.id_persyaratan WHERE d.id_pengajuan=? ORDER BY r.id_persyaratan', [$p['id_pengajuan']])->fetchAll();
$riw = q($pdo, 'SELECT * FROM riwayat_status WHERE id_pengajuan=? ORDER BY waktu DESC, id_riwayat DESC', [$p['id_pengajuan']])->fetchAll();
$judul = 'Detail Pengajuan'; require __DIR__ . '/../includes/header.php'; ?>
<h1>Detail Pengajuan</h1><p class="muted"><?= e($p['nomor_pengajuan']) ?></p>
<div class="card"><dl class="kv"><dt>Jenis surat</dt><dd><?= e($p['nama_jenis']) ?></dd><dt>Tujuan</dt><dd><?= e($p['tujuan']) ?></dd>
<dt>Keperluan</dt><dd><?= nl2br(e($p['keperluan'])) ?></dd><dt>Tanggal</dt><dd><?= tgl($p['tanggal_pengajuan'], true) ?></dd><dt>Status</dt><dd><?= pill($p['status']) ?></dd></dl>
<?php if ($p['status'] !== 'Ditolak'): ?><p></p><div class="bar"><i style="width:<?= progres($p['status'], $p['nomor_surat']) ?>%"></i></div><?php endif; ?></div>
<?php if ($p['status'] === 'Ditolak'): ?><div class="alert err"><b>Ditolak.</b> <?= nl2br(e($p['alasan_penolakan'])) ?><p><a class="btn" href="<?= url('mahasiswa/ajukan.php?ulang=' . $p['id_pengajuan']) ?>">Ajukan ulang</a></p></div><?php endif; ?>
<?php if ($p['status'] === 'Disetujui'): ?><div class="alert <?= $p['nomor_surat'] ? 'ok' : 'info' ?>">
<?php if ($p['nomor_surat']): ?>Surat terbit dengan nomor <b><?= e($p['nomor_surat']) ?></b> (<?= tgl($p['tanggal_terbit']) ?>).
<a class="btn sm sec" href="<?= url('surat_cetak.php?id=' . $p['id_pengajuan']) ?>" target="_blank">Lihat</a> <a class="btn sm" href="<?= url('surat_cetak.php?unduh=1&id=' . $p['id_pengajuan']) ?>">Unduh PDF</a>
<?php else: ?>Pengajuan disetujui. Surat sedang diterbitkan oleh admin.<?php endif; ?></div><?php endif; ?>
<div class="card"><h2>Dokumen persyaratan</h2><div class="tablewrap"><table><tr><th>Persyaratan</th><th>File</th><th>Pemeriksaan</th><th>Catatan</th></tr>
<?php foreach ($docs as $d): ?><tr><td><?= e($d['nama_persyaratan']) ?></td><td><a href="<?= url('file.php?id=' . $d['id_dokumen']) ?>" target="_blank">Lihat</a></td>
<td><?= ['belum' => '<span class="pill abu">Belum diperiksa</span>', 'sesuai' => '<span class="pill hijau">Sesuai</span>', 'tidak_sesuai' => '<span class="pill merah">Tidak sesuai</span>'][$d['status_pemeriksaan']] ?></td><td><?= e($d['catatan']) ?></td></tr><?php endforeach; ?></table></div></div>
<div class="card"><h2>Riwayat status</h2><ul class="timeline"><?php foreach ($riw as $r): ?><li><?= pill($r['status']) ?> <span class="muted"><?= tgl($r['waktu'], true) ?></span><?= $r['catatan'] ? '<br>' . nl2br(e($r['catatan'])) : '' ?></li><?php endforeach; ?></ul></div>
<p><a class="btn sec" href="<?= url('mahasiswa/riwayat.php') ?>">Kembali</a></p>
<?php require __DIR__ . '/../includes/footer.php';
