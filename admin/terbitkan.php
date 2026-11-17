<?php require __DIR__ . '/../includes/bootstrap.php';
$u = require_login('admin'); $id = (int)($_GET['id'] ?? 0);
$p = q($pdo, 'SELECT p.*, u.nama, u.nim, u.prodi, u.semester, j.nama_jenis FROM pengajuan p JOIN pengguna u ON u.id_pengguna=p.id_pengguna JOIN jenis_surat j ON j.id_jenis=p.id_jenis WHERE p.id_pengajuan=?', [$id])->fetch();
if (!$p) { http_response_code(404); exit('Pengajuan tidak ditemukan.'); }
if ($p['status'] !== 'Disetujui') { flash('err', 'Surat hanya dapat diterbitkan untuk pengajuan berstatus Disetujui.'); redirect('admin/detail.php?id=' . $id); }
$s = q($pdo, 'SELECT * FROM surat WHERE id_pengajuan=?', [$id])->fetch();   // sudah terbit = mode ubah
$nomor = trim($_POST['nomor_surat'] ?? ($s['nomor_surat'] ?? ''));
$tanggal = $_POST['tanggal_terbit'] ?? ($s['tanggal_terbit'] ?? date('Y-m-d'));
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if ($nomor === '' || mb_strlen($nomor) > 60) $errors[] = 'Nomor surat wajib diisi (maksimal 60 karakter).';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) || !strtotime($tanggal)) $errors[] = 'Tanggal terbit tidak valid.';
    if (!$errors && q($pdo, 'SELECT 1 FROM surat WHERE nomor_surat=? AND id_pengajuan<>?', [$nomor, $id])->fetch()) $errors[] = 'Nomor surat sudah dipakai surat lain.';
    $ada = isset($_FILES['file_surat']) && $_FILES['file_surat']['error'] !== UPLOAD_ERR_NO_FILE;
    $f = $ada ? $_FILES['file_surat'] : null;
    if ($ada) {
        if ($f['error'] !== UPLOAD_ERR_OK) $errors[] = 'File gagal diunggah (kemungkinan melebihi batas ukuran server).';
        elseif ($f['size'] > 5 * 1024 * 1024) $errors[] = 'Ukuran file surat maksimal 5 MB.';
        elseif (strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)) !== 'pdf' || (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) !== 'application/pdf') $errors[] = 'File surat harus berformat PDF.';
    } elseif (!$s || !$s['file_pdf']) $errors[] = 'File surat (PDF) wajib diunggah.';
    if (!$errors) {
        $baru = null;
        try {
            if ($ada) {
                $baru = bin2hex(random_bytes(16)) . '.pdf';
                if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . $baru)) throw new RuntimeException('Gagal menyimpan file.');
            }
            $pdo->beginTransaction();
            if ($s) {
                q($pdo, 'UPDATE surat SET nomor_surat=?, tanggal_terbit=?, file_pdf=COALESCE(?, file_pdf) WHERE id_pengajuan=?', [$nomor, $tanggal, $baru, $id]);
                catat($pdo, $id, 'Disetujui', 'Surat diperbarui, nomor ' . $nomor, $u['id']);
            } else {
                q($pdo, 'INSERT INTO surat(id_pengajuan,nomor_surat,tanggal_terbit,file_pdf) VALUES(?,?,?,?)', [$id, $nomor, $tanggal, $baru]);
                catat($pdo, $id, 'Disetujui', 'Surat diterbitkan, nomor ' . $nomor, $u['id']);
                notif($pdo, $p['id_pengguna'], $id, 'Surat Anda telah terbit (' . $nomor . '). Silakan unduh.');
            }
            $pdo->commit();
            if ($baru && $s && $s['file_pdf']) @unlink(UPLOAD_DIR . basename($s['file_pdf']));
            flash('ok', $s ? 'Surat diperbarui.' : 'Surat diterbitkan dengan nomor ' . $nomor . '.');
            redirect('admin/detail.php?id=' . $id);
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if ($baru) @unlink(UPLOAD_DIR . $baru);
            $errors[] = 'Gagal menyimpan. Coba lagi.';
        }
    }
}
$judul = $s ? 'Ubah Surat Terbit' : 'Terbitkan Surat'; require __DIR__ . '/../includes/header.php'; ?>
<h1><?= e($judul) ?></h1>
<?php if ($errors): ?><div class="alert err"><ul><?php foreach ($errors as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="card"><h2>Data untuk pembuatan surat</h2><dl class="kv"><dt>Nama</dt><dd><?= e($p['nama']) ?></dd><dt>NIM</dt><dd><?= e($p['nim']) ?></dd>
<dt>Prodi / Semester</dt><dd><?= e($p['prodi']) ?> / <?= e($p['semester']) ?></dd><dt>Jenis surat</dt><dd><?= e($p['nama_jenis']) ?></dd><dt>Tujuan</dt><dd><?= e($p['tujuan']) ?></dd><dt>Keperluan</dt><dd><?= nl2br(e($p['keperluan'])) ?></dd></dl>
<p class="muted">Buat surat di luar sistem (mis. Word), tanda tangani dan stempel, simpan sebagai PDF, lalu unggah di bawah ini.</p></div>
<form method="post" enctype="multipart/form-data" class="card"><?= csrf_field() ?>
<label>Nomor surat (sesuai yang tertulis pada surat)</label><input type="text" name="nomor_surat" value="<?= e($nomor) ?>" required maxlength="60">
<label>Tanggal terbit</label><input type="date" name="tanggal_terbit" value="<?= e($tanggal) ?>" required>
<label>File surat (PDF, maksimal 5 MB)<?= $s ? ' - kosongkan jika tidak diganti' : '' ?></label><input type="file" name="file_surat" accept=".pdf,application/pdf" <?= $s ? '' : 'required' ?>>
<?php if ($s && $s['file_pdf']): ?><p class="muted">File saat ini: <a href="<?= url('surat_cetak.php?id=' . $id) ?>" target="_blank">lihat surat</a></p><?php endif; ?>
<p><button class="btn" type="submit"><?= $s ? 'Simpan Perubahan' : 'Terbitkan' ?></button> <a class="btn sec" href="<?= url('admin/detail.php?id=' . $id) ?>">Batal</a></p></form>
<?php require __DIR__ . '/../includes/footer.php';
