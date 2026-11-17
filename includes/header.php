<?php
$u = user();
$isAdmin = $u['role'] === 'admin';
$script = ltrim(substr($_SERVER['SCRIPT_NAME'], strlen(BASE_URL)), '/');
$key = $script . (($script === 'admin/pengajuan.php' && isset($_GET['status'])) ? '?status=' . $_GET['status'] : '');
$menu = $isAdmin ? [
    ['Dashboard', 'admin/dashboard.php'], ['Kelola Pengajuan', 'admin/pengajuan.php'],
    ['Surat Diproses', 'admin/pengajuan.php?status=Diproses'], ['Surat Disetujui', 'admin/pengajuan.php?status=Disetujui'],
    ['Surat Ditolak', 'admin/pengajuan.php?status=Ditolak'], ['Data Mahasiswa', 'admin/mahasiswa.php'],
    ['Laporan', 'admin/laporan.php'], ['Notifikasi', 'notifikasi.php'], ['Profil', 'profil.php'],
] : [
    ['Dashboard', 'mahasiswa/dashboard.php'], ['Ajukan Surat', 'mahasiswa/ajukan.php'],
    ['Riwayat Pengajuan', 'mahasiswa/riwayat.php'], ['Notifikasi', 'notifikasi.php'], ['Profil', 'profil.php'],
];
$nu = unread($pdo);
?><!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($judul ?? 'Beranda') ?> - <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>"></head><body>
<div class="layout">
<aside class="sidebar">
  <div class="brand"><?= e(APP_NAME) ?></div>
  <nav><?php foreach ($menu as [$lbl, $href]): ?>
    <a href="<?= url($href) ?>" class="<?= $key === $href ? 'on' : '' ?>"><?= e($lbl) ?></a>
  <?php endforeach; ?></nav>
  <div class="usercard"><strong><?= e($u['nama']) ?></strong><br><span style="opacity:.8"><?= $isAdmin ? 'Admin / Staf Akademik' : 'Mahasiswa' ?></span>
    <form method="post" action="<?= url('logout.php') ?>" data-confirm="Yakin ingin keluar?"><?= csrf_field() ?><button class="btn sec sm" type="submit">Keluar</button></form>
  </div>
</aside>
<main class="main">
  <div class="topbar"><button class="btn sec sm burger" type="button">&#9776; Menu</button><span></span>
    <a class="bell" href="<?= url('notifikasi.php') ?>" title="Notifikasi">&#128276;<?php if ($nu): ?><b><?= $nu ?></b><?php endif; ?></a></div>
  <?php if (!empty($_SESSION['flash'])): [$ft, $fm] = $_SESSION['flash']; unset($_SESSION['flash']); ?>
    <div class="alert <?= e($ft) ?>"><?= e($fm) ?></div><?php endif; ?>
