<?php require __DIR__ . '/includes/bootstrap.php';
$u = require_login(); $judul = 'Notifikasi';
$dest = $u['role'] === 'admin' ? 'admin/detail.php?id=' : 'mahasiswa/detail.php?id=';
if (isset($_GET['buka'])) {
    $n = q($pdo, 'SELECT * FROM notifikasi WHERE id_notifikasi=? AND id_pengguna=?', [(int)$_GET['buka'], $u['id']])->fetch();
    if ($n) { q($pdo, 'UPDATE notifikasi SET sudah_dibaca=1 WHERE id_notifikasi=?', [$n['id_notifikasi']]); if ($n['id_pengajuan']) redirect($dest . $n['id_pengajuan']); }
    redirect('notifikasi.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') { csrf_check(); q($pdo, 'UPDATE notifikasi SET sudah_dibaca=1 WHERE id_pengguna=?', [$u['id']]); redirect('notifikasi.php'); }
$rows = q($pdo, 'SELECT * FROM notifikasi WHERE id_pengguna=? ORDER BY waktu DESC LIMIT 100', [$u['id']])->fetchAll();
require __DIR__ . '/includes/header.php'; ?>
<h1>Notifikasi</h1>
<form method="post"><?= csrf_field() ?><button class="btn sec sm" type="submit">Tandai semua dibaca</button></form><p></p>
<div class="card tablewrap"><table><?php foreach ($rows as $n): ?><tr class="<?= $n['sudah_dibaca'] ? '' : 'unread' ?>"><td><a href="<?= url('notifikasi.php?buka=' . $n['id_notifikasi']) ?>"><?= e($n['pesan']) ?></a></td><td class="muted"><?= tgl($n['waktu'], true) ?></td></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td class="muted">Belum ada notifikasi.</td></tr><?php endif; ?></table></div>
<?php require __DIR__ . '/includes/footer.php';
