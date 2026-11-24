<?php
$reviewStatuses = ['pending' => 'Menunggu moderasi', 'approved' => 'Disetujui', 'rejected' => 'Disembunyikan'];
ob_start();
?>
<section class="admin-list-card operations-page">
    <div class="section-heading">
        <div><span class="section-kicker">PENGALAMAN PELANGGAN</span><h2>Ulasan</h2><p class="text-secondary mb-0">Moderasi ulasan sebelum ditampilkan kepada publik.</p></div>
        <div class="review-rating-summary"><strong><?= number_format((float) $reviewData['stats']['average_rating'], 1) ?><span>/5</span></strong><small><?= (int) $reviewData['stats']['review_count'] ?> ulasan disetujui</small></div>
    </div>
    <form class="admin-filter-bar customer-search-bar" method="get" action="<?= e($url('admin/reviews')) ?>">
        <div><label class="form-label" for="reviewStatus">Status moderasi</label><select class="form-select" id="reviewStatus" name="status"><option value="">Semua ulasan</option><?php foreach ($reviewStatuses as $value => $label): ?><option value="<?= e($value) ?>" <?= $reviewData['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
        <button class="btn btn-cozy-primary" type="submit">Terapkan filter</button>
    </form>
    <div class="review-card-grid">
        <?php foreach ($reviewData['reviews'] as $review): ?>
            <article class="review-admin-card">
                <header><div><span class="review-stars" aria-label="<?= (int) $review['rating'] ?> dari 5 bintang"><?= str_repeat('★', (int) $review['rating']) . str_repeat('☆', 5 - (int) $review['rating']) ?></span><span class="availability-badge status-<?= e($review['moderation_status']) ?>"><?= e($reviewStatuses[$review['moderation_status']] ?? '—') ?></span></div><small><?= e(formatDateTimeWib($review['created_at'])) ?></small></header>
                <div class="review-admin-meta"><strong><?= e($review['customer_name'] ?: 'Pelanggan anonim') ?></strong><span><?= $review['review_type'] === 'product' ? e($review['product_name'] ?: 'Produk') : 'Pelayanan kafe' ?> · <?= e($review['order_code']) ?></span></div>
                <p><?= e($review['comment'] ?: 'Pelanggan tidak menambahkan komentar.') ?></p>
                <footer>
                    <div class="table-actions">
                        <?php if ($review['moderation_status'] !== 'approved'): ?>
                            <form method="post" action="<?= e($url('admin/reviews/moderate')) ?>"><?= csrfField() ?><input type="hidden" name="review_id" value="<?= (int) $review['id'] ?>"><input type="hidden" name="decision" value="approve"><button class="btn btn-sm btn-cozy-primary" type="submit">Setujui</button></form>
                        <?php endif; ?>
                        <?php if ($review['moderation_status'] !== 'rejected'): ?>
                            <form method="post" action="<?= e($url('admin/reviews/moderate')) ?>" onsubmit="return confirm('Sembunyikan ulasan ini dari pelanggan?')"><?= csrfField() ?><input type="hidden" name="review_id" value="<?= (int) $review['id'] ?>"><input type="hidden" name="decision" value="hide"><button class="btn btn-sm btn-outline-secondary" type="submit">Sembunyikan</button></form>
                        <?php endif; ?>
                        <form method="post" action="<?= e($url('admin/reviews/delete')) ?>" onsubmit="return confirm('Hapus ulasan secara permanen? Testimonial terkait juga akan dihapus.')"><?= csrfField() ?><input type="hidden" name="review_id" value="<?= (int) $review['id'] ?>"><button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button></form>
                    </div>
                </footer>
            </article>
        <?php endforeach; ?>
        <?php if ($reviewData['reviews'] === []): ?><div class="empty-state-panel"><strong>Belum ada ulasan</strong><span>Ulasan pelanggan akan muncul di halaman ini setelah dikirim.</span></div><?php endif; ?>
    </div>
    <p class="form-help mt-3">Maksimal 200 ulasan terbaru ditampilkan. Ulasan yang disembunyikan tetap tersimpan untuk riwayat internal.</p>
</section>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/admin.php';
