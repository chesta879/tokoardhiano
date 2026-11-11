<?php
require __DIR__.'/_auth.php';
$stat = ['Diproses', 'Dikirim', 'Selesai', 'Dibatalkan'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  cek_csrf(); $kode = $_POST['kode'] ?? ''; $aksi = $_POST['aksi'] ?? 'status';
  if ($aksi === 'pembayaran') {
    $baru = $_POST['status_pembayaran'] ?? '';
    if (in_array($baru, ['Terverifikasi', 'Ditolak'], true)) {
      try {
        $db->beginTransaction();
        $st = $db->prepare('SELECT status_pembayaran,bukti_pembayaran FROM tbl_pesanan WHERE kode_pesanan=? FOR UPDATE');
        $st->execute([$kode]); $pesanan = $st->fetch();
        if ($pesanan && $pesanan['status_pembayaran'] === 'Menunggu verifikasi' && $pesanan['bukti_pembayaran']) {
          $db->prepare('UPDATE tbl_pesanan SET status_pembayaran=? WHERE kode_pesanan=?')->execute([$baru, $kode]);
          $db->commit(); flash("Pembayaran $kode diubah menjadi $baru.");
        } else {
          $db->rollBack(); flash('Bukti pembayaran tidak ditemukan atau sudah diproses.');
        }
      } catch (Exception $ex) { if ($db->inTransaction()) $db->rollBack(); flash('Gagal memverifikasi pembayaran: '.$ex->getMessage()); }
    } else flash('Status pembayaran tidak valid.');
    pindah('index.php');
  }
  $baru = $_POST['status'] ?? '';
  if (in_array($baru, $stat)) {
    try {
      $db->beginTransaction();
      $st = $db->prepare('SELECT status FROM tbl_pesanan WHERE kode_pesanan=? FOR UPDATE'); $st->execute([$kode]); $lama = $st->fetchColumn();
      if ($lama !== false && $lama !== $baru) {
        $sql = 'UPDATE tbl_produk p JOIN tbl_detail_transaksi d ON d.id_produk=p.id_produk JOIN tbl_transaksi t ON t.id_transaksi=d.id_transaksi SET p.stok=p.stok%s d.jumlah WHERE t.kode_pesanan=?';
        if ($baru === 'Dibatalkan') $db->prepare(sprintf($sql, '+'))->execute([$kode]);
        if ($lama === 'Dibatalkan') $db->prepare(sprintf($sql, '-'))->execute([$kode]);
        $db->prepare('UPDATE tbl_pesanan SET status=? WHERE kode_pesanan=?')->execute([$baru, $kode]);
      }
      $db->commit(); flash("Status $kode diubah menjadi $baru.");
    } catch (Exception $ex) { $db->rollBack(); flash('Gagal mengubah status: '.$ex->getMessage()); }
  }
  pindah('index.php');
}
$ringkas = $db->query("SELECT (SELECT COUNT(*) FROM tbl_pesanan) pesanan, (SELECT COUNT(*) FROM tbl_pesanan WHERE status='Diproses') proses, (SELECT COUNT(*) FROM tbl_pelanggan) pelanggan, (SELECT COALESCE(SUM(t.total_belanja),0) FROM tbl_transaksi t LEFT JOIN tbl_pesanan o ON o.kode_pesanan=t.kode_pesanan WHERE o.status IS NULL OR o.status<>'Dibatalkan') omzet")->fetch();
$st = $db->query("SELECT t.tanggal, SUM(t.total_belanja) omzet FROM tbl_transaksi t LEFT JOIN tbl_pesanan o ON o.kode_pesanan=t.kode_pesanan WHERE t.tanggal >= CURDATE() - INTERVAL 6 DAY AND (o.status IS NULL OR o.status <> 'Dibatalkan') GROUP BY t.tanggal ORDER BY t.tanggal");
$omzet_harian = []; foreach ($st as $r) $omzet_harian[$r['tanggal']] = (float)$r['omzet'];
$grafik = []; for ($i = 6; $i >= 0; $i--) { $tanggal = date('Y-m-d', strtotime("-$i days")); $grafik[] = ['tanggal' => $tanggal, 'omzet' => $omzet_harian[$tanggal] ?? 0]; }
$puncak = max(1, ...array_column($grafik, 'omzet'));
$rows = $db->query('SELECT o.*, p.nama_pelanggan, (SELECT COALESCE(SUM(total_belanja),0) FROM tbl_transaksi WHERE kode_pesanan=o.kode_pesanan) total FROM tbl_pesanan o JOIN tbl_pelanggan p USING(id_pelanggan) ORDER BY o.dibuat_pada DESC LIMIT 100')->fetchAll();
admin_head('Pesanan');
?>
<div class="statistik"><div><b><?= $ringkas['pesanan'] ?></b>Pesanan</div><div><b><?= $ringkas['proses'] ?></b>Perlu diproses</div><div><b><?= $ringkas['pelanggan'] ?></b>Pelanggan</div><div><b><?= rupiah($ringkas['omzet']) ?></b>Omzet</div></div>
<section class="laporan-card" aria-labelledby="judul-grafik"><div class="laporan-head"><div><small>RINGKASAN PENJUALAN</small><h2 id="judul-grafik">Omzet 7 hari terakhir</h2></div><span>Termasuk transaksi selesai dan penjualan kasir</span></div>
<div class="grafik-scroll"><div class="grafik" role="img" aria-label="Grafik batang omzet per hari selama tujuh hari terakhir">
<?php foreach ($grafik as $g): $tinggi = $g['omzet'] > 0 ? max(4, (int)round($g['omzet'] / $puncak * 100)) : 2; $hari = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'][(int)date('w', strtotime($g['tanggal']))]; ?>
<div class="grafik-hari"><strong><?= rupiah($g['omzet']) ?></strong><div class="grafik-track"><span style="height:<?= $tinggi ?>%"></span></div><b><?= $hari ?></b><small><?= date('d/m', strtotime($g['tanggal'])) ?></small></div>
<?php endforeach; ?></div></div>
<div class="scroll"><table class="tabel tabel-ringkas"><thead><tr><th>Tanggal</th><th>Omzet</th></tr></thead><tbody><?php foreach ($grafik as $g): ?><tr><td><?= ['Min','Sen','Sel','Rab','Kam','Jum','Sab'][(int)date('w', strtotime($g['tanggal']))] ?>, <?= date('d/m/Y', strtotime($g['tanggal'])) ?></td><td><?= rupiah($g['omzet']) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<h2 class="judul">Pesanan terbaru</h2>
<div class="scroll"><table class="tabel"><thead><tr><th>Kode</th><th>Pelanggan</th><th>Total</th><th>Metode</th><th>Konfirmasi pembayaran</th><th>Alamat</th><th>Status pesanan</th></tr></thead><tbody>
<?php foreach ($rows as $o): ?><tr><td><?= e($o['kode_pesanan']) ?><br><small><?= e($o['dibuat_pada']) ?></small></td><td><?= e($o['nama_pelanggan']) ?></td><td><?= rupiah($o['total']) ?></td><td><?= e($o['metode_pembayaran']) ?></td>
<td>
<strong><?= e($o['status_pembayaran']) ?></strong>
<?php if ($o['bukti_pembayaran']): ?><p><a href="../bukti_pembayaran.php?kode=<?= urlencode($o['kode_pesanan']) ?>" target="_blank" rel="noopener">Lihat bukti</a></p><?php endif; ?>
<?php if ($o['status_pembayaran'] === 'Menunggu verifikasi' && $o['bukti_pembayaran']): ?>
<form method="post" class="baris"><?= csrf_field() ?><input type="hidden" name="aksi" value="pembayaran"><input type="hidden" name="kode" value="<?= e($o['kode_pesanan']) ?>"><select name="status_pembayaran"><option>Terverifikasi</option><option>Ditolak</option></select><button class="btn abu">Simpan</button></form>
<?php endif; ?>
</td><td><?= e($o['alamat_pengiriman']) ?></td>
<td><form method="post" class="baris"><?= csrf_field() ?><input type="hidden" name="aksi" value="status"><input type="hidden" name="kode" value="<?= e($o['kode_pesanan']) ?>"><select name="status"><?php foreach ($stat as $s) echo '<option'.($s === $o['status'] ? ' selected' : '').'>'.$s.'</option>'; ?></select><button class="btn abu">Simpan</button></form></td></tr><?php endforeach; ?>
<?php if (!$rows) echo '<tr><td colspan="7">Belum ada pesanan.</td></tr>'; ?></tbody></table></div></main></div>
<footer class="foot"><div class="wrap"><strong>Toko Kita</strong><span>Panel pengelolaan toko</span></div></footer></body></html>
