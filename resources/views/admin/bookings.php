<?php
$bookingStatuses = [
    'pending' => 'Menunggu konfirmasi', 'confirmed' => 'Dikonfirmasi', 'rejected' => 'Ditolak',
    'completed' => 'Selesai', 'cancelled' => 'Dibatalkan', 'cancellation_requested' => 'Minta pembatalan',
    'checked_in' => 'Check-in', 'no_show' => 'Tidak hadir',
];
ob_start();
?>
<section class="admin-list-card operations-page">
    <div class="section-heading">
        <div><span class="section-kicker">OPERASIONAL</span><h2>Reservasi</h2></div>
        <span class="status-pill"><?= count($bookingData['bookings']) ?> reservasi ditampilkan</span>
    </div>
    <form class="admin-filter-bar" method="get" action="<?= e($url('admin/bookings')) ?>">
        <div><label class="form-label" for="bookingFilterDate">Tanggal</label><input class="form-control" type="date" id="bookingFilterDate" name="date" value="<?= e($bookingData['date']) ?>"></div>
        <div>
            <label class="form-label" for="bookingFilterStatus">Status</label>
            <select class="form-select" id="bookingFilterStatus" name="status">
                <option value="">Semua status</option>
                <?php foreach ($bookingStatuses as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $bookingData['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-cozy-primary" type="submit">Terapkan filter</button>
    </form>
    <div class="table-responsive">
        <table class="table align-middle admin-data-table">
            <thead><tr><th>Reservasi</th><th>Pelanggan</th><th>Area / Meja</th><th>Tamu</th><th>Status</th><th>Tindakan</th></tr></thead>
            <tbody>
                <?php foreach ($bookingData['bookings'] as $booking): ?>
                    <tr>
                        <td><strong><?= e($booking['booking_code']) ?></strong><small class="d-block text-secondary"><?= e(date('d/m/Y', strtotime($booking['booking_date']))) ?> · <?= e(substr($booking['slot_start'], 0, 5) . '–' . substr($booking['slot_end'], 0, 5)) ?></small></td>
                        <td><?= e($booking['customer_name']) ?><small class="d-block text-secondary"><?= e($booking['phone']) ?></small></td>
                        <td><?= e($booking['area_names'] ?: '—') ?><small class="d-block text-secondary"><?= e($booking['table_numbers'] ?: 'Belum ditentukan') ?></small></td>
                        <td><?= (int) $booking['guest_count'] ?> orang</td>
                        <td><span class="availability-badge status-<?= e($booking['status']) ?>"><?= e($bookingStatuses[$booking['status']] ?? '—') ?></span></td>
                        <td>
                            <?php if ($booking['cancellation_id'] !== null): ?>
                                <div class="table-actions">
                                    <?php foreach (['approve' => 'Setujui pembatalan', 'reject' => 'Tolak pembatalan'] as $decision => $label): ?>
                                        <form method="post" action="<?= e($url('admin/bookings/cancellation')) ?>" onsubmit="return confirm('<?= e($label) ?>?')">
                                            <?= csrfField() ?><input type="hidden" name="request_id" value="<?= (int) $booking['cancellation_id'] ?>">
                                            <input type="hidden" name="decision" value="<?= e($decision) ?>">
                                            <button class="btn btn-sm <?= $decision === 'approve' ? 'btn-outline-danger' : 'btn-outline-secondary' ?>" type="submit"><?= e($label) ?></button>
                                        </form>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <?php
                                $actions = [];
                                if ($booking['status'] === 'pending') {
                                    $actions = ['confirmed' => 'Konfirmasi', 'rejected' => 'Tolak'];
                                } elseif ($booking['status'] === 'confirmed') {
                                    $actions = ['checked_in' => 'Check-in'];
                                    $slotStart = new DateTimeImmutable(
                                        $booking['booking_date'] . ' ' . $booking['slot_start'],
                                        new DateTimeZone('Asia/Jakarta')
                                    );
                                    if (new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')) >= $slotStart->modify('+30 minutes')) {
                                        $actions['no_show'] = 'Tidak hadir';
                                    }
                                } elseif ($booking['status'] === 'checked_in') {
                                    $actions = ['completed' => 'Selesaikan'];
                                }
                                ?>
                                <?php if ($actions !== []): ?>
                                    <div class="table-actions">
                                        <?php foreach ($actions as $status => $label): ?>
                                            <form method="post" action="<?= e($url('admin/bookings/status')) ?>" onsubmit="return confirm('<?= e($label) ?> reservasi ini?')">
                                                <?= csrfField() ?><input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>">
                                                <input type="hidden" name="status" value="<?= e($status) ?>">
                                                <button class="btn btn-sm <?= $status === 'rejected' || $status === 'no_show' ? 'btn-outline-danger' : 'btn-outline-secondary' ?>" type="submit"><?= e($label) ?></button>
                                            </form>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?><span class="text-secondary">Tidak ada tindakan</span><?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($bookingData['bookings'] === []): ?><tr><td colspan="6" class="text-center text-secondary py-4">Tidak ada reservasi pada filter ini.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/admin.php';
