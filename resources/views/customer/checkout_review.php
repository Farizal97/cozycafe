<?php
$checkoutInput = $checkout['checkout'];
$cartJson = json_encode($checkoutInput['cart'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$orderTypeLabel = $checkoutInput['order_type'] === 'dine_in' ? 'Dine-in' : 'Takeaway';
$paymentMethod = $checkout['payment_method'];
ob_start();
?>
<main class="container page-section">
    <a class="back-link" href="<?= e($url('checkout')) ?>">← Ubah detail checkout</a>
    <div class="page-heading checkout-heading">
        <div>
            <span class="section-kicker">LANGKAH TERAKHIR</span>
            <h1>Tinjau pesanan</h1>
            <p>Pastikan jumlah menu, rincian pembayaran, dan data pelanggan sudah benar.</p>
        </div>
    </div>

    <div class="checkout-grid">
        <section class="checkout-form-card">
            <div class="checkout-review-section">
                <h2>Detail pesanan</h2>
                <dl class="checkout-detail-list">
                    <div><dt>Nama</dt><dd><?= e($checkoutInput['customer_name']) ?></dd></div>
                    <div><dt>Telepon</dt><dd><?= e($checkoutInput['phone']) ?></dd></div>
                    <div><dt>Jenis pesanan</dt><dd><?= e($orderTypeLabel) ?></dd></div>
                    <?php if ($checkout['booking'] !== null): ?>
                        <div><dt>Kode reservasi</dt><dd><?= e($checkoutInput['booking_code']) ?></dd></div>
                        <div><dt>Meja reservasi</dt><dd><?= e($checkout['booking']['table_number']) ?></dd></div>
                    <?php endif; ?>
                </dl>
            </div>

            <div class="checkout-review-section">
                <h2>Menu yang dipesan</h2>
                <div class="order-summary-items">
                    <?php foreach ($checkout['items'] as $item): ?>
                        <div class="order-summary-item">
                            <div>
                                <strong><?= e($item['name']) ?></strong>
                                <small><?= (int) $item['quantity'] ?> × <?= e(formatRupiahCents($item['price_cents'])) ?></small>
                            </div>
                            <strong><?= e(formatRupiahCents($item['subtotal_cents'])) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="checkout-review-section">
                <h2>Pembayaran</h2>
                <div class="checkout-price-lines">
                    <div><span>Subtotal</span><strong><?= e(formatRupiahCents($checkout['subtotal_cents'])) ?></strong></div>
                    <div>
                        <span>Diskon<?= $checkout['discount_label'] !== null ? ' — ' . e($checkout['discount_label']) : '' ?></span>
                        <strong>−<?= e(formatRupiahCents($checkout['discount_cents'])) ?></strong>
                    </div>
                    <div>
                        <span>Voucher</span>
                        <strong><?= $checkout['voucher_code'] !== '' ? e($checkout['voucher_code']) : 'Tidak digunakan' ?></strong>
                    </div>
                    <div><span>Pajak</span><strong><?= e(formatRupiahCents($checkout['tax_cents'])) ?></strong></div>
                    <div><span>Biaya layanan</span><strong><?= e(formatRupiahCents($checkout['service_charge_cents'])) ?></strong></div>
                    <div class="checkout-price-total"><span>Total pembayaran</span><strong><?= e(formatRupiahCents($checkout['total_cents'])) ?></strong></div>
                </div>
                <div class="payment-instructions-preview">
                    <strong><?= e($paymentMethod['name']) ?></strong>
                    <p><?= e($paymentMethod['instructions'] ?? 'Instruksi pembayaran akan ditampilkan setelah pesanan dibuat.') ?></p>
                    <?php if ($paymentMethod['method_type'] === 'bank_transfer'): ?>
                        <?php if ($paymentMethod['bank_name'] !== null): ?><p>Bank: <?= e($paymentMethod['bank_name']) ?></p><?php endif; ?>
                        <?php if ($paymentMethod['account_number'] !== null): ?><p>Nomor rekening: <?= e($paymentMethod['account_number']) ?></p><?php endif; ?>
                        <?php if ($paymentMethod['account_holder'] !== null): ?><p>Atas nama: <?= e($paymentMethod['account_holder']) ?></p><?php endif; ?>
                    <?php endif; ?>
                    <small>Batas pembayaran <?= (int) $paymentMethod['deadline_minutes'] ?> menit setelah pesanan dibuat.</small>
                </div>
            </div>

            <form method="post" action="<?= e($url('checkout/confirm')) ?>" class="checkout-confirm-form">
                <?= csrfField() ?>
                <input type="hidden" name="cart_json" value="<?= e($cartJson === false ? '[]' : $cartJson) ?>">
                <input type="hidden" name="customer_name" value="<?= e($checkoutInput['customer_name']) ?>">
                <input type="hidden" name="phone" value="<?= e($checkoutInput['phone']) ?>">
                <input type="hidden" name="order_type" value="<?= e($checkoutInput['order_type']) ?>">
                <input type="hidden" name="payment_method_id" value="<?= (int) $paymentMethod['id'] ?>">
                <input type="hidden" name="voucher_code" value="<?= e($checkoutInput['voucher_code']) ?>">
                <input type="hidden" name="booking_code" value="<?= e($checkoutInput['booking_code']) ?>">
                <div class="form-check mb-3">
                    <input class="form-check-input" id="confirmOrder" name="confirm_order" type="checkbox" value="1" required>
                    <label class="form-check-label" for="confirmOrder">Saya sudah memeriksa ringkasan dan menyetujui pembuatan pesanan.</label>
                </div>
                <button class="btn btn-cozy-primary btn-lg w-100" type="submit">Konfirmasi dan Buat Pesanan</button>
            </form>
        </section>

        <aside class="checkout-side-note">
            <span class="section-kicker">KONFIRMASI</span>
            <h2>Harga dan stok diperiksa kembali</h2>
            <p>Ringkasan ini adalah hasil verifikasi server. Harga, voucher, stok, dan batas pembelian akan diperiksa ulang saat pesanan dibuat.</p>
            <p class="mb-0">Stok belum dikurangi; pengurangan dilakukan setelah admin mengonfirmasi pesanan.</p>
        </aside>
    </div>
</main>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/customer.php';
