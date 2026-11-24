<?php
$isEditing = is_array($category ?? null);
$formTitle = $isEditing ? 'Ubah kategori' : 'Tambah kategori';
$formAction = $isEditing ? 'admin/categories/update' : 'admin/categories/store';
ob_start();
?>
<section class="admin-form-card">
    <a class="back-link" href="<?= e($url('admin/categories')) ?>">← Kembali ke kategori</a>
    <div class="section-heading mt-3">
        <div>
            <span class="section-kicker">KATALOG</span>
            <h2><?= e($formTitle) ?></h2>
        </div>
    </div>
    <form method="post" action="<?= e($url($formAction)) ?>" class="admin-form">
        <?= csrfField() ?>
        <?php if ($isEditing): ?>
            <input type="hidden" name="category_id" value="<?= (int) $category['id'] ?>">
        <?php endif; ?>
        <div class="mb-3">
            <label class="form-label" for="categoryName">Nama kategori</label>
            <input class="form-control" id="categoryName" name="name" type="text" maxlength="120" value="<?= e($category['name'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="categoryDescription">Deskripsi</label>
            <textarea class="form-control" id="categoryDescription" name="description" rows="4"><?= e($category['description'] ?? '') ?></textarea>
        </div>
        <div class="form-check mb-4">
            <input class="form-check-input" id="categoryActive" name="is_active" type="checkbox" value="1"<?= !isset($category) || (int) $category['is_active'] === 1 ? ' checked' : '' ?>>
            <label class="form-check-label" for="categoryActive">Kategori aktif</label>
        </div>
        <button class="btn btn-cozy-primary" type="submit"><?= $isEditing ? 'Simpan Perubahan' : 'Tambah Kategori' ?></button>
    </form>
</section>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/admin.php';
