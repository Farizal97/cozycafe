<?php
$availabilityLabels = [
    'available' => 'Tersedia',
    'sold_out' => 'Habis',
    'unavailable' => 'Tidak tersedia',
];
$imageName = $product['image'] === null ? '' : basename((string) $product['image']);
$maxQuantity = min((int) $product['stock'], (int) $product['max_order_quantity']);
$canOrder = $product['availability_status'] === 'available'
    && (int) $product['stock'] >= (int) $product['min_order_quantity'];
ob_start();
?>
<main class="container page-section">
    <a class="back-link" href="<?= e($url('menu')) ?>">← Kembali ke menu</a>
    <article class="product-detail">
        <div class="product-detail-image">
            <?php if ($imageName !== ''): ?>
                <img src="<?= e($url('media/products/' . rawurlencode($imageName))) ?>" alt="<?= e($product['name']) ?>">
            <?php else: ?>
                <span aria-hidden="true">CozyCafe</span>
            <?php endif; ?>
        </div>
        <div class="product-detail-copy">
            <span class="section-kicker"><?= e($product['category_name']) ?></span>
            <h1><?= e($product['name']) ?></h1>
            <span class="availability-badge status-<?= e($product['availability_status']) ?>">
                <?= e($availabilityLabels[$product['availability_status']] ?? 'Tidak tersedia') ?>
            </span>
            <p class="product-detail-description"><?= e($product['description'] ?? 'Pilihan CozyCafe untuk menemani waktu Anda.') ?></p>
            <p class="product-detail-price"><?= e(formatRupiah($product['price'])) ?></p>
            <p class="product-stock-note">Stok tersedia: <?= (int) $product['stock'] ?> · Minimum pembelian: <?= (int) $product['min_order_quantity'] ?></p>
            <button
                class="btn btn-cozy-primary btn-lg"
                type="button"
                data-add-to-cart
                data-product-id="<?= (int) $product['id'] ?>"
                data-product-name="<?= e($product['name']) ?>"
                data-product-price="<?= e($product['price']) ?>"
                data-min-quantity="<?= (int) $product['min_order_quantity'] ?>"
                data-max-quantity="<?= $maxQuantity ?>"
                <?= $canOrder ? '' : 'disabled' ?>
            >
                <?= $canOrder ? 'Tambah ke Keranjang' : 'Tidak tersedia' ?>
            </button>
        </div>
    </article>
</main>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/customer.php';
