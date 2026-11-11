<?php
require __DIR__.'/_auth.php';
function hapus_gambar_produk($gambar) {
  if (!preg_match('/\Auploads\/produk\/[a-f0-9]{32}\.(?:jpg|png|webp)\z/', (string)$gambar)) return;
  $path = dirname(__DIR__).'/'.$gambar;
  if (is_file($path) && !unlink($path)) error_log('Gagal menghapus gambar produk: '.$path);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  cek_csrf(); $a = $_POST['aksi'] ?? '';
  try {
    if ($a === 'hapus') {
      $st = $db->prepare('SELECT gambar FROM tbl_produk WHERE id_produk=?'); $st->execute([(int)($_POST['id'] ?? 0)]); $gambar = $st->fetchColumn();
      $db->prepare('DELETE FROM tbl_produk WHERE id_produk=?')->execute([(int)($_POST['id'] ?? 0)]);
      hapus_gambar_produk($gambar); flash('Produk dihapus.');
    }
    elseif ($a === 'simpan') {
      $id = (int)($_POST['id'] ?? 0);
      $st = $db->prepare('SELECT gambar FROM tbl_produk WHERE id_produk=?'); $st->execute([$id]); $gambar_lama = $id ? $st->fetchColumn() : null;
      $gambar = trim($_POST['gambar'] ?? '') ?: null;
      $file = $_FILES['foto'] ?? null;
      $nama_file = null;
      if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        if ($file['error'] !== UPLOAD_ERR_OK) throw new Exception('Unggahan foto gagal. Pastikan ukuran file tidak melebihi 5 MB.');
        if ($file['size'] > 5 * 1024 * 1024 || !is_uploaded_file($file['tmp_name'])) throw new Exception('Foto tidak valid atau ukurannya melebihi 5 MB.');
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $image = getimagesize($file['tmp_name']);
        $format = [
          'image/jpeg' => ['extension' => '.jpg', 'type' => IMAGETYPE_JPEG],
          'image/png' => ['extension' => '.png', 'type' => IMAGETYPE_PNG],
          'image/webp' => ['extension' => '.webp', 'type' => IMAGETYPE_WEBP]
        ];
        if (!$image || !isset($format[$mime]) || $image[2] !== $format[$mime]['type']) throw new Exception('Foto harus berupa gambar JPG, PNG, atau WebP yang valid.');
        $direktori = dirname(__DIR__).'/uploads/produk';
        if (!is_dir($direktori) && !mkdir($direktori, 0755, true) && !is_dir($direktori)) throw new Exception('Folder penyimpanan foto produk tidak dapat dibuat.');
        $nama_file = bin2hex(random_bytes(16)).$format[$mime]['extension'];
        if (!move_uploaded_file($file['tmp_name'], $direktori.'/'.$nama_file)) throw new Exception('Foto gagal disimpan. Periksa izin folder uploads.');
        $gambar = 'uploads/produk/'.$nama_file;
      }
      $d = [trim($_POST['nama'] ?? ''), (int)($_POST['kategori'] ?? 0), max(0, (float)($_POST['harga'] ?? 0)), max(0, (int)($_POST['stok'] ?? 0)), trim($_POST['deskripsi'] ?? ''), $gambar];
      if ($d[0] === '') throw new Exception('Nama produk wajib diisi.');
      if ($id) {
        $db->prepare('UPDATE tbl_produk SET nama_produk=?,id_kategori=?,harga=?,stok=?,deskripsi=?,gambar=? WHERE id_produk=?')->execute(array_merge($d, [$id]));
        if ($gambar_lama !== $gambar) hapus_gambar_produk($gambar_lama);
        flash('Produk diperbarui.');
      } else {
        $db->prepare('INSERT INTO tbl_produk(nama_produk,id_kategori,harga,stok,deskripsi,gambar) VALUES(?,?,?,?,?,?)')->execute($d);
        flash('Produk ditambahkan.');
      }
    }
  } catch (Exception $ex) {
    if (!empty($nama_file)) {
      $path = dirname(__DIR__).'/uploads/produk/'.$nama_file;
      if (is_file($path)) unlink($path);
    }
    flash($a === 'hapus' ? 'Produk tidak bisa dihapus karena sudah pernah terjual. Set stoknya ke 0 saja.' : $ex->getMessage());
  }
  pindah('produk.php');
}
$kategori = $db->query('SELECT * FROM tbl_kategori ORDER BY nama_kategori')->fetchAll();
$edit = null; if (!empty($_GET['edit'])) { $st = $db->prepare('SELECT * FROM tbl_produk WHERE id_produk=?'); $st->execute([(int)$_GET['edit']]); $edit = $st->fetch(); }
$rows = $db->query('SELECT p.*, k.nama_kategori FROM tbl_produk p JOIN tbl_kategori k USING(id_kategori) ORDER BY p.id_produk DESC')->fetchAll();
admin_head('Produk');
?>
<h2 class="judul"><?= $edit ? 'Ubah produk' : 'Tambah produk' ?></h2>
<form method="post" enctype="multipart/form-data" class="form product-edit-form"><?= csrf_field() ?><input type="hidden" name="aksi" value="simpan"><input type="hidden" name="id" value="<?= $edit['id_produk'] ?? '' ?>">
<label>Nama produk<input name="nama" value="<?= e($edit['nama_produk'] ?? '') ?>" required></label>
<label>Kategori<select name="kategori"><?php foreach ($kategori as $k) echo '<option value="'.$k['id_kategori'].'"'.(($edit['id_kategori'] ?? 0) == $k['id_kategori'] ? ' selected' : '').'>'.e($k['nama_kategori']).'</option>'; ?></select></label>
<label>Harga (Rp)<input type="number" name="harga" min="0" value="<?= e($edit['harga'] ?? 0) ?>" required></label>
<label>Stok<input type="number" name="stok" min="0" value="<?= e($edit['stok'] ?? 0) ?>" required></label>
<label>Foto produk (JPG, PNG, atau WebP; maks. 5 MB)<input type="file" name="foto" accept="image/jpeg,image/png,image/webp"></label>
<label>Alamat gambar (URL, opsional)<input name="gambar" value="<?= e($edit['gambar'] ?? '') ?>"><small>Jika foto diunggah, foto tersebut akan menggantikan gambar URL.</small></label>
<label>Deskripsi<textarea name="deskripsi" rows="3"><?= e($edit['deskripsi'] ?? '') ?></textarea></label>
<div class="baris"><button class="btn besar">Simpan produk</button><?php if ($edit) echo '<a class="btn abu" href="produk.php">Batal</a>'; ?></div></form>
<h2 class="judul">Daftar produk</h2>
<div class="scroll"><table class="tabel"><thead><tr><th>Foto</th><th>Produk</th><th>Kategori</th><th>Harga</th><th>Stok</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?php if ($r['gambar']): ?><img class="admin-product-thumb" src="<?= e(str_starts_with($r['gambar'], 'uploads/') ? '../'.$r['gambar'] : $r['gambar']) ?>" alt="<?= e($r['nama_produk']) ?>"><?php else: ?>Belum ada<?php endif; ?></td><td><?= e($r['nama_produk']) ?></td><td><?= e($r['nama_kategori']) ?></td><td><?= rupiah($r['harga']) ?></td><td><?= (int)$r['stok'] ?></td>
<td class="baris"><a class="btn abu" href="?edit=<?= $r['id_produk'] ?>">Ubah</a><form method="post" onsubmit="return confirm('Hapus produk ini?')"><?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= $r['id_produk'] ?>"><button class="btn abu">Hapus</button></form></td></tr><?php endforeach; ?>
</tbody></table></div></main></div><footer class="foot"><div class="wrap"><strong>Toko Kita</strong><span>Panel pengelolaan toko</span></div></footer></body></html>
