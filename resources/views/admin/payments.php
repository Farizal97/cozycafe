<?php
$paymentStatuses = [
    'unpaid' => 'Belum dibayar', 'waiting_verification' => 'Menunggu verifikasi',
    'paid' => 'Lunas', 'rejected' => 'Ditolak', 'refunded' => 'Dikembalikan',
];
$renderRefundAction = static function (array $payment) use ($url): string {
    if (!in_array($payment['payment_status'], ['paid', 'refunded'], true)
        || (float) $payment['refunded_amount'] >= (float) $payment['amount']) {
        return '';
    }
    ob_start();
    ?>
    <details class="payment-reject-details">
        <summary class="btn btn-sm btn-outline-secondary">Catat pengembalian</summary>
        <form method="post" action="<?= e($url('admin/payments/refund')) ?>" class="payment-reject-form" onsubmit="return confirm('Catat pengembalian dana ini?')">
            <?= csrfField() ?><input type="hidden" name="payment_id" value="<?= (int) $payment['payment_id'] ?>">
            <label class="form-label" for="refundAmount<?= (int) $payment['payment_id'] ?>">Jumlah (maksimal <?= e(formatRupiah((float) $payment['amount'] - (float) $payment['refunded_amount'])) ?>)</label>
            <input class="form-control" id="refundAmount<?= (int) $payment['payment_id'] ?>" name="amount" type="number" min="0.01" max="<?= e(number_format((float) $payment['amount'] - (float) $payment['refunded_amount'], 2, '.', '')) ?>" step="0.01" required>
            <label class="form-label mt-2" for="refundNote<?= (int) $payment['payment_id'] ?>">Alasan</label>
            <textarea class="form-control" id="refundNote<?= (int) $payment['payment_id'] ?>" name="admin_note" rows="2" maxlength="2000" required></textarea>
            <button class="btn btn-sm btn-outline-danger mt-2" type="submit">Simpan pengembalian</button>
        </form>
    </details>
    <?php
    $html = ob_get_clean();
    return $html === false ? '' : $html;
};
ob_start();
?>
<section class="admin-list-card operations-page">
    <div class="section-heading">
        <div><span class="section-kicker">OPERASIONAL</span><h2>Pembayaran</h2></div>
        <span class="status-pill"><?= count($payments) ?> transaksi ditampilkan</span>
    </div>
    <p class="form-help">Periksa bukti transfer/QRIS atau konfirmasi pembayaran tunai. Penolakan bukti wajib menyertakan alasan.</p>
    <div class="table-responsive">
        <table class="table align-middle admin-data-table">
            <thead><tr><th>Pesanan</th><th>Pelanggan</th><th>Metode</th><th>Jumlah</th><th>Status</th><th>Bukti / tindakan</th></tr></thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><strong><?= e($payment['order_code']) ?></strong><small class="d-block text-secondary"><?= e(formatDateTimeWib($payment['created_at'])) ?></small></td>
                        <td><?= e($payment['customer_name']) ?></td>
                        <td><?= e($payment['payment_method_name']) ?></td>
                        <td class="fw-semibold"><?= e(formatRupiah($payment['amount'])) ?><?php if ((float) $payment['refunded_amount'] > 0): ?><small class="d-block text-secondary">Dikembalikan: <?= e(formatRupiah($payment['refunded_amount'])) ?></small><?php endif; ?></td>
                        <td><span class="availability-badge status-<?= e($payment['payment_status']) ?>"><?= e($paymentStatuses[$payment['payment_status']] ?? '—') ?></span></td>
                        <td>
                            <?php if ($payment['proof_id'] !== null): ?>
                                <a class="btn btn-sm btn-outline-secondary mb-2" target="_blank" rel="noopener" href="<?= e($url('admin/payments/proof?id=' . (int) $payment['proof_id'])) ?>">Lihat bukti</a>
                                <?php if ($payment['proof_status'] === 'pending'): ?>
                                    <div class="table-actions">
                                        <form method="post" action="<?= e($url('admin/payments/verify')) ?>">
                                            <?= csrfField() ?><input type="hidden" name="proof_id" value="<?= (int) $payment['proof_id'] ?>">
                                            <input type="hidden" name="decision" value="approve">
                                            <button class="btn btn-sm btn-cozy-primary" type="submit">Setujui</button>
                                        </form>
                                        <details class="payment-reject-details">
                                            <summary class="btn btn-sm btn-outline-danger">Tolak</summary>
                                            <form method="post" action="<?= e($url('admin/payments/verify')) ?>" class="payment-reject-form">
                                                <?= csrfField() ?><input type="hidden" name="proof_id" value="<?= (int) $payment['proof_id'] ?>">
                                                <input type="hidden" name="decision" value="reject">
                                                <label class="form-label" for="reason-<?= (int) $payment['proof_id'] ?>">Alasan penolakan</label>
                                                <textarea class="form-control" id="reason-<?= (int) $payment['proof_id'] ?>" name="rejection_reason" rows="2" maxlength="2000" required></textarea>
                                                <button class="btn btn-sm btn-danger mt-2" type="submit">Konfirmasi penolakan</button>
                                            </form>
                                        </details>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if ($payment['method_type'] === 'cash' && $payment['payment_status'] === 'unpaid'): ?>
                                <form method="post" action="<?= e($url('admin/payments/cash')) ?>" onsubmit="return confirm('Konfirmasi pembayaran tunai diterima?')">
                                    <?= csrfField() ?><input type="hidden" name="payment_id" value="<?= (int) $payment['payment_id'] ?>">
                                    <button class="btn btn-sm btn-cozy-primary" type="submit">Konfirmasi tunai</button>
                                </form>
                            <?php elseif (in_array($payment['payment_status'], ['paid', 'refunded'], true)): ?>
                                <?= $renderRefundAction($payment) ?>
                            <?php elseif ($payment['proof_id'] === null): ?>
                                <span class="text-secondary">Belum ada bukti</span>
                            <?php elseif ($payment['proof_status'] !== 'pending'): ?>
                                <span class="text-secondary">Bukti sudah diverifikasi</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($payments === []): ?><tr><td colspan="6" class="text-center text-secondary py-4">Belum ada transaksi pembayaran.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/admin.php';
