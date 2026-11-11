<?php
require __DIR__.'/../config.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
if (empty($_SESSION['admin'])) pindah('login.php');
function admin_head($t) { $page = basename($_SERVER['PHP_SELF'] ?? 'index.php'); ?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e($t) ?> · <?= e(NAMA_TOKO) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/style.css"></head><body class="admin">
<div class="topbar"><div class="wrap topbar-in"><span class="topbar-note"><span class="topbar-dot"></span>Panel staf · <?= e(NAMA_TOKO) ?></span><nav class="akun" aria-label="Akun staf"><a href="../index.php">Lihat toko</a><a href="logout.php">Keluar (<?= e($_SESSION['admin']['nama']) ?>)</a></nav></div></div>
<header class="admin-head"><div class="head-in">
<a class="logo" href="index.php"><span class="logo-mark" aria-hidden="true">TK</span><span class="logo-copy"><span>Toko<b>Kita</b></span><small>Panel staf</small></span></a>
<div class="admin-context"><span>AREA STAF</span><strong><?= e($t) ?></strong></div>
<a class="btn besar admin-store-link" href="../index.php">Lihat toko</a>
</div></header>
<div class="admin-layout"><aside class="admin-side-nav" aria-label="Navigasi staf">
<strong>Menu staf</strong>
<a class="<?= $page === 'index.php' ? 'current' : '' ?>" href="index.php">Pesanan</a>
<a class="<?= $page === 'produk.php' ? 'current' : '' ?>" href="produk.php">Produk</a>
<a class="<?= $page === 'pengguna.php' ? 'current' : '' ?>" href="pengguna.php">Pengguna staf</a>
<hr><a href="../index.php">Lihat toko</a><a href="logout.php">Keluar akun</a>
</aside><main class="admin-main wrap">
<?php if ($f = flash()): ?><div class="info"><?= e($f) ?></div><?php endif; }
