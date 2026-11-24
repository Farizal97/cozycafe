<?php
$reportLabels = [
    'transactions' => 'Transaksi',
    'orders' => 'Pesanan',
    'payments' => 'Pembayaran',
    'bookings' => 'Reservasi',
    'revenue' => 'Pendapatan',
];
$orderTypes = ['dine_in' => 'Dine-in', 'takeaway' => 'Takeaway'];
$paymentStatuses = [
    'unpaid' => 'Belum dibayar',
    'waiting_verification' => 'Menunggu verifikasi',
    'paid' => 'Lunas',
    'rejected' => 'Ditolak',
    'refunded' => 'Dikembalikan',
];
$orderStatuses = [
    'pending' => 'Menunggu konfirmasi',
    'processing' => 'Diproses',
    'ready' => 'Siap',
    'completed' => 'Selesai',
    'cancelled' => 'Dibatalkan',
    'expired' => 'Kedaluwarsa',
    'cancellation_requested' => 'Pengajuan pembatalan',
    'cancellation_rejected' => 'Pembatalan ditolak',
];
$bookingStatuses = [
    'pending' => 'Menunggu konfirmasi',
    'confirmed' => 'Dikonfirmasi',
    'rejected' => 'Ditolak',
    'completed' => 'Selesai',
    'cancelled' => 'Dibatalkan',
    'cancellation_requested' => 'Pengajuan pembatalan',
    'checked_in' => 'Check-in',
    'no_show' => 'Tidak hadir',
];
$isBookingReport = $reportFilters['report'] === 'bookings';
$exportUrl = $url('admin/reports/export?' . http_build_query($reportFilters));
$paginationUrl = static function (int $page) use ($url, $reportFilters): string {
    return $url('admin/reports?' . http_build_query(array_merge($reportFilters, ['page' => $page])));
};
ob_start();
?>
<section class="admin-list-card report-page">
    <div class="section-heading">
        <div>
            <span class="section-kicker">ANALISIS OPERASIONAL</span>
            <h2>Laporan CozyCafe</h2>
        </div>
        <div class="report-actions">
            <a class="btn btn-outline-secondary" href="<?= e($exportUrl) ?>">Unduh CSV (Excel)</a>
            <button class="btn btn-cozy-primary" type="button" onclick="window.print()">Cetak / Simpan PDF</button>
        </div>
    </div>

    <form class="report-filters" method="get" action="<?= e($url('admin/reports')) ?>">
        <div>
            <label class="form-label" for="reportType">Jenis laporan</label>
            <select class="form-select" id="reportType" name="report">
                <?php foreach ($reportLabels as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $reportFilters['report'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label" for="reportStart">Dari tanggal</label>
            <input class="form-control" id="reportStart" type="date" name="start_date" value="<?= e($reportFilters['start_date']) ?>" required>
        </div>
        <div>
            <label class="form-label" for="reportEnd">Sampai tanggal</label>
            <input class="form-control" id="reportEnd" type="date" name="end_date" value="<?= e($reportFilters['end_date']) ?>" required>
        </div>
        <?php if (!$isBookingReport): ?>
            <div>
                <label class="form-label" for="reportOrderType">Jenis transaksi</label>
                <select class="form-select" id="reportOrderType" name="order_type">
                    <option value="">Semua jenis</option>
                    <?php foreach ($orderTypes as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $reportFilters['order_type'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label" for="reportMethod">Metode pembayaran</label>
                <select class="form-select" id="reportMethod" name="payment_method_id">
                    <option value="">Semua metode</option>
                    <?php foreach ($paymentMethods as $method): ?>
                        <option value="<?= (int) $method['id'] ?>" <?= $reportFilters['payment_method_id'] === (int) $method['id'] ? 'selected' : '' ?>>
                            <?= e($method['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label" for="reportPaymentStatus">Status pembayaran</label>
                <select class="form-select" id="reportPaymentStatus" name="payment_status">
                    <option value="">Semua status</option>
                    <?php foreach ($paymentStatuses as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $reportFilters['payment_status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <button class="btn btn-cozy-primary report-filter-submit" type="submit">Terapkan filter</button>
    </form>

    <div class="report-summary">
        <?php foreach ($reportData['summary'] as $metric): ?>
            <article class="report-summary-card">
                <span><?= e($metric['label']) ?></span>
                <strong><?= e($metric['value']) ?></strong>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="report-results-heading">
        <h3>Laporan <?= e($reportLabels[$reportFilters['report']]) ?></h3>
        <span><?= number_format($reportData['total_rows'], 0, ',', '.') ?> baris · <?= e($reportFilters['start_date']) ?> – <?= e($reportFilters['end_date']) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle admin-data-table report-table">
            <?php if ($reportFilters['report'] === 'bookings'): ?>
                <thead><tr><th>Tanggal</th><th>Slot</th><th>Kode Reservasi</th><th>Area / Meja</th><th>Tamu</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($reportData['rows'] as $row): ?>
                        <tr>
                            <td><?= e(date('d/m/Y', strtotime($row['booking_date']))) ?></td>
                            <td><?= e(substr($row['slot_start'], 0, 5) . '–' . substr($row['slot_end'], 0, 5)) ?></td>
                            <td><strong><?= e($row['booking_code']) ?></strong></td>
                            <td><?= e($row['area_names'] ?: '—') ?><?= $row['table_numbers'] ? ' · ' . e($row['table_numbers']) : '' ?></td>
                            <td><?= (int) $row['guest_count'] ?> orang</td>
                            <td><?= e($bookingStatuses[$row['status']] ?? 'Tidak diketahui') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            <?php elseif ($reportFilters['report'] === 'revenue'): ?>
                <thead><tr><th>Tanggal</th><th>Jumlah pesanan</th><th class="text-end">Pendapatan terbayar</th></tr></thead>
                <tbody>
                    <?php foreach ($reportData['rows'] as $row): ?>
                        <tr>
                            <td><?= e(date('d/m/Y', strtotime($row['report_date']))) ?></td>
                            <td><?= (int) $row['order_count'] ?></td>
                            <td class="text-end fw-semibold"><?= e(formatRupiah($row['revenue'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            <?php elseif ($reportFilters['report'] === 'payments'): ?>
                <thead><tr><th>Tanggal</th><th>Kode Pesanan</th><th>Jenis</th><th>Metode</th><th>Status pembayaran</th><th class="text-end">Jumlah</th></tr></thead>
                <tbody>
                    <?php foreach ($reportData['rows'] as $row): ?>
                        <tr>
                            <td><?= e(formatDateTimeWib($row['payment_date'])) ?></td>
                            <td><strong><?= e($row['order_code']) ?></strong></td>
                            <td><?= e($orderTypes[$row['order_type']] ?? '—') ?></td>
                            <td><?= e($row['payment_method_name']) ?></td>
                            <td><?= e($paymentStatuses[$row['payment_status']] ?? 'Tidak diketahui') ?></td>
                            <td class="text-end"><?= e(formatRupiah($row['amount'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            <?php elseif ($reportFilters['report'] === 'orders'): ?>
                <thead><tr><th>Tanggal</th><th>Kode Pesanan</th><th>Produk</th><th>Jumlah</th><th>Status pembayaran</th><th>Status pesanan</th><th class="text-end">Subtotal</th></tr></thead>
                <tbody>
                    <?php foreach ($reportData['rows'] as $row): ?>
                        <tr>
                            <td><?= e(formatDateTimeWib($row['created_at'])) ?></td>
                            <td><strong><?= e($row['order_code']) ?></strong><small class="d-block text-secondary"><?= e($orderTypes[$row['order_type']] ?? '—') ?></small></td>
                            <td><?= e($row['product_name']) ?></td>
                            <td><?= (int) $row['quantity'] ?></td>
                            <td><?= e($paymentStatuses[$row['payment_status']] ?? 'Tidak diketahui') ?></td>
                            <td><?= e($orderStatuses[$row['order_status']] ?? 'Tidak diketahui') ?></td>
                            <td class="text-end"><?= e(formatRupiah($row['subtotal'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            <?php else: ?>
                <thead><tr><th>Tanggal</th><th>Kode Pesanan</th><th>Jenis</th><th>Metode pembayaran</th><th>Status pembayaran</th><th>Status pesanan</th><th class="text-end">Total</th></tr></thead>
                <tbody>
                    <?php foreach ($reportData['rows'] as $row): ?>
                        <tr>
                            <td><?= e(formatDateTimeWib($row['created_at'])) ?></td>
                            <td><strong><?= e($row['order_code']) ?></strong></td>
                            <td><?= e($orderTypes[$row['order_type']] ?? '—') ?></td>
                            <td><?= e($row['payment_method_name']) ?></td>
                            <td><?= e($paymentStatuses[$row['payment_status']] ?? 'Tidak diketahui') ?></td>
                            <td><?= e($orderStatuses[$row['order_status']] ?? 'Tidak diketahui') ?></td>
                            <td class="text-end"><?= e(formatRupiah($row['total_amount'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            <?php endif; ?>
            <?php if ($reportData['rows'] === []): ?>
                <tbody><tr><td colspan="8" class="text-center text-secondary py-4">Tidak ada data pada rentang dan filter ini.</td></tr></tbody>
            <?php endif; ?>
        </table>
    </div>

    <?php if ($reportData['total_pages'] > 1): ?>
        <nav class="report-pagination" aria-label="Halaman laporan">
            <?php if ($reportData['page'] > 1): ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e($paginationUrl($reportData['page'] - 1)) ?>">Sebelumnya</a>
            <?php endif; ?>
            <span>Halaman <?= (int) $reportData['page'] ?> dari <?= (int) $reportData['total_pages'] ?></span>
            <?php if ($reportData['page'] < $reportData['total_pages']): ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e($paginationUrl($reportData['page'] + 1)) ?>">Berikutnya</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
    <p class="report-footnote">Pendapatan menghitung pembayaran berstatus lunas. Ekspor CSV dapat dibuka di Excel; gunakan Cetak / Simpan PDF untuk menyimpan laporan sebagai PDF.</p>
</section>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/admin.php';
