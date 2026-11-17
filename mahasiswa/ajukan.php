<?php require __DIR__ . '/../includes/bootstrap.php';
$u = require_login('mahasiswa');
$ulang = null; $docs = [];
if (isset($_GET['ulang'])) {
    $ulang = q($pdo, "SELECT * FROM pengajuan WHERE id_pengajuan=? AND id_pengguna=? AND status='Ditolak'", [(int)$_GET['ulang'], $u['id']])->fetch();
    if (!$ulang) { flash('err', 'Pengajuan tidak ditemukan atau tidak dapat diajukan ulang.'); redirect('mahasiswa/riwayat.php'); }
    foreach (q($pdo, 'SELECT * FROM dokumen_pengajuan WHERE id_pengajuan=?', [$ulang['id_pengajuan']])->fetchAll() as $d) $docs[$d['id_persyaratan']] = $d;
}
$jenis = $pdo->query('SELECT * FROM jenis_surat ORDER BY id_jenis')->fetchAll();
$syarat = [];
foreach ($pdo->query('SELECT * FROM persyaratan ORDER BY id_persyaratan')->fetchAll() as $s) $syarat[$s['id_jenis']][] = $s;
$errors = []; $idj = $ulang ? (int)$ulang['id_jenis'] : (int)($_POST['id_jenis'] ?? 0);
$tujuan = trim($_POST['tujuan'] ?? ($ulang['tujuan'] ?? '')); $keperluan = trim($_POST['keperluan'] ?? ($ulang['keperluan'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!isset($syarat[$idj])) $errors[] = 'Pilih jenis surat.';
    if ($tujuan === '') $errors[] = 'Tujuan surat wajib diisi.';
    if ($keperluan === '') $errors[] = 'Keperluan wajib diisi.';
    $simpan = [];
    foreach ($syarat[$idj] ?? [] as $r) {
        $rid = $r['id_persyaratan'];
        $ada = isset($_FILES['dok']['error'][$rid]) && $_FILES['dok']['error'][$rid] !== UPLOAD_ERR_NO_FILE;
        $lama = isset($docs[$rid]) && $docs[$rid]['status_pemeriksaan'] !== 'tidak_sesuai';
        if (!$ada) { if (!$lama) $errors[] = $r['nama_persyaratan'] . ': dokumen wajib diunggah.'; continue; }
        [$ok, $info] = cek_upload($_FILES['dok']['error'][$rid], $_FILES['dok']['size'][$rid], $_FILES['dok']['tmp_name'][$rid], $_FILES['dok']['name'][$rid]);
        if (!$ok) { $errors[] = $r['nama_persyaratan'] . ': ' . $info; continue; }
        $simpan[$rid] = [$_FILES['dok']['tmp_name'][$rid], $info];
    }
    if (!$errors) {
        $baru = [];
        try {
            $pdo->beginTransaction();
            if ($ulang) {
                $idp = (int)$ulang['id_pengajuan'];
                q($pdo, "UPDATE pengajuan SET status='Menunggu', tujuan=?, keperluan=?, alasan_penolakan=NULL, catatan_admin=NULL WHERE id_pengajuan=?", [$tujuan, $keperluan, $idp]);
            } else {
                q($pdo, 'INSERT INTO pengajuan(id_pengguna,id_jenis,tujuan,keperluan) VALUES(?,?,?,?)', [$u['id'], $idj, $tujuan, $keperluan]);
                $idp = (int)$pdo->lastInsertId();
                q($pdo, 'UPDATE pengajuan SET nomor_pengajuan=? WHERE id_pengajuan=?', ['PGJ-' . date('Ymd') . '-' . str_pad($idp, 4, '0', STR_PAD_LEFT), $idp]);
            }
            foreach ($simpan as $rid => [$tmp, $ext]) {
                $nama = bin2hex(random_bytes(16)) . '.' . $ext;
                if (!move_uploaded_file($tmp, UPLOAD_DIR . $nama)) throw new RuntimeException('Gagal menyimpan file.');
                $baru[] = $nama;
                if (isset($docs[$rid])) @unlink(UPLOAD_DIR . $docs[$rid]['nama_file']);
                q($pdo, "INSERT INTO dokumen_pengajuan(id_pengajuan,id_persyaratan,nama_file,tipe_file) VALUES(?,?,?,?)
                  ON DUPLICATE KEY UPDATE nama_file=VALUES(nama_file), tipe_file=VALUES(tipe_file), tanggal_upload=NOW(),
                  status_pemeriksaan='belum', catatan=NULL, id_pemeriksa=NULL, tanggal_periksa=NULL", [$idp, $rid, $nama, $ext]);
            }
            catat($pdo, $idp, 'Menunggu', $ulang ? 'Diajukan ulang oleh mahasiswa' : 'Pengajuan dikirim');
            notif_admins($pdo, $idp, ($ulang ? 'Pengajuan ulang dari ' : 'Pengajuan baru dari ') . $u['nama']);
            $pdo->commit();
            flash('ok', $ulang ? 'Pengajuan ulang berhasil dikirim.' : 'Pengajuan berhasil dikirim.');
            redirect('mahasiswa/detail.php?id=' . $idp);
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            foreach ($baru as $f) @unlink(UPLOAD_DIR . $f);
            $errors[] = 'Terjadi kesalahan saat menyimpan. Coba lagi.';
        }
    }
}
$judul = $ulang ? 'Ajukan Ulang Surat' : 'Ajukan Surat';
require __DIR__ . '/../includes/header.php'; ?>
<h1><?= e($judul) ?></h1>
<?php if ($ulang && $ulang['alasan_penolakan']): ?><div class="alert err"><b>Alasan penolakan:</b> <?= nl2br(e($ulang['alasan_penolakan'])) ?></div><?php endif; ?>
<?php if ($errors): ?><div class="alert err"><ul><?php foreach ($errors as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="card"><?= csrf_field() ?>
<label>Jenis surat</label>
<?php if ($ulang): ?>
<?php foreach ($jenis as $j) { if ($j['id_jenis'] == $idj) { echo '<p><b>' . e($j['nama_jenis']) . '</b></p>'; } } ?>
<?php else: ?>
<select name="id_jenis" id="jenis" required><option value="">-- pilih jenis surat --</option>
<?php foreach ($jenis as $j): ?><option value="<?= $j['id_jenis'] ?>" <?= $idj == $j['id_jenis'] ? 'selected' : '' ?>><?= e($j['nama_jenis']) ?></option><?php endforeach; ?></select><?php endif; ?>
<label>Tujuan surat (ditujukan kepada / untuk apa)</label><input type="text" name="tujuan" value="<?= e($tujuan) ?>" required>
<label>Keperluan</label><textarea name="keperluan" required><?= e($keperluan) ?></textarea>
<h2 style="margin-top:1.25rem">Dokumen persyaratan</h2><p class="muted">PDF, JPG, atau PNG. Maksimal 2 MB per file.</p>
<?php foreach ($syarat as $jid => $list): ?><div class="grp" data-j="<?= $jid ?>" <?= $idj == $jid ? '' : 'hidden' ?>>
<?php foreach ($list as $r): $d = $docs[$r['id_persyaratan']] ?? null; ?>
<label><?= e($r['nama_persyaratan']) ?>
<?php if ($d): ?> <?= $d['status_pemeriksaan'] === 'sesuai' ? '<span class="pill hijau">sesuai</span>' : ($d['status_pemeriksaan'] === 'tidak_sesuai' ? '<span class="pill merah">perlu diganti</span>' : '<span class="pill abu">belum diperiksa</span>') ?><?php endif; ?></label>
<?php if ($d && $d['catatan']): ?><div class="muted">Catatan admin: <?= e($d['catatan']) ?></div><?php endif; ?>
<input type="file" name="dok[<?= $r['id_persyaratan'] ?>]" accept=".pdf,.jpg,.jpeg,.png" <?= $idj == $jid ? '' : 'disabled' ?>>
<?php if ($d && $d['status_pemeriksaan'] !== 'tidak_sesuai'): ?><div class="muted">Kosongkan jika tetap memakai file yang sudah diunggah.</div><?php endif; ?>
<?php endforeach; ?></div><?php endforeach; ?>
<p><button class="btn" type="submit"><?= $ulang ? 'Kirim Ulang' : 'Kirim Pengajuan' ?></button></p></form>
<script>
var sel = document.getElementById('jenis');
if (sel) sel.addEventListener('change', function () {
  document.querySelectorAll('.grp').forEach(function (g) {
    var on = g.getAttribute('data-j') === sel.value; g.hidden = !on;
    g.querySelectorAll('input[type=file]').forEach(function (i) { i.disabled = !on; });
  });
});
</script>
<?php require __DIR__ . '/../includes/footer.php';
