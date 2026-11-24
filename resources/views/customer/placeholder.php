<?php
ob_start();
?>
<main class="container page-section">
    <section class="empty-state">
        <span class="section-kicker">COZYCAFE</span>
        <h1><?= e($pageTitle) ?></h1>
        <p>Halaman ini akan tersedia pada tahap implementasi berikutnya.</p>
        <a class="btn btn-cozy-primary" href="<?= e($url('menu')) ?>">Jelajahi Menu</a>
    </section>
</main>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/customer.php';
