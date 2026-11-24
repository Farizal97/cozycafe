<?php
$statusLabels = [
    'available' => 'Tersedia',
    'sold_out' => 'Habis',
    'unavailable' => 'Tidak tersedia',
];
ob_start();
?>
<section class="admin-list-card">
    <div class="section-heading">
        <div>
            <span class="section-kicker">KATALOG</span>
            <h2>Manajemen Produk</h2>
        </div>
        <a class="btn btn-cozy-primary" href="<?= e($url('admin/products/create')) ?>">Tambah Produk</a>
    </div>
    <p class="form-help">Harga produk yang pernah dipesan dikunci. Pengelolaan jumlah stok tersedia pada menu Stok.</p>
    <div class="table-responsive">
        <table class="table align-middle admin-data-table">
            <thead>
                <tr>
                    <th scope="col">Produk</th>
                    <th scope="col">Kategori</th>
                    <th scope="col">Harga</th>
                    <th scope="col">Stok</th>
                    <th scope="col">Ketersediaan</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-end">Tindakan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <?php $imageName = $product['image'] === null ? '' : basename((string) $product['image']); ?>
                    <tr>
                        <td>
                            <div class="admin-product-name">
                                <?php if ($imageName !== ''): ?>
                                    <img src="<?= e($url('media/products/' . rawurlencode($imageName))) ?>" alt="" loading="lazy">
                                <?php else: ?>
                                    <span class="admin-product-placeholder" aria-hidden="true">C</span>
                                <?php endif; ?>
                                <span>
                                    <strong><?= e($product['name']) ?></strong>
                                    <?php if ((int) $product['is_historical'] === 1): ?>
                                        <small>Produk historis</small>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </td>
                        <td><?= e($product['category_name']) ?></td>
                        <td><?= e(formatRupiah($product['price'])) ?></td>
                        <td>
                            <?= (int) $product['stock'] ?>
                            <?php if ((int) $product['stock'] <= (int) $product['min_stock']): ?>
                                <span class="low-stock-badge">Stok rendah</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="availability-badge status-<?= e($product['availability_status']) ?>">
                                <?= e($statusLabels[$product['availability_status']] ?? 'Tidak tersedia') ?>
                            </span>
                        </td>
                        <td><?= (int) $product['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></td>
                        <td class="text-end">
                            <div class="table-actions">
                                <a class="btn btn-sm btn-outline-secondary" href="<?= e($url('admin/products/edit?id=' . (int) $product['id'])) ?>">Ubah</a>
                                <?php if ((int) $product['is_historical'] !== 1): ?>
                                    <form method="post" action="<?= e($url('admin/products/delete')) ?>" onsubmit="return confirm('Hapus produk ini?')">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($products === []): ?>
                    <tr><td colspan="7" class="text-center text-secondary py-4">Belum ada produk.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/admin.php';
