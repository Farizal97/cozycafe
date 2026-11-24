<?php
$formValues = is_array($formValues ?? null) ? $formValues : [];
ob_start();
?>
<main class="container page-section">
    <a class="back-link" href="<?= e($url('cart')) ?>">← Kembali ke keranjang</a>
    <div class="page-heading checkout-heading">
        <div>
            <span class="section-kicker">PESAN SEKARANG</span>
            <h1>Checkout</h1>
            <p>Isi data pesanan. Anda tidak perlu membuat akun.</p>
        </div>
    </div>

    <div class="checkout-grid">
        <form class="checkout-form-card" method="post" action="<?= e($url('checkout/review')) ?>" data-checkout-form>
            <?= csrfField() ?>
            <input type="hidden" name="cart_json" value="">
            <div class="checkout-form-section">
                <h2>Informasi pelanggan</h2>
                <div class="mb-3">
                    <label class="form-label" for="checkoutName">Nama pelanggan</label>
                    <input class="form-control" id="checkoutName" name="customer_name" type="text" maxlength="160" autocomplete="name" value="<?= e($formValues['customer_name'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="checkoutPhone">Nomor telepon</label>
                    <input class="form-control" id="checkoutPhone" name="phone" type="tel" inputmode="tel" maxlength="24" autocomplete="tel" placeholder="Contoh: 081234567890" value="<?= e($formValues['phone'] ?? '') ?>" required>
                    <div class="form-text">Nomor ini digunakan untuk mengenali pesanan aktif dan batas penggunaan voucher.</div>
                </div>
            </div>

            <div class="checkout-form-section">
                <h2>Jenis pesanan</h2>
                <div class="mb-3">
                    <label class="form-label" for="orderType">Pilih jenis</label>
                    <select class="form-select" id="orderType" name="order_type" required>
                        <option value="takeaway"<?= ($formValues['order_type'] ?? 'takeaway') === 'takeaway' ? ' selected' : '' ?>>Takeaway</option>
                        <option value="dine_in"<?= ($formValues['order_type'] ?? '') === 'dine_in' ? ' selected' : '' ?>>Dine-in</option>
                    </select>
                </div>
                <div class="mb-3" data-booking-field<?= ($formValues['order_type'] ?? 'takeaway') === 'dine_in' ? '' : ' hidden' ?>>
                    <label class="form-label" for="bookingCode">Kode reservasi (opsional)</label>
                    <input class="form-control" id="bookingCode" name="booking_code" type="text" maxlength="32" value="<?= e($formValues['booking_code'] ?? '') ?>">
                    <div class="form-text">Reservasi harus sudah dikonfirmasi dan berlaku hari ini. Dine-in tanpa reservasi juga dapat dipilih.</div>
                </div>
            </div>

            <div class="checkout-form-section">
                <h2>Metode pembayaran</h2>
                <?php if ($paymentMethods === []): ?>
                    <div class="alert alert-warning" role="alert">Belum ada metode pembayaran yang aktif. Checkout belum dapat dilanjutkan.</div>
                <?php else: ?>
                    <div class="payment-method-options">
                        <?php foreach ($paymentMethods as $method): ?>
                            <?php $methodId = (int) $method['id']; ?>
                            <label class="payment-method-option" for="payment-<?= $methodId ?>">
                                <input
                                    class="form-check-input"
                                    id="payment-<?= $methodId ?>"
                                    name="payment_method_id"
                                    type="radio"
                                    value="<?= $methodId ?>"
                                    <?= (string) ($formValues['payment_method_id'] ?? $paymentMethods[0]['id']) === (string) $methodId ? ' checked' : '' ?>
                                    required
                                >
                                <span>
                                    <strong><?= e($method['name']) ?></strong>
                                    <small>Batas pembayaran <?= (int) $method['deadline_minutes'] ?> menit setelah pesanan dibuat.</small>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="checkout-form-section">
                <h2>Voucher</h2>
                <label class="form-label" for="voucherCode">Kode voucher (opsional)</label>
                <input class="form-control text-uppercase" id="voucherCode" name="voucher_code" type="text" maxlength="64" autocomplete="off" value="<?= e($formValues['voucher_code'] ?? '') ?>">
                <div class="form-text">Jika voucher digunakan, diskon otomatis tidak ditumpuk.</div>
            </div>

            <div class="checkout-submit-row">
                <p class="form-help">Jumlah stok diperiksa saat checkout dan pesanan diproses setelah dikonfirmasi admin.</p>
                <button class="btn btn-cozy-primary btn-lg" type="submit"<?= $paymentMethods === [] ? ' disabled' : '' ?>>Tinjau Pesanan</button>
            </div>
        </form>

        <aside class="checkout-side-note">
            <span class="section-kicker">SEBELUM MELANJUTKAN</span>
            <h2>Pesanan diproses segera</h2>
            <p>Periksa ringkasan harga dan detail pembayaran pada halaman berikutnya sebelum mengonfirmasi pesanan.</p>
            <ul>
                <li>Harga dan stok diverifikasi ulang oleh server.</li>
                <li>Stok baru dikurangi setelah admin mengonfirmasi pesanan.</li>
                <li>Setiap nomor telepon dibatasi satu pesanan aktif per jenis pesanan.</li>
            </ul>
        </aside>
    </div>
</main>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/customer.php';
