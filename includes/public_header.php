<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($judul ?? APP_NAME) ?> - <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>"></head><body>
<div class="pub-top"><a class="brand" href="<?= url('index.php') ?>"><?= e(APP_NAME) ?></a>
<span><?php if (user()): ?><a class="btn sm" href="<?= url(user()['role'] === 'admin' ? 'admin/dashboard.php' : 'mahasiswa/dashboard.php') ?>">Dashboard</a>
<?php else: ?><a class="btn sec sm" href="<?= url('login.php') ?>">Masuk</a> <a class="btn sm" href="<?= url('register.php') ?>">Daftar</a><?php endif; ?></span></div>
