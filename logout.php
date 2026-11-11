<?php
require __DIR__.'/../config.php';
unset($_SESSION['admin']);
session_regenerate_id(true);
flash('Akun staf sudah keluar. Anda sedang melihat halaman toko.');
pindah('../index.php');
