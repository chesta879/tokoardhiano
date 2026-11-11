<?php
require __DIR__.'/_auth.php';

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  cek_csrf();
  $aksi = $_POST['aksi'] ?? '';
  try {
    if ($aksi === 'tambah') {
      $nama = trim($_POST['nama'] ?? '');
      $username = trim($_POST['username'] ?? '');
      $password = $_POST['password'] ?? '';
      if ($nama === '' || $username === '' || strlen($password) < 6) {
        throw new Exception('Nama dan username wajib diisi. Kata sandi minimal 6 karakter.');
      }
      $st = $db->prepare("INSERT INTO tbl_pengguna(nama,username,password_hash,role) VALUES(?,?,?,'kasir')");
      $st->execute([$nama, $username, password_hash($password, PASSWORD_DEFAULT)]);
      flash('Akun kasir berhasil dibuat.');
    } elseif ($aksi === 'status') {
      $id = (int)($_POST['id'] ?? 0);
      $st = $db->prepare("UPDATE tbl_pengguna SET aktif=IF(aktif=1,0,1) WHERE id_pengguna=? AND role='kasir'");
      $st->execute([$id]);
      flash($st->rowCount() ? 'Status akun kasir diperbarui.' : 'Akun kasir tidak ditemukan.');
    }
  } catch (PDOException $ex) {
    $err = $ex->getCode() === '23000' ? 'Username tersebut sudah digunakan.' : 'Akun staf gagal disimpan.';
  } catch (Exception $ex) { $err = $ex->getMessage(); }
  if (!$err) pindah('pengguna.php');
}
$rows = $db->query('SELECT id_pengguna,nama,username,role,aktif,dibuat_pada FROM tbl_pengguna ORDER BY role,nama')->fetchAll();
admin_head('Pengguna staf');
?>
<h2 class="judul">Pengguna staf</h2>
<p>Kelola akun kasir yang dapat digunakan untuk masuk ke bagian staf.</p>
<?php if ($err): ?><div class="info salah"><?= e($err) ?></div><?php endif; ?>
<form method="post" class="form sempit"><?= csrf_field() ?><input type="hidden" name="aksi" value="tambah">
  <h3>Tambah akun kasir</h3>
  <label>Nama staf<input name="nama" required value="<?= e($_POST['nama'] ?? '') ?>"></label>
  <label>Username<input name="username" required value="<?= e($_POST['username'] ?? '') ?>"></label>
  <label>Kata sandi (minimal 6 karakter)<input type="password" name="password" minlength="6" required></label>
  <button class="btn besar">Buat akun kasir</button>
</form>
<h2 class="judul">Daftar akun</h2>
<div class="scroll"><table class="tabel"><thead><tr><th>Nama</th><th>Username</th><th>Peran</th><th>Status</th><th>Dibuat</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['nama']) ?></td><td><?= e($r['username']) ?></td><td><?= $r['role'] === 'admin' ? 'Admin' : 'Kasir' ?></td><td><?= $r['aktif'] ? 'Aktif' : 'Nonaktif' ?></td><td><?= e($r['dibuat_pada']) ?></td><td><?php if ($r['role'] === 'kasir'): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="status"><input type="hidden" name="id" value="<?= (int)$r['id_pengguna'] ?>"><button class="btn abu"><?= $r['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?></button></form><?php endif; ?></td></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="6">Belum ada akun staf.</td></tr><?php endif; ?></tbody></table></div>
</main></div><footer class="foot"><div class="wrap"><strong>Toko Kita</strong><span>Panel pengelolaan toko</span></div></footer></body></html>
