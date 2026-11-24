<?php
$isEditing = is_array($table);
ob_start();
?>
<section class="admin-form-card operation-form-card">
    <a class="back-link" href="<?= e($url('admin/tables')) ?>">← Kembali ke meja</a>
    <span class="section-kicker"><?= $isEditing ? 'PERBARUI MEJA' : 'MEJA BARU' ?></span>
    <h2><?= $isEditing ? 'Ubah data meja' : 'Tambah meja' ?></h2>
    <form method="post" action="<?= e($url($isEditing ? 'admin/tables/update' : 'admin/tables/store')) ?>" class="admin-form">
        <?= csrfField() ?>
        <?php if ($isEditing): ?><input type="hidden" name="table_id" value="<?= (int) $table['id'] ?>"><?php endif; ?>
        <div><label class="form-label" for="tableNumber">Nomor meja</label><input class="form-control" id="tableNumber" name="table_number" maxlength="40" value="<?= e($table['table_number'] ?? '') ?>" required></div>
        <div><label class="form-label" for="tableArea">Area</label><select class="form-select" id="tableArea" name="area_id" required><option value="">Pilih area</option><?php foreach ($areas as $area): ?><option value="<?= (int) $area['id'] ?>" <?= (int) ($table['area_id'] ?? 0) === (int) $area['id'] ? 'selected' : '' ?> <?= (int) $area['is_active'] !== 1 ? 'disabled' : '' ?>><?= e($area['name']) ?><?= (int) $area['is_active'] !== 1 ? ' (nonaktif)' : '' ?></option><?php endforeach; ?></select></div>
        <div><label class="form-label" for="tableCapacity">Kapasitas tamu</label><input class="form-control" id="tableCapacity" name="capacity" type="number" min="1" max="255" value="<?= e($table['capacity'] ?? 2) ?>" required></div>
        <div class="table-position-fields">
            <div><label class="form-label" for="tableX">Posisi horizontal (%)</label><input class="form-control" id="tableX" name="position_x" type="number" min="0" max="100" step="0.01" value="<?= e($table['position_x'] ?? 50) ?>" required></div>
            <div><label class="form-label" for="tableY">Posisi vertikal (%)</label><input class="form-control" id="tableY" name="position_y" type="number" min="0" max="100" step="0.01" value="<?= e($table['position_y'] ?? 50) ?>" required></div>
        </div>
        <label class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" <?= !$isEditing || (int) $table['is_active'] === 1 ? 'checked' : '' ?>><span>Meja aktif dan dapat dipesan</span></label>
        <button class="btn btn-cozy-primary" type="submit">Simpan meja</button>
    </form>
</section>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/admin.php';
