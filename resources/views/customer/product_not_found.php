<?php
ob_start();
?>
<main class="container page-section">
    <section class="empty-state">
        <span class="section-kicker">MENU</span>
        <h1>Produk tidak ditemukan</h1>
        <p>Produk mungkin sudah tidak tersedia.</p>
        <a class="btn btn-cozy-primary" href="<?= e($url('menu')) ?>">Kembali ke Menu</a>
    </section>
</main>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/customer.php';
