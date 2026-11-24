<?php
$statusLabels = [
    'available' => 'Available',
    'sold_out' => 'Sold Out',
    'unavailable' => 'Unavailable',
];
$lowStockCount = count(array_filter(
    $products,
    static fn (array $product): bool => (int) $product['stock'] <= (int) $product['min_stock']
));
ob_start();
?>
<section class="admin-list-card">
    <div class="section-heading">
        <div>
            <span class="section-kicker">INVENTARIS</span>
            <h2>Manajemen Stok</h2>
        </div>
        <span class="status-pill"><?= $lowStockCount ?> produk stok rendah</span>
    </div>

    <div class="stock-add-panel">
        <div>
            <h3>Tambah stok</h3>
            <p>Jumlah tambahan dijumlahkan dengan stok saat ini.</p>
        </div>
        <form method="post" action="<?= e($url('admin/stock/add')) ?>" class="stock-add-form">
            <?= csrfField() ?>
            <label class="visually-hidden" for="stockProduct">Pilih produk</label>
            <select class="form-select" id="stockProduct" name="product_id" required>
                <option value="">Pilih produk</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= (int) $product['id'] ?>"><?= e($product['name']) ?> — stok <?= (int) $product['stock'] ?></option>
                <?php endforeach; ?>
            </select>
            <label class="visually-hidden" for="stockQuantity">Jumlah tambahan</label>
            <input class="form-control" id="stockQuantity" name="quantity" type="number" min="1" step="1" placeholder="Jumlah" required>
            <button class="btn btn-cozy-primary" type="submit">Tambah Stok</button>
        </form>
    </div>

    <form id="bulkStatusForm" method="post" action="<?= e($url('admin/stock/bulk-status')) ?>">
        <?= csrfField() ?>
        <div class="bulk-status-bar">
            <strong>Ubah status massal</strong>
            <label class="visually-hidden" for="bulkStatus">Status massal</label>
            <select class="form-select" id="bulkStatus" name="availability_status" required>
                <?php foreach ($statusLabels as $value => $label): ?>
                    <option value="<?= e($value) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-outline-secondary" type="submit">Terapkan ke pilihan</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table align-middle admin-data-table stock-table">
            <thead>
                <tr>
                    <th scope="col"><span class="visually-hidden">Pilih</span></th>
                    <th scope="col">Produk</th>
                    <th scope="col">Kategori</th>
                    <th scope="col">Stok</th>
                    <th scope="col">Minimum</th>
                    <th scope="col">Status</th>
                    <th scope="col">Ubah Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <?php $isLowStock = (int) $product['stock'] <= (int) $product['min_stock']; ?>
                    <tr<?= $isLowStock ? ' class="low-stock-row"' : '' ?>>
                        <td>
                            <input class="form-check-input" form="bulkStatusForm" type="checkbox" name="product_ids[]" value="<?= (int) $product['id'] ?>" aria-label="Pilih <?= e($product['name']) ?>">
                        </td>
                        <td>
                            <strong><?= e($product['name']) ?></strong>
                            <?php if ((int) $product['is_active'] !== 1): ?><small class="d-block text-secondary">Produk nonaktif</small><?php endif; ?>
                        </td>
                        <td><?= e($product['category_name']) ?></td>
                        <td>
                            <strong><?= (int) $product['stock'] ?></strong>
                            <?php if ($isLowStock): ?><span class="low-stock-badge">Stok rendah</span><?php endif; ?>
                        </td>
                        <td><?= (int) $product['min_stock'] ?></td>
                        <td>
                            <span class="availability-badge status-<?= e($product['availability_status']) ?>">
                                <?= e($statusLabels[$product['availability_status']] ?? 'Unavailable') ?>
                            </span>
                        </td>
                        <td>
                            <form method="post" action="<?= e($url('admin/stock/status')) ?>" class="inline-status-form">
                                <?= csrfField() ?>
                                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                <label class="visually-hidden" for="status-<?= (int) $product['id'] ?>">Status <?= e($product['name']) ?></label>
                                <select class="form-select form-select-sm" id="status-<?= (int) $product['id'] ?>" name="availability_status">
                                    <?php foreach ($statusLabels as $value => $label): ?>
                                        <option value="<?= e($value) ?>"<?= $product['availability_status'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-sm btn-outline-secondary" type="submit">Simpan</button>
                            </form>
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
