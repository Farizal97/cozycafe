<?php
$orderStatuses = [
    'pending' => 'Menunggu konfirmasi', 'processing' => 'Diproses', 'ready' => 'Siap',
    'completed' => 'Selesai', 'cancelled' => 'Dibatalkan', 'expired' => 'Kedaluwarsa',
    'cancellation_requested' => 'Pengajuan pembatalan', 'cancellation_rejected' => 'Pembatalan ditolak',
];
$paymentStatuses = [
    'unpaid' => 'Belum dibayar', 'waiting_verification' => 'Menunggu verifikasi',
    'paid' => 'Lunas', 'rejected' => 'Ditolak', 'refunded' => 'Dikembalikan',
];
ob_start();
?>
<section class="admin-list-card operations-page">
    <div class="section-heading">
        <div><span class="section-kicker">OPERASIONAL</span><h2>Pesanan</h2></div>
        <span class="status-pill"><?= count($orderData['orders']) ?> pesanan ditampilkan</span>
    </div>
    <form class="admin-filter-bar" method="get" action="<?= e($url('admin/orders')) ?>">
        <div><label class="form-label" for="orderStart">Dari tanggal</label><input class="form-control" id="orderStart" type="date" name="start_date" value="<?= e($orderData['filters']['start_date']) ?>" required></div>
        <div><label class="form-label" for="orderEnd">Sampai tanggal</label><input class="form-control" id="orderEnd" type="date" name="end_date" value="<?= e($orderData['filters']['end_date']) ?>" required></div>
        <div>
            <label class="form-label" for="orderStatus">Status pesanan</label>
            <select class="form-select" id="orderStatus" name="status">
                <option value="">Semua status</option>
                <?php foreach ($orderStatuses as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $orderData['filters']['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-cozy-primary" type="submit">Terapkan filter</button>
    </form>
    <p class="form-help">Filter tanggal dan status mengikuti alur operasional. Maksimal 200 pesanan terbaru ditampilkan.</p>
    <div class="table-responsive">
        <table class="table align-middle admin-data-table">
            <thead><tr><th>Pesanan</th><th>Pelanggan</th><th>Jenis</th><th>Pembayaran</th><th>Status</th><th class="text-end">Total</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($orderData['orders'] as $order): ?>
                    <tr>
                        <td><strong><?= e($order['order_code']) ?></strong><small class="d-block text-secondary"><?= e(formatDateTimeWib($order['created_at'])) ?></small></td>
                        <td><?= e($order['customer_name']) ?><small class="d-block text-secondary"><?= e($order['phone']) ?></small></td>
                        <td><?= $order['order_type'] === 'dine_in' ? 'Dine-in' : 'Takeaway' ?></td>
                        <td><span class="status-pill"><?= e($paymentStatuses[$order['payment_status']] ?? '—') ?></span><small class="d-block text-secondary"><?= e($order['payment_method_name']) ?></small></td>
                        <td><span class="availability-badge status-<?= e($order['order_status']) ?>"><?= e($orderStatuses[$order['order_status']] ?? '—') ?></span></td>
                        <td class="text-end fw-semibold"><?= e(formatRupiah($order['total_amount'])) ?></td>
                        <td><a class="btn btn-sm btn-outline-secondary" href="<?= e($url('admin/orders/detail?id=' . (int) $order['id'])) ?>">Detail</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($orderData['orders'] === []): ?><tr><td colspan="7" class="text-center text-secondary py-4">Tidak ada pesanan pada filter ini.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/admin.php';
