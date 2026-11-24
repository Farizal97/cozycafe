<?php
$activeCategories = count(array_filter(
    $categories,
    static fn (array $category): bool => (int) $category['is_active'] === 1
));
ob_start();
?>
<section class="admin-list-card">
    <div class="section-heading">
        <div>
            <span class="section-kicker">KATALOG</span>
            <h2>Manajemen Kategori</h2>
        </div>
        <a class="btn btn-cozy-primary" href="<?= e($url('admin/categories/create')) ?>">Tambah Kategori</a>
    </div>
    <p class="form-help">Kategori aktif: <?= $activeCategories ?>. Minimal satu kategori harus tetap aktif.</p>
    <div class="table-responsive">
        <table class="table align-middle admin-data-table">
            <thead>
                <tr>
                    <th scope="col">Nama</th>
                    <th scope="col">Deskripsi</th>
                    <th scope="col">Produk</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-end">Tindakan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($category['name']) ?></td>
                        <td><?= e($category['description'] ?? '—') ?></td>
                        <td><?= (int) $category['product_count'] ?></td>
                        <td>
                            <span class="availability-badge <?= (int) $category['is_active'] === 1 ? 'status-available' : 'status-unavailable' ?>">
                                <?= (int) $category['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?>
                            </span>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a class="btn btn-sm btn-outline-secondary" href="<?= e($url('admin/categories/edit?id=' . (int) $category['id'])) ?>">Ubah</a>
                                <form method="post" action="<?= e($url('admin/categories/delete')) ?>" onsubmit="return confirm('Hapus kategori ini?')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="category_id" value="<?= (int) $category['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($categories === []): ?>
                    <tr><td colspan="5" class="text-center text-secondary py-4">Belum ada kategori.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/admin.php';
