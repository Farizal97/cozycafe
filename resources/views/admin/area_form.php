<?php
$isEditing = is_array($area);
ob_start();
?>
<section class="admin-form-card operation-form-card">
    <a class="back-link" href="<?= e($url('admin/areas')) ?>">← Kembali ke area</a>
    <span class="section-kicker"><?= $isEditing ? 'PERBARUI AREA' : 'AREA BARU' ?></span>
    <h2><?= $isEditing ? 'Ubah area reservasi' : 'Tambah area reservasi' ?></h2>
    <form method="post" action="<?= e($url($isEditing ? 'admin/areas/update' : 'admin/areas/store')) ?>" class="admin-form">
        <?= csrfField() ?>
        <?php if ($isEditing): ?><input type="hidden" name="area_id" value="<?= (int) $area['id'] ?>"><?php endif; ?>
        <div><label class="form-label" for="areaName">Nama area</label><input class="form-control" id="areaName" name="name" maxlength="100" value="<?= e($area['name'] ?? '') ?>" required></div>
        <div><label class="form-label" for="areaDescription">Deskripsi</label><textarea class="form-control" id="areaDescription" name="description" rows="3"><?= e($area['description'] ?? '') ?></textarea></div>
        <label class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" <?= !$isEditing || (int) $area['is_active'] === 1 ? 'checked' : '' ?>><span>Area aktif dan dapat dipilih pelanggan</span></label>
        <button class="btn btn-cozy-primary" type="submit">Simpan area</button>
    </form>
</section>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/admin.php';
