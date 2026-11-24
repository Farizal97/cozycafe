<?php
$formatBytes = static function (mixed $bytes): string {
    $size = (int) $bytes;
    if ($size < 1024) {
        return $size . ' B';
    }
    if ($size < 1048576) {
        return number_format($size / 1024, 1, ',', '.') . ' KB';
    }
    return number_format($size / 1048576, 1, ',', '.') . ' MB';
};
ob_start();
?>
<section class="operations-page">
    <div class="section-heading">
        <div><span class="section-kicker">KEAMANAN DATA</span><h2>Backup dan restore</h2><p class="text-secondary mb-0">Buat salinan database dan pulihkan data CozyCafe dari backup tersimpan.</p></div>
    </div>
    <div class="backup-warning-panel">
        <span class="backup-warning-icon" aria-hidden="true">!</span>
        <div><strong>Backup database saja</strong><p>File bukti pembayaran dan foto produk di folder storage tidak termasuk dalam backup. Simpan salinan file storage secara terpisah.</p></div>
    </div>
    <section class="admin-list-card backup-create-card">
        <div><span class="section-kicker">MANUAL</span><h3>Buat backup sekarang</h3><p>Backup disimpan di luar direktori publik dan hanya dapat diunduh setelah login sebagai admin.</p></div>
        <form method="post" action="<?= e($url('admin/backups/create')) ?>" onsubmit="return confirm('Buat backup database CozyCafe sekarang?')">
            <?= csrfField() ?><button class="btn btn-cozy-primary" type="submit">Buat backup</button>
        </form>
    </section>
    <section class="admin-list-card">
        <div class="section-heading">
            <div><span class="section-kicker">ARSIP</span><h3 class="h4">Riwayat backup</h3></div>
            <span class="status-pill"><?= count($backupLogs) ?> catatan terakhir</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle admin-data-table">
                <thead><tr><th>File backup</th><th>Dibuat</th><th>Ukuran</th><th>Status</th><th>Tindakan</th></tr></thead>
                <tbody>
                    <?php foreach ($backupLogs as $backup): ?>
                        <tr>
                            <td><strong class="font-monospace"><?= e($backup['filename']) ?></strong><small class="d-block text-secondary"><?= $backup['backup_type'] === 'manual' ? 'Manual' : 'Terjadwal' ?></small></td>
                            <td><?= e(formatDateTimeWib($backup['created_at'])) ?></td>
                            <td><?= e($formatBytes($backup['file_size'])) ?></td>
                            <td><span class="availability-badge status-<?= e($backup['status']) ?>"><?= $backup['status'] === 'completed' ? 'Berhasil' : ($backup['status'] === 'failed' ? 'Gagal' : ($backup['status'] === 'missing' ? 'File tidak tersedia' : 'Diproses')) ?></span></td>
                            <td>
                                <?php if ($backup['status'] === 'completed'): ?>
                                    <div class="backup-action-stack">
                                        <a class="btn btn-sm btn-outline-secondary" href="<?= e($url('admin/backups/download?file=' . rawurlencode($backup['filename']))) ?>">Unduh</a>
                                        <details class="backup-restore-details">
                                            <summary class="btn btn-sm btn-outline-danger">Pulihkan</summary>
                                            <form method="post" action="<?= e($url('admin/backups/restore')) ?>" class="backup-restore-form" onsubmit="return confirm('Semua data database saat ini akan diganti dengan isi backup. Lanjutkan?')">
                                                <?= csrfField() ?><input type="hidden" name="filename" value="<?= e($backup['filename']) ?>">
                                                <strong>Peringatan: mengganti seluruh data</strong>
                                                <p>Data yang dibuat setelah backup ini akan hilang. File upload tidak dipulihkan.</p>
                                                <label class="form-label" for="restoreConfirmation<?= (int) $backup['id'] ?>">Ketik RESTORE untuk konfirmasi</label>
                                                <input class="form-control" id="restoreConfirmation<?= (int) $backup['id'] ?>" name="confirmation" autocomplete="off" required>
                                                <label class="form-label mt-2" for="restorePassword<?= (int) $backup['id'] ?>">Password admin saat ini</label>
                                                <input class="form-control" id="restorePassword<?= (int) $backup['id'] ?>" type="password" name="current_password" autocomplete="current-password" required>
                                                <button class="btn btn-danger mt-3" type="submit">Pulihkan database</button>
                                            </form>
                                        </details>
                                    </div>
                                <?php else: ?><span class="text-secondary">Tidak tersedia</span><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($backupLogs === []): ?><tr><td colspan="5" class="text-center text-secondary py-4">Belum ada backup. Buat backup pertama sebelum melakukan perubahan besar.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/admin.php';
