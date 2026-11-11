</main>
</div>
<footer class="foot"><div class="wrap"><strong>Toko Kita</strong>
<span>Cabang: <?php foreach ($db->query('SELECT nama_cabang,kota FROM tbl_cabang') as $c) echo e($c['nama_cabang'].' ('.$c['kota'].')').' &nbsp; '; ?></span></div></footer>
</body></html>
