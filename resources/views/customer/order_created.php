<?php
$orderTypeLabel = $order['order_type'] === 'dine_in' ? 'Dine-in' : 'Takeaway';
ob_start();
?>
<main class="container page-section">
    <section class="order-created-card" data-order-created data-clear-cart="<?= $clearCart ? '1' : '0' ?>">
        <span class="order-created-mark" aria-hidden="true">✓</span>
        <span class="section-kicker">PESANAN TERCATAT</span>
        <h1>Terima kasih, pesanan Anda dibuat.</h1>
        <p class="order-created-code-label">Simpan kode pesanan berikut untuk pelacakan:</p>
        <strong class="order-created-code"><?= e($order['order_code']) ?></strong>
        <div class="order-created-status">
            <span>Status pesanan</span><strong>Menunggu konfirmasi admin</strong>
            <span>Jenis pesanan</span><strong><?= e($orderTypeLabel) ?></strong>
            <?php if ($order['table_number'] !== null): ?>
                <span>Meja reservasi</span><strong><?= e($order['table_number']) ?></strong>
            <?php endif; ?>
            <span>Metode pembayaran</span><strong><?= e($order['payment_method_name']) ?></strong>
            <span>Batas pembayaran</span><strong><?= e(formatDateTimeWib($order['payment_deadline'])) ?></strong>
        </div>

        <div class="order-created-items">
            <h2>Ringkasan</h2>
            <?php foreach ($order['items'] as $item): ?>
                <div class="order-summary-item">
                    <div>
                        <strong><?= e($item['name']) ?></strong>
                        <small><?= (int) $item['quantity'] ?> × <?= e(formatRupiahCents($item['price_cents'])) ?></small>
                    </div>
                    <strong><?= e(formatRupiahCents($item['subtotal_cents'])) ?></strong>
                </div>
            <?php endforeach; ?>
            <div class="checkout-price-lines mt-3">
                <div><span>Subtotal</span><strong><?= e(formatRupiahCents($order['subtotal_cents'])) ?></strong></div>
                <div><span>Diskon dan voucher</span><strong>−<?= e(formatRupiahCents($order['discount_amount_cents'])) ?></strong></div>
                <div><span>Pajak</span><strong><?= e(formatRupiahCents($order['tax_amount_cents'])) ?></strong></div>
                <div><span>Biaya layanan</span><strong><?= e(formatRupiahCents($order['service_charge_cents'])) ?></strong></div>
                <div class="checkout-price-total"><span>Total pembayaran</span><strong><?= e(formatRupiahCents($order['total_amount_cents'])) ?></strong></div>
            </div>
        </div>

        <div class="payment-instructions-preview order-created-payment">
            <h2><?= e($order['payment_method_name']) ?></h2>
            <p><?= e($order['instructions'] ?? 'Silakan ikuti petunjuk pembayaran dari admin CozyCafe.') ?></p>
            <?php if ($order['method_type'] === 'cash'): ?>
                <p class="mb-0">Pembayaran tunai dilakukan di kasir setelah pesanan dikonfirmasi.</p>
            <?php elseif ($order['method_type'] === 'bank_transfer'): ?>
                <?php if ($order['bank_name'] !== null): ?><p>Bank: <?= e($order['bank_name']) ?></p><?php endif; ?>
                <?php if ($order['account_number'] !== null): ?><p>Nomor rekening: <?= e($order['account_number']) ?></p><?php endif; ?>
                <?php if ($order['account_holder'] !== null): ?><p class="mb-0">Atas nama: <?= e($order['account_holder']) ?></p><?php endif; ?>
            <?php else: ?>
                <p class="mb-0">Ikuti instruksi QRIS yang ditampilkan oleh CozyCafe untuk metode pembayaran ini.</p>
            <?php endif; ?>
        </div>

        <div class="order-created-actions">
            <a class="btn btn-cozy-outline mt-4" href="<?= e($url('track-order?code=' . rawurlencode($order['order_code']))) ?>">Lacak pesanan</a>
            <a class="btn btn-cozy-primary mt-4" href="<?= e($url('menu')) ?>">Kembali ke Menu</a>
        </div>
    </section>
</main>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/customer.php';
