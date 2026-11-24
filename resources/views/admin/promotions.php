<?php
$discountLabel = static function (array $row): string {
    return $row['discount_type'] === 'percentage'
        ? rtrim(rtrim(number_format((float) $row['discount_value'], 2, ',', '.'), '0'), ',') . '%'
        : formatRupiah($row['discount_value']);
};
ob_start();
?>
<section class="operations-page">
    <div class="section-heading">
        <div><span class="section-kicker">PELANGGAN & PROMOSI</span><h2>Voucher dan promosi</h2><p class="text-secondary mb-0">Kelola kode voucher pelanggan dan diskon otomatis pada checkout.</p></div>
    </div>
    <div class="operations-two-column">
        <section class="admin-list-card">
            <div class="section-heading">
                <div><span class="section-kicker">KODE PROMO</span><h2 class="h4">Voucher</h2></div>
                <a class="btn btn-cozy-primary" href="<?= e($url('admin/vouchers/create')) ?>">Buat voucher</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle admin-data-table">
                    <thead><tr><th>Kode</th><th>Diskon</th><th>Penggunaan</th><th>Status</th><th>Tindakan</th></tr></thead>
                    <tbody>
                        <?php foreach ($promotionData['vouchers'] as $voucher): ?>
                            <?php $isCurrent = (int) $voucher['is_active'] === 1 && strtotime($voucher['start_date']) <= time() && strtotime($voucher['end_date']) >= time(); ?>
                            <tr>
                                <td><strong class="font-monospace"><?= e($voucher['code']) ?></strong><small class="d-block text-secondary">Berlaku s.d. <?= e(formatDateTimeWib($voucher['end_date'])) ?></small></td>
                                <td><?= e($discountLabel($voucher)) ?><small class="d-block text-secondary">Min. <?= e(formatRupiah($voucher['minimum_purchase'])) ?></small></td>
                                <td><?= (int) $voucher['usage_count'] ?><?= $voucher['total_usage_limit'] !== null ? ' / ' . (int) $voucher['total_usage_limit'] : '' ?></td>
                                <td><span class="availability-badge <?= $isCurrent ? 'status-available' : 'status-unavailable' ?>"><?= $isCurrent ? 'Aktif' : 'Tidak aktif' ?></span></td>
                                <td><div class="table-actions">
                                    <a class="btn btn-sm btn-outline-secondary" href="<?= e($url('admin/vouchers/edit?id=' . (int) $voucher['id'])) ?>">Ubah</a>
                                    <?php if ((int) $voucher['usage_count'] === 0): ?>
                                        <form method="post" action="<?= e($url('admin/vouchers/delete')) ?>" onsubmit="return confirm('Hapus voucher ini?')">
                                            <?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $voucher['id'] ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                                        </form>
                                    <?php endif; ?>
                                </div></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($promotionData['vouchers'] === []): ?><tr><td colspan="5" class="text-center text-secondary py-4">Belum ada voucher.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <section class="admin-list-card">
            <div class="section-heading">
                <div><span class="section-kicker">DISKON OTOMATIS</span><h2 class="h4">Promosi</h2></div>
                <a class="btn btn-cozy-primary" href="<?= e($url('admin/promotions/create')) ?>">Buat promosi</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle admin-data-table">
                    <thead><tr><th>Nama promosi</th><th>Diskon</th><th>Masa berlaku</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($promotionData['promotions'] as $promotion): ?>
                            <?php $isCurrent = (int) $promotion['is_active'] === 1 && strtotime($promotion['start_date']) <= time() && strtotime($promotion['end_date']) >= time(); ?>
                            <tr>
                                <td><strong><?= e($promotion['name']) ?></strong><small class="d-block text-secondary">Min. <?= e(formatRupiah($promotion['minimum_purchase'])) ?></small></td>
                                <td><?= e($discountLabel($promotion)) ?></td>
                                <td><?= e(formatDateTimeWib($promotion['start_date'])) ?><small class="d-block text-secondary">s.d. <?= e(formatDateTimeWib($promotion['end_date'])) ?></small></td>
                                <td><span class="availability-badge <?= $isCurrent ? 'status-available' : 'status-unavailable' ?>"><?= $isCurrent ? 'Aktif' : 'Tidak aktif' ?></span></td>
                                <td><div class="table-actions">
                                    <a class="btn btn-sm btn-outline-secondary" href="<?= e($url('admin/promotions/edit?id=' . (int) $promotion['id'])) ?>">Ubah</a>
                                    <form method="post" action="<?= e($url('admin/promotions/delete')) ?>" onsubmit="return confirm('Hapus promosi ini?')">
                                        <?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $promotion['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                                    </form>
                                </div></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($promotionData['promotions'] === []): ?><tr><td colspan="5" class="text-center text-secondary py-4">Belum ada promosi otomatis.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</section>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/admin.php';
