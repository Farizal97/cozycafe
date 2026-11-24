<?php
$actionLabels = [
    'admin_login' => 'Admin masuk',
    'admin_logout' => 'Admin keluar',
    'admin_login_failed' => 'Login admin gagal',
    'admin_login_locked' => 'Akun admin dikunci',
    'admin_profile_updated' => 'Profil admin diperbarui',
    'admin_password_updated' => 'Password admin diperbarui',
    'category_created' => 'Kategori dibuat',
    'category_updated' => 'Kategori diperbarui',
    'category_deleted' => 'Kategori dihapus',
    'product_created' => 'Produk dibuat',
    'product_updated' => 'Produk diperbarui',
    'product_deleted' => 'Produk dihapus',
    'product_availability_updated' => 'Ketersediaan produk diperbarui',
    'stock_added' => 'Stok ditambahkan',
];
$entityLabels = [
    'admin' => 'Admin',
    'category' => 'Kategori',
    'product' => 'Produk',
    'order' => 'Pesanan',
    'payment' => 'Pembayaran',
    'booking' => 'Reservasi',
];
$formatLogData = static function (?string $value): string {
    if ($value === null || $value === '') {
        return '—';
    }
    $decoded = json_decode($value, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return $value;
    }

    return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)
        ?: $value;
};
$paginationUrl = static fn (int $page): string => $url('admin/activity-logs?' . http_build_query(['page' => $page]));
ob_start();
?>
<section class="admin-list-card activity-log-page">
    <div class="section-heading">
        <div>
            <span class="section-kicker">AUDIT ADMINISTRASI</span>
            <h2>Activity Log</h2>
        </div>
        <span class="status-pill"><?= number_format($activityLogData['total_rows'], 0, ',', '.') ?> aktivitas</span>
    </div>
    <p class="form-help">Catatan aktivitas bersifat hanya-baca, ditampilkan dari yang terbaru.</p>

    <div class="table-responsive">
        <table class="table align-middle admin-data-table activity-log-table">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Admin</th>
                    <th>Tindakan</th>
                    <th>Entitas</th>
                    <th>Data perubahan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activityLogData['logs'] as $log): ?>
                    <tr>
                        <td class="activity-log-time"><?= e(formatDateTimeWib($log['created_at'])) ?></td>
                        <td><?= e($log['admin_username'] ?? 'Sistem') ?></td>
                        <td><strong><?= e($actionLabels[$log['action']] ?? str_replace('_', ' ', (string) $log['action'])) ?></strong></td>
                        <td><?= e($entityLabels[$log['entity_type']] ?? $log['entity_type']) ?><?= $log['entity_id'] === null ? '' : ' #' . (int) $log['entity_id'] ?></td>
                        <td>
                            <?php if ($log['before_data'] === null && $log['after_data'] === null): ?>
                                <span class="text-secondary">Tidak ada detail perubahan.</span>
                            <?php else: ?>
                                <details class="activity-log-details">
                                    <summary>Lihat detail</summary>
                                    <div class="activity-log-json">
                                        <?php if ($log['before_data'] !== null): ?>
                                            <div>
                                                <strong>Sebelum</strong>
                                                <pre><?= e($formatLogData($log['before_data'])) ?></pre>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($log['after_data'] !== null): ?>
                                            <div>
                                                <strong>Sesudah</strong>
                                                <pre><?= e($formatLogData($log['after_data'])) ?></pre>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </details>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($activityLogData['logs'] === []): ?>
                    <tr><td colspan="5" class="text-center text-secondary py-4">Belum ada aktivitas tercatat.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($activityLogData['total_pages'] > 1): ?>
        <nav class="report-pagination" aria-label="Halaman activity log">
            <?php if ($activityLogData['page'] > 1): ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e($paginationUrl($activityLogData['page'] - 1)) ?>">Sebelumnya</a>
            <?php endif; ?>
            <span>Halaman <?= (int) $activityLogData['page'] ?> dari <?= (int) $activityLogData['total_pages'] ?></span>
            <?php if ($activityLogData['page'] < $activityLogData['total_pages']): ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e($paginationUrl($activityLogData['page'] + 1)) ?>">Berikutnya</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/admin.php';
