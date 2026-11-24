<?php
ob_start();
?>
<section class="admin-list-card operations-page">
    <div class="section-heading">
        <div><span class="section-kicker">DATA PELANGGAN</span><h2>Pelanggan</h2></div>
        <span class="status-pill"><?= count($customers) ?> pelanggan ditampilkan</span>
    </div>
    <form class="admin-filter-bar customer-search-bar" method="get" action="<?= e($url('admin/customers')) ?>">
        <div><label class="form-label" for="customerSearch">Cari nama atau nomor telepon</label><input class="form-control" id="customerSearch" name="q" maxlength="80" value="<?= e($search) ?>" placeholder="Nama atau nomor telepon"></div>
        <button class="btn btn-cozy-primary" type="submit">Cari pelanggan</button>
    </form>
    <div class="table-responsive">
        <table class="table align-middle admin-data-table">
            <thead><tr><th>Pelanggan</th><th>Pesanan</th><th>Reservasi</th><th>Total transaksi</th><th>Data</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td><strong><?= e($customer['name'] ?: 'Pelanggan') ?></strong><small class="d-block text-secondary"><?= e($customer['phone'] ?: 'Nomor dianonimkan') ?></small></td>
                        <td><?= (int) $customer['order_count'] ?></td>
                        <td><?= (int) $customer['booking_count'] ?></td>
                        <td><?= e(formatRupiah($customer['total_spend'])) ?></td>
                        <td><span class="availability-badge <?= (int) $customer['is_anonymized'] === 1 ? 'status-unavailable' : 'status-available' ?>"><?= (int) $customer['is_anonymized'] === 1 ? 'Anonim' : 'Aktif' ?></span></td>
                        <td><a class="btn btn-sm btn-outline-secondary" href="<?= e($url('admin/customers/detail?id=' . (int) $customer['id'])) ?>">Lihat detail</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($customers === []): ?><tr><td colspan="6" class="text-center text-secondary py-4">Pelanggan tidak ditemukan.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <p class="form-help">Daftar dibatasi hingga 200 pelanggan terbaru. Data identitas dianonimkan sesuai kebijakan retensi.</p>
</section>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/admin.php';
