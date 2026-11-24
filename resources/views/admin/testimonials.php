<?php
ob_start();
?>
<section class="operations-page">
    <div class="section-heading">
        <div><span class="section-kicker">KONTEN BERANDA</span><h2>Testimonial</h2><p class="text-secondary mb-0">Hanya testimonial yang disetujui dan ditampilkan yang terlihat di halaman utama.</p></div>
    </div>
    <section class="admin-list-card mb-4">
        <h3 class="h5">Tambahkan dari ulasan disetujui</h3>
        <form class="testimonial-create-form" method="post" action="<?= e($url('admin/testimonials/create')) ?>">
            <?= csrfField() ?>
            <div><label class="form-label" for="testimonialReview">Pilih ulasan pelanggan</label><select class="form-select" id="testimonialReview" name="review_id" required><option value="">Pilih ulasan</option><?php foreach ($availableReviews as $review): ?><option value="<?= (int) $review['id'] ?>"><?= e(($review['customer_name'] ?: 'Pelanggan') . ' · ' . ($review['product_name'] ?: ($review['review_type'] === 'cafe' ? 'Kafe' : 'Produk')) . ' · ' . (int) $review['rating'] . '/5 · ' . mb_strimwidth((string) ($review['comment'] ?? ''), 0, 90, '...')) ?></option><?php endforeach; ?></select></div>
            <button class="btn btn-cozy-primary" type="submit" <?= $availableReviews === [] ? 'disabled' : '' ?>>Tambahkan testimonial</button>
        </form>
        <?php if ($availableReviews === []): ?><p class="form-help mb-0 mt-2">Tidak ada ulasan disetujui yang belum digunakan sebagai testimonial.</p><?php endif; ?>
    </section>
    <div class="testimonial-card-grid">
        <?php foreach ($testimonials as $testimonial): ?>
            <article class="testimonial-admin-card">
                <div class="testimonial-admin-badges">
                    <span class="availability-badge <?= (int) $testimonial['is_approved'] === 1 ? 'status-approved' : 'status-pending' ?>"><?= (int) $testimonial['is_approved'] === 1 ? 'Disetujui' : 'Menunggu persetujuan' ?></span>
                    <span class="availability-badge <?= (int) $testimonial['is_visible'] === 1 ? 'status-available' : 'status-unavailable' ?>"><?= (int) $testimonial['is_visible'] === 1 ? 'Tampil di beranda' : 'Disembunyikan' ?></span>
                </div>
                <div class="review-stars"><?= str_repeat('★', (int) $testimonial['rating']) . str_repeat('☆', 5 - (int) $testimonial['rating']) ?></div>
                <blockquote><?= e($testimonial['comment'] ?: 'Tanpa komentar') ?></blockquote>
                <p class="testimonial-credit"><strong><?= e($testimonial['customer_name'] ?: 'Pelanggan anonim') ?></strong><span><?= e($testimonial['review_type'] === 'product' ? ($testimonial['product_name'] ?: 'Produk') : 'Pelayanan kafe') ?></span></p>
                <div class="table-actions">
                    <?php if ((int) $testimonial['is_approved'] !== 1): ?>
                        <form method="post" action="<?= e($url('admin/testimonials/status')) ?>"><?= csrfField() ?><input type="hidden" name="testimonial_id" value="<?= (int) $testimonial['id'] ?>"><input type="hidden" name="action" value="approve"><button class="btn btn-sm btn-cozy-primary" type="submit">Setujui & tampilkan</button></form>
                    <?php elseif ((int) $testimonial['is_visible'] !== 1): ?>
                        <form method="post" action="<?= e($url('admin/testimonials/status')) ?>"><?= csrfField() ?><input type="hidden" name="testimonial_id" value="<?= (int) $testimonial['id'] ?>"><input type="hidden" name="action" value="show"><button class="btn btn-sm btn-cozy-primary" type="submit">Tampilkan</button></form>
                    <?php else: ?>
                        <form method="post" action="<?= e($url('admin/testimonials/status')) ?>"><?= csrfField() ?><input type="hidden" name="testimonial_id" value="<?= (int) $testimonial['id'] ?>"><input type="hidden" name="action" value="hide"><button class="btn btn-sm btn-outline-secondary" type="submit">Sembunyikan</button></form>
                    <?php endif; ?>
                    <form method="post" action="<?= e($url('admin/testimonials/delete')) ?>" onsubmit="return confirm('Hapus testimonial? Ulasan pelanggan tetap tersimpan.')"><?= csrfField() ?><input type="hidden" name="testimonial_id" value="<?= (int) $testimonial['id'] ?>"><button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button></form>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if ($testimonials === []): ?><div class="empty-state-panel"><strong>Belum ada testimonial</strong><span>Pilih ulasan yang sudah disetujui untuk mulai mengisi konten beranda.</span></div><?php endif; ?>
    </div>
</section>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/admin.php';
