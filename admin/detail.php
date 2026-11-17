<?php require __DIR__ . '/../includes/bootstrap.php';
$u = require_login('admin'); $id = (int)($_GET['id'] ?? 0);
$sqlP = 'SELECT p.*, u.nama, u.nim, u.prodi, u.semester, u.email, u.no_hp, j.nama_jenis, s.nomor_surat FROM pengajuan p JOIN pengguna u ON u.id_pengguna=p.id_pengguna JOIN jenis_surat j ON j.id_jenis=p.id_jenis LEFT JOIN surat s ON s.id_pengajuan=p.id_pengajuan WHERE p.id_pengajuan=?';
$p = q($pdo, $sqlP, [$id])->fetch();
if (!$p) { http_response_code(404); exit('Pengajuan tidak ditemukan.'); }
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $aksi = $_POST['aksi'] ?? ''; $st = $p['status'];
    if ($aksi === 'periksa' && in_array($st, ['Menunggu', 'Diproses'], true)) {
        $adaTidak = false;
        foreach (($_POST['hasil'] ?? []) as $idd => $h) {
            $cat = trim($_POST['catatan'][$idd] ?? '');
            if (!in_array($h, ['sesuai', 'tidak_sesuai'], true)) continue;
            if ($h === 'tidak_sesuai' && $cat === '') { $errors[] = 'Catatan wajib diisi untuk dokumen yang tidak sesuai.'; continue; }
            if ($h === 'tidak_sesuai') $adaTidak = true;
            q($pdo, 'UPDATE dokumen_pengajuan SET status_pemeriksaan=?, catatan=?, id_pemeriksa=?, tanggal_periksa=NOW() WHERE id_dokumen=? AND id_pengajuan=?', [$h, $cat ?: null, $u['id'], (int)$idd, $id]);
        }
        if (!$errors) {
            if ($adaTidak) notif($pdo, $p['id_pengguna'], $id, 'Ada dokumen yang perlu diperbaiki pada pengajuan ' . $p['nomor_pengajuan'] . '. Lihat catatan admin.');
            flash('ok', 'Hasil pemeriksaan disimpan.'); redirect('admin/detail.php?id=' . $id);
        }
    } elseif ($aksi === 'proses' && $st === 'Menunggu') {
        if (!semua_sesuai($pdo, $id, $p['id_jenis'])) $errors[] = 'Semua dokumen harus diperiksa dan berstatus sesuai sebelum diproses.';
        else {
            q($pdo, "UPDATE pengajuan SET status='Diproses' WHERE id_pengajuan=?", [$id]); catat($pdo, $id, 'Diproses', 'Dokumen lengkap, pengajuan diproses', $u['id']);
            notif($pdo, $p['id_pengguna'], $id, 'Pengajuan ' . $p['nomor_pengajuan'] . ' sedang diproses.');
            flash('ok', 'Status diubah menjadi Diproses.'); redirect('admin/detail.php?id=' . $id);
        }
    } elseif ($aksi === 'setujui' && $st === 'Diproses') {
        if (!semua_sesuai($pdo, $id, $p['id_jenis'])) $errors[] = 'Dokumen belum seluruhnya sesuai.';
        else {
            q($pdo, "UPDATE pengajuan SET status='Disetujui' WHERE id_pengajuan=?", [$id]); catat($pdo, $id, 'Disetujui', 'Pengajuan disetujui', $u['id']);
            notif($pdo, $p['id_pengguna'], $id, 'Pengajuan ' . $p['nomor_pengajuan'] . ' disetujui. Surat sedang diterbitkan.');
            flash('ok', 'Pengajuan disetujui. Silakan terbitkan surat.'); redirect('admin/terbitkan.php?id=' . $id);
        }
    } elseif ($aksi === 'tolak' && in_array($st, ['Menunggu', 'Diproses'], true)) {
        $alasan = trim($_POST['alasan'] ?? '');
        if ($alasan === '') $errors[] = 'Alasan penolakan wajib diisi.';
        else {
            q($pdo, "UPDATE pengajuan SET status='Ditolak', alasan_penolakan=? WHERE id_pengajuan=?", [$alasan, $id]); catat($pdo, $id, 'Ditolak', $alasan, $u['id']);
            notif($pdo, $p['id_pengguna'], $id, 'Pengajuan ' . $p['nomor_pengajuan'] . ' ditolak. Lihat alasan dan ajukan ulang.');
            flash('ok', 'Pengajuan ditolak.'); redirect('admin/detail.php?id=' . $id);
        }
    } else $errors[] = 'Aksi tidak dapat dilakukan pada status saat ini.';
    $p = q($pdo, $sqlP, [$id])->fetch();
}
$docs = q($pdo, 'SELECT r.id_persyaratan, r.nama_persyaratan, d.* FROM persyaratan r LEFT JOIN dokumen_pengajuan d ON d.id_persyaratan=r.id_persyaratan AND d.id_pengajuan=? WHERE r.id_jenis=? ORDER BY r.id_persyaratan', [$id, $p['id_jenis']])->fetchAll();
$riw = q($pdo, 'SELECT * FROM riwayat_status WHERE id_pengajuan=? ORDER BY waktu DESC, id_riwayat DESC', [$id])->fetchAll();
$lengkap = semua_sesuai($pdo, $id, $p['id_jenis']); $bisaPeriksa = in_array($p['status'], ['Menunggu', 'Diproses'], true);
$judul = 'Detail Pengajuan'; require __DIR__ . '/../includes/header.php'; ?>
<h1>Detail Pengajuan</h1><p class="muted"><?= e($p['nomor_pengajuan']) ?> &middot; <?= pill($p['status']) ?></p>
<?php if ($errors): ?><div class="alert err"><ul><?php foreach ($errors as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="card"><dl class="kv"><dt>Mahasiswa</dt><dd><?= e($p['nama']) ?> (<?= e($p['nim']) ?>)</dd><dt>Prodi / Semester</dt><dd><?= e($p['prodi']) ?> / <?= e($p['semester']) ?></dd>
<dt>Kontak</dt><dd><?= e($p['email']) ?> &middot; <?= e($p['no_hp']) ?></dd><dt>Jenis surat</dt><dd><?= e($p['nama_jenis']) ?></dd><dt>Tujuan</dt><dd><?= e($p['tujuan']) ?></dd>
<dt>Keperluan</dt><dd><?= nl2br(e($p['keperluan'])) ?></dd><dt>Tanggal</dt><dd><?= tgl($p['tanggal_pengajuan'], true) ?></dd>
<?php if ($p['alasan_penolakan']): ?><dt>Alasan penolakan</dt><dd><?= nl2br(e($p['alasan_penolakan'])) ?></dd><?php endif; ?>
<?php if ($p['nomor_surat']): ?><dt>Nomor surat</dt><dd><?= e($p['nomor_surat']) ?> <a class="btn sm sec" target="_blank" href="<?= url('surat_cetak.php?id=' . $id) ?>">Lihat</a> <a class="btn sm sec" href="<?= url('admin/terbitkan.php?id=' . $id) ?>">Ubah</a></dd><?php endif; ?></dl></div>

<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="aksi" value="periksa"><h2>Pemeriksaan dokumen</h2>
<div class="tablewrap"><table><tr><th>Persyaratan</th><th>File</th><th>Hasil</th><th>Catatan</th></tr>
<?php foreach ($docs as $d): ?><tr><td><?= e($d['nama_persyaratan']) ?></td>
<?php if (!$d['id_dokumen']): ?><td colspan="3" class="muted">Belum diunggah</td>
<?php else: ?><td><a href="<?= url('file.php?id=' . $d['id_dokumen']) ?>" target="_blank">Buka</a></td>
<td><?php if ($bisaPeriksa): ?><select name="hasil[<?= $d['id_dokumen'] ?>]"><option value="">Belum diperiksa</option><option value="sesuai" <?= $d['status_pemeriksaan'] === 'sesuai' ? 'selected' : '' ?>>Sesuai</option><option value="tidak_sesuai" <?= $d['status_pemeriksaan'] === 'tidak_sesuai' ? 'selected' : '' ?>>Tidak sesuai</option></select>
<?php else: echo e(['belum' => 'Belum diperiksa', 'sesuai' => 'Sesuai', 'tidak_sesuai' => 'Tidak sesuai'][$d['status_pemeriksaan']]); endif; ?></td>
<td><?php if ($bisaPeriksa): ?><input type="text" name="catatan[<?= $d['id_dokumen'] ?>]" value="<?= e($d['catatan']) ?>" placeholder="Wajib bila tidak sesuai"><?php else: echo e($d['catatan']); endif; ?></td><?php endif; ?></tr><?php endforeach; ?></table></div>
<?php if ($bisaPeriksa): ?><p><button class="btn" type="submit">Simpan Pemeriksaan</button></p><?php endif; ?></form>

<?php if ($bisaPeriksa): ?><div class="card"><h2>Tindakan</h2><div class="row-actions">
<?php if ($p['status'] === 'Menunggu'): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="proses"><button class="btn" <?= $lengkap ? '' : 'disabled' ?>>Proses</button></form><?php endif; ?>
<?php if ($p['status'] === 'Diproses'): ?><form method="post" data-confirm="Setujui pengajuan ini?"><?= csrf_field() ?><input type="hidden" name="aksi" value="setujui"><button class="btn" <?= $lengkap ? '' : 'disabled' ?>>Setujui</button></form><?php endif; ?></div>
<?php if (!$lengkap): ?><p class="muted">Tombol aktif setelah seluruh dokumen diperiksa dan berstatus sesuai.</p><?php endif; ?>
<form method="post" data-confirm="Tolak pengajuan ini?"><?= csrf_field() ?><input type="hidden" name="aksi" value="tolak"><label>Alasan penolakan (wajib)</label><textarea name="alasan" required></textarea><p><button class="btn bad" type="submit">Tolak</button></p></form></div><?php endif; ?>
<?php if ($p['status'] === 'Disetujui' && !$p['nomor_surat']): ?><p><a class="btn" href="<?= url('admin/terbitkan.php?id=' . $id) ?>">Terbitkan surat (unggah PDF)</a></p><?php endif; ?>

<div class="card"><h2>Riwayat status</h2><ul class="timeline"><?php foreach ($riw as $r): ?><li><?= pill($r['status']) ?> <span class="muted"><?= tgl($r['waktu'], true) ?></span><?= $r['catatan'] ? '<br>' . nl2br(e($r['catatan'])) : '' ?></li><?php endforeach; ?></ul></div>
<?php require __DIR__ . '/../includes/footer.php';
