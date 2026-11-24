<?php
$isEditing = is_array($product ?? null);
$formTitle = $isEditing ? 'Ubah produk' : 'Tambah produk';
$formAction = $isEditing ? 'admin/products/update' : 'admin/products/store';
$statusLabels = [
    'available' => 'Available',
    'sold_out' => 'Sold Out',
    'unavailable' => 'Unavailable',
];
ob_start();
?>
<section class="admin-form-card admin-product-form-card">
    <a class="back-link" href="<?= e($url('admin/products')) ?>">← Kembali ke produk</a>
    <div class="section-heading mt-3">
        <div>
            <span class="section-kicker">KATALOG</span>
            <h2><?= e($formTitle) ?></h2>
        </div>
    </div>
    <form method="post" action="<?= e($url($formAction)) ?>" enctype="multipart/form-data" class="admin-form product-admin-form">
        <?= csrfField() ?>
        <?php if ($isEditing): ?>
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
        <?php endif; ?>
        <div class="mb-3">
            <label class="form-label" for="productName">Nama produk</label>
            <input class="form-control" id="productName" name="name" type="text" maxlength="160" value="<?= e($product['name'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="productCategory">Kategori</label>
            <select class="form-select" id="productCategory" name="category_id" required>
                <option value="">Pilih kategori</option>
                <?php foreach ($categories as $category): ?>
                    <?php $categoryId = (int) $category['id']; ?>
                    <option value="<?= $categoryId ?>"<?= $isEditing && (int) $product['category_id'] === $categoryId ? ' selected' : '' ?>>
                        <?= e($category['name']) ?><?= (int) $category['is_active'] === 1 ? '' : ' (Nonaktif)' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label" for="productDescription">Deskripsi</label>
            <textarea class="form-control" id="productDescription" name="description" rows="4"><?= e($product['description'] ?? '') ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label" for="productImage">Foto produk</label>
            <input class="form-control" id="productImage" name="image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
            <div class="form-text">JPG, PNG, atau WebP. Ukuran maksimal 5 MB. File disimpan di luar web root.</div>
            <?php if ($isEditing && $product['image'] !== null): ?>
                <?php $imageName = basename((string) $product['image']); ?>
                <img class="product-form-preview" src="<?= e($url('media/products/' . rawurlencode($imageName))) ?>" alt="Foto <?= e($product['name']) ?>">
            <?php endif; ?>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="productPrice">Harga (Rp)</label>
                <input class="form-control" id="productPrice" name="price" type="number" min="0.01" step="0.01" value="<?= e($product['price'] ?? '') ?>"<?= $isEditing && (int) $product['is_historical'] === 1 ? ' readonly aria-describedby="priceHistoryHelp"' : ' required' ?>>
                <?php if ($isEditing && (int) $product['is_historical'] === 1): ?>
                    <div class="form-text" id="priceHistoryHelp">Harga dikunci karena produk sudah pernah dipesan.</div>
                <?php endif; ?>
            </div>
            <?php if (!$isEditing): ?>
                <div class="col-md-6">
                    <label class="form-label" for="productStock">Stok awal</label>
                    <input class="form-control" id="productStock" name="stock" type="number" min="0" step="1" value="0" required>
                </div>
            <?php else: ?>
                <div class="col-md-6">
                    <label class="form-label" for="productStockReadOnly">Stok saat ini</label>
                    <input class="form-control" id="productStockReadOnly" type="number" value="<?= (int) $product['stock'] ?>" readonly>
                    <div class="form-text"><a href="<?= e($url('admin/stock')) ?>">Ubah stok melalui menu Stok.</a></div>
                </div>
            <?php endif; ?>
            <div class="col-md-4">
                <label class="form-label" for="minStock">Batas minimum stok</label>
                <input class="form-control" id="minStock" name="min_stock" type="number" min="0" step="1" value="<?= e($product['min_stock'] ?? '0') ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="minOrder">Minimum pembelian</label>
                <input class="form-control" id="minOrder" name="min_order_quantity" type="number" min="1" step="1" value="<?= e($product['min_order_quantity'] ?? '1') ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="maxOrder">Maksimum pembelian</label>
                <input class="form-control" id="maxOrder" name="max_order_quantity" type="number" min="1" step="1" value="<?= e($product['max_order_quantity'] ?? '99') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="availabilityStatus">Ketersediaan</label>
                <select class="form-select" id="availabilityStatus" name="availability_status" required>
                    <?php foreach ($statusLabels as $value => $label): ?>
                        <option value="<?= e($value) ?>"<?= ($product['availability_status'] ?? 'available') === $value ? ' selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-check my-4">
            <input class="form-check-input" id="productActive" name="is_active" type="checkbox" value="1"<?= !isset($product) || (int) $product['is_active'] === 1 ? ' checked' : '' ?>>
            <label class="form-check-label" for="productActive">Produk aktif</label>
        </div>
        <button class="btn btn-cozy-primary" type="submit"><?= $isEditing ? 'Simpan Perubahan' : 'Tambah Produk' ?></button>
    </form>
</section>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/admin.php';
