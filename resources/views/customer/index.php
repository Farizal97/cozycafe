<?php
ob_start();
?>
<main>
    <section class="container customer-hero">
        <div class="hero-panel">
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
        </div>
    </section>

    <section class="container content-section">
        <div class="section-heading">
            <div>
                <span class="section-kicker">JELAJAHI</span>
                <h2>Kategori menu</h2>
            </div>
            <a class="section-link" href="<?= e($url('menu')) ?>">Semua menu <span aria-hidden="true">→</span></a>
        </div>
        <div class="row g-3">
            <?php foreach ($homeData['categories'] as $category): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <a class="category-tile" href="<?= e($url('menu?category=' . (int) $category['id'])) ?>">
                        <span class="category-tile-name"><?= e($category['name']) ?></span>
                        <span class="category-tile-count"><?= (int) $category['product_count'] ?> menu</span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="container content-section">
        <div class="section-heading">
            <div>
                <span class="section-kicker">PILIHAN KAMI</span>
                <h2>Menu unggulan</h2>
            </div>
            <a class="section-link" href="<?= e($url('menu')) ?>">Lihat menu <span aria-hidden="true">→</span></a>
        </div>
        <?php $products = $homeData['featured']; require $root . '/resources/views/components/product_cards.php'; ?>
    </section>

    <section class="container content-section">
        <div class="section-heading">
            <div>
                <span class="section-kicker">FAVORIT PELANGGAN</span>
                <h2>Menu populer</h2>
            </div>
        </div>
        <?php if ($homeData['popular'] !== []): ?>
            <?php $products = $homeData['popular']; require $root . '/resources/views/components/product_cards.php'; ?>
        <?php else: ?>
            <p class="catalog-empty-note">Produk populer akan tampil setelah ada data pesanan.</p>
        <?php endif; ?>
    </section>

    <?php if ($homeData['testimonials'] !== []): ?>
        <section class="testimonial-section">
            <div class="container">
                <div class="section-heading">
                    <div>
                        <span class="section-kicker">CERITA PELANGGAN</span>
                        <h2>Kata mereka tentang CozyCafe</h2>
                    </div>
                </div>
                <div class="row g-3">
                    <?php foreach ($homeData['testimonials'] as $testimonial): ?>
                        <div class="col-md-4">
                            <figure class="testimonial-card h-100">
                                <blockquote>“<?= e($testimonial['comment']) ?>”</blockquote>
                                <figcaption><?= e($testimonial['customer_name']) ?></figcaption>
                            </figure>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section class="container content-section">
        <div class="contact-panel">
            <div>
                <span class="section-kicker">KUNJUNGI KAMI</span>
                <h2>Singgah dan nikmati suasana CozyCafe.</h2>
            </div>
            <div class="contact-details">
                <p><strong>Alamat</strong><br><?= e($homeData['address'] !== '' ? $homeData['address'] : 'Belum dikonfigurasi') ?></p>
                <p><strong>Telepon</strong><br><?= e($homeData['phone'] !== '' ? $homeData['phone'] : 'Belum dikonfigurasi') ?></p>
                <p><strong>Jam operasional</strong><br>Setiap hari, 09.00–22.00 WIB</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-cozy-primary" href="<?= e($url('menu')) ?>">Pesan Menu</a>
                <a class="btn btn-cozy-outline" href="<?= e($url('booking')) ?>">Reservasi Meja</a>
            </div>
        </div>
    </section>
</main>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/customer.php';
