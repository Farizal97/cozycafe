<?php
ob_start();
?>
<main class="container customer-hero">
    <section class="hero-panel">
        <div class="hero-copy">
            <span class="hero-kicker">SELAMAT DATANG DI COZYCAFE</span>
            <h1>Waktu santai,<br><span>rasa yang istimewa.</span></h1>
            <p>Nikmati suasana nyaman dan pilihan menu CozyCafe. Lihat menu atau rencanakan kunjungan Anda.</p>
            <div class="d-flex flex-wrap gap-3">
                <a class="btn btn-cozy-primary btn-lg" href="<?= e($url('menu')) ?>">Lihat Menu</a>
                <a class="btn btn-cozy-outline btn-lg" href="<?= e($url('booking')) ?>">Reservasi Meja</a>
            </div>
        </div>
        <div class="hero-decoration" aria-hidden="true">
            <div class="coffee-mark">C</div>
            <div class="hero-note">Dibuat dengan<br>sepenuh hati</div>
        </div>
    </section>
    <section class="customer-intro">
        <span class="section-kicker">COZYCAFE</span>
        <h2>Selalu ada alasan untuk menikmati waktu bersama.</h2>
        <p>Jelajahi menu dan reservasi meja dengan mudah melalui website CozyCafe.</p>
    </section>
</main>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/customer.php';
