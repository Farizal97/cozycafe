<?php
ob_start();
?>
<section class="admin-list-card operations-page">
    <div class="section-heading">
        <div><span class="section-kicker">DENAH KAFE</span><h2>Meja</h2></div>
        <a class="btn btn-cozy-primary" href="<?= e($url('admin/tables/create')) ?>">Tambah meja</a>
    </div>
    <p class="form-help">Posisi denah memakai koordinat 0–100%. Meja dengan reservasi aktif tidak dapat dinonaktifkan.</p>
    <div class="table-responsive">
        <table class="table align-middle admin-data-table">
            <thead><tr><th>Nomor meja</th><th>Area</th><th>Kapasitas</th><th>Posisi denah</th><th>Reservasi aktif</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($tables as $table): ?>
                    <tr>
                        <td><strong><?= e($table['table_number']) ?></strong></td>
                        <td><?= e($table['area_name']) ?></td>
                        <td><?= (int) $table['capacity'] ?> kursi</td>
                        <td><?= e($table['position_x']) ?>%, <?= e($table['position_y']) ?>%</td>
                        <td><?= (int) $table['active_bookings'] ?></td>
                        <td><span class="availability-badge <?= (int) $table['is_active'] === 1 ? 'status-available' : 'status-unavailable' ?>"><?= (int) $table['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></span></td>
                        <td><a class="btn btn-sm btn-outline-secondary" href="<?= e($url('admin/tables/edit?id=' . (int) $table['id'])) ?>">Ubah</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($tables === []): ?><tr><td colspan="7" class="text-center text-secondary py-4">Belum ada meja.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/admin.php';
