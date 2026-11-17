<?php
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function url($p = '') { return BASE_URL . '/' . ltrim($p, '/'); }
function redirect($p) { header('Location: ' . url($p)); exit; }
function user() { return $_SESSION['user'] ?? null; }
function q($pdo, $sql, $params = []) { $s = $pdo->prepare($sql); $s->execute($params); return $s; }

function csrf_token() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field() { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check() {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419);
        exit('Sesi formulir tidak valid. Silakan muat ulang halaman.');
    }
}
function flash($type, $msg) { $_SESSION['flash'] = [$type, $msg]; }

function require_login($role = null) {
    $u = user();
    if (!$u) redirect('login.php');
    if ($role && $u['role'] !== $role) { http_response_code(403); exit('Akses ditolak.'); }
    return $u;
}

function pill($s) {
    $c = ['Menunggu' => 'kuning', 'Diproses' => 'biru', 'Disetujui' => 'hijau', 'Ditolak' => 'merah'][$s] ?? 'abu';
    return '<span class="pill ' . $c . '">' . e($s) . '</span>';
}
function tgl($d, $jam = false) {
    if (!$d) return '-';
    $b = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $t = strtotime($d);
    return date('j', $t) . ' ' . $b[(int)date('n', $t)] . ' ' . date('Y', $t) . ($jam ? date(', H:i', $t) : '');
}
function romawi($n) { return ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$n]; }
function progres($status, $terbit) {
    if ($status === 'Menunggu') return 25;
    if ($status === 'Diproses') return 50;
    if ($status === 'Disetujui') return $terbit ? 100 : 75;
    return 0;
}
function valid_password($p) { return strlen($p) >= 8 && preg_match('/[A-Za-z]/', $p) && preg_match('/\d/', $p); }

function notif($pdo, $uid, $idp, $pesan) {
    q($pdo, 'INSERT INTO notifikasi(id_pengguna,id_pengajuan,pesan) VALUES(?,?,?)', [$uid, $idp, $pesan]);
}
function notif_admins($pdo, $idp, $pesan) {
    foreach ($pdo->query("SELECT id_pengguna FROM pengguna WHERE role='admin'")->fetchAll() as $a) notif($pdo, $a['id_pengguna'], $idp, $pesan);
}
function catat($pdo, $idp, $status, $catatan = null, $admin = null) {
    q($pdo, 'INSERT INTO riwayat_status(id_pengajuan,status,catatan,id_admin) VALUES(?,?,?,?)', [$idp, $status, $catatan, $admin]);
}
function unread($pdo) {
    return (int)q($pdo, 'SELECT COUNT(*) FROM notifikasi WHERE id_pengguna=? AND sudah_dibaca=0', [user()['id']])->fetchColumn();
}

// Validasi unggahan: ukuran, ekstensi, dan isi file (MIME) sebenarnya
function cek_upload($err, $size, $tmp, $name) {
    if ($err !== UPLOAD_ERR_OK) return [false, 'Gagal mengunggah file.'];
    if ($size > MAX_UPLOAD) return [false, 'Ukuran file maksimal 2 MB.'];
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $mimes = ['pdf' => ['application/pdf'], 'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png']];
    if (!isset($mimes[$ext])) return [false, 'Format file harus PDF, JPG, atau PNG.'];
    $m = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    if (!in_array($m, $mimes[$ext], true)) return [false, 'Isi file tidak sesuai dengan formatnya.'];
    return [true, $ext];
}
// Cek apakah semua dokumen persyaratan sudah ada dan berstatus sesuai
function semua_sesuai($pdo, $idp, $idj) {
    $total = (int)q($pdo, 'SELECT COUNT(*) FROM persyaratan WHERE id_jenis=?', [$idj])->fetchColumn();
    $ok = (int)q($pdo, "SELECT COUNT(*) FROM dokumen_pengajuan WHERE id_pengajuan=? AND status_pemeriksaan='sesuai'", [$idp])->fetchColumn();
    return $total > 0 && $ok === $total;
}
