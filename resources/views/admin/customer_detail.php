<?php
$orderStatuses = [
    'pending' => 'Menunggu konfirmasi', 'processing' => 'Diproses', 'ready' => 'Siap',
    'completed' => 'Selesai', 'cancelled' => 'Dibatalkan', 'expired' => 'Kedaluwarsa',
    'cancellation_requested' => 'Pengajuan pembatalan', 'cancellation_rejected' => 'Pembatalan ditolak',
];
$bookingStatuses = [
    'pending' => 'Menunggu konfirmasi', 'confirmed' => 'Dikonfirmasi', 'rejected' => 'Ditolak',
    'completed' => 'Selesai', 'cancelled' => 'Dibatalkan', 'cancellation_requested' => 'Minta pembatalan',
    'checked_in' => 'Check-in', 'no_show' => 'Tidak hadir',
];
ob_start();
?>
<section class="admin-list-card operations-page">
    <div class="section-heading">
        <div><span class="section-kicker">PROFIL PELANGGAN</span><h2><?= e($customer['name'] ?: 'Pelanggan') ?></h2></div>
        <a class="btn btn-outline-secondary" href="<?= e($url('admin/customers')) ?>">Kembali ke pelanggan</a>
    </div>
    <div class="operation-summary-grid customer-summary">
        <article><span>Nomor telepon</span><strong><?= e($customer['phone'] ?: 'Dianonimkan') ?></strong></article>
        <article><span>Pesanan tersimpan</span><strong><?= count($customer['orders']) ?></strong></article>
        <article><span>Reservasi tersimpan</span><strong><?= count($customer['bookings']) ?></strong></article>
        <article><span>Ulasan</span><strong><?= count($customer['reviews']) ?></strong></article>
    </div>

    <?php if ((int) $customer['is_anonymized'] !== 1): ?>
        <details class="customer-anonymize-panel">
            <summary>Anonimkan data pribadi</summary>
            <p>Nama dan nomor telepon akan dihapus. Riwayat transaksi tetap disimpan. Pelanggan dengan pesanan atau reservasi aktif tidak dapat dianonimkan.</p>
            <form method="post" action="<?= e($url('admin/customers/anonymize')) ?>" onsubmit="return confirm('Anonimkan data pribadi pelanggan ini? Tindakan ini tidak dapat dibatalkan.')">
                <?= csrfField() ?><input type="hidden" name="customer_id" value="<?= (int) $customer['id'] ?>">
                <label class="form-check"><input class="form-check-input" type="checkbox" name="confirm_anonymize" value="1" required><span>Saya memahami identitas pribadi akan dihapus</span></label>
                <button class="btn btn-outline-danger mt-2" type="submit">Anonimkan pelanggan</button>
            </form>
        </details>
    <?php else: ?>
        <div class="alert alert-secondary">Data pribadi pelanggan ini sudah dianonimkan.</div>
    <?php endif; ?>

    <div class="operations-two-column mt-4">
        <section class="operation-panel">
            <h3>Riwayat pesanan</h3>
            <?php foreach ($customer['orders'] as $order): ?>
                <div class="operation-line-item">
                    <div><strong><?= e($order['order_code']) ?></strong><small><?= e(formatDateTimeWib($order['created_at'])) ?> · <?= e($orderStatuses[$order['order_status']] ?? '—') ?></small></div>
                    <strong><?= e(formatRupiah($order['total_amount'])) ?></strong>
                </div>
            <?php endforeach; ?>
            <?php if ($customer['orders'] === []): ?><p class="text-secondary">Belum ada pesanan.</p><?php endif; ?>
        </section>
        <section class="operation-panel">
            <h3>Riwayat reservasi</h3>
            <?php foreach ($customer['bookings'] as $booking): ?>
                <div class="operation-line-item">
                    <div><strong><?= e($booking['booking_code']) ?></strong><small><?= e(date('d/m/Y', strtotime($booking['booking_date']))) ?> · <?= e(substr($booking['slot_start'], 0, 5)) ?> · <?= (int) $booking['guest_count'] ?> tamu</small></div>
                    <strong><?= e($bookingStatuses[$booking['status']] ?? '—') ?></strong>
                </div>
            <?php endforeach; ?>
            <?php if ($customer['bookings'] === []): ?><p class="text-secondary">Belum ada reservasi.</p><?php endif; ?>
        </section>
    </div>
    <section class="operation-panel mt-4">
        <h3>Ulasan pelanggan</h3>
        <?php foreach ($customer['reviews'] as $review): ?>
            <article class="customer-review">
                <strong><?= $review['review_type'] === 'product' ? 'Produk' : 'Kafe' ?> · <?= (int) $review['rating'] ?>/5</strong>
                <span><?= e($review['moderation_status']) ?> · <?= e(formatDateTimeWib($review['created_at'])) ?></span>
                <p><?= e($review['comment'] ?: 'Tanpa komentar') ?></p>
            </article>
        <?php endforeach; ?>
        <?php if ($customer['reviews'] === []): ?><p class="text-secondary">Belum ada ulasan.</p><?php endif; ?>
    </section>
</section>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/admin.php';
