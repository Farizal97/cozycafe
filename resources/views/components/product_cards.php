<?php
$availabilityLabels = [
    'available' => 'Tersedia',
    'sold_out' => 'Habis',
    'unavailable' => 'Tidak tersedia',
];
?>
<?php if ($products === []): ?>
    <div class="empty-state">
        <h3>Menu belum tersedia</h3>
        <p>Silakan kembali lagi nanti untuk melihat pilihan menu CozyCafe.</p>
    </div>
<?php else: ?>
    <div class="row g-4 product-grid">
        <?php foreach ($products as $product): ?>
            <?php
            $canOrder = $product['availability_status'] === 'available'
                && (int) $product['stock'] >= (int) $product['min_order_quantity'];
            $maxQuantity = min((int) $product['stock'], (int) $product['max_order_quantity']);
            $imageName = $product['image'] === null ? '' : basename((string) $product['image']);
            ?>
            <div class="col-sm-6 col-xl-3">
                <article class="product-card h-100">
                    <a class="product-image-link" href="<?= e($url('product/' . (int) $product['id'])) ?>" aria-label="Lihat <?= e($product['name']) ?>">
                        <?php if ($imageName !== ''): ?>
                            <img class="product-image" src="<?= e($url('media/products/' . rawurlencode($imageName))) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
                        <?php else: ?>
                            <span class="product-image-placeholder" aria-hidden="true">CozyCafe</span>
                        <?php endif; ?>
                    </a>
                    <div class="product-card-body">
                        <div class="product-meta">
                            <span><?= e($product['category_name']) ?></span>
                            <span class="availability-badge status-<?= e($product['availability_status']) ?>">
                                <?= e($availabilityLabels[$product['availability_status']] ?? 'Tidak tersedia') ?>
                            </span>
                        </div>
                        <a class="product-name" href="<?= e($url('product/' . (int) $product['id'])) ?>">
                            <?= e($product['name']) ?>
                        </a>
                        <p class="product-description"><?= e($product['description'] ?? 'Pilihan CozyCafe untuk menemani waktu Anda.') ?></p>
                        <div class="product-card-bottom">
                            <strong class="product-price"><?= e(formatRupiah($product['price'])) ?></strong>
                            <button
                                class="btn btn-sm btn-cozy-primary"
                                type="button"
                                data-add-to-cart
                                data-product-id="<?= (int) $product['id'] ?>"
                                data-product-name="<?= e($product['name']) ?>"
                                data-product-price="<?= e($product['price']) ?>"
                                data-min-quantity="<?= (int) $product['min_order_quantity'] ?>"
                                data-max-quantity="<?= $maxQuantity ?>"
                                <?= $canOrder ? '' : 'disabled' ?>
                            >
                                <?= $canOrder ? 'Tambah' : 'Tidak tersedia' ?>
                            </button>
                        </div>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
