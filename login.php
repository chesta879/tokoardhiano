<?php
require __DIR__.'/../config.php'; $err = '';
$kosong = !$db->query('SELECT 1 FROM tbl_pengguna LIMIT 1')->fetch();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  cek_csrf(); $un = trim($_POST['username'] ?? ''); $pw = $_POST['password'] ?? '';
  if ($kosong) {
    if ($un === '' || strlen($pw) < 6) $err = 'Isi username dan kata sandi minimal 6 karakter.';
    else { $db->prepare("INSERT INTO tbl_pengguna(nama,username,password_hash,role) VALUES(?,?,?,'admin')")->execute([trim($_POST['nama'] ?? '') ?: $un, $un, password_hash($pw, PASSWORD_DEFAULT)]); flash('Admin pertama dibuat. Silakan masuk.'); pindah('login.php'); }
  } else {
    $st = $db->prepare('SELECT * FROM tbl_pengguna WHERE username=? AND aktif=1'); $st->execute([$un]); $u = $st->fetch();
    if ($u && password_verify($pw, $u['password_hash'])) { session_regenerate_id(true); $_SESSION['admin'] = ['id' => $u['id_pengguna'], 'nama' => $u['nama'], 'role' => $u['role']]; pindah('index.php'); }
    else $err = 'Username atau kata sandi salah.';
  }
}
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= $kosong ? 'Buat admin pertama' : 'Masuk staf' ?> · <?= e(NAMA_TOKO) ?></title><link rel="stylesheet" href="../assets/style.css"></head><body class="admin login-page">
<main class="login-shell">
  <a class="login-brand" href="../index.php"><span class="login-brand-mark" aria-hidden="true">TK</span><span>Toko<b>Kita</b></span></a>
  <section class="login-panel" aria-labelledby="login-title">
    <div class="login-intro"><span class="login-eyebrow">PORTAL STAF</span>
      <h1 id="login-title"><?= $kosong ? 'Siapkan akun admin' : 'Selamat datang kembali' ?></h1>
      <p><?= $kosong ? 'Buat akun admin pertama untuk mulai mengelola toko.' : 'Masuk untuk mengelola pesanan dan produk toko.' ?></p>
    </div>
    <?php if ($f = flash()): ?><div class="info"><?= e($f) ?></div><?php endif; if ($err): ?><div class="info salah" role="alert"><?= e($err) ?></div><?php endif; ?>
    <form method="post" class="form login-form"><?= csrf_field() ?>
      <?php if ($kosong): ?><label>Nama<input name="nama" autocomplete="name"></label><?php endif; ?>
      <label>Username<input name="username" autocomplete="username" required></label>
      <label>Kata sandi<input type="password" name="password" autocomplete="<?= $kosong ? 'new-password' : 'current-password' ?>" required></label>
      <button class="btn besar"><?= $kosong ? 'Buat admin' : 'Masuk ke panel staf' ?></button>
    </form>
    <a class="login-back" href="../index.php"><span aria-hidden="true">←</span> Kembali ke toko</a>
  </section>
  <p class="login-foot">Akses khusus untuk pengelola dan staf <?= e(NAMA_TOKO) ?>.</p>
</main></body></html>
