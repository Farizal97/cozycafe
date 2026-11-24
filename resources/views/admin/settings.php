<?php
$settingValue = static fn (string $key, string $default = ''): string =>
    (string) ($settingsData['settings'][$key]['setting_value'] ?? $default);
$hoursByDay = [];
foreach ($settingsData['hours'] as $hour) {
    $hoursByDay[(int) $hour['day_of_week']] = $hour;
}
ob_start();
?>
<section class="operations-page settings-page">
    <div class="section-heading">
        <div><span class="section-kicker">KONFIGURASI OPERASIONAL</span><h2>Pengaturan kafe</h2><p class="text-secondary mb-0">Perubahan berlaku untuk checkout, reservasi, dan informasi yang ditampilkan kepada pelanggan.</p></div>
    </div>
    <div class="settings-section-grid">
        <section class="admin-list-card">
            <header class="settings-section-heading"><span class="settings-icon">01</span><div><h3>Transaksi & reservasi</h3><p>Pajak, biaya layanan, konfirmasi reservasi, dan durasi meja.</p></div></header>
            <form method="post" action="<?= e($url('admin/settings/save')) ?>" class="settings-form">
                <?= csrfField() ?>
                <div class="settings-form-grid">
                    <div><label class="form-label" for="taxPercentage">Pajak (%)</label><input class="form-control" id="taxPercentage" name="tax_percentage" type="number" min="0" max="100" step="0.01" value="<?= e($settingValue('tax_percentage', '10')) ?>" required></div>
                    <div><label class="form-label" for="serviceChargePercentage">Biaya layanan (%)</label><input class="form-control" id="serviceChargePercentage" name="service_charge_percentage" type="number" min="0" max="100" step="0.01" value="<?= e($settingValue('service_charge_percentage', '5')) ?>" required></div>
                    <div><label class="form-label" for="bookingMode">Konfirmasi reservasi</label><select class="form-select" id="bookingMode" name="booking_confirmation_mode"><option value="manual" <?= $settingValue('booking_confirmation_mode', 'manual') === 'manual' ? 'selected' : '' ?>>Manual oleh admin</option><option value="auto" <?= $settingValue('booking_confirmation_mode') === 'auto' ? 'selected' : '' ?>>Otomatis</option></select></div>
                    <div><label class="form-label" for="sessionDuration">Durasi sesi meja (menit)</label><input class="form-control" id="sessionDuration" name="table_session_duration_minutes" type="number" min="30" max="1440" step="1" value="<?= e($settingValue('table_session_duration_minutes', '180')) ?>" required></div>
                    <div><label class="form-label" for="cafeAddress">Alamat kafe</label><input class="form-control" id="cafeAddress" name="cafe_address" maxlength="255" value="<?= e($settingValue('cafe_address')) ?>"></div>
                    <div><label class="form-label" for="cafePhone">Telepon kafe</label><input class="form-control" id="cafePhone" name="cafe_phone" maxlength="24" value="<?= e($settingValue('cafe_phone')) ?>"></div>
                </div>
                <div class="settings-switches">
                    <label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="tax_enabled" value="1" <?= $settingValue('tax_enabled', '1') === '1' ? 'checked' : '' ?>><span>Aktifkan pajak</span></label>
                    <label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="service_charge_enabled" value="1" <?= $settingValue('service_charge_enabled', '1') === '1' ? 'checked' : '' ?>><span>Aktifkan biaya layanan</span></label>
                </div>
                <button class="btn btn-cozy-primary" type="submit">Simpan pengaturan</button>
            </form>
        </section>

        <section class="admin-list-card">
            <header class="settings-section-heading"><span class="settings-icon">02</span><div><h3>Jam operasional mingguan</h3><p>Gunakan zona waktu Asia/Jakarta (WIB).</p></div></header>
            <form method="post" action="<?= e($url('admin/settings/hours')) ?>" class="hours-settings-form">
                <?= csrfField() ?>
                <?php foreach ($settingsData['weekdays'] as $day => $label): ?>
                    <?php $hour = $hoursByDay[$day] ?? ['is_open' => 1, 'opening_time' => '09:00:00', 'closing_time' => '22:00:00']; ?>
                    <div class="hours-settings-row">
                        <label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="hours[<?= (int) $day ?>][is_open]" value="1" <?= (int) $hour['is_open'] === 1 ? 'checked' : '' ?>><span><?= e($label) ?></span></label>
                        <input class="form-control" aria-label="Jam buka <?= e($label) ?>" type="time" name="hours[<?= (int) $day ?>][opening_time]" value="<?= e($hour['opening_time'] ? substr($hour['opening_time'], 0, 5) : '') ?>">
                        <span class="hours-separator">sampai</span>
                        <input class="form-control" aria-label="Jam tutup <?= e($label) ?>" type="time" name="hours[<?= (int) $day ?>][closing_time]" value="<?= e($hour['closing_time'] ? substr($hour['closing_time'], 0, 5) : '') ?>">
                    </div>
                <?php endforeach; ?>
                <button class="btn btn-cozy-primary" type="submit">Simpan jam operasional</button>
            </form>
        </section>

        <section class="admin-list-card special-closures-section">
            <header class="settings-section-heading"><span class="settings-icon">03</span><div><h3>Penutupan khusus</h3><p>Tanggal libur atau penutupan sementara yang menggantikan jadwal mingguan.</p></div></header>
            <form method="post" action="<?= e($url('admin/settings/closures/create')) ?>" class="closure-create-form">
                <?= csrfField() ?>
                <div><label class="form-label" for="closureDate">Tanggal</label><input class="form-control" id="closureDate" name="closure_date" type="date" required></div>
                <div><label class="form-label" for="closureReason">Alasan</label><input class="form-control" id="closureReason" name="reason" maxlength="255" placeholder="Contoh: Libur hari raya" required></div>
                <button class="btn btn-cozy-primary" type="submit">Tambah tanggal</button>
            </form>
            <div class="closure-list">
                <?php foreach ($settingsData['closures'] as $closure): ?>
                    <div class="operation-line-item">
                        <div><strong><?= e(date('d/m/Y', strtotime($closure['closure_date']))) ?></strong><small><?= e($closure['reason']) ?></small></div>
                        <form method="post" action="<?= e($url('admin/settings/closures/delete')) ?>" onsubmit="return confirm('Hapus penutupan khusus ini?')">
                            <?= csrfField() ?><input type="hidden" name="closure_id" value="<?= (int) $closure['id'] ?>"><button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                        </form>
                    </div>
                <?php endforeach; ?>
                <?php if ($settingsData['closures'] === []): ?><p class="text-secondary mb-0">Belum ada tanggal penutupan khusus.</p><?php endif; ?>
            </div>
        </section>
    </div>
</section>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/admin.php';
