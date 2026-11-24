<?php
ob_start();
?>
<main class="container py-5">
    <section class="admin-form-card">
        <span class="section-kicker">404</span>
        <h1 class="h3 mt-2">Halaman tidak ditemukan</h1>
        <p>Alamat yang Anda buka tidak tersedia.</p>
        <a class="btn btn-cozy-primary" href="<?= e($url('')) ?>">Kembali ke Beranda</a>
    </section>
</main>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/customer.php';
