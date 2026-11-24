<?php
ob_start();
?>
<section class="admin-form-card">
    <span class="section-kicker">404</span>
    <h2>Halaman admin tidak ditemukan</h2>
    <p>Modul ini belum tersedia atau alamat yang diminta tidak benar.</p>
    <a class="btn btn-cozy-primary" href="<?= e($url('admin')) ?>">Kembali ke Dashboard</a>
</section>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/admin.php';
