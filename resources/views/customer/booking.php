<?php
ob_start();
$selectedArea = null;
foreach ($areas as $area) {
    if ((int) $area['id'] === $areaId) {
        $selectedArea = $area;
        break;
    }
}
?>
<main class="container page-section booking-page">
    <?php if ($bookingConfirmation !== null): ?>
        <section class="booking-confirmation">
            <span class="section-kicker">RESERVASI BERHASIL DIBUAT</span>
            <h1>Terima kasih, meja sudah kami catat.</h1>
            <p class="booking-confirmation-status">
                Status:
                <strong><?= $bookingConfirmation['status'] === 'confirmed' ? 'Dikonfirmasi' : 'Menunggu konfirmasi kafe' ?></strong>
            </p>
            <div class="booking-ticket">
                <div>
                    <span class="booking-ticket-label">Kode reservasi</span>
                    <strong class="booking-code"><?= e($bookingConfirmation['booking_code']) ?></strong>
                    <dl class="booking-ticket-details">
                        <div><dt>Tanggal</dt><dd><?= e(date('d/m/Y', strtotime($bookingConfirmation['booking_date']))) ?></dd></div>
                        <div><dt>Waktu</dt><dd><?= e($bookingConfirmation['slot_label']) ?> WIB</dd></div>
                        <div><dt>Area & meja</dt><dd><?= e($bookingConfirmation['area_name'] . ' · ' . $bookingConfirmation['table_number']) ?></dd></div>
                        <div><dt>Jumlah tamu</dt><dd><?= e($bookingConfirmation['guest_count']) ?> orang</dd></div>
                    </dl>
                </div>
                <div class="booking-qr">
                    <?php
                    $autoload = $root . '/vendor/autoload.php';
                    if (is_file($autoload)) {
                        require_once $autoload;
                    }
                    if (class_exists(\Endroid\QrCode\Builder\Builder::class)) {
                        $qr = \Endroid\QrCode\Builder\Builder::create()
                            ->writer(new \Endroid\QrCode\Writer\SvgWriter())
                            ->data($bookingConfirmation['qr_token'])
                            ->encoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
                            ->errorCorrectionLevel(new \Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow())
                            ->size(180)
                            ->margin(8)
                            ->build();
                        echo '<img src="data:image/svg+xml;base64,' . e(base64_encode($qr->getString())) . '" alt="QR Code reservasi">';
                    } else {
                        echo '<span class="booking-qr-fallback">Token check-in<br>' . e(substr($bookingConfirmation['qr_token'], 0, 16)) . '…</span>';
                    }
                    ?>
                </div>
            </div>
            <p class="booking-note">Simpan kode reservasi ini untuk menunjukkan konfirmasi saat tiba. Durasi reservasi adalah 3 jam.</p>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-cozy-outline" type="button" onclick="window.print()">Cetak konfirmasi</button>
                <a class="btn btn-cozy-primary" href="<?= e($url('booking')) ?>">Buat reservasi lain</a>
            </div>
        </section>
    <?php else: ?>
        <header class="page-heading booking-heading">
            <div>
                <span class="section-kicker">PESAN TEMPATMU</span>
                <h1>Reservasi meja</h1>
                <p>Pilih meja favorit untuk hari ini. Setiap reservasi berlaku selama 3 jam.</p>
            </div>
            <a class="btn btn-cozy-outline booking-track-link" href="<?= e($url('track-booking')) ?>">Lacak reservasi</a>
        </header>

        <div class="booking-steps" aria-label="Langkah reservasi">
            <span><b>1</b> Pilih waktu & area</span>
            <span><b>2</b> Pilih meja</span>
            <span><b>3</b> Isi data tamu</span>
        </div>

        <?php if ($bookingSlots === []): ?>
            <section class="booking-closed" role="status">
                <strong>Reservasi hari ini sudah tutup.</strong>
                <p class="mb-0">Silakan hubungi CozyCafe untuk informasi lebih lanjut.</p>
            </section>
        <?php elseif ($areas === []): ?>
            <section class="booking-closed" role="status">
                <strong>Area reservasi belum tersedia.</strong>
                <p class="mb-0">Silakan coba lagi nanti atau hubungi CozyCafe.</p>
            </section>
        <?php else: ?>
            <form class="booking-filter" method="get" action="<?= e($url('booking')) ?>">
                <input type="hidden" name="show_tables" value="1">
                <div>
                    <label class="form-label" for="bookingDate">Tanggal reservasi</label>
                    <input class="form-control" id="bookingDate" type="date" value="<?= e($today) ?>" disabled>
                    <div class="form-text">Reservasi hanya tersedia untuk hari ini.</div>
                </div>
                <div>
                    <label class="form-label" for="bookingSlot">Slot waktu</label>
                    <select class="form-select" id="bookingSlot" name="slot" required>
                        <?php foreach ($bookingSlots as $slot): ?>
                            <option value="<?= e($slot['start']) ?>" <?= $selectedSlot !== null && $selectedSlot['start'] === $slot['start'] ? 'selected' : '' ?>>
                                <?= e($slot['label']) ?> WIB
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="bookingArea">Area</label>
                    <select class="form-select" id="bookingArea" name="area" required>
                        <?php foreach ($areas as $area): ?>
                            <option value="<?= e($area['id']) ?>" <?= (int) $area['id'] === $areaId ? 'selected' : '' ?>>
                                <?= e($area['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="bookingGuests">Jumlah tamu</label>
                    <input class="form-control" id="bookingGuests" name="guests" type="number" min="1" max="255" value="<?= e($guestCount) ?>" required>
                </div>
                <div class="booking-filter-action">
                    <button class="btn btn-cozy-primary" type="submit">Lihat meja</button>
                </div>
            </form>

            <?php if ($showTables && $selectedSlot !== null): ?>
                <section class="booking-selection">
                    <div class="booking-section-heading">
                        <div>
                            <span class="section-kicker">DENAH MEJA</span>
                            <h2><?= e($selectedArea['name'] ?? 'Area') ?></h2>
                            <p><?= e($selectedSlot['label']) ?> WIB · <?= e($guestCount) ?> orang</p>
                        </div>
                        <div class="booking-map-legend">
                            <span><i class="available-dot"></i> Tersedia</span>
                            <span><i class="occupied-dot"></i> Terisi</span>
                        </div>
                    </div>
                    <?php if ($bookingTables === []): ?>
                        <div class="booking-closed">Belum ada meja dengan kapasitas yang sesuai. Coba area atau jumlah tamu lain.</div>
                    <?php else: ?>
                        <div class="booking-floor-map" aria-label="Denah meja <?= e($selectedArea['name'] ?? '') ?>">
                            <?php foreach ($bookingTables as $table): ?>
                                <?php
                                $isAvailable = (int) $table['is_available'] === 1;
                                $x = max(8, min(92, (float) $table['position_x']));
                                $y = max(12, min(88, (float) $table['position_y']));
                                ?>
                                <label class="booking-table <?= $isAvailable ? 'is-available' : 'is-occupied' ?>"
                                    style="left: <?= e($x) ?>%; top: <?= e($y) ?>%">
                                    <input type="radio" name="table_id" value="<?= e($table['id']) ?>"
                                        form="bookingSubmit" <?= $isAvailable ? '' : 'disabled' ?> required>
                                    <span class="booking-table-number"><?= e($table['table_number']) ?></span>
                                    <span class="booking-table-capacity"><?= e($table['capacity']) ?> kursi</span>
                                </label>
                            <?php endforeach; ?>
                            <span class="booking-map-label">Area kafe</span>
                        </div>
                        <form class="booking-guest-form" id="bookingSubmit" method="post" action="<?= e($url('booking')) ?>">
                            <?= csrfField() ?>
                            <input type="hidden" name="area_id" value="<?= e($areaId) ?>">
                            <input type="hidden" name="slot_start" value="<?= e($selectedSlot['start']) ?>">
                            <input type="hidden" name="guest_count" value="<?= e($guestCount) ?>">
                            <div class="booking-section-heading">
                                <div>
                                    <span class="section-kicker">DATA TAMU</span>
                                    <h2>Siapa yang akan datang?</h2>
                                    <p>Nama dan nomor telepon digunakan untuk konfirmasi reservasi.</p>
                                </div>
                            </div>
                            <div class="booking-guest-fields">
                                <div>
                                    <label class="form-label" for="customerName">Nama lengkap</label>
                                    <input class="form-control" id="customerName" name="customer_name" maxlength="160" autocomplete="name" value="<?= e($bookingDraft['customer_name'] ?? '') ?>" required>
                                </div>
                                <div>
                                    <label class="form-label" for="customerPhone">Nomor telepon</label>
                                    <input class="form-control" id="customerPhone" name="phone" type="tel" maxlength="32" autocomplete="tel" placeholder="08xxxxxxxxxx" value="<?= e($bookingDraft['phone'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="booking-submit-row">
                                <p class="mb-0">Tidak ada biaya deposit. Reservasi berlaku 3 jam.</p>
                                <button class="btn btn-cozy-primary" type="submit">Konfirmasi reservasi</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</main>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/customer.php';
