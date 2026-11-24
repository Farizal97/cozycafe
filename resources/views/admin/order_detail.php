<?php
$orderStatuses = [
    'pending' => 'Menunggu konfirmasi', 'processing' => 'Diproses', 'ready' => 'Siap',
    'completed' => 'Selesai', 'cancelled' => 'Dibatalkan', 'expired' => 'Kedaluwarsa',
    'cancellation_requested' => 'Pengajuan pembatalan', 'cancellation_rejected' => 'Pembatalan ditolak',
];
$transitions = [
    'pending' => ['processing', 'cancelled', 'expired'],
    'processing' => ['ready', 'cancelled'],
    'ready' => ['completed', 'cancelled'],
];
$availableStatuses = $transitions[$order['order_status']] ?? [];
ob_start();
?>
<section class="admin-list-card operations-page">
    <div class="section-heading">
        <div><span class="section-kicker">DETAIL PESANAN</span><h2><?= e($order['order_code']) ?></h2></div>
        <a class="btn btn-outline-secondary" href="<?= e($url('admin/orders')) ?>">Kembali ke daftar</a>
    </div>
    <div class="operation-summary-grid">
        <article><span>Pelanggan</span><strong><?= e($order['customer_name']) ?></strong><small><?= e($order['phone']) ?></small></article>
        <article><span>Status pesanan</span><strong><?= e($orderStatuses[$order['order_status']] ?? '—') ?></strong><small><?= $order['order_type'] === 'dine_in' ? 'Dine-in' : 'Takeaway' ?><?= $order['table_number'] ? ' · ' . e($order['table_number']) : '' ?></small></article>
        <article><span>Pembayaran</span><strong><?= e($order['payment_method_name']) ?></strong><small><?= e($order['payment_status']) ?></small></article>
        <article><span>Total</span><strong><?= e(formatRupiah($order['total_amount'])) ?></strong><small><?= e(formatDateTimeWib($order['created_at'])) ?></small></article>
    </div>

    <div class="operations-two-column">
        <section class="operation-panel">
            <h3>Item pesanan</h3>
            <?php foreach ($order['items'] as $item): ?>
                <div class="operation-line-item">
                    <div><strong><?= e($item['product_name_snapshot']) ?></strong><small><?= (int) $item['quantity'] ?> × <?= e(formatRupiah($item['price_snapshot'])) ?></small></div>
                    <strong><?= e(formatRupiah($item['subtotal'])) ?></strong>
                </div>
            <?php endforeach; ?>
            <div class="operation-total-line"><span>Subtotal</span><strong><?= e(formatRupiah($order['subtotal'])) ?></strong></div>
            <div class="operation-total-line"><span>Diskon</span><strong>−<?= e(formatRupiah($order['discount_amount'])) ?></strong></div>
            <div class="operation-total-line"><span>Pajak & layanan</span><strong><?= e(formatRupiah((float) $order['tax_amount'] + (float) $order['service_charge'])) ?></strong></div>
            <div class="operation-total-line operation-grand-total"><span>Total</span><strong><?= e(formatRupiah($order['total_amount'])) ?></strong></div>
        </section>
        <section class="operation-panel">
            <h3>Perbarui status</h3>
            <?php if ($availableStatuses !== []): ?>
                <form method="post" action="<?= e($url('admin/orders/status')) ?>" class="admin-operation-form">
                    <?= csrfField() ?>
                    <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                    <label class="form-label" for="nextOrderStatus">Status berikutnya</label>
                    <select class="form-select" id="nextOrderStatus" name="order_status" required>
                        <?php foreach ($availableStatuses as $status): ?>
                            <option value="<?= e($status) ?>"><?= e($orderStatuses[$status]) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($order['confirmed_at'] !== null): ?>
                        <label class="form-check operation-check">
                            <input class="form-check-input" type="checkbox" name="restore_stock" value="1">
                            <span>Kembalikan stok saat pesanan dibatalkan</span>
                        </label>
                    <?php endif; ?>
                    <p class="form-help">Stok dikurangi otomatis saat pesanan mulai diproses. Pembatalan tidak mengembalikan stok kecuali opsi dipilih.</p>
                    <button class="btn btn-cozy-primary" type="submit" onclick="return confirm('Simpan perubahan status pesanan?')">Simpan status</button>
                </form>
            <?php else: ?>
                <p class="text-secondary mb-0"><?= $order['order_status'] === 'cancellation_requested' ? 'Tinjau dan proses permintaan pembatalan di bawah.' : 'Pesanan sudah ditutup dan tidak dapat diubah.' ?></p>
            <?php endif; ?>
        </section>
    </div>

    <?php if ($order['cancellations'] !== []): ?>
        <section class="operation-panel mt-4">
            <h3>Permintaan pembatalan</h3>
            <?php foreach ($order['cancellations'] as $request): ?>
                <div class="operation-line-item">
                    <div><strong><?= e($request['status']) ?> · <?= e(formatDateTimeWib($request['created_at'])) ?></strong><small><?= e($request['reason']) ?></small></div>
                    <?php if ($request['status'] === 'pending'): ?>
                        <div class="table-actions">
                            <?php foreach (['approve' => 'Setujui', 'reject' => 'Tolak'] as $decision => $label): ?>
                                <form method="post" action="<?= e($url('admin/orders/cancellation')) ?>" onsubmit="return confirm('<?= e($label) ?> permintaan pembatalan ini?')">
                                    <?= csrfField() ?><input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                                    <input type="hidden" name="decision" value="<?= e($decision) ?>">
                                    <button class="btn btn-sm <?= $decision === 'approve' ? 'btn-outline-danger' : 'btn-outline-secondary' ?>" type="submit"><?= e($label) ?></button>
                                </form>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($request['admin_response']): ?><span><?= e($request['admin_response']) ?></span><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <section class="operation-panel mt-4">
        <h3>Riwayat status</h3>
        <ol class="operation-timeline">
            <?php foreach ($order['histories'] as $history): ?>
                <li><strong><?= e($orderStatuses[$history['new_status']] ?? $history['new_status']) ?></strong><span><?= e(formatDateTimeWib($history['changed_at'])) ?> · <?= e($history['username'] ?? 'Sistem') ?></span></li>
            <?php endforeach; ?>
        </ol>
    </section>
</section>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/admin.php';
