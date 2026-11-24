<?php
ob_start();
?>
<main class="container page-section">
    <div class="page-heading">
        <div>
            <span class="section-kicker">PESANAN ANDA</span>
            <h1>Keranjang</h1>
            <p>Periksa pilihan menu sebelum melanjutkan ke checkout.</p>
        </div>
    </div>

    <section
        class="cart-layout"
        data-cart-app
        data-menu-url="<?= e($url('menu')) ?>"
        data-checkout-url="<?= e($url('checkout')) ?>"
        aria-live="polite"
    >
        <div class="cart-items-panel">
            <div class="cart-panel-heading">
                <h2>Daftar menu</h2>
                <button class="btn btn-sm btn-outline-danger" type="button" data-cart-clear>Hapus semua</button>
            </div>
            <div class="cart-item-list" data-cart-items></div>
            <div class="empty-state cart-empty" data-cart-empty hidden>
                <h3>Keranjang masih kosong</h3>
                <p>Pilih menu CozyCafe untuk memulai pesanan.</p>
                <a class="btn btn-cozy-primary" href="<?= e($url('menu')) ?>">Jelajahi Menu</a>
            </div>
        </div>

        <aside class="cart-summary-card">
            <h2>Ringkasan sementara</h2>
            <div class="summary-line">
                <span>Subtotal menu</span>
                <strong data-cart-subtotal>Rp0</strong>
            </div>
            <div class="summary-line summary-total">
                <span>Total sementara</span>
                <strong data-cart-total>Rp0</strong>
            </div>
            <p class="form-help">Harga, stok, diskon, pajak, dan biaya layanan akan dihitung ulang saat checkout.</p>
            <a class="btn btn-cozy-primary w-100" data-cart-checkout href="<?= e($url('checkout')) ?>">Lanjut Checkout</a>
            <a class="btn btn-cozy-outline w-100 mt-2" href="<?= e($url('menu')) ?>">Tambah Menu Lain</a>
            <p class="cart-notice">Keranjang tersimpan di browser ini dan tetap tersedia setelah halaman dimuat ulang.</p>
        </aside>
    </section>
</main>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/customer.php';
