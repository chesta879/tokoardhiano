<?php
$kategori = $db->query('SELECT * FROM tbl_kategori ORDER BY nama_kategori')->fetchAll();
$kat_aktif = (int)($_GET['kat'] ?? 0);
$p = pelanggan();
$halaman_aktif = basename($_SERVER['PHP_SELF'] ?? 'index.php');
?>
<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e(($judul ?? 'Belanja kebutuhan harian').' · '.NAMA_TOKO) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css"></head><body>
<div class="topbar"><div class="wrap topbar-in">
  <span class="topbar-note"><span class="topbar-dot"></span>Belanja kebutuhan harian, diantar sampai rumah.</span>
  <nav class="akun" aria-label="Akun">
  <?php if ($p): ?><span class="sapaan">Halo, <?= e($p['nama']) ?></span><a class="<?= $halaman_aktif === 'pesanan.php' ? 'current' : '' ?>" href="pesanan.php">Pesanan saya</a><a href="keluar.php">Keluar</a>
  <?php else: ?><a class="<?= $halaman_aktif === 'masuk.php' ? 'current' : '' ?>" href="masuk.php">Masuk</a><a class="akun-daftar <?= $halaman_aktif === 'daftar.php' ? 'current' : '' ?>" href="daftar.php">Daftar</a><?php endif; ?><a class="akun-toko <?= $halaman_aktif === 'index.php' ? 'current' : '' ?>" href="index.php">Lihat toko</a><?php if (!empty($_SESSION['admin'])): ?><a class="akun-staf" href="admin/index.php">Panel staf (<?= e($_SESSION['admin']['nama']) ?>)</a><?php else: ?><a class="akun-staf" href="admin/login.php">Panel staf</a><?php endif; ?></nav>
</div></div>
<header class="head"><div class="wrap head-in">
  <a class="logo" href="index.php"><span class="logo-mark" aria-hidden="true">TK</span><span class="logo-copy"><span>Toko<b>Kita</b></span><small>Belanja harian lebih mudah</small></span></a>
  <form class="cari" action="index.php" role="search"><input name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Cari beras, minyak, sabun..." aria-label="Cari produk"><button>Cari</button></form>
  <a class="keranjang" href="keranjang.php"><span>Keranjang</span><span class="badge"><?= jumlah_keranjang() ?></span></a>
</div>
 </header>
<div class="page-layout <?= !empty($auth_page) ? 'auth-layout' : '' ?>">
<aside class="side-nav" aria-label="Navigasi toko">
  <strong>Menu toko</strong>
  <a href="index.php" class="<?= $halaman_aktif === 'index.php' && !$kat_aktif ? 'on' : '' ?>">Semua produk</a>
  <?php if ($p): ?><a href="pesanan.php" class="<?= $halaman_aktif === 'pesanan.php' ? 'on' : '' ?>">Pesanan saya</a><?php endif; ?>
  <a href="keranjang.php" class="<?= $halaman_aktif === 'keranjang.php' ? 'on' : '' ?>">Keranjang <span class="badge"><?= jumlah_keranjang() ?></span></a>
  <hr><strong>Kategori</strong>
  <?php foreach ($kategori as $k): ?><a href="index.php?kat=<?= $k['id_kategori'] ?>" class="<?= $halaman_aktif === 'index.php' && $kat_aktif == $k['id_kategori'] ? 'on' : '' ?>"><?= e($k['nama_kategori']) ?></a><?php endforeach; ?>
</aside>
<main class="wrap">
<?php if ($f = flash()): ?><div class="info"><?= e($f) ?></div><?php endif; ?>
