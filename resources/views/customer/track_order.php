<?php
$orderStatuses = [
    'pending' => 'Menunggu konfirmasi',
    'processing' => 'Sedang diproses',
    'ready' => 'Siap',
    'completed' => 'Selesai',
    'cancelled' => 'Dibatalkan',
    'expired' => 'Kedaluwarsa',
    'cancellation_requested' => 'Pengajuan pembatalan',
    'cancellation_rejected' => 'Pembatalan ditolak',
];
$paymentStatuses = [
    'unpaid' => 'Belum dibayar',
    'waiting_verification' => 'Menunggu verifikasi',
    'paid' => 'Lunas',
    'rejected' => 'Ditolak',
    'refunded' => 'Dikembalikan',
];
$substitutionStatuses = [
    'pending' => 'Menunggu jawaban',
    'accepted' => 'Disetujui',
    'rejected' => 'Ditolak',
    'expired' => 'Kedaluwarsa',
    'cancelled' => 'Dibatalkan',
];
$orderTypeLabel = $order === null
    ? ''
    : ($order['order_type'] === 'dine_in' ? 'Dine-in' : 'Takeaway');
ob_start();
?>
<main class="container page-section tracking-page">
    <?php if ($order === null): ?>
        <header class="page-heading">
            <div>
                <span class="section-kicker">PESANAN COZYCAFE</span>
                <h1>Lacak pesanan</h1>
                <p>Masukkan kode pesanan dan nomor telepon yang digunakan saat checkout.</p>
            </div>
        </header>
        <form class="tracking-form" method="post" action="<?= e($url('track-order')) ?>">
            <?= csrfField() ?>
            <div>
                <label class="form-label" for="trackingCode">Kode pesanan</label>
                <input class="form-control" id="trackingCode" name="order_code" maxlength="32"
                    placeholder="ORD-20260101-0123456789ABCDEF01"
                    value="<?= e(is_string($_GET['code'] ?? null) ? $_GET['code'] : '') ?>" required>
            </div>
            <div>
                <label class="form-label" for="trackingPhone">Nomor telepon</label>
                <input class="form-control" id="trackingPhone" name="phone" type="tel" maxlength="32"
                    placeholder="08xxxxxxxxxx" autocomplete="tel" required>
            </div>
            <button class="btn btn-cozy-primary" type="submit">Lacak pesanan</button>
            <p class="tracking-privacy">Informasi pesanan hanya ditampilkan setelah kode dan nomor telepon cocok.</p>
        </form>
    <?php else: ?>
        <?php
        $paymentDeadline = $order['payment_deadline'] === null
            ? ''
            : (new DateTimeImmutable($order['payment_deadline'], new DateTimeZone('Asia/Jakarta')))->format(DATE_ATOM);
        ?>
        <header class="page-heading tracking-heading">
            <div>
                <span class="section-kicker">PEMBARUAN PESANAN</span>
                <h1>Lacak pesanan</h1>
                <p>Informasi status diperbarui otomatis.</p>
            </div>
            <a class="btn btn-cozy-outline" href="<?= e($url('track-order?reset=1')) ?>">Lacak pesanan lain</a>
        </header>

        <section class="tracking-card" data-order-tracking data-status-url="<?= e($url('track-order/status')) ?>"
            data-payment-deadline="<?= e($paymentDeadline) ?>"
            data-payment-status="<?= e($order['payment_status']) ?>"
            aria-live="polite">
            <div class="tracking-overview">
                <div>
                    <span class="tracking-label">Kode pesanan</span>
                    <strong class="tracking-code"><?= e($order['order_code']) ?></strong>
                    <span class="tracking-created">Dibuat <?= e(formatDateTimeWib($order['created_at'])) ?></span>
                </div>
                <span class="tracking-status-badge" data-order-status><?= e($orderStatuses[$order['order_status']] ?? 'Status tidak diketahui') ?></span>
            </div>

            <div class="tracking-status-grid">
                <div class="tracking-status-panel">
                    <span class="tracking-label">Status pembayaran</span>
                    <strong data-payment-status><?= e($paymentStatuses[$order['payment_status']] ?? 'Status tidak diketahui') ?></strong>
                    <span><?= e($order['payment_method_name']) ?></span>
                </div>
                <div class="tracking-status-panel">
                    <span class="tracking-label">Jenis pesanan</span>
                    <strong><?= e($orderTypeLabel) ?></strong>
                    <span>Total pembayaran</span>
                    <strong class="tracking-total" data-order-total><?= e(formatRupiah($order['total_amount'])) ?></strong>
                </div>
                <div class="tracking-countdown-panel" data-countdown-panel>
                    <span class="tracking-label">Batas pembayaran</span>
                    <strong data-payment-countdown>—</strong>
                    <span data-payment-deadline-label><?= e($order['payment_deadline'] === null ? 'Tidak ada batas pembayaran.' : formatDateTimeWib($order['payment_deadline'])) ?></span>
                </div>
            </div>

            <section class="tracking-section">
                <h2>Detail pesanan</h2>
                <div class="tracking-items">
                    <?php foreach ($order['items'] as $item): ?>
                        <article class="tracking-item">
                            <div>
                                <strong><?= e($item['name']) ?></strong>
                                <span><?= (int) $item['quantity'] ?> × <?= e(formatRupiah($item['price_snapshot'])) ?></span>
                            </div>
                            <strong><?= e(formatRupiah($item['subtotal'])) ?></strong>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="tracking-section">
                <h2>Riwayat status</h2>
                <ol class="tracking-timeline" data-order-history>
                    <?php foreach ($order['histories'] as $history): ?>
                        <li>
                            <span class="tracking-timeline-dot" aria-hidden="true"></span>
                            <div>
                                <strong><?= e($orderStatuses[$history['new_status']] ?? 'Status tidak diketahui') ?></strong>
                                <time><?= e(formatDateTimeWib($history['changed_at'])) ?></time>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </section>

            <section class="tracking-section" data-substitution-section <?= $order['substitutions'] === [] ? 'hidden' : '' ?>>
                <h2>Informasi produk pengganti</h2>
                <div class="tracking-substitutions" data-order-substitutions>
                    <?php foreach ($order['substitutions'] as $substitution): ?>
                        <article class="tracking-substitution">
                            <div>
                                <strong><?= e($substitution['original_product_name']) ?> → <?= e($substitution['replacement_product_name']) ?></strong>
                                <span><?= (int) $substitution['quantity'] ?> item · <?= e(formatRupiah($substitution['replacement_price'])) ?></span>
                            </div>
                            <div class="tracking-substitution-status">
                                <strong><?= e($substitutionStatuses[$substitution['status']] ?? 'Status tidak diketahui') ?></strong>
                                <?php if ($substitution['status'] === 'pending'): ?>
                                    <span>Batas respons <?= e(formatDateTimeWib($substitution['expires_at'])) ?></span>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <p class="tracking-refresh-note" data-tracking-message role="status">Status diperbarui otomatis setiap 20 detik.</p>
        </section>
    <?php endif; ?>
</main>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/customer.php';
