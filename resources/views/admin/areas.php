<?php
ob_start();
?>
<section class="admin-list-card operations-page">
    <div class="section-heading">
        <div><span class="section-kicker">DENAH KAFE</span><h2>Area Reservasi</h2></div>
        <a class="btn btn-cozy-primary" href="<?= e($url('admin/areas/create')) ?>">Tambah area</a>
    </div>
    <p class="form-help">Area yang masih memiliki meja tidak dapat dihapus. Nonaktifkan area jika sementara tidak menerima reservasi.</p>
    <div class="table-responsive">
        <table class="table align-middle admin-data-table">
            <thead><tr><th>Area</th><th>Deskripsi</th><th>Meja</th><th>Status</th><th>Tindakan</th></tr></thead>
            <tbody>
                <?php foreach ($areas as $area): ?>
                    <tr>
                        <td><strong><?= e($area['name']) ?></strong></td>
                        <td><?= e($area['description'] ?: '—') ?></td>
                        <td><?= (int) $area['table_count'] ?></td>
                        <td><span class="availability-badge <?= (int) $area['is_active'] === 1 ? 'status-available' : 'status-unavailable' ?>"><?= (int) $area['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></span></td>
                        <td><div class="table-actions">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e($url('admin/areas/edit?id=' . (int) $area['id'])) ?>">Ubah</a>
                            <form method="post" action="<?= e($url('admin/areas/delete')) ?>" onsubmit="return confirm('Hapus area ini?')">
                                <?= csrfField() ?><input type="hidden" name="area_id" value="<?= (int) $area['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                            </form>
                        </div></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($areas === []): ?><tr><td colspan="5" class="text-center text-secondary py-4">Belum ada area reservasi.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/admin.php';
